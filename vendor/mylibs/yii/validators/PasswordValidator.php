<?php
/**
 * 密码格式证器类
 * 长度限为8-16个字符，其中必须含有一个数字(0-9),一个字母(a-zA-Z),和一个特殊字符(!#$%()*+,-./: = ?@[]^_`{|}~),并且不得含有空格
 *
 * @author suxh
 */

namespace mylibs\yii\validators;

use yii\validators\RegularExpressionValidator;

class PasswordValidator extends RegularExpressionValidator
{
	public $type = 'simple';

	/**
	 * @inheritdoc
	 */
	public function init() {
		if ($this->type == 'complex') {
			$this->pattern = '/(?=(?:.*?\d){1})(?=(?:.*?)[a-zA-Z]{1})(?=(?:.*?[!#\$%\(\)\*\+,-\.\/:=\?@\[\]\^_`\{\|\}~]){1})(^((?![\s\x{4e00}-\x{9fa5}\x{f900}-\x{fa2d}]).)+$)/u';
			$this->message = '必须由数字，英文字母和特殊字符组成，且不包含空格。';

		}
		else {
			$this->pattern = '/^[!-~]*$/';
			$this->message = '必须是英文的字母、数字和符号。';
		}
	}
}