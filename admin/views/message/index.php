<?php
/**
 * index.php
 *
 * @author chenzhf
 */

use app\helpers\Url;
use app\widgets\DataTableActiveForm;
use app\widgets\StatusGroup;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $searchForm app\models\message\SearchForm */
/* @var $conditionGroup array */

?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">消息列表</h3>
    </div>
    <div class="card-body">
        <div class="row search-form">
            <div class="col-sm-12">
                <?php $form = DataTableActiveForm::begin(['dataTableId'=>'data-list']); ?>
                <input class="condition" type="hidden" name="condition" value="">
                <?= StatusGroup::widget([
                    'id' => 'status-group',
                    'groups' => [''=>'全部消息'] + $conditionGroup,
                    'clientOptions' => ['onSelect'=> new JsExpression('changeStatus')],
                ]) ?>
                <button type="button" class="btn btn-light" id="mark-all-read">全部标记为已读</button>
                <?php DataTableActiveForm::end(); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?= $form->table([
                    'head' => [
                        '标题'	=> ['width'=>'15%'],
                        '内容'	=> ['width'=>'40%'],
                        '类型'	=> ['width'=>'15%'],
                        '发布时间'	=> ['width'=>'15%'],
                        '操作'		=> ['width'=>'15%'],
                    ],
                    'url' => ['list'],
                    'load' => true,
                ]) ?>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {

        var MessageTable = (function() {

            function MessageTable (options, ajax, ui, message) {
                this.options = options;
                this.ajax = ajax;
                this.ui = ui;
                this.message = message;

                this.init();
            }

            MessageTable.prototype.init = function() {
                $(this.options.tableSelector).on('click', '.detail', $.proxy(this.read, this));
                $(this.options.tableSelector).on('click', '.mark-read', $.proxy(this.markRead, this));
                $('#mark-all-read').on('click', $.proxy(this.markAllRead, this));
            };

            MessageTable.prototype.markRead = function(obj) {
                var that = this;
                var $self = $(obj.currentTarget);
                var $tr = $self.closest('tr');

                this.ajax.post(this.options.readUrl, {id : $tr.attr('data-id')}, function(data) {
                    if (data.success()) {
                        that.removeNewLabel($tr);
                    }
                    data = data.data();
                    that.updateNums(data.nums);
                });
            };

            MessageTable.prototype.removeNewLabel = function($targetTr) {
                if (arguments[0]) {
                    $targetTr.find('.un-read').remove();
                }
            };

            MessageTable.prototype.updateNums = function(nums) {
                this.message && this.message.updateNewNums(nums);
            };

            MessageTable.prototype.read = function(obj) {
                var that = this;
                var $self = $(obj.currentTarget);
                var $tr = $self.closest('tr');

                var id = $tr.attr('data-id');

                this.ajax.post(this.options.readUrl, {id : id},
                function(data) {
                    if(data.success()){
                        that.removeNewLabel($tr);

                        if(!(data = data.data())) {
                            return
                        }

                        that.updateNums(data.nums);

                        if (data.url && data.modal){
                        	that.ui.showModal(data.url, data.title, 800, 400, '');
						}
						else if (data.url) {
							that.ui.navigate(data.url);
						}
                    }
                });
            };

            MessageTable.prototype.markAllRead = function() {
                var that = this;
                this.ajax.post(this.options.markAllReadUrl, {},
                function(data) {
                    if (data.success()) {
                        $(that.options.tableSelector).find('.un-read').remove();
                        that.updateNums(0);
                    }
                })
            };

            return MessageTable;

        })();

        seajs.use(['admin.ajax', 'admin.ui', 'admin.message'], function(ajax, ui, message) {
            var options = {
                tableSelector  : '#data-list',
                readUrl : '<?= Url::to(['message/read']) ?>',
                markAllReadUrl : '<?= Url::to(['message/mark-all-read']) ?>'
            };

            new MessageTable(options, ajax, ui, top._messageMenu);
        });

    })(window.jQuery);

function changeStatus(value) {
	$('.condition').val(value);
	$('#data-list').data('dataTable').query();
}
</script>
