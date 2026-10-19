<?php
/**
 * TenPayConfig.php
 * @author James
 */

namespace mylibs\payment;

class TenPayConfig
{
	/**
	 * @var string 名称，用于记录日志
	 */
	public $name = 'tenpay';

	/**
	 * @var string 绑定支付的APPID（必须配置，开户邮件中可查看）
	 */
	public $appId;

	/**
	 * @var string 商户号（必须配置，开户邮件中可查看）
	 */
	public $mchId;

	/**
	 * @var string 商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
	 */
	public $key;

	/**
	 * @var string API接口地址
	 */
	public $apiUrl;

	/**
	 * @var string 扫码支付地址
	 */
	public $nativePayUrl;

	/**
	 * @var string 支付通知回调地址
	 */
	public $notiryUrl;

	/**
	 * 构造函数
	 * @param array $config
	 */
	public function __construct($config) {
		$this->checkConfig($config);
		foreach ($config as $name => $value) {
			$this->$name = $value;
		}
	}

	/**
	 * 验证配置
	 * @param array $config
	 * @throws \Exception
	 */
	protected function checkConfig($config) {
		$requires = array('appId', 'mchId', 'key', 'key', 'apiUrl', 'nativePayUrl');
		foreach ($requires as $key) {
			if (!isset($config[$key])) {
				throw new \Exception('undefined ' . $key);
			}
		}
	}
}