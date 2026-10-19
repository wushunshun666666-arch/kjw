<?php
/**
 * VinClient.php
 *
 * @author suxh
 */

namespace mylibs\api\pms;

use mylibs\api\JsonRpc;

class VinClient extends JsonRpc
{
	public function __construct() {
		parent::__construct('vin','pms');
	}

	/**
	 * Vin查询
	 * @param string $vin
	 * @return false|array
	 */
	public function query($vin) {
		$response = $this->client->call('query', [$vin]);

		if ($response === false) {
			return $this->setError($this->client->getError());
		}
		else {
			return $response;
		}
	}
}