<?php
/**
 * 桌面
 *
 * @author suxh
 */

namespace app\controllers;

use app\controllers\Controller;

class DesktopController extends Controller
{
	public function actionIndex() {
		return $this->render('index');
	}
}