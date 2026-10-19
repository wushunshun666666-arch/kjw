<?php
/**
 * CheckBoxTreeView.php
 * @author suxh
 */

namespace mylibs\yii\widgets\treeview;

class CheckBoxTreeView extends TreeView
{
	public $checkboxName = 'checkbox[]';
	public $disabled = false;
	public $onClick = null;

	protected function getClientOptions() {
		$options = parent::getClientOptions();
		$options['checkboxName'] = $this->checkboxName;
		$options['disabled'] = $this->disabled;
		$options['onClick'] = $this->onClick;
		return $options;
	}

	protected function registerJs($view, $options) {
		$id=$this->options['id'];
		$view->registerJs("\$('#{$id}').checkBoxTree({$options})");
	}
}
