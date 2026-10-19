<?php
/**
 * ActiveRecord.php
 *
 * @author suxh
 */

namespace common\models;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

class ActiveRecord extends \yii\db\ActiveRecord
{
	protected function timestampBehavior() {
		return [
         	'class' => TimestampBehavior::className(),
         	'createdAtAttribute' => 'create_time',
         	'updatedAtAttribute' => 'update_time',
         	'value' => new Expression('NOW()'),
     	];
	}

	protected function timestampBehaviorStatusTime() {
		return [
         	'class' => TimestampBehavior::className(),
         	'createdAtAttribute' => 'create_time',
         	'updatedAtAttribute' => 'status_time',
         	'value' => new Expression('NOW()'),
     	];
	}

	public static function expressionNow() {
		return new Expression('NOW()');
	}

	public static function expressionCurdate() {
		return new Expression('CURDATE()');
	}
}