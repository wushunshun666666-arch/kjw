<?php
/**
 * 文件服务器客户端接口
 *
 * @author suxh
 */

namespace mylibs\api\file;

use mylibs\Curl;
use mylibs\DateTimeUtility;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\base\Exception;

class FileClient extends \yii\base\Component
{
	/**
	 * @var string 文件服务接口根路径，如：http://file.example.com
	 */
	public $server = null;
	/**
	 * @var string 文件服务API接口根路径，如果未设置，则使用server参数
	 */
	public $apiServer = null;
	/**
	 * @var string 私钥
	 */
	public $apiKey = null;

	/**
	 * @var string 上传结果重定向地址，如果需要跨域上传，则必须设置
	 */
	public $uploadRedirectUrl = null;

	public function init() {
		if (empty($this->server)) {
			throw new Exception('server 参数必须设置。');
		}
		if (empty($this->apiKey)) {
			throw new Exception('apiKey 参数必须设置。');
		}
		if (!$this->apiServer) {
			$this->apiServer = $this->server;
		}

		$this->publicUrl 	= $this->server.'/resource';
		$this->tmpUploadUrl = $this->server.'/file/tmp-upload';
		$this->uploadUrl 	= $this->server.'/file/upload';
		$this->viewUrl 		= $this->server.'/file/view';
		$this->apiUrl 		= $this->apiServer.'/file/api';

		if ($this->uploadRedirectUrl) {
			$uploadRedirectUrl = Url::to($this->uploadRedirectUrl, true);

			$this->tmpUploadUrl .= '?_r='.$uploadRedirectUrl;
			$this->uploadUrl 	.= '?_r='.$uploadRedirectUrl;
		}

		parent::init();
	}

	/**
	 * 获取文件对应的完整URL地址
	 * @param string $alias 文件别名
	 * @param string $subPath 子路径，默认为空
	 * @return 返回文件对应的完整URL地址
	 */
	public function getViewUrl($alias, $subPath=null) {
		if (strpos($alias, '{SUBPATH}') !== false) {
			if ($subPath === null) {
				return $this->setError('未指定子目录名称');
			}
			$alias = str_replace('{SUBPATH}', $subPath, $alias);
		}

		if (strncmp($alias, '@', 1)) {
			return $this->setError('不是有效的文件别名');
		}

		$pos = strpos($alias, '/');
		if ($pos === false) {
			return $this->setError('不是有效的文件别名');
		}
		$root = substr($alias, 0, $pos);
		$file = substr($alias, $pos);

		if ($root == '@public') {
			return $this->publicUrl.$file;
		}
		else {
			$token = $this->getViewToken($alias);
			if ($token === false) {
				return $this->setError('获取Token失败');
			}
			return $this->viewUrl."?alias={$alias}&token={$token}";
		}
	}

	/**
	 * @return string 返回临时上传路径
	 */
	public function getTmpUploadUrl() {
		return $this->tmpUploadUrl;
	}

	public function getUploadUrl() {
		return $this->uploadUrl;
	}

	/**
	 * 创建文件上传参数，这些参数需要与文件一起post到上传路径
	 * @param string $appId
	 * @return array 返回参数
	 */
	public function createUploadParams($appId, $path='') {
        $timestamp = DateTimeUtility::format('now', 'YmdHis');
        $token = md5($appId.$timestamp.$this->apiKey.$path);

        return [
            'appId' => $appId,
            'timestamp' => $timestamp,
        	'path' => $path,
            'token' => $token,
        ];
	}

	public function getViewToken($alias) {
		$options = [
			'alias' => $alias,
		];
		$res = $this->send('getViewToken', $options);
		if ($res === false) {
			return false;
		}

		return $res->data->token;
	}

    public function move($source, $target, $cover = false) {
        $options = [
            'source' => $source,
            'target' => $target,
            'cover'  => $cover,
        ];
        $res = $this->send('move', $options);
        if ($res === false) {
            return false;
        }

        return true;
    }

    public function copy($source, $target, $cover = false) {
    	$options = [
    		'source' => $source,
    		'target' => $target,
    		'cover'  => $cover,
    	];
    	$res = $this->send('copy', $options);
    	if ($res === false) {
    		return false;
    	}

    	return true;
    }

    public function delete($alias) {
    	$options = [
			'alias' => $alias,
    	];
    	$res = $this->send('delete', $options);
    	if ($res === false) {
    		return false;
    	}

    	return true;
    }

    public function getContents($alias) {
    	$options = [
    		'alias' => $alias,
    	];
    	$res = $this->send('getContents', $options);
    	if ($res === false) {
    		return false;
    	}

    	return $res->data->contents;
    }

    public function getError() {
    	return $this->error;
    }

    protected function setError($error) {
    	$this->error = $error;
    	return false;
    }

    private function send($method, $data) {
    	$data['version'] = '1.0';
    	$data['method'] = $method;
    	$data['transactionId'] = $this->generateTransactionId();

        $data = Json::encode([
        	'request' => $data,
        ]);
        $sign = md5($data.$this->apiKey);

        $url = $this->apiUrl.'?sign='.$sign;

        $client = new Curl();
        $res = $client->post($url, $data);
        if (!$res) {
        	return $this->setError($client->getError());
        }

        try {
        	$res = Json::decode($res, false);
        	if (!isset($res->response)) {
        		return $this->setError('返回的数据格式错误，缺少response信息');
        	}

        	$res = $res->response;
        	if (!isset($res->code)) {
        		return $this->setError('返回的数据格式错误，缺少code信息');
        	}
        	if ($res->code != 1000) {
        		return $this->setError("{$res->msg}($res->code)：{$res->value}");
        	}
        	return $res;
    	} catch(\Exception $e) {
    		return $this->setError($e->getMessage());
    	}
    }

	private static function generateTransactionId() {
		$date = date('YmdHis');
		$rand = mt_rand(1000000, 9999999);
		return 'CL'.$date.$rand;
	}

	private $publicUrl;
	private $tmpUploadUrl;
	private $uploadUrl;
	private $viewUrl;
	private $apiUrl;
	private $error;
}