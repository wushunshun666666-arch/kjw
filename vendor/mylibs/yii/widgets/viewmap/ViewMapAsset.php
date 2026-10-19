<?php
/**
 * ViewMapAsset.php
 * @author suxh
 */

namespace mylibs\yii\widgets\viewmap;

use yii\web\AssetBundle;

class ViewMapAsset extends AssetBundle
{
	public static $appkey = '';
	public $sourcePath = '@mylibs/yii/widgets/viewmap/assets';
	
	public function init() {
		parent::init();
		$this->js[] = 'viewmap.js';
		$this->js[] = 'http://api.map.baidu.com/api?v=2.0&ak='.self::$appkey;
	}
}