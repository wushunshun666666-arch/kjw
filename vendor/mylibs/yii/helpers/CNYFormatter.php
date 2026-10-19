<?php
/**
 * CNYFormatter.php
 * @author James
 */

namespace mylibs\yii\helpers;

class CNYFormatter{
	static $basical  = [0=>"零","壹","贰","叁","肆","伍","陆","柒","捌","玖"];
	static $advanced = [1=>"拾","佰","仟"];

	public static function format($number){
		$isNegative = ($number < 0) ? true : false;
		$number = abs($number);
		$result = '';
		$number=trim($number);
		if ($number==0) {
			$result = "零元整";
		} else {
			if(strpos($number,'.') !== false){
				$number=round($number,2);
				$data=explode(".",$number);
				$ini=self::int($data[0]);
				$dex=self::dec($data[0], $data[1]);
				$result = $ini.$dex;
			}else{
				$result = self::int($number).'整';
			}
		}

		if ($isNegative) {
			$result = '负数'.$result;
		}

		return '人民币'.$result;
	}

	protected static function int($number){
		$arr=array_reverse(str_split($number));
		$data='';
		$zero=false;
		$zero_num=0;
		if ($number ==0) return $data;
		foreach ($arr as $k=>$v){
			$_chinese='';
			$zero=($v==0)?true:false;
			$x=$k%4;
			if($x && $zero && $zero_num>1)continue;
			switch ($x){
				case 0:
					if($zero){
						$zero_num=0;
					}else{
						$_chinese=self::$basical[$v];
						$zero_num=1;
					}
					if($k==8){
						$_chinese.='亿';
					}elseif($k==4){
						$_chinese.='万';
					}
					break;
				default:
					if($zero){
						if($zero_num==1){
							$_chinese=self::$basical[$v];
							$zero_num++;
						}
					}else{
						$_chinese=self::$basical[$v];
						$_chinese.=self::$advanced[$x];
					}
			}
			$data=$_chinese.$data;
		}
		return $data.'元';
	}

	protected static function dec($int, $number){
		if(strlen($number)<2) $number.='0';
		$arr=array_reverse(str_split($number));
		$data='';
		$zero_num=false;
		if ($int==0 && $arr[1]==0) {
			return self::$basical[$arr[0]].'分';
		}
		foreach ($arr as $k=>$v){
			$zero=($v==0)?true:false;
			$_chinese='';
			if($k==0){
				if(!$zero){
					$_chinese=self::$basical[$v];
					$_chinese.='分';
					$zero_num=true;
				}
			}else{
				if($zero){
					if($zero_num){
						$_chinese=self::$basical[$v];
					}
				}else{
					$_chinese=self::$basical[$v];
					$_chinese.='角';
				}
			}
			$data=$_chinese.$data;
		}
		return $data;
	}
}