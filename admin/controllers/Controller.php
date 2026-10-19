<?php
/**
 * Controller.php
 *
 * @author suxh
 */

namespace app\controllers;

use app\widgets\DataTableData;
use app\widgets\ActiveForm;
use app\helpers\Url;
use app\helpers\Json;
use common\models\system\Permission;
use mylibs\yii\filters\AccessLog;
use mylibs\yii\filters\Authorization;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;

class Controller extends \yii\web\Controller
{
	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Component::behaviors()
	 */
	public function behaviors() {
		$rules = [];

		// 错误页面任意情况下均可访问
		$rules[] = [
            'allow' => true,
            'controllers' => ['site'],
            'actions' => ['error'],
        ];
		// 未登录也可访问的action
		$actions = $this->accessAllow();
		if (!empty($actions)) {
			$rules[] = [
	            'allow' => true,
	            'actions' => $actions,
	        ];
    	}
        // 登录后可以访问所有action
		$rules[] = [
			'allow' => true,
			'roles' => ['@'],
		];

		return [
			'access' => [
				'class' => AccessControl::className(),
				'rules' => $rules,
			],
			'authorization' => [
				'class' => Authorization::className(),
				'permission' => new Permission(\Yii::$app->user->id),
				'exclude' => array_merge($this->accessAllow(), $this->withoutAuthorization()),
			],
			'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => $this->verbFilter(),
            ],
            // 访问日志
			'accessLog' => [
				'class' => AccessLog::className(),
			],
		];
	}

	protected function verbFilter() {
		return [];
	}

	protected function accessAllow() {
		return [];
	}

	protected function withoutAuthorization() {
		return [];
	}

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Controller::actions()
	 */
	public function actions() {
		return [
			'error' => [
				'class' => 'common\actions\ErrorAction',
			],
		];
	}

	public function jsonError($message, $data = [], $code = null) {
		$code = ($code)? $code:2000;
		$result = [
			'code'	 =>$code,
			'message'=>$message,
			'data'	 =>$data,
		];

		return Json::encode($result);
	}

	public function jsonSuccess($message='', $data = null) {
		$data = ($data===null && is_array($message))? $message:$data;
		$message = (is_string($message))? $message:null;

		$result = [
			'code'=>1000,
			'message'=>$message,
			'data'=>$data,
		];

		return Json::encode($result);
	}

	public function jsonTable($view, $dataProvider, $emptyText = null, $viewData=array()) {
		return DataTableData::widget([
			'dataProvider'=>$dataProvider,
			'itemView'=>$view,
			'emptyText'=>$emptyText,
			'viewData'=>$viewData,
			'controller'=>$this,
		]);
	}

	public function renderValidate($model, $attributes = null) {
		\Yii::$app->response->format = Response::FORMAT_JSON;
		return ActiveForm::validate($model, $attributes);
	}

	public function renderModal($view, $params = []) {
		$this->layout = 'modal';
		return $this->render($view, $params);
	}

	public function renderError($error) {
		return $this->render('@app/views/site/error', ['name'=>'', 'message'=>$error]);
	}

	public function renderSuccess($message) {
		return $this->render('@app/views/site/success', ['message'=>$message]);
	}

	public function getResourceUrl() {
		return \Yii::$app->request->baseUrl;
	}

	public function getMenus() {
		return [
			['label' => '我的桌面', 'url' => ['desktop/index'], 'i'=>'fa fa-desktop'],
			['label' => '开奖门户', 'i' => 'fa fa-bar-chart', 'items' => [
				['label' => '彩种管理', 'url' => ['portal/game/index']],
				['label' => '开奖记录', 'url' => ['portal/draw/index']],
				['label' => '公告管理', 'url' => ['portal/notice/index']],
				['label' => '站点与广告配置', 'url' => ['portal/settings/index']],
			]],
			// 会员模块入口：包含会员查询和后台人工新增功能。
			['label' => '会员管理', 'i' => 'fa fa-users', 'items' => [
				['label' => '会员管理', 'items' => [
					['label' => '会员列表', 'url' => ['member/member/index']],
					['label' => '新增会员', 'url' => ['member/member/add']],
				]],
			    ['label' => '代理管理', 'items' => [
			        ['label' => '代理列表', 'url' => ['member/agent/index']],
			    ]],
			]],
			// 在线客服工作台入口，接待权限由后台模块权限统一控制。
			['label' => '在线客服', 'i' => 'fa fa-comments', 'items' => [
				['label' => '客服管理', 'items' => [
					['label' => '客服工作台', 'url' => ['kefu/kefu/index']],
					
				]],
			    ['label' => '快捷回复管理', 'items' => [
			        ['label' => '快捷回复', 'url' => ['kefu/quick-reply/index']],
			        ['label' => '新增快捷', 'url' => ['kefu/quick-reply/add']],
			    ]],
	 
			]],
			['label' => '消息中心', 'i'=>'fa fa-envelope-o', 'items' => [
				['label' => '站内消息', 'items' => [
					['label' => '消息列表', 'url' => ['message/index']],
				]],
			]],
			['label' => '系统管理', 'i'=>'fa fa-cog', 'items' => [
				['label' => '组织架构和用户管理', 'items' => [
					['label' => '用户列表', 'url' => ['system/user/index']],
					['label' => '新增用户', 'url' => ['system/user/add']]
				]],
				['label' => '角色管理', 'items' => [
					['label' => '角色列表', 'url' => ['system/role/index']],
					['label' => '新增角色', 'url' => ['system/role/add']]
				]],
				['label' => '模块管理', 'url' => ['system/module/index']],
				['label' => '可登录IP管理', 'items' => [
					['label' => '可登录IP列表', 'url' => ['system/allow-login-ip/index']],
					['label' => '新增可登录IP', 'url' => ['system/allow-login-ip/add']],
				]],
			]],
		];
	}

	public function getMyMenusAndModules() {
		// 所有菜单
		$menus = $this->getMenus();

		// 根据当前用户权限配置，获取有操作权限的模块列表
		$userId = \Yii::$app->user->id;
		$permission = new Permission($userId);
		$modules = $permission->getModules();
		$isAdmin = $permission->isAdmin();

		// 根据模块列表过滤菜单，仅显示有权限的菜单项
		$this->filterMenu($menus, $modules, $isAdmin);

		return [
			'menus'=>$menus,
			'modules'=>$modules,
			'isAdmin'=>$isAdmin,
		];
	}

	private function filterMenu(&$menus, $modules, $isAdmin) {
		foreach ($menus as $id=>&$menu) {
			if (isset($menu['items'])) {
				$this->filterMenu($menu['items'], $modules, $isAdmin);
				if (empty($menu['items'])) {
					unset($menus[$id]);
				}
			}
			else if (isset($menu['url'])) {
				$url = $menu['url'][0];
				if (!$isAdmin && !in_array($url, $modules)) {
					unset($menus[$id]);
				}
				else {
					$menu['url'] = Url::to($menu['url']);
				}
			}
		}
	}
}
