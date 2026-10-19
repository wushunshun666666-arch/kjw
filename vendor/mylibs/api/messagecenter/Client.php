<?php
/**
 * Client.php
 * @author suxh
 */

namespace mylibs\api\messagecenter;

class Client
{
	public function __construct($redis) {
		$this->redis = $redis;
	}

	public function publish($userType, $message, $userId = 0) {
		if ($userId == 0) {
			$channel = $userType;
		}
		else {
			$channel = $userType.'/'.$userId;
		}
		$message = json_encode($message);
		$this->redis->publish($channel, $message);
	}

	private $redis;
}