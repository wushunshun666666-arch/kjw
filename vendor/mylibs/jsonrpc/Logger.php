<?php
/**
 * 日志记录
 *
 * @author suxh
 */

namespace mylibs\jsonrpc;

class Logger
{
	/**
	 * 记录请求
	 * @param string $request
	 * @param string $url
	 */
	public function logRequest($request, $url = '') {
		if (is_array($request)) {
			$request = var_export($request, true);
		}

		$message = $url . PHP_EOL . $request;
		$this->log($message, 'REQUEST');
	}

	/**
	 * 记录结果
	 * @param string $response
	 */
	public function logResponse($response) {
		if (is_array($response)) {
			$response = var_export($response, true);
		}
		$this->log($response, 'RESPONSE');
	}

	/**
	 * 记录日志
	 * @param string $message
	 * @param string $type
	 */
	public function log($message, $type = 'NOTICE') {
		if (is_array($message)) {
			$message = var_export($message, true);
		}

		$message = sprintf('[%f] %s %s', microtime(true), $type, PHP_EOL.$message). PHP_EOL;
		$this->doLog($message);
	}

	/**
	 * 记录日志
	 * @param string $message
	 */
	protected function doLog($message) {
	}
}