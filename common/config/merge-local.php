<?php

use yii\helpers\ArrayHelper;

/**
 * 为各环境应用本站私有配置；未配置本站文件时保持环境默认值。
 */
return static function (array $config): array {
    $localConfigFile = __DIR__ . '/main-local.php';
    if (!is_file($localConfigFile)) {
        return $config;
    }

    $localConfig = require $localConfigFile;
    // 私有缓存配置可能改用 FileCache，不能保留 RedisCache 的专用选项。
    if (isset($localConfig['components']['cache'])) {
        unset($config['components']['cache']);
    }

    return ArrayHelper::merge($config, $localConfig);
};
