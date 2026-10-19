<?php
/**
 * FeedbackForm.php
 * @author suxh
 */

namespace app\models\system;

use common\models\FormModel;

class FeedbackForm extends FormModel
{
	public $title;
	public $content;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['title', 'content'], 'required'],
            [['title'], 'string', 'max' => 64],
            [['content'], 'string', 'max' => 500],
		];
	}

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
			'title' => '主题',
            'content' => '内容',
		];
	}
}