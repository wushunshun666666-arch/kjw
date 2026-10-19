<?php
/**
 * MobileDetect.php
 * @author suxh
 */

namespace mylibs;

class MobileDetect
{
	public function __construct() {
		$this->detect = new \Mobile_Detect();
	}
	
	public function isMobile() {
		return $this->detect->isMobile();
	}
	
	public function isTablet() {
		return $this->detect->isTablet();
	}
	
	private $detect = null;
}