<?php

namespace frontend\models;

use common\models\lottery\Draw;
use common\models\lottery\Game;
use common\models\lottery\Notice;
use common\models\lottery\Settings;
use common\models\lottery\Prediction;
use common\models\lottery\PredictionAlgorithm;
use common\models\lottery\SharedDataConfig;
use common\models\lottery\SharedDataGateway;
use common\resource\PortalImageResource;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

/** 将独立开奖后台的数据适配到参考前端协议。 */
final class ReferencePortalService
{
    private SharedDataConfig $sharedConfig;
    private ?SharedDataGateway $sharedGateway;

    /** 注入共享数据配置与只读网关，公告和外观始终读取本站数据库。 */
    public function __construct(?SharedDataConfig $sharedConfig = null, ?SharedDataGateway $sharedGateway = null)
    {
        $this->sharedConfig = $sharedConfig ?? SharedDataConfig::current();
        $this->sharedGateway = $sharedGateway;
    }

    /** 返回前端公开配置，不暴露服务端接入选项。 */
    public function website(): array
    {
        $settings = Settings::current();
        $config = $settings->getAttributes(['website', 'title', 'landing', 'ad_wx', 'ad_ww', 'notice_scroll', 'show_qrcode']);
        $config['show_qrcode'] = (int) $config['show_qrcode'];
        foreach (['banner', 'icon', 'ad_icon'] as $name) { $config[$name] = $this->imageUrl($settings->$name); }
        $config['ad'] = array_map(fn (array $ad): array => ['cover' => $this->imageUrl($ad['cover']), 'link' => $ad['link']], $settings->ads(true));
        return $config;
    }

    /** 仅向访客发送当前公开配置引用的图片，隐藏广告图片不公开。 */
    public function image(string $reference): ?array
    {
        if (!in_array($reference, Settings::current()->images(true), true)) { return null; }
        return (new PortalImageResource())->resolve($reference);
    }

    /** 上传图片通过同源受控入口读取，内置图片保留原地址。 */
    private function imageUrl(string $reference): string
    {
        return str_starts_with($reference, 'upload:')
            ? \yii\helpers\Url::to(['/reference/image', 'reference' => $reference]) : $reference;
    }

    /** 中心读取已保存的开奖和预测；分站读取共享数据，展示开关仍由本站控制。 */
    public function open(bool $sharedExport = false): array
    {
        $criteria = ['code' => 'jnd28'];
        if (!$sharedExport) { $criteria['enabled'] = Game::VISIBLE; }
        $game = Game::findOne($criteria);
        if ($sharedExport && ($game === null || !\common\models\lottery\FeedCatalog::matches($game))) {
            throw new NotFoundHttpException('彩种不存在或号码规则不匹配。');
        }
        if (!$sharedExport && $this->sharedConfig->isClient() && $game !== null) {
            if (!\common\models\lottery\FeedCatalog::matches($game)) {
                throw new NotFoundHttpException('彩种号码规则不匹配。');
            }
            $data = ($this->sharedGateway ?? new SharedDataGateway($this->sharedConfig))->fetch('latest', ['game' => 'jnd28']);
            $now = time();
            $nextTime = strtotime($data['period']['next_time_end'] . ' Asia/Shanghai');
            $data['period']['open_time'] = max(0, $nextTime - $now);
            $data['period']['time'] = date('Y-m-d H:i:s', $now);
            $data['prediction_source'] = 'shared';
            return $data;
        }
        $draws = $game === null ? [] : Draw::find()->where(['game_id' => $game->id])
            ->orderBy(['draw_time' => SORT_DESC, 'id' => SORT_DESC])->limit(100)->all();
        $latestIssue = $draws[0]->issue ?? '';
        $nextIssue = ctype_digit($latestIssue) && strlen($latestIssue) <= 13 ? (string) ((int) $latestIssue + 1) : '';
        $issues = array_map(static fn (Draw $draw): string => $draw->issue, $draws);
        if ($nextIssue !== '') { $issues[] = $nextIssue; }
        $predictions = $game === null || $issues === [] ? [] : Prediction::find()
            ->where(['game_id' => $game->id, 'issue' => $issues])->indexBy('issue')->all();
        $list = $this->formatDraws($draws, $predictions);
        $last = $list[0] ?? null;
        $now = time();
        $nextTime = $last === null ? $now : strtotime($last['created'] . ' Asia/Shanghai') + PredictionAlgorithm::INTERVAL_SECONDS;
        $stored = $predictions[$nextIssue] ?? null;
        $prediction = $stored !== null && $stored->source_issue === $latestIssue ? $stored->publicValues() : [];
        return [
            'list' => $list,
            'prediction_source' => 'local',
            'prediction_algorithm' => $stored->algorithm ?? PredictionAlgorithm::VERSION,
            'prediction' => $prediction ?: ['pdx' => '-', 'pds' => '-', 'pbdx' => '-', 'pbds' => '-', 'pbdxds' => '-'],
            'prediction_available' => $prediction !== [],
            'prediction_period' => $nextIssue ?: '-',
            'period' => ['cperiod' => $last['period'] ?? '-', 'period' => $nextIssue ?: '-',
                'open_time' => max(0, $nextTime - $now), 'time' => date('Y-m-d H:i:s', $now),
                'next_time_end' => date('Y-m-d H:i:s', $nextTime)],
        ];
    }

