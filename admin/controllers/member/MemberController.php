<?php

/**
 * 后台会员管理控制器。
 *
 * 负责会员列表、详情、新增、修改、冻结和解冻请求的输入输出调度。
 */

namespace app\controllers\member;

use app\controllers\Controller;
use app\helpers\Url;
use app\models\member\AddForm;
use app\models\member\ModifyForm;
use app\models\member\SearchForm;
use app\models\member\MemberService;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * 会员后台管理入口。
 */
final class MemberController extends Controller
{
    /**
     * 配置会员管理接口允许使用的HTTP请求方法。
     */
    protected function verbFilter(): array
    {
        return [
            'list' => ['POST'],
            'check-add' => ['POST'],
            'check-modify' => ['POST'],
            'freeze' => ['POST'],
            'unfreeze' => ['POST'],
        ];
    }

    /**
     * 显示会员列表页面。
     */
    public function actionIndex()
    {
        $service = new MemberService();
        return $this->render('index', [
            'model' => new SearchForm(),
            'statusNames' => $service->getStatusNames(),
            'registerTypeNames' => $service->getRegisterTypeNames(),
        ]);
    }

    /**
     * 返回会员列表异步表格数据。
     */
    public function actionList()
    {
        $form = new SearchForm();
        if (!$form->loadPost() || !$form->validate()) {
            return $this->jsonError($form->getError());
        }

        $service = new MemberService();
        return $this->jsonTable('_view_list', $service->search($form));
    }

    /**
     * 执行新增会员表单的异步字段校验。
     */
    public function actionCheckAdd()
    {
        $form = new AddForm();
        if ($form->loadPost()) {
            return $this->renderValidate($form, ['username', 'mobile', 'email']);
        }

        return $this->jsonError('缺少验证数据。');
    }

    /**
     * 执行修改会员表单的异步字段校验。
     */
    public function actionCheckModify()
    {
        $form = new ModifyForm();
        if ($form->loadPost()) {
            return $this->renderValidate($form, ['mobile', 'email']);
        }

        return $this->jsonError('缺少验证数据。');
    }

    /**
     * 显示新增会员页面或处理新增提交。
     */
    public function actionAdd()
    {
        $form = new AddForm();
        if (!$form->loadPost()) {
            return $this->render('add', ['model' => $form]);
        }
        if (!$form->validate()) {
            return $this->jsonError($form->getError());
        }

        $service = new MemberService();
        if (!$service->add($form)) {
            return $this->jsonError($service->getError());
        }

        return $this->jsonSuccess('新增会员成功。', ['url' => Url::to(['index'])]);
    }

    /**
     * 显示会员详情页面。
     */
    public function actionDetail()
    {
        $service = new MemberService();
        $member = $service->getDetail($this->getId());
        if ($member === false) {
            return $this->renderError($service->getError());
        }

        return $this->render('detail', ['model' => $member]);
    }

    /**
     * 显示修改会员页面或处理修改提交。
     */
    public function actionModify()
    {
        $id = $this->getId();
        $form = new ModifyForm();
        $service = new MemberService();
        if ($form->loadPost()) {
            $form->id = $id;
            if (!$form->validate()) {
                return $this->jsonError($form->getError());
            }
            if (!$service->modify($id, $form)) {
                return $this->jsonError($service->getError());
            }

            return $this->jsonSuccess('修改会员成功。', ['url' => Url::to(['index'])]);
        }

        $member = $service->getDetail($id);
        if ($member === false) {
            return $this->renderError($service->getError());
        }
        $form->id = $id;
        $form->load($member->attributes, '');

        return $this->render('modify', ['model' => $form]);
    }

    /**
     * 冻结会员并使旧登录态失效。
     */
    public function actionFreeze()
    {
        $service = new MemberService();
        if (!$service->freeze($this->getId())) {
            return $this->jsonError($service->getError());
        }

        return $this->jsonSuccess('冻结会员成功。');
    }

    /**
     * 解冻会员并提升认证版本。
     */
    public function actionUnfreeze()
    {
        $service = new MemberService();
        if (!$service->unfreeze($this->getId())) {
            return $this->jsonError($service->getError());
        }

        return $this->jsonSuccess('解冻会员成功。');
    }

    /**
     * 从查询参数读取并校验会员ID。
     *
     * @throws NotFoundHttpException 参数缺失或不是正整数时抛出
     */
    private function getId(): int
    {
        $id = (int) Yii::$app->request->get('id', 0);
        if ($id < 1) {
            throw new NotFoundHttpException();
        }

        return $id;
    }
}
