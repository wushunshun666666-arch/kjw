<?php
/**
 * DateTimeUtility.php
 * @author suxh
 */

namespace mylibs;

class DateTimeUtility
{
	public static function diffDays($endDate, $startDate) {
		$endDate = new \DateTime($endDate);
		$startDate = new \DateTime($startDate);
		$interval = $startDate->diff($endDate);

		return $interval->format('%r%a');
	}

	public static function diffSeconds($endTime, $startTime) {
		$endTime = new \DateTime($endTime);
		$startTime = new \DateTime($startTime);
		$interval = $startTime->diff($endTime);

		return $interval->format('%r%s');
	}

	public static function format($time = 'now', $format = 'Y-m-d') {
		$date = new \DateTime($time);
		return $date->format($format);
	}

	/**
	 * 计算多少天之前的日期
	 * @param integer $days
	 * @param string $now
	 * @param string $format
	 */
	public static function daysBefore($days, $now = 'now', $format = 'Y-m-d') {
		$date = new \DateTime($now);
		$date->modify("-{$days} day");
		return $date->format($format);
	}

	/**
	 * 计算多少天之后的日期
	 * @param integer $days
	 * @param string $now
	 * @param string $format
	 */
	public static function daysAfter($days, $now = 'now', $format = 'Y-m-d') {
		$date = new \DateTime($now);
		$date->modify("+{$days} day");
		return $date->format($format);
	}

	/**
	 * 计算多少小时之前的时间
	 * @param integer $hours
	 * @param string $currentTime
	 * @param string $format
	 */
	public static function hoursBefore($hours, $currentTime = 'now', $format = 'Y-m-d H:i:s') {
		$date = new \DateTime($currentTime);
		$date->modify("-{$hours} hour");
		return $date->format($format);
	}

	/**
	 * 获取指定日期的上个月月份
	 * 注：只能取月份，不能取到具体日期
	 * @param string $now
	 * @param string $format 返回的月份格式，Y-m或Ym
	 * @return string 返回上个月月份，如：2015-01、201501，格式取决于format参数
	 */
	public static function lastMonth($now='now', $format='Y-m') {
		$date = new \DateTime($now);
		$now = $date->format('Y-m-15');
		$date = new \DateTime($now);
		$date->modify('-1 month');

		if (!in_array($format, ['Y-m','Ym'])) {
			throw new \Exception('不支持的格式：'.$format);
		}

		return $date->format($format);
	}

	/**
	 *
	 * @param integer $range
	 * @param string $endDate
	 * @param string $format
	 */
	public static function monthRange($range, $endDate='now', $format='Ym') {
		if ($range <= 0) {
			return [];
		}

		$date = new \DateTime($endDate);

		$items = [];
		while($range) {
			$date->modify('-1 month');
			$month = $date->format($format);
			$items[] = $date->format($format);
			$range--;
		}
		return $items;
	}

	/**
	 * 获取指定日期的上一年
	 * 注：只能取年份，不能取到具体日期
	 * @param string $now
	 * @return string 返回上一年，如：2015
	 */
	public static function lastYear($now='now') {
		$date = new \DateTime($now);
		$year = $date->format('Y');

		return $year - 1;
	}

	/**
	 * 获取最近7天
	 * @param string $time
	 * @param string $format
	 */
	public static function last7Days($time = 'now', $format = 'Y-m-d') {
		$date = new \DateTime($time);
		$end = $date->format($format);

		$date->modify('-6 day');
		$start = $date->format($format);

		return [
			'start'	=> $start,
			'end'	=> $end
		];
	}

	/**
	 * 获取最近30天
	 * @param string $time
	 * @param string $format
	 */
	public static function last30Days($time = 'now', $format = 'Y-m-d') {
		$date = new \DateTime($time);
		$end = $date->format($format);

		$date->modify('-29 day');
		$start = $date->format($format);

		return [
			'start'	=> $start,
			'end'	=> $end
		];
	}

	/**
	 * 返回起始日期至结束日期的所有日期
	 * @param string $startDate 起始日期
	 * @param string $endDate 结束日期
	 * @param string $format 格式化
	 * @return array 返回日期
	 */
	public static function dateRange($startDate, $endDate, $format = 'Y-m-d') {
		$items = [];

		if (self::diffDays($endDate, $startDate) < 0) {
			return $items;
		}

		$date = new \DateTime($startDate);
		$items[] = self::format($startDate, $format);
		while(self::diffDays($endDate, $startDate)) {
			$date->modify('+1 day');
			$startDate = $date->format($format);
			$items[] = self::format($startDate, $format);
		}
		return $items;
	}

	/**
	 * 获取指定日期本周的日期范围，周一至周日
	 * @param string $time
	 * @param string $format
	 * @return array array('start' => '2015-11-02', 'end'=>'2012-11-07')
	 */
	public static function dateRangeOfWeek($time = 'now', $format = 'Y-m-d') {
		$date = new \DateTime($time);
		$date->modify('monday this week');
		$start = $date->format($format);
		$date->modify('sunday this week');
		$end = $date->format($format);
		return [
			'start' => $start,
			'end' => $end,
		];
	}

	/**
	 * 获取指定日期本月的日期范围
	 * @param string $time
	 * @param string $format
	 * @return array array('start' => '2015-11-01', 'end'=>'2015-11-31')
	 */
	public static function dateRangeOfMonth($time = 'now', $format = 'Y-m-d') {
		$date = new \DateTime($time);
		$date->modify('first day of this month');
		$start = $date->format($format);
		$date->modify('last day of this month');
		$end = $date->format($format);
		return [
			'start' => $start,
			'end' => $end,
		];
	}

}