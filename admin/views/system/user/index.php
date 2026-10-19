<?php
/**
 * index.php
 *
 * @author suxh
 */

use app\helpers\Html;
use app\helpers\Url;
use app\widgets\DataTableActiveForm;
use app\widgets\EditTreeView;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $form app\widgets\DataTableActiveForm */
/* @var $model app\models\system\UserSearchForm */
?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header">
		<h3 class="card-title h6 mb-0">用户列表</h3>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-lg-2">
			<?= EditTreeView::widget([
				'data' => $departments,
				'autoOpen' => true,
				'onClickTitle' => new JsExpression('selectDepartment'),
				'onClickNew' => new JsExpression('addDepartment'),
				'onClickEdit' => new JsExpression('modifyDepartment'),
				'onClickRemove' => new JsExpression('deleteDepartment'),
			]) ?>
			</div>
			<div class="col-lg-10">
				<div class="row search-form">
					<div class="col-12">
					<?php $form = DataTableActiveForm::begin(['dataTableId'=>'user-list']); ?>
						<?= $form->field($model, 'id')->textInput(['size'=>5]) ?>
						<?= $form->field($model, 'username')->textInput(['size'=>8]) ?>
						<?= $form->field($model, 'name')->textInput(['size'=>8]) ?>
						<?= Html::activeHiddenInput($model, 'departmentId') ?>
						<?= $form->field($model, 'status')->searchDropDownList($statusNames) ?>
						<?= $form->queryButton() ?>
					<?php DataTableActiveForm::end(); ?>
					</div>
				</div>
				<div class="row">
					<div class="col-12">
					<?= $form->table([
						'head' => [
							'用户ID'		=> ['width'=>'8%'],
							'用户名'		=> ['width'=>'10%'],
							'姓名'		=> ['width'=>'10%'],
							'所属部门'	=> ['width'=>'15%'],
							'添加时间'	=> ['width'=>'15%'],
							'状态'		=> ['width'=>'8%'],
							'操作'		=> ['width'=>'15%'],
						],
						'url' => ['list'],
						'load' => true,
					]) ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
function selectDepartment(id) {
	$('#usersearchform-departmentid').val(id);
	loadSystemDataTable('user-list');
}

function loadSystemDataTable(id) {
	var attempts = 0;
	var load = function() {
		var dataTable = $('#' + id).data('dataTable');
		if (dataTable) {
			dataTable.load();
			return;
		}
		if (++attempts < 20) {
			window.setTimeout(load, 50);
		}
	};
	load();
}

function addDepartment(id) {
	seajs.use(['admin.ui'], function(ui) {
		var url = "<?= Url::to(['add-department'])?>"+'?id='+id;
		ui.showModal(url, '新增下级部门', 800, 400);
	});
}

function modifyDepartment(id) {
	seajs.use(['admin.ui'], function(ui) {
		var url = "<?= Url::to(['modify-department'])?>"+'?id='+id;
		ui.showModal(url, '修改部门', 800, 400);
	});

}

function deleteDepartment(id) {
	seajs.use(['admin.ui', 'admin.ajax'], function(ui, ajax) {
		var url = <?= Url::toJs(['system/user/del-department']) ?>;
		if (!ui.verifyPermissions(url)) {
            return false;
        }
		ui.confirmBox('确认删除此部门吗？', function() {
			ui.showLoading();

			ajax.post(url[1], {id: id}, function(data) {
				ui.closeLoading();
				if (data.success()) {
					ui.successBox(data.message(), function() {
						ui.refresh();
					});
				} else {
					ui.messageBox(data.message());
				}
			}, function() {
				ui.closeLoading();
				ui.messageBox('提交请求失败');
			});
		});
	});

}
</script>
