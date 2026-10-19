<?php
/**
 * ErrorAction.php
 *
 * @author suxh
 */

namespace mylibs\yii\actions;

use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;

class ErrorAction extends Action
{
	public $view;
	public $defaultName;
	public $defaultMessage;

	public function run() {

		if (($exception = Yii::$app->getErrorHandler()->exception) === null) {
			return '';
		}

		if ($exception instanceof HttpException) {
			$code = $exception->statusCode;
		} else {
			$code = $exception->getCode();
		}
		if ($exception instanceof Exception) {
			$name = $exception->getName();
		} else {
			$name = $this->defaultName ?: Yii::t('yii', 'Error');
		}
		if ($code) {
			$name .= " (#$code)";
		}

		if ($exception instanceof UserException) {
			$message = $exception->getMessage();
		} else {
			$message = $this->defaultMessage ?: Yii::t('yii', 'An internal server error occurred.');
		}

		if (Yii::$app->getRequest()->getIsAjax()) {
			if ($exception instanceof HttpException) {
				$exception->statusCode = 200;
			}
			if ($exception instanceof BadRequestHttpException) {
				$appendMessage = '请刷新页面后重试';
			}
			return $this->controller->jsonError($message . (isset($appendMessage ) ? $appendMessage : ''));
		} else {
			return $this->controller->render($this->view ?: $this->id, [
				'name' => $name,
				'message' => $message,
				'exception' => $exception,
			]);
		}
	}
}