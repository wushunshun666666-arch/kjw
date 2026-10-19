<?php
/**
 * default.php
 *
 * @author suxh
 */

use app\assets\AppAsset;
use app\helpers\Html;
use app\helpers\Url;
use app\helpers\Json;

/* @var $this \yii\web\View */
/* @var $content string */
AppAsset::register($this);


$resourceUrl 	= $this->context->getResourceUrl();
$messageCenter 	= $this->context->getMessageCenter();
$data 			= $this->context->getMyMenusAndModules();
$menus 			= $data['menus'];
$modules 		= $data['modules'];
$isAdmin 		= $data['isAdmin'];

$app = \Yii::$app;
$appName 	= $app->name;
$userName 	= $app->user->getIdentity()->getName();


$navigate = $app->request->get('navigate');
if (!$navigate) {
	$navigate = Url::to(['desktop/index']);
}
$homeUrl 			= Url::to(['site/index']);
$logoutUrl 			= Url::to(['site/logout']);
$kefuUrl	= Url::to(['kefu/kefu/index']);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?= Html::encode($this->title) ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
</head>
<body class="sidebar-mini sidebar-modern layout-fixed sidebar-expand-lg bg-body-tertiary classic-blue">
    <?php $this->beginBody() ?>
	<div class="app-wrapper">
		<nav class="app-header navbar navbar-expand classic-navbar" role="navigation">
			<div class="container-fluid">
				<!-- Sidebar toggle button-->
				<ul class="navbar-nav">
				<li class="nav-item"><a href="#" class="nav-link" data-lte-toggle="sidebar" role="button">
					<i class="fa fa-bars"></i>
					<span class="visually-hidden">Toggle navigation</span>
				</a></li>
				</ul>
				<ul class="navbar-nav">
					<li class="nav-item">
						<a class="nav-link" href="<?= $kefuUrl; ?>" target="_blank"><i class="fa fa-search"></i>&nbsp;&nbsp;客服工作台</a>
					</li>

				</ul>
				<ul class="navbar-nav ms-auto">
						<li class="nav-item dropdown messages-menu">
							<a aria-expanded="false" href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
								<i class="fa fa-envelope-o"></i>
								<span class="badge text-bg-warning message-badge"></span>消息
							</a>
							<ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
								<li class="dropdown-header message-header">您有<span></span>条新消息
									<small class="float-end mark-all-read">
										<a title="全部标记为已读" href="javascript:void(0);">
											<i class="fa fa-bars"></i>
										</a>
									</small>
								</li>
								<li>
									<!-- inner menu: contains the actual data -->
									<ul class="list-unstyled message-list mb-0">
										<li class="message-loading dropdown-item-text">
											<i class="fa fa-circle-o-notch fa-spin"></i> 加载中...
										</li>
									</ul>
								</li>
								<li><a class="dropdown-item dropdown-footer message-index" href="#">查看全部消息</a></li>
							</ul>
						</li>
						<li class="nav-item dropdown user-menu">
							<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
								<img class="rounded-circle shadow user-avatar" alt="User Image" src="<?= $resourceUrl ?>/img/avatar5.png">
								<span class="d-none d-sm-inline"><?= Html::encode($userName) ?></span>
							</a>
							<ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
								<!-- User image -->
								<li class="p-3 text-center user-profile-header">
									<img src="<?= $resourceUrl ?>/img/avatar5.png" class="rounded-circle shadow" alt="User Image" />
									<p><?= Html::encode($userName) ?></p>
								</li>
								<li class="px-3 py-2">
									<div class="text-center">
	                      				<?= Html::navigate('反馈问题', ['site/feedback']) ?>
	                    			</div>
	                    		</li>
								<!-- Menu Footer-->
								<li class="d-flex justify-content-between p-2 user-actions">
									<div>
										<?= Html::navigate('我的信息', ['site/profile'], ['class'=>'btn btn-light rounded-0']) ?>
									</div>
									<div>
										<a href="<?= $logoutUrl ?>" class="btn btn-light rounded-0">退出</a>
									</div>
								</li>
							</ul>
						</li>
				</ul>
			</div>
		</nav>
		<!-- Left side column. contains the logo and sidebar -->
		<aside class="app-sidebar classic-sidebar dashboard-sidebar shadow" data-bs-theme="dark">
			<div class="sidebar-brand">
				<a href="<?= $homeUrl ?>" class="brand-link">
					<span class="brand-mark" aria-hidden="true">M</span>
					<span class="brand-text">
						<strong><?= Html::encode($appName) ?></strong>
						<small>AdminLTE 4 · Yii2</small>
					</span>
				</a>
			</div>
			<div class="sidebar-wrapper">
				<div class="sidebar-context">
					<div class="sidebar-search" role="search">
						<label class="visually-hidden" for="sidebar-menu-search">搜索菜单</label>
						<input id="sidebar-menu-search" type="search" data-lte-toggle="sidebar-search" data-lte-target=".sidebar-menu" placeholder="搜索菜单..." autocomplete="off">
						<span class="sidebar-search-icon" aria-hidden="true"><i class="fa fa-search"></i></span>
					</div>
					<div class="sidebar-search-empty" data-lte-search-empty hidden>没有匹配的菜单</div>
				</div>
				<nav class="sidebar-menu-host"></nav>
			</div>
		</aside>

		<!-- Content Wrapper. Contains page content -->
		<main class="app-main">
			<!-- Content Header (Page header) -->
			<div class="app-content-header"><div class="container-fluid"><div class="content-menu"></div></div></div>

			<!-- Main content -->
			<div class="app-content"><div class="container-fluid h-100 p-0">
				<iframe id="__content" src="" width="100%" height="100%" frameborder="0" scrolling="auto"></iframe>
	     	</div></div>
		</main>
	</div>

    <?php $this->endBody() ?>

<script type="text/javascript">
(function ($) {
	seajs.use(['admin.ui', 'admin.message'], function(ui, message) {
		// 初始化框架布局
		ui.initLayout();
		// 初始化菜单
		ui.initMenu('.sidebar-menu-host', '.content-menu', <?= Json::htmlEncode($menus) ?>);
		// 初始化权限信息
		ui.initAuthorization(<?= Json::htmlEncode($modules) ?>, <?= Json::htmlEncode($isAdmin) ?>);
		// 导航到我的桌面
		ui.navigate('<?= $navigate;?>');

		// 初始化消息
		message.init('.messages-menu', {
			push: {
				server: <?= Json::htmlEncode($messageCenter['server']) ?>,
				token: <?= Json::htmlEncode($messageCenter['token']) ?>,
			},
			urls : {
				getList: '<?= Url::to(['message/get-menu-list']) ?>',
				getNewNums: '<?= Url::to(['message/get-new-nums']) ?>',
				read: '<?= Url::to(['message/read']) ?>',
				markAllRead : '<?= Url::to(['message/mark-all-read']) ?>',
				index: '<?= Url::to(['message/index']) ?>'
			}
		});
	});
})(window.jQuery);
</script>

    </body>
</html>
<?php $this->endPage() ?>
