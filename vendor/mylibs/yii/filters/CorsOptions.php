<?php
/**
 * 处理CORS Preflighted requests
 *
 * @author suxh
 */

namespace mylibs\yii\filters;

use yii\base\ActionFilter;

class CorsOptions extends ActionFilter
{
	/**
	 * @inheritdoc
	 */
	public function beforeAction($action) {
		if (\Yii::$app->getRequest()->getMethod() == 'OPTIONS') {
            return false;
        }

		return true;
	}
}