<?php
/**
 * QueryHelper.php
 *
 * @author suxh
 */

namespace mylibs\yii\db;

class QueryHelper
{
	public static function falseCondition() {
		return '1=0';
	}

	public static function integerRange($column, $start, $end, $zero=null) {
		if (($start != '' && !is_numeric($start)) || ($end != '' && !is_numeric($end))
				|| ($start != '' && $end != '' && $start > $end)) {
			return self::falseCondition();
		}

		$start = ($start == '')? '' : intval($start);
		$end = ($end == '')? '' : intval($end);
		if ($start === 0 && $end === 0) {
			return ($zero)? ['OR', [$column => 0], $zero] : [$column => 0];
		}

		$conditions = ['AND'];
		if ($start !== '') {
			$conditions[] = ($start === 0 && $zero)? ['OR', ['>=', $column, 0], $zero] : ['>=', $column, $start];
		}
		if ($end !== '') {
			$conditions[] = ($end === 0 && $zero)? ['OR', ['<=', $column, 0], $zero] : ['<=', $column, $end];
		}

		return $conditions;
	}

    public static function region($regionId, $columns=null) {
        if (empty($regionId)) {
            return ['AND'];
        }

        if ($columns==null) {
            $columns = ['province_id','city_id','district_id'];
        }

        $condition = ['AND'];
        $regions = explode('/', $regionId);
        for ($i = 2; $i >= 0; --$i) {
            if (!empty($regions[$i])) {
                $condition = [$columns[$i]=>$regions[$i]];
                break;
            }
        }

        return $condition;
    }

    public static function timeRange($column, $range) {
        $conditions = ['AND'];
        if (empty($range)) {
            return $conditions;
        }

        $times = explode('/', $range);
        $start = (isset($times[0]))? $times[0]:null;
        $end = (isset($times[1]))? $times[1]:null;
        if ($start) {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $start)) {
                $condition = ['>=', $column, $start];
            }
            elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
                $condition = ['>=', $column, $start.' 00:00:00'];
            }
            else {
                $condition = '1=0';
            }
            $conditions[] = $condition;
        }
        if ($end) {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $end)) {
                $condition = ['<=', $column, $end];
            }
            elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
                $condition = ['<=', $column, $end.' 23:59:59'];
            }
            else {
                $condition = '1=0';
            }
            $conditions[] = $condition;
        }

        return $conditions;
    }

    public static function dateRange($column, $range) {
        $conditions = ['AND'];
        if (empty($range)) {
            return $conditions;
        }

        $times = explode('/', $range);
        $start = (isset($times[0]))? $times[0]:null;
        $end = (isset($times[1]))? $times[1]:null;
        if ($start) {
            if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $start)) {
                $condition = ['>=', $column, $start];
            }
            else {
                $condition = '1=0';
            }
            $conditions[] = $condition;
        }
        if ($end) {
            if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $end)) {
                $condition = ['<=', $column, $end];
            }
            else {
                $condition = '1=0';
            }
            $conditions[] = $condition;
        }

        return $conditions;
    }
}