<?php
/**
 * SoapClient.php
* @author suxh
*/

namespace mylibs\soap;

class SoapClient extends \SoapClient
{
	public function __construct($wsdl, $options = []) {
		if (isset($options['timeout'])) {
			$this->timeout = $options['timeout'];
			unset($options['timeout']);
		}
		$options['exceptions'] = true;

		try {
			$timeout = new SocketTimeout($this->timeout);
			parent::SoapClient($wsdl, $options);
		} catch (\SoapFault $e) {
			set_error_handler(function() {}, 0);
			@trigger_error("");
			restore_error_handler();
			throw $e;
		}
	}

	public function __call($function_name, $arguments) {
		$timeout = new SocketTimeout($this->timeout);
		return parent::__call($function_name, $arguments);
	}

	public function __soapCall($function_name, $arguments, $options = NULL, $input_headers = NULL, &$output_headers = NULL) {
		$timeout = new SocketTimeout($this->timeout);
		return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
	}

	private $timeout = null;
}

class SocketTimeout {
	public function __construct($timeout) {
		if ($timeout) {
			$this->defaultTimeout = ini_get('default_socket_timeout');
			ini_set('default_socket_timeout', $timeout);
		}
	}

	public function __destruct() {
		if ($this->defaultTimeout !== null) {
			ini_set('default_socket_timeout', $this->defaultTimeout);
		}
	}

	private $defaultTimeout = null;
}
