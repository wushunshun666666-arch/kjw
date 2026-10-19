<?php
/**
 * ModifyPasswordForm.php
 * @author suxh
 */

namespace app\models\site;

use common\models\FormModel;

class ModifyPasswordForm extends FormModel
{
    public $oldPassword;
    public $newPassword;
    public $confirmPassword;

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['oldPassword', 'newPassword', 'confirmPassword'], 'required'],
            [['newPassword', 'confirmPassword'], 'string', 'min'=>8, 'max'=>20],
            [['newPassword'], 'mylibs\yii\validators\PasswordValidator', 'type'=>'complex'],
            [['confirmPassword'], 'compare', 'compareAttribute'=>'newPassword', 'message'=>'与新密码不一致。'],
        ];
    }

    /**
     * (non-PHPdoc)
     * @see \yii\base\Model::attributeLabels()
     */
    public function attributeLabels() {
    	return [
			'oldPassword'=>'旧密码',
			'newPassword'=>'新密码',
            'confirmPassword'=>'确认密码',
    	];
    }
}
