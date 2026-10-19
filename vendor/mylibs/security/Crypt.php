<?php
/**
 * 加密解密
 * @author suxh
 */

namespace mylibs\security;

class Crypt
{
	/**
	 * 构造函数
	 * @param string $key 密钥，如果不指定，则使用默认的密钥
	 * @param string $method 加密算法
	 * @throws Exception
	 */
	public function __construct($key = null, $method = 'AES-128-CBC') {
		if(!extension_loaded('openssl')) {
			throw new \Exception('no openssl module');
		}

		$this->key = $this->createKey($key);
		$this->method = $method;
	}

	/**
	 * 加密数据
	 * @param string $data 要加密的数据
	 * @return string 返回加密后的字符串
	 */
	public function encrypt($data) {
		return base64_encode(openssl_encrypt($data, $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv));
	}

	/**
	 * 解密数据
	 * @param string $data 要解密的数据
	 * @return string 返回解密后的数据
	 */
	public function decrypt($data) {
		return openssl_decrypt(base64_decode($data), $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv);
	}

	/**
	 * 创建密钥
	 * @param string $key 原始密钥
	 * @return string 返回密钥
	 */
	protected function createKey($key) {
		$key = ($key === null) ? $this->defaultKey : $key;

		return md5($this->keyPrefix.$key);
	}

	protected $key= null;
	protected $keyPrefix = 'Man%543$Q4p%@20120317co';
	protected $defaultKey= 'CRYPT-PHP-MYLIBS';
	protected $method;
	protected $iv = 'Iv-CRYPT-MYLIBS%';
}