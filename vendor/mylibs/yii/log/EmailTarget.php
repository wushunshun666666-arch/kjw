<?php
/**
 * EmailTarget.php
 *
 * @author suxh
 */

namespace mylibs\yii\log;

use yii\helpers\VarDumper;

class EmailTarget extends \yii\log\EmailTarget
{
	protected function getContextMessage() {
		$context = [];
		foreach ($this->logVars as $name) {
			if (!empty($GLOBALS[$name])) {
				$data = $GLOBALS[$name];
				if (is_array($data)) {
					$this->filter($data);
				}

				$context[] = "\${$name} = " . VarDumper::dumpAsString($data);
			}
		}

		return implode("\n\n", $context);
	}

	/**
	 * 过滤数据，将敏感字段(如密码等)过滤掉
	 * @param array $data 数据
	 */
	protected function filter(&$data) {
		foreach ($data as $key=>&$value) {
			if (is_array($value)) {
				$this->filter($value);
			}
			else if (stripos($key, 'password') !== false) {
				unset($data[$key]);
			}
		}
	}
}
