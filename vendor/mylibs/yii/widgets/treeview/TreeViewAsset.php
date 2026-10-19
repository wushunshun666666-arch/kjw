<?php
/**
 * TreeViewAsset.php
 * @author suxh
 */

namespace mylibs\yii\widgets\treeview;

use yii\web\AssetBundle;

class TreeViewAsset extends AssetBundle
{
	public $sourcePath = '@mylibs/yii/widgets/treeview/assets';
	public $js = [
		'tree.jquery.js',
		'edit-tree.js',
	];
	public $css = [
		'jqtree.css',
	];
}