    /**
     * 最新开奖与历史分页共用加拿大28号码及开奖前已保存的预测命中格式。
     *
     * @param Draw[] $draws 加拿大28开奖记录
     * @param Prediction[]|null $predictions 按期号索引的已加载预测；未传入时批量查询
     */
    public function formatDraws(array $draws, ?array $predictions = null): array
    {
        if ($predictions === null) {
            $predictions = $draws === [] ? [] : Prediction::find()
                ->where(['game_id' => $draws[0]->game_id,
                    'issue' => array_map(static fn (Draw $draw): string => $draw->issue, $draws)])
                ->indexBy('issue')->all();
        }
        $list = [];
        foreach ($draws as $draw) {
            $balls = $draw->getBalls();
            $sum = $draw->getTotal();
            $dx = $sum >= 14 ? '大' : '小';
            $ds = $sum % 2 === 0 ? '双' : '单';
            $stored = $predictions[$draw->issue] ?? null;
            $prediction = $stored !== null && strcmp($stored->create_time, $draw->draw_time) < 0 ? $stored->publicValues() : [];
            $list[] = ['period' => $draw->issue, 'created' => $draw->draw_time,
                'code_arr' => $balls, 'sum' => $sum, 'dx' => $dx, 'ds' => $ds,
                'result' => implode(' + ', $balls) . ' = ' . $sum,
                'prediction_available' => $prediction !== [],
                'prdx' => isset($prediction['pdx']) ? (int) ($prediction['pdx'] === $dx) : null,
                'prds' => isset($prediction['pds']) ? (int) ($prediction['pds'] === $ds) : null,
            ] + ($prediction ?: ['pdx' => '-', 'pds' => '-', 'pbdx' => '-', 'pbds' => '-', 'pbdxds' => '-']);
        }
        return $list;
    }

    /** 仅展示本站后台已发布公告，没有已发布内容时返回空列表。 */
    public function notices(): array
    {
        $local = Notice::find()->where(['enabled' => Notice::VISIBLE])->orderBy(['id' => SORT_DESC])->all();
        return array_map(static fn (Notice $notice): array => ['id' => 'local-' . $notice->id,
            'title' => $notice->title, 'created' => $notice->create_time], $local);
    }

    /** 公告详情只公开已发布站内内容，并按纯文本安全展示。 */
    public function notice(string $id): array
    {
        if (preg_match('/^local-([1-9][0-9]{0,9})$/D', $id, $match)) {
            $notice = (new PortalService())->notice((int) $match[1]);
            return ['id' => $id, 'title' => $notice['title'], 'created' => $notice['create_time'],
                'content' => nl2br(Html::encode($notice['content']))];
        }
        throw new NotFoundHttpException('公告不存在。');
    }
}
