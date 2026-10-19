<?php

namespace frontend\models;

use common\models\lottery\Draw;
use common\models\lottery\Game;
use common\models\lottery\Notice;
use common\models\lottery\Settings;
use common\models\lottery\Prediction;
use common\models\lottery\PredictionAlgorithm;
use common\resource\PortalImageResource;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\web\NotFoundHttpException;

/** 将独立开奖后台的数据适配到参考前端协议。 */
final class ReferencePortalService
{
    private ReferenceGateway $gateway;

    /** 注入只读网关，便于验证期号匹配与降级行为。 */
    public function __construct(?ReferenceGateway $gateway = null)
    {
        $this->gateway = $gateway ?? new ReferenceGateway();
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

    /** 只读取本站数据库中的开奖和已保存的本地预测，不访问预测接口或生成预测。 */
    public function open(): array
    {
        $game = Game::findOne(['code' => 'jnd28', 'enabled' => Game::VISIBLE]);
        $draws = $game === null ? [] : Draw::find()->where(['game_id' => $game->id])
            ->orderBy(['draw_time' => SORT_DESC, 'id' => SORT_DESC])->limit(100)->all();
        $latestIssue = $draws[0]->issue ?? '';
        $nextIssue = ctype_digit($latestIssue) && strlen($latestIssue) <= 13 ? (string) ((int) $latestIssue + 1) : '';
        $issues = array_map(static fn (Draw $draw): string => $draw->issue, $draws);
        if ($nextIssue !== '') { $issues[] = $nextIssue; }
        $predictions = $game === null || $issues === [] ? [] : Prediction::find()
            ->where(['game_id' => $game->id, 'issue' => $issues])->indexBy('issue')->all();
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
        $last = $list[0] ?? null;
        $now = time();
        $nextTime = $last === null ? $now : strtotime($last['created'] . ' Asia/Shanghai') + PredictionAlgorithm::INTERVAL_SECONDS;
        $stored = $predictions[$nextIssue] ?? null;
        $prediction = $stored !== null && $stored->source_issue === $latestIssue ? $stored->publicValues() : [];
        return [
            'list' => $list,
            'prediction_source' => 'local',
            'prediction_algorithm' => $stored?->algorithm ?? PredictionAlgorithm::VERSION,
            'prediction' => $prediction ?: ['pdx' => '-', 'pds' => '-', 'pbdx' => '-', 'pbds' => '-', 'pbdxds' => '-'],
            'prediction_available' => $prediction !== [],
            'prediction_period' => $nextIssue ?: '-',
            'period' => ['cperiod' => $last['period'] ?? '-', 'period' => $nextIssue ?: '-',
                'open_time' => max(0, $nextTime - $now), 'time' => date('Y-m-d H:i:s', $now),
                'next_time_end' => date('Y-m-d H:i:s', $nextTime)],
        ];
    }

    /** 后台发布公告优先；没有站内公告时显示参考站公开公告。 */
    public function notices(): array
    {
        $local = Notice::find()->where(['enabled' => Notice::VISIBLE])->orderBy(['id' => SORT_DESC])->all();
        if ($local !== []) {
            return array_map(static fn (Notice $notice): array => ['id' => 'local-' . $notice->id,
                'title' => $notice->title, 'created' => $notice->create_time], $local);
        }
        if (!$this->config()['reference_notices']) { return []; }
        $result = [];
        foreach ($this->gateway->fetch('notice/list') as $row) {
            if (!is_array($row) || !ctype_digit((string) ($row['id'] ?? ''))) { continue; }
            $result[] = ['id' => 'reference-' . $row['id'], 'title' => (string) ($row['title'] ?? ''),
                'created' => (string) ($row['created'] ?? '')];
        }
        return $result;
    }

    /** 公告详情只公开已发布站内内容，外部 HTML 必须净化。 */
    public function notice(string $id): array
    {
        if (preg_match('/^local-([1-9][0-9]{0,9})$/D', $id, $match)) {
            $notice = (new PortalService())->notice((int) $match[1]);
            return ['id' => $id, 'title' => $notice['title'], 'created' => $notice['create_time'],
                'content' => nl2br(Html::encode($notice['content']))];
        }
        if ($this->config()['reference_notices'] && preg_match('/^reference-([1-9][0-9]{0,9})$/D', $id, $match)) {
            $notice = $this->gateway->fetch('notice/detail', ['id' => (int) $match[1], 'source' => 'https://pc28p.com/']);
            if (!empty($notice['id'])) {
                return ['id' => $id, 'title' => (string) ($notice['title'] ?? ''),
                    'created' => (string) ($notice['created'] ?? ''),
                    'content' => HtmlPurifier::process((string) ($notice['content'] ?? ''), [
                        'URI.AllowedSchemes' => ['http' => true, 'https' => true],
                        'HTML.SafeIframe' => false,
                    ])];
            }
        }
        throw new NotFoundHttpException('公告不存在。');
    }

    /** 加载部署时的站点配置。 */
    private function config(): array { return require dirname(__DIR__) . '/config/portal.php'; }
}
