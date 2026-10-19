<?php
/**
 * 用户密码重置通知邮件
 *
 * @author suxh
 */

namespace common\mail;

class StorehouseUserResetPasswordMailer extends Mailer
{
	public $password;

	public $subject = '仓库用户密码重置';
	public $view = 'storehouse_user_reset_password';
}