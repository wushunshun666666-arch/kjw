<?php

namespace mylibs\yii\data;

use yii\data\BaseDataProvider;

class DataProvider extends BaseDataProvider
{
	public $models;
	public $totalCount;

    /**
     * @inheritdoc
     */
    protected function prepareModels()
    {
    	$this->getPagination()->totalCount = $this->getTotalCount();
        return $this->models;
    }

    /**
     * @inheritdoc
     */
    protected function prepareKeys($models)
    {
        return array_keys($models);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTotalCount()
    {
        return $this->totalCount;
    }
}
