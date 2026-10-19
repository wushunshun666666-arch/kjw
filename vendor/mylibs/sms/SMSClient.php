<?php
/**
 * SMSClient.php
 *
 * @author James
 */

namespace mylibs\sms;

use mylibs\sms\sms253\Sms253;
use mylibs\sms\base\Logger;
use mylibs\yii\config\Configure;

class SMSClient
{
	public function __construct() {
		$configure = new Configure();
		$config = $configure->loadCommonConfig('sms-253');
		$logger = new Logger('sms-253');
		$this->client = new Sms253($config, $logger);
	}

	public function getError() {
		return $this->error;
	}

	public function send($mobile, $message) {
		$result = $this->client->send($mobile, $message);
		if (false === $result) {
			return $this->setError($this->client->getError());
		}

		return $result;
	}

	protected function setError($error) {
		$this->error = $error;
		return false;
	}

	private $error;
	private $client;
}