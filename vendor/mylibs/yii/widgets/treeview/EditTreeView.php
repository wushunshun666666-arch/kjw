<?php
/**
 * EditTreeView.php
 * @author suxh
 */

namespace mylibs\yii\widgets\treeview;

class EditTreeView extends TreeView
{
	public $onClickTitle = null;
	public $onClickNew = null;
	public $onClickEdit = null;
	public $onClickRemove = null;
	
	protected function getClientOptions() {
		$options = parent::getClientOptions();
		$options['onClickTitle'] = $this->onClickTitle;
		$options['onClickNew'] = $this->onClickNew;
		$options['onClickEdit'] = $this->onClickEdit;
		$options['onClickRemove'] = $this->onClickRemove;
		
		return $options;
	}

	protected function registerJs($view, $options) {
		$id=$this->options['id'];
		$view->registerJs("\$('#{$id}').editTree({$options})");
	}
}
