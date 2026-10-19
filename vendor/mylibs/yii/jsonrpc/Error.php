<?php
/**
 * Error.php
 * @author suxh
 */

namespace mylibs\yii\jsonrpc;

class Error
{
	/**
	 * @var integer Parse error语法解析错误，服务端接收到无效的json。该错误发送于服务器尝试解析json文本
	 */
    const PARSE_ERROR = -32700;
	/**
	 * @var integer 无效请求，发送的json不是一个有效的请求对象。
	 */
    const INVALID_REQUEST = -32600;
	/**
	 * @var integer 方法不存在或无效
	 */
    const METHOD_NOT_FOUND = -32601;
	/**
	 * @var integer 无效的参数
	 */
    const INVALID_PARAMS = -32602;
	/**
	 * @var integer 内部错误
	 */
    const INTERNAL_ERROR = -32603;
    /**
     * @var integer 服务端错误
     */
    const SERVER_ERROR = -32000;

    public static function parseError($message = '', $data = null) {
    	$message = self::formatMessage('Parse Error', $message);
    	return new self($message, self::PARSE_ERROR, $data);
    }

    public static function invalidRequest($message = '', $data = null) {
    	$message = self::formatMessage('Invalid Request', $message);
    	return new self($message, self::INVALID_REQUEST, $data);
    }

    public static function internalError($message = '', $data = null) {
    	$message = self::formatMessage('Internal Error', $message);
    	return new self($message, self::INTERNAL_ERROR, $data);
    }

    public static function methodNotFound($message = '', $data = null) {
    	$message = self::formatMessage('Method Not Found', $message);
    	return new self($message, self::METHOD_NOT_FOUND, $data);
    }

    public static function serverError($message = '', $data = null) {
    	$message = self::formatMessage('Server Error', $message);
    	return new self($message, self::SERVER_ERROR, $data);
    }

    public static function isError($error) {
    	return ($error instanceof self);
    }

    private static function formatMessage($message, $description = '') {
    	if (!empty($description)) {
    		$message = $message."($description)";
    	}

    	return $message;
    }

    public function __construct($message, $code, $data = null) {
    	$this->message = $message;
    	$this->code = $code;
        $this->data = $data;
    }

    public function toArray() {
        return [
            'code' 	   => $this->code,
            'message'  => $this->message,
			'data'     => $this->data,
        ];
    }

    private $message;
    private $code;
    private $data;
}