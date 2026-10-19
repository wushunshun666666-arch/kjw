<?php

namespace common\models\common;

/**
 * This is the model class for table "region".
 *
 * @property integer $id
 * @property string $name
 * @property string $full_name
 * @property integer $level
 * @property integer $parent_id
 * @property string $postal_code
 * @property string $area_code
 */
class Region extends \common\models\ActiveRecord
{
    /**
     * @var integer 级别-国家
     */
    CONST LEVEL_COUNTRY = 0;
    /**
     * @var integer 级别-省份
     */
    CONST LEVEL_PROVINCE= 1;
    /**
     * @var integer 级别-城市
     */
    CONST LEVEL_CITY = 2;
    /**
     * @var integer 级别-区县
     */
    CONST LEVEL_DISTRICT = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'region';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'name', 'full_name', 'level', 'parent_id'], 'required'],
            [['id', 'level', 'parent_id'], 'integer'],
            [['name'], 'string', 'max' => 32],
        	[['full_name'], 'string', 'max' => 100],
            [['postal_code'], 'string', 'max' => 10],
        	[['area_code'], 'string', 'max' => 3]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '区域编码',
            'name' => '区域名称',
        	'full_name' => '区域全称',
            'level' => '区域级别',
            'parent_id' => '上级区域',
            'postal_code' => '邮政编码',
        	'area_code' => '区号',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent() {
        return $this->hasOne(self::className(), ['id' => 'parent_id']);
    }

    /**
     * 根据ID获取区域对象
     * @param integer $id 区域ID
     * @return Region|null
     */
    public static function getById($id) {
        return static::find()->where(['id' => $id])->one();
    }

    public static function getAllAsTree() {
        $regions = static::find()->where(['>', 'level', self::LEVEL_COUNTRY])
        ->orderBy(['level'=>SORT_ASC])->all();

        $trees = [];
        $index = [];
        foreach ($regions as $region) {
            $parentId = $region->parent_id;
            if (isset($index[$parentId])) {
                $parent = &$index[$parentId]['childs'];
            }
            else {
                $parent = &$trees;
            }

            $parent[$region->id] = [
            	'id' 	=> $region->id,
                'name' 	=> $region->name,
                'level' => $region->level,
                'childs'=> [],
            ];
            $index[$region->id] = &$parent[$region->id];
        }

        return  $trees;
    }

    public static function getByDistrictId($id) {
        $district = self::getById($id);
        if ($district->level != self::LEVEL_DISTRICT) {
            return new Error('不是区县级别的区域');
        }
        if (!$district->parent) {
            return new Error('城市不存在');
        }
        if (!$district->parent->parent) {
            return new Error('省份不存在');
        }

        return $district;
    }

    public function getReadableName() {
        if ($this->level == self::LEVEL_PROVINCE || $this->level == self::LEVEL_COUNTRY) {
            return $this->name;
        }
        else if ($this->level == self::LEVEL_CITY) {
            return $this->parent->name.'/'.$this->name;
        }
        else {
            return $this->parent->parent->name.'/'.$this->parent->name.'/'.$this->name;
        }
    }
}
