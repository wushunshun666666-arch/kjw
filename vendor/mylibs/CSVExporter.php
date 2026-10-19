<?php
/**
 * CSVExporter.php
 * @author James
 */

namespace mylibs;

class CSVExporter
{
	/**
	 * 导出Excel
	 * @param string $filename
	 * @param array  $data 二维数组
	 * [
	 * 	[field1, field2, field3],
	 * 	...
	 * ]
	 */
	public static function export($filename, $data) {
		self::header($filename);

		$csv = "";
		foreach ($data as $items) {
			$csv .= implode(',', $items)."\r\n";
		}

		print mb_convert_encoding($csv, 'GBK', 'UTF-8');
		self::end();
	}

	public static function header($filename) {
		$filename = $filename.'.csv';

		// 处理文件名乱码问题
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') === false) {
			$filename = rawurlencode($filename);
		}

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Transfer-Encoding: binary');
		header('Pragma: public');
	}

	public static function line($data) {
		$data = str_replace(',', '，', $data);
		$data = implode(',', $data)."\r\n";
		print mb_convert_encoding($data, 'GBK', 'UTF-8');
	}

	public static function end() {
		exit();
	}

	public static function error($error) {
		print mb_convert_encoding($error, 'GBK', 'UTF-8');
		self::end();
	}

	/**
	 * 格式化数字-避免在Excel中被格式化为科学记数法
	 * @param unknown $number
	 */
	public static function formatText($text) {
		return '="'.$text.'"';
	}

	/**
	 * 格式化数字-避免在Excel中被格式化为科学记数法
	 * @param unknown $number
	 */
	public static function formatNumeric($number) {
		return '="'.$number.'"';
	}
}