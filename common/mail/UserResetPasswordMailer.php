<?php
/**
 * 用户密码重置通知邮件
 *
 * @author suxh
 */

namespace common\mail;

class UserResetPasswordMailer extends Mailer
{
	public $password;

	public $subject = '用户密码重置';
	public $view = 'user_reset_password';
}