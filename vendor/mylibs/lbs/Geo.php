<?php
/**
 * Geo.php
 * @author suxh
 */

namespace mylibs\lbs;

class Geo
{
	//根据经纬度计算距离 其中A($lat1,$lng1)、B($lat2,$lng2)
	public static function getDistance($lat1,$lng1,$lat2,$lng2) {
		//地球半径
		$R = 6378137;

		//将角度转为弧度
		$radLat1 = deg2rad($lat1);
		$radLat2 = deg2rad($lat2);
		$radLng1 = deg2rad($lng1);
		$radLng2 = deg2rad($lng2);

		//结果
		$s = acos(cos($radLat1)*cos($radLat2)*cos($radLng1-$radLng2)+sin($radLat1)*sin($radLat2))*$R;

		//精度
		$s = round($s* 10000)/10000;

		return round($s);
	}

	/**
	 * 米->纬度转换
	 * @param float $meters
	 */
	public static function meterToLat($meters) {
		// 子午线长度40009km
		$meridianLength = 40009000;
		$lengthPerDegree = $meridianLength/360;

		return $meters / $lengthPerDegree;
	}

	/**
	 * 米->经度转换
	 * @param float $lat 纬度
	 * @param float $meters
	 */
	public static function meterToLng($lat, $meters) {
		// 子午线长度40009km
		$meridianLength = 40009000;
		$lengthPerDegree = $meridianLength/360;

		$lengthPerDegree = ($lengthPerDegree * cos($lat * (pi() / 180)));

		return $meters/$lengthPerDegree;
	}
}