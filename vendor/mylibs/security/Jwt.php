<?php
/**
 * json web token
 * @author suxh
 */

namespace mylibs\security;

use Firebase\JWT\JWT as FirebaseJwt;

class Jwt
{
	public static function encode($payload, $key, $algo = 'HS256', $keyId = null) {
		try {
			return FirebaseJwt::encode($payload, $key, $algo, $keyId);
		} catch (\DomainException $exception) {
			// The historical message-center keys are shorter than the minimum now
			// enforced by php-jwt 7. Preserve their wire-compatible signatures
			// until the external service can rotate both ends to a 256-bit key.
			if (!in_array($algo, ['HS256', 'HS384', 'HS512'], true)) {
				throw $exception;
			}

			$header = ['typ' => 'JWT', 'alg' => $algo];
			if ($keyId !== null) {
				$header['kid'] = $keyId;
			}
			$segments = [self::base64UrlEncode(json_encode($header)), self::base64UrlEncode(json_encode($payload))];
			$hashAlgorithm = 'sha' . strtolower(substr($algo, 2));
			$segments[] = self::base64UrlEncode(hash_hmac($hashAlgorithm, implode('.', $segments), $key, true));
			return implode('.', $segments);
		}
	}

	private static function base64UrlEncode($value) {
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}
}
