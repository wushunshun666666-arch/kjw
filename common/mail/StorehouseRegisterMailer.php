<?php
/**
 * 用户注册成功通知邮件
 *
 * @author suxh
 */

namespace common\mail;

class StorehouseRegisterMailer extends Mailer
{
	public $username;
	public $password;

	public $subject = '用户注册成功';
	public $view = 'storehouse_register';
}