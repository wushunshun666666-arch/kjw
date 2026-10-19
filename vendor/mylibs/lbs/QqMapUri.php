<?php
/**
 * QqMapUri.php
 *
 * @author suxh
 */

class QqMapUri
{
	public function drive($start, $end) {
		$params = array(
			'type'		=>'drive',
			'sword'		=>$start['address'],
			'spointx'	=>$start['lng'],
			'spointy'	=>$start['lat'],
			'eword'		=>$end['address'],
			'epointx'	=>$end['lng'],
			'epointy'	=>$end['lat'],
			'cond'		=>0,
			'noback'	=>'',
		);
		
		return 'http://map.qq.com/m/drive/routeplan/'.http_build_query($params);
	}
}