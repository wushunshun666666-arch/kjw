<?php
/**
 * 邮件
 *
 * @author suxh
 */

namespace common\mail;

class Mailer
{
	public $to;
	public $subject;
	public $from = 'support@santouliubi.com';
	public $view;
	
	public function send() {
		$params = $this->getAttributes();

		try {
			$message = \Yii::$app->mailer->compose($this->view, $params);
			$message->setTo($this->to);
			$message->setFrom($this->from);
			$message->setSubject($this->subject);

			return $message->send();
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function getAttributes() {
		$values = [];
		$names = $this->attributes();
		foreach ($names as $name) {
			$values[$name] = $this->$name;
		}
	
		return $values;
	}
	
    protected function attributes() {
        $class = new \ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }
}