<?php

/**
 * 第三方登录配置读取器。
 *
 * 从 common/config/<环境>/social-login.php 读取公共配置，
 * 并仅在存在时合并同目录的 social-login-local.php 私密配置。
 */

namespace common\oauth;

use mylibs\yii\config\Configure;
use Yii;
use yii\helpers\ArrayHelper;

final class SocialLoginConfig
{
    /**
     * 读取当前环境的第三方登录配置。
     *
     * @return array<string, mixed> 当前环境配置
     */
    public static function load(): array
    {
        $config = (new Configure())->loadCommonConfig('social-login');
        if (!is_array($config)) {
            throw new \RuntimeException('第三方登录配置格式无效。');
        }

        $localFile = Yii::getAlias('@common/config/' . YII_ENV . '/social-login-local.php');
        if (is_file($localFile)) {
            $localConfig = require $localFile;
            if (!is_array($localConfig)) {
                throw new \RuntimeException('第三方登录本地配置格式无效。');
            }
            $config = ArrayHelper::merge($config, $localConfig);
        }

        return $config;
    }

    /**
     * 返回指定平台的已启用配置。
     *
     * @param int $provider 社交平台编码
     * @return array<string, mixed>|null 平台未启用时返回 null
     */
    public static function getProvider(int $provider): ?array
    {
        $providers = self::load()['providers'] ?? null;
        $providerConfig = is_array($providers) ? ($providers[$provider] ?? null) : null;
        if (!is_array($providerConfig) || !($providerConfig['enabled'] ?? false)) {
            return null;
        }

        return $providerConfig;
    }
}
