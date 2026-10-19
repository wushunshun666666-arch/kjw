<?php
/**
 * DatePickerAsset.php
 * @author suxh
 */

namespace mylibs\yii\widgets\datePicker;

use yii\web\AssetBundle;

class DatePickerAsset extends AssetBundle
{
	public $sourcePath = '@mylibs/yii/widgets/datePicker/assets/native';
	public $js = [
		'date-picker.js',
	];
}
