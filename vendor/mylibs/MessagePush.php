<?php
/**
 * MessagePush.php
 *
 * @author James
 */

namespace mylibs;
require dirname(__FILE__)."/../jpush/autoload.php";

use mylibs\yii\config\Configure;

class MessagePush
{
	public function __construct() {
		$configure = new Configure();
		$config = $configure->loadCommonConfig('jpush');
		$logHandler = \Yii::$app->basePath.'/runtime/logs/jpush-'.date('Ymd').'.log';

		$this->client = new \JPush\Client($config['appKey'], $config['masterSecret'], $logHandler);
	}

	public function sendToAll($message, $title, $options=[]) {
		try {
			$result = $this->client->push()
				->setPlatform(['ios', 'android'])
				->addAllAudience()
				->setNotificationAlert($message)
				->iosNotification($message, ['extras' => $options])
				->androidNotification($message, ['title' => $title, 'extras' => $options])
				->message($message, ['title' => $title, 'extras' => $options])
				->options(['apns_production' => true])
				->send();
			return $result;
		} catch (APIRequestException $e) {
			return new Error($e->getMessage());
		} catch (APIConnectionException $e) {
			return new Error($e->getMessage());
		} catch (InvalidArgumentException $e) {
			return new Error($e->getMessage());
		}
	}

	public function sendById($id, $message, $title, $options=[]) {
		try {
			$result = $this->client->push()
				->setPlatform(['ios', 'android'])
				->addRegistrationId($id)
				->setNotificationAlert($message)
				->iosNotification($message, ['extras' => $options])
				->androidNotification($message, ['title' => $title, 'extras' => $options])
				->message($message, ['title' => $title, 'extras' => $options])
				->options(['apns_production' => true])
				->send();
			return $result;
		} catch (APIRequestException $e) {
			return new Error($e->getMessage());
		} catch (APIConnectionException $e) {
			return new Error($e->getMessage());
		} catch (InvalidArgumentException $e) {
			return new Error($e->getMessage());
		}
	}

	private $client;
}