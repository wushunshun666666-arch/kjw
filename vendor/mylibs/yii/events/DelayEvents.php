<?php
/**
 * DelayEvents.php
 * @author suxh
 */

namespace mylibs\yii\events;

use yii\base\Component;

class DelayEvents extends Component
{
	public function init() {
		parent::init();
		register_shutdown_function(function () {
			$this->flush();
		});
	}

	public function dealy($handler, $data = null) {
		$this->events[] = [$handler, $data];
	}

	public function flush() {
		foreach ($this->events as $handler) {
			$data = $handler[1];
			call_user_func($handler[0], $data);
		}
	}

	private $events = [];
}