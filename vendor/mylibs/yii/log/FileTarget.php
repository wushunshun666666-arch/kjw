<?php
/**
 * FileTarget.php
 *
 * @author suxh
 */

namespace mylibs\yii\log;

use yii\log\Target;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use \Yii;

class FileTarget extends Target
{
	public $logPath;
	public $fileMode;
	public $dirMode = 0775;

	/**
	 * Initializes the route.
	 * This method is invoked after the route is created by the route manager.
	 */
	public function init() {
		parent::init();
		if ($this->logPath === null) {
			$this->logPath = Yii::$app->getRuntimePath() . '/logs/';
		} else {
			$this->logPath = Yii::getAlias($this->logPath);
		}

		if (!is_dir($this->logPath)) {
			FileHelper::createDirectory($this->logPath, $this->dirMode, true);
		}
	}

	/**
	 * Writes log messages to a file.
	 * @throws InvalidConfigException if unable to open the log file for writing
	 */
	public function export() {
		$messages = [];
		foreach ($this->messages as $message) {
			$category = $message[2];
			$messages[$category][] = $message;
		}

		foreach ($messages as $category=>$message) {
			$text = implode("\n", array_map([$this, 'formatMessage'], $message)) . "\n";
			$category = str_replace(['/', '\\', '::', ':'], '-', $category);
			$logFile = $this->logPath.$category.'-'.date('Ymd').'.log';

			if (($fp = @fopen($logFile, 'a')) === false) {
				throw new InvalidConfigException("Unable to append to log file: {$logFile}");
			}
			@flock($fp, LOCK_EX);
			@fwrite($fp, $text);
			@flock($fp, LOCK_UN);
			@fclose($fp);
			if ($this->fileMode !== null) {
				@chmod($logFile, $this->fileMode);
			}
		}
	}
}