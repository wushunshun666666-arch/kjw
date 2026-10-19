<?php
/**
 * QRCode.php
 * @author James
 */

namespace mylibs;

require_once(dirname(__FILE__).'/../phpqrcode/phpqrcode.php');
class QRCode
{
	public static function create($url, $size=6, $margin=4) {
		\QRcode::png($url, false, QR_ECLEVEL_L, $size, $margin);
		exit;
	}
}