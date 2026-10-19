<?php
/**
 * index.php
 *
 * @author suxh
 */

use app\helpers\Html;
use app\widgets\EditTreeView;
use yii\web\JsExpression;
use app\widgets\DataTableActiveForm;
use app\helpers\Url;

/* @var $this yii\web\View */
/* @var $form app\widgets\DataTableActiveForm */
/* @var $model app\models\system\UserSearchForm */
?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header">
		<h3 class="card-title h6 mb-0">模块列表</h3>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-lg-2">
			<?= EditTreeView::widget([
				'data' => $module,
				'autoOpen' => true,
				'onClickTitle' => new JsExpression('selectCategory'),
				'onClickNew' => new JsExpression('addCategory'),
				'onClickEdit' => new JsExpression('modifyCategory'),
				'onClickRemove' => new JsExpression('deleteCategory'),
			]) ?>
			</div>
			<div class="col-lg-10">
				 <button type="button" class="btn btn-sm btn-primary mb-3" id="add-module">新增模块</button>
				<div class="row search-form">
					<div class="col-12">
						<?php $form = DataTableActiveForm::begin(['dataTableId'=>'module-list']); ?>
							<?= Html::activeHiddenInput($model, 'id') ?>
							<div class="d-none"><?= $form->queryButton();?></div>
                        <?php DataTableActiveForm::end(); ?>
					</div>
				</div>

				<div class="row">
					<div class="col-12">
						<?= $form->table([
                            'head' => [
                                 '模块ID'	=> ['width'=>'7%'],
                                 '模块名称'	=> ['width'=>'20%'],
                                 '模块URL'	=> ['width'=>'20%'],
                                  '说明'  	=> ['width'=>'20%'],
                                 '创建时间'	=> ['width'=>'12%'],
                                 '更新时间'	=> ['width'=>'12%'],
								   '操作'   => ['width'=>'10%'],
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
function selectCategory(id) {
    $('#modulesearchform-id').val(id);
	$('#add-module').data('category-id', id);
	loadSystemDataTable('module-list');
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

function addCategory(id) {
	seajs.use(['admin.ui'], function(ui) {
		var url = "<?= Url::to(['add-category'])?>"+'?id='+id;
		ui.showModal(url, '新增模块类别', 800, 400);
	});
}

function modifyCategory(id) {
	seajs.use(['admin.ui'], function(ui) {
		var url = "<?= Url::to(['modify-category'])?>"+'?id='+id;
		ui.showModal(url, '修改类别信息', 800, 300);
	});

}

function deleteCategory(id) {
	seajs.use(['admin.ui', 'admin.ajax'], function(ui, ajax) {
		var url = <?= Url::toJs(['system/module/delete-category']) ?>;
		if (!ui.verifyPermissions(url)) {
            return false;
        }
		ui.confirmBox('确认操作删除此类别吗？', function() {
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

function add(id){
	id = id || $('#add-module').data('category-id');
	if(!id){
		seajs.use(['admin.ui'], function(ui) {
			ui.messageBox('请选择模块类，再进行添加模块');
	});

	}else{
		seajs.use(['admin.ui'], function(ui) {
		var url = "<?= Url::to(['add'])?>"+'?id='+id;
		ui.showModal(url, '新增模块', 1000, 400);
	});
	}
}

$(document)
	.off('click.systemModuleAdd', '#add-module')
	.on('click.systemModuleAdd', '#add-module', function() {
		add();
	});

</script>
