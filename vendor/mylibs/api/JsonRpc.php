<?php
/**
 * JsonRpc.php
 * @author suxh
 */

namespace mylibs\api;

use mylibs\yii\config\Configure;

class JsonRpc
{
    /**
     * @param $name 模块名称
     * @param $configPath 配置文件路径
     */
	public function __construct($name, $configPath) {
		$alias = '@mylibs/api/config/'.YII_ENV.'/'.$configPath.'.php';
		$configure = new Configure();
        $config = $configure->load($alias);

        $logger = new Logger($config['name'].'\\'.$name);

        $url = $config['url'].$name;

        $this->client = new \mylibs\jsonrpc\Client($url, $logger, $config['signKey']);
	}

	public function getError() {
		return $this->error;
	}

	protected function setError($error) {
		$this->error = $error;
		return false;
	}

	protected $client;
	private $error;
}