<?php
/**
 * FileLock.php
 * @author suxh
 */

namespace mylibs\proc;

class FileLock
{
	public function __construct($file) {
		$this->file = $file;
	}

	public function __destruct() {
		if ($this->locked) {
			$this->unlock();
		}
	}

	public function lock() {
		if (file_exists($this->file)) {
			return $this->setError('加锁失败，锁文件已经存在');
		}

		if (!@touch($this->file)) {
			return $this->setError('加锁失败，创建锁文件失败');
		}
		@chmod($this->file, 0777);
		$this->locked = true;

		return true;
	}

	public function unlock() {
		unlink($this->file);
		$this->locked = false;
	}

	public function getError() {
		return $this->error;
	}

	protected function setError($error) {
		$this->error = $error;
		return false;
	}

	private $file;
	private $locked = false;
	private $error = null;
}