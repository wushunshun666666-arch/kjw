<?php
/**
 * ModuleModifyForm.php
 * @author xursh
 */

namespace app\models\system;

use common\models\FormModel;
use common\models\Error;
use common\models\system\ModuleModel;

class ModuleModifyForm extends FormModel
{
    public $id;
    public $categoryId;
	public $name;
	public $url;
	public $description;
    
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['name','url'], 'required'],
			[['name','url'], 'string', 'max' =>60],
			[['url'],'match', 'pattern' => '/^[a-zA-Z][a-zA-Z-\/]*$/','message'=>'请输入正确的模块URL'] ,
			[['url'], 'validateUrl'],
			[['id'], 'safe'],
			[['description'], 'string','max'=>255],	
		];
    }

	public function validateUrl() {
        $res = ModuleModel::checkUrl($this->url,$this->id);
		
		if (Error::isError($res)) {
            return  $this->addError('url', $res->getError());
        }
    }
	
	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
			'id'         => '模块ID',
			'categoryId' => '所属类别',
            'name'       => '模块名称',
			'url'        => '模块URL',
            'description'=> '模块说明',
		];
	}


}