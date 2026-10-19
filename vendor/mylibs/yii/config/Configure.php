<?php
/**
 * Configure.php
 *
 * @author James
 */

namespace mylibs\yii\config;

use Yii;
use yii\base\InvalidConfigException;

class Configure
{
	public function loadAppConfig($config) {
		$alias = '@app/config/'.YII_ENV.'/'.$config.'.php';
		return $this->load($alias);
	}

	public function loadCommonConfig($config) {
		$alias = '@common/config/'.YII_ENV.'/'.$config.'.php';
		return $this->load($alias);
	}

	public function load($alias) {
		$file = Yii::getAlias($alias);

		if (!file_exists($file)) {
			throw new InvalidConfigException("配置文件'{$file}'不存在。");
		}

		return require($file);
	}
}