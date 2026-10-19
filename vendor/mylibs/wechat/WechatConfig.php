<?php
/**
 * WechatConfig.php
 * @author suxh
 */

namespace mylibs\wechat;

class WechatConfig
{
	public $token;
	public $appid;
	public $appsecret;
	public $encodingKey;

	public $authUrl;
	public $menuCreateUrl;
	public $menuGetUrl;
	public $menuDeleteUrl;
	public $userInfoUrl;

	const API_URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin';
	const AUTH_URL = '/token?grant_type=client_credential&';

	const MENU_CREATE_URL = '/menu/create?';
	const MENU_GET_URL = '/menu/get?';
	const MENU_DELETE_URL = '/menu/delete?';
	const MENU_CONDITIONAL_RUL = '/menu/addconditional?';

	const USER_GET_URL='/user/get?';
	const USER_INFO_URL='/user/info?';

	const MEDIA_GET_URL = '/media/get?';
	const QRCODE_CREATE_URL='/qrcode/create?';
	const QR_SCENE = 0;
	const QR_LIMIT_SCENE = 1;
	const QRCODE_IMG_URL='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=';
	const GROUP_GET_URL='/groups/get?';
	const GROUP_CREATE_URL='/groups/create?';
	const GROUP_UPDATE_URL='/groups/update?';
	const GROUP_DELETE_URL='/groups/delete?';
	const GROUP_MEMBER_UPDATE_URL='/groups/members/update?';
	const OAUTH_PREFIX = 'https://open.weixin.qq.com/connect/oauth2';
	const OAUTH_AUTHORIZE_URL = '/authorize?';
	const OAUTH_TOKEN_PREFIX = 'https://api.weixin.qq.com/sns/oauth2';
	const OAUTH_TOKEN_URL = '/access_token?';
	const OAUTH_REFRESH_URL = '/refresh_token?';
	const OAUTH_USERINFO_URL = '/userinfo?';
	const MESSAGE_USER_SEND = '/message/custom/send?';
	const MESSAGE_MASS_SENDALL = '/message/mass/sendall?';
	const MESSAGE_UPLOAD_NEWS = '/media/uploadnews?';
	const TEMPLATE_MESSAGE_SEND_URL = '/message/template/send?';

	const MEDIA_UPLOAD_URL = '/media/upload?';

	const JSAPI_TICKET = '/ticket/getticket?type=jsapi&access_token=';

	const BASE_SCOPE = 'snsapi_base';
	const USERINFO_SCOPE = 'snsapi_userinfo';

	public function __construct($options = null) {
		$this->authUrl = self::API_URL_PREFIX.self::AUTH_URL;
		$this->menuCreateUrl = self::API_URL_PREFIX.self::MENU_CREATE_URL;
		$this->menuGetUrl = self::API_URL_PREFIX.self::MENU_GET_URL;
		$this->menuDeleteUrl = self::API_URL_PREFIX.self::MENU_DELETE_URL;
		$this->menuConditionalUrl = self::API_URL_PREFIX.self::MENU_CONDITIONAL_RUL;
		$this->userInfoUrl = self::API_URL_PREFIX.self::USER_INFO_URL;

		$this->oauthAuthorizeUrl = self::OAUTH_PREFIX.self::OAUTH_AUTHORIZE_URL;
		$this->oauthTokenUrl = self::OAUTH_TOKEN_PREFIX.self::OAUTH_TOKEN_URL;
		$this->oauthRefreshUrl = self::OAUTH_TOKEN_PREFIX.self::OAUTH_REFRESH_URL;

		$this->groupCreateUrl = self::API_URL_PREFIX.self::GROUP_CREATE_URL;
		$this->groupMemberUpdateUrl = self::API_URL_PREFIX.self::GROUP_MEMBER_UPDATE_URL;
		$this->groupDeleteUrl = self::API_URL_PREFIX.self::GROUP_DELETE_URL;
		$this->groupGetUrl = self::API_URL_PREFIX.self::GROUP_GET_URL;

		$this->sendMessageUrl = self::API_URL_PREFIX.self::MESSAGE_USER_SEND;
		$this->sendMessageToAllUrl = self::API_URL_PREFIX.self::MESSAGE_MASS_SENDALL;
		$this->sendTemplateMessageUrl = self::API_URL_PREFIX.self::TEMPLATE_MESSAGE_SEND_URL;
		$this->uploadNewsUrl = self::API_URL_PREFIX.self::MESSAGE_UPLOAD_NEWS;

		$this->jsApiTicketUrl = self::API_URL_PREFIX.self::JSAPI_TICKET;

		$this->mediaUploadUrl = self::API_URL_PREFIX.self::MEDIA_UPLOAD_URL;

		if ($options) {
			foreach ($options as $key=>$val) {
				$this->$key = $val;
			}
		}
	}
}