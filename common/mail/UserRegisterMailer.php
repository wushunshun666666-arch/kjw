<?php
/**
 * 用户注册成功通知邮件
 *
 * @author suxh
 */

namespace common\mail;

class UserRegisterMailer extends Mailer
{
	public $username;
	public $password;

	public $subject = '用户注册成功';
	public $view = 'user_register';
}