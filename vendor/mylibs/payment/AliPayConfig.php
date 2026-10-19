<?php
/**
 * 支付宝配置
 * @author suxh
 */

namespace mylibs\payment;

class AliPayConfig
{
	/**
	 * @var string 名称，用于记录日志
	 */
	public $name = 'alipay';

	/**
	 * @var string 网关地址
	 */
	public $gatewayUrl;

	/**
	 * @var string HTTPS形式消息验证地址
	 */
	public $httpsVerifyUrl;

	/**
	 * @var string HTTP形式消息验证地址
	 */
	public $httpVerifyUrl;

	/**
	 * @var string 合作者身份ID，以2088开头的16位纯数字组成
	 */
	public $partner;

	/**
	 * @var string 收款支付宝账号，一般情况下收款账号就是签约账号
	 */
	public $sellerEmail;

	/**
	 * @var string 安全检验码，以数字和字母组成的32位字符
	 */
	public $key;

	/**
	 * @var string ca证书路径地址，用于curl中ssl校验
	 */
	public $cacert;

	/**
	 * @var string 签名方式 ，默认为MD5
	 */
	public $signType = 'MD5';

	/**
	 * @var string 字符编码格式 ，默认为utf-8，目前支持 gbk或utf-8
	 */
	public $inputCharset = 'utf-8';

	/**
	 * @var string 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
	 */
	public $transport = 'https';

	/**
	 * 构造函数
	 * @param array $config
	 */
	public function __construct($config) {
		$this->checkConfig($config);
		foreach ($config as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}

	public function getVerifyUrl($notifyId) {
		$veryfyUrl = ($this->transport == 'https')? $this->httpsVerifyUrl:$this->httpVerifyUrl;

		$veryfyUrl = $veryfyUrl.'partner=' . $this->partner . "&notify_id=" . $notifyId;

		return $veryfyUrl;
	}

	/**
	 * 验证配置
	 * @param array $config
	 * @throws \Exception
	 */
	protected function checkConfig($config) {
		$requires = array('gatewayUrl', 'partner', 'key', 'cacert');
		foreach ($requires as $key) {
			if (!isset($config[$key])) {
				throw new \Exception('undefined ' . $key);
			}
		}
	}
}