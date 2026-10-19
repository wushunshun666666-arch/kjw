<?php

/**
 * 第三方 OAuth 平台适配器契约。
 *
 * 适配器只负责与一个外部平台交换授权码并解析可信身份，
 * 不创建会员、不签发平台 JWT，也不处理 HTTP 请求或响应。
 */

namespace common\oauth;

interface SocialOAuthAdapterInterface
{
    /**
     * 构造跳转到第三方平台的授权地址。
     *
     * @param string $state 服务端签发的一次性防伪状态值
     * @return string|false 授权地址；配置或参数无效时返回 false
     */
    public function buildAuthorizeUrl(string $state): string|false;

    /**
     * 使用第三方回调授权码交换可信身份资料。
     *
     * @param string $code 第三方平台回传的授权码
     * @param string $state 服务端已校验的状态值
     * @return array{providerAppId: string, providerUserId: string, unionId?: string|null, nickname?: string|null, avatar?: string|null}|false
     */
    public function exchangeCode(string $code, string $state): array|false;

    /**
     * 返回最近一次可展示的授权失败原因。
     */
    public function getError(): ?string;
}
