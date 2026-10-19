<?php
/**
 * Openssl.php
 * @author suxh
 */

namespace mylibs\security;

class Openssl
{
	public function __construct() {
		if(!extension_loaded('openssl')) {
			throw new \Exception('no openssl module');
		}
	}

	public function setPrivateKey($key) {
		$this->privateKey = $key;
	}

	public function setPrivateKeyFile($keyFile, $passphrase) {
		$key = file_get_contents($keyFile);
		if ($key === false) {
			return false;
		}

		$key = openssl_get_privatekey($key, $passphrase);
		if (key === false) {
			return false;
		}

		$this->setPrivateKey($key);

		return true;
	}

	public function setPublicKey($key) {
		$this->publicKey = $key;
	}

	public function setPublicKeyFile($keyFile) {
		$key = file_get_contents($keyFile);
		if ($key === false) {
			return false;
		}

		$key = openssl_get_publickey($key);
		if (key === false) {
			return false;
		}

		$this->setPublicKey($key);

		return true;
	}

	public function privateDecrypt($data, $padding = OPENSSL_PKCS1_PADDING) {
		if (empty($this->privateKey)) {
			throw new \Exception('解密之前，必须先设置私钥');
		}

		if (openssl_private_decrypt(base64_decode($data), $decrypted, $this->privateKey, $padding)) {
			return $decrypted;
		}

		return false;
	}

	public function publicEncrypt($data, $key, $padding = OPENSSL_PKCS1_PADDING) {
		if (empty($this->publicKey)) {
			throw new \Exception('加密之前，必须先设置公钥');
		}

		openssl_public_encrypt($data, $crypted, $this->publicKey);

		return(base64_encode($crypted));
	}

	private $privateKey;
	private $publicKey;
}