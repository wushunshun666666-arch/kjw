<?php
/**
 * 遵循JSON-RPC2.0规范
 * 1、请求必须以POST方式提交，并且Content-type必须为：application/json。
 * 2、请求对象
 * jsonrpc：指定JSON-RPC协议版本的字符串，必须准确写为“2.0”。
 * method：包含所要调用方法名称的字符串。
 * 	以rpc开头的方法名，用英文句号（U+002E or ASCII 46）连接的为预留给rpc内部的方法名及扩展名，且不能在其他地方使用。
 * params：调用方法所需要的结构化参数值，该成员参数可以被省略。
 * id：已建立客户端的唯一标识id，值必须包含一个字符串、数值或NULL空值。
 * 	如果不包含该成员则被认定为是一个通知。该值一般不为NULL，若为数值则不应该包含小数。
 * 例子：{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}
 *
 * 3、响应对象
 * jsonrpc：指定JSON-RPC协议版本的字符串，必须准确写为“2.0”。
 * result：成功时必须包含，错误时必须不包含该成员。
 * error：失败时必须包含，没有错误时必须不包含该成员。
 * id：该成员必须包含。该成员值必须于请求对象中的id成员值一致。若在检查请求对象id时错误（例如参数错误或无效请求），则该值必须为空值。
 *
 * 4、错误对象
 * code：使用数值表示该异常的错误类型。必须为整数。
 * message：对该错误的简单描述字符串。
 * data：包含关于错误附加信息的基本类型或结构化类型。该成员可忽略。
 *
 * @author suxh
 */

namespace mylibs\yii\jsonrpc;

use Yii;
use yii\base\InlineAction;
use yii\base\InvalidParamException;
use yii\base\InvalidRouteException;
use yii\base\UserException;
use yii\helpers\Json;
use yii\web\Response;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

class Controller extends \yii\web\Controller
{
    public $enableCsrfValidation = false;

    /**
     * 异常错误处理，以JSON-RPC2.0规范输出响应对象
     */
    public function actionError() {
    	http_response_code(200);

    	if (($exception = Yii::$app->getErrorHandler()->exception) === null) {
    		$error = Error::internalError('未知错误');
    	}
        else if ($exception instanceof NotFoundHttpException) {
    		$error = Error::methodNotFound();
    	}
    	else if ($exception instanceof HttpException) {
    		$error = Error::internalError($exception->getMessage());
    	}
    	else if ($exception instanceof UserException) {
    		$error = Error::internalError($exception->getMessage());
    	}
    	else {
    		$this->log($exception->getMessage());
    		$error = Error::internalError();
    	}

    	$response = new Response();
    	$response->format = Response::FORMAT_JSON;
    	$response->data = $this->formatResponse($error);

    	$this->log("RESPONSE\n".json_encode($response->data, JSON_UNESCAPED_UNICODE));

    	return $response;
    }

    /**
     * {@inheritDoc}
     * @see \yii\base\Controller::runAction()
     */
    public function runAction($id, $params = []) {
    	if ($id == 'error') {
    		return parent::runAction($id, $params);
    	}

    	$contents = file_get_contents('php://input');
    	$this->log("REQUEST\n".$contents);

    	// 获取解析并验证请求对象
		$requests = $this->getRequests($id, $contents);
		if (Error::isError($requests)) {
			$data = $this->formatResponse($requests);
		}
		else {
	        $results = [];
	        foreach ($requests as $request) {
    			$response = $this->validateRequest($request);
	        	if (!Error::isError($response)) {
	        		try {
		        		//ob_start();
		        		$response = parent::runAction($request->method, $request);
		        		//ob_clean();
	        		} catch (NotFoundHttpException $e) {
	        			$response = Error::methodNotFound();
	        		} catch (InvalidRouteException $e) {
	        			$response = Error::methodNotFound();
	        		} catch (ForbiddenHttpException $e) {
	        			$response = Error::internalError($e->getMessage());
	        		} catch (UserException $e) {
	        			$response = Error::internalError($e->getMessage());
	        		} catch (\Exception $e) {
	        			$this->log($e->getMessage());
	        			$response = Error::internalError(YII_DEBUG? $e->getMessage() : '');
	        		}
				}

	        	$id = (isset($request->id))? $request->id : null;
				$results[] = $this->formatResponse($response, $id);
	        }

			$data = (count($requests) > 1)? $results : current($results);
		}

		$response = new Response();
		$response->format = Response::FORMAT_JSON;
		$response->data = $data;

		$this->log("RESPONSE\n".json_encode($data, JSON_UNESCAPED_UNICODE));

		return $response;
    }

    protected function filterParamsLog() {
    	return [
    		'password'
    	];
    }

    protected function log($message) {
    	$filterParams = $this->filterParamsLog();
    	foreach ($filterParams as $param) {
    		$pattern = '/("'.$param.'":)"(.*)"/i';
			$message = preg_replace($pattern, '${1}"FILTERED"', $message);
    	}

    	\Yii::info($message, 'app\access');
    }

    protected static $defaultResult = [
    	'success' => true
    ];

    /**
     * 格式化响应对象
     * @param Error|mixed $result 响应结果
     * @param integer|string $id 请求ID
     */
    protected function formatResponse($result, $id = null) {
    	if ($result instanceof Error) {
    		$resultKey = 'error';
    		$resultValue = $result->toArray();
    	}
    	else {
    		if (null === $result) {
    			$result = $this->$defaultResult;
    		}
    		$resultKey = 'result';
    		$resultValue = $result;
    	}
    	return [
    		'jsonrpc' => '2.0',
    		$resultKey => $resultValue,
    		'id' => $id,
    	];
    }

    /**
     * 取得请求对象
     * @param string $id ACTION ID
     * @param string $contents
     * @return mixed[]|Error 成功返回响应对象数组，否则返回Error对象
     */
    private function getRequests($id, $contents) {
    	if (!empty($id)) {
    		return Error::invalidRequest($id);
    	}

    	if (!Yii::$app->request->getIsPost()) {
    		return Error::invalidRequest('Must sent as POST');
    	}

    	$contentType = Yii::$app->request->getContentType();
    	if (empty($contentType) || $contentType != 'application/json') {
    		return Error::invalidRequest('Content-type must be: application/json');
    	}

    	if (empty($contents)) {
    		return Error::invalidRequest('Request is empty');
    	}

    	try {
    		$request = Json::decode($contents, false);
    	} catch (InvalidParamException $e) {
    		return Error::parseError('Json decode error: '.$e->getMessage());
    	}

    	if (empty($request)) {
    		return Error::invalidRequest('Request is empty');
    	}

    	return is_array($request) ? $request : [$request];
    }

    /**
     * 验证请求对象
     * @param mixed $request 请求对象
     * @return Error|true 成功返回true，否则返回Error对象
     */
    private function validateRequest($request) {
    	if (empty($request)) {
    		return Error::invalidRequest('Request is empty');
    	}

   		if (!is_object($request)) {
   			return Error::invalidRequest('Request must be a object');
   		}

		if (!isset($request->jsonrpc)) {
			return Error::invalidRequest('jsonrpc is empty');
		}
		if ($request->jsonrpc !== '2.0') {
			return Error::invalidRequest('jsonrpc must be: 2.0');
		}
		if (empty($request->method)) {
			return Error::invalidRequest('method is empty');
		}
		if (!is_string($request->method)) {
			return Error::invalidRequest('method must be a string');
		}

		return true;
    }

    /**
     * {@inheritDoc}
     * @see \yii\web\Controller::bindActionParams()
     */
    public function bindActionParams($action, $params) {
	    if ($action instanceof InlineAction) {
			$method = new \ReflectionMethod($this, $action->actionMethod);
	    } else {
			$method = new \ReflectionMethod($action, 'run');
	    }

	    $request = $params;
	    $params = (!isset($request->params))? []:$request->params;
	    if (!is_array($params)) {
	    	$params = [$params];
	    }

	    $methodInfo = $this->parseMethodDocComment($method);
	    $paramsTypes = $this->getMethodParamsTypes($methodInfo);

	    $args = [];
	    $missing = [];
	    $actionParams = [];

	    $i = 0;
	    foreach ($method->getParameters() as $param) {
			$name = $param->getName();
			if (isset($params[$i])) {
				$type = (isset($paramsTypes[$name]))? $paramsTypes[$name] : null;
				$args[] = $actionParams[$name] = $this->mapParam($name, $params[$i], $type);
			    unset($params[$i]);
			} elseif ($param->isDefaultValueAvailable()) {
			    $args[] = $actionParams[$name] = $param->getDefaultValue();
			} else {
			    $missing[] = $name;
			}
			$i++;
	    }

	    if (!empty($missing)) {
			throw new UserException(Yii::t('yii', 'Missing required parameters: {params}', [
		    	'params' => implode(', ', $missing),
			]));
	    }

	    $this->actionParams = $actionParams;

	    return $args;
    }

    private function mapParam($name, $param, $type) {
    	if ($type) {
    		if (strpos($type, "[]") !== false) {
    			$type = 'array';
    		}

    		switch ($type) {
    			case 'integer':$param = (int)$param;break;
    			case 'float':$param = (float)$param;break;
    			case 'string':$param = (string)$param;break;
    			case 'array':
    				$param = is_array($param) ? $param : [$param];break;
    			case 'bool':
    			case 'boolean':
    				$param = (bool)$param;break;
    			default:
    				if (class_exists($type, true)) {
    					if (!(is_array($param) || is_object($param))) {
    						throw new UserException(Yii::t('yii', 'Invalid data received for parameter "{param}".', [
    							'param' => $name,
    						]));
    					}
    					$obj = new $type();
    					foreach ($param as $key=>$value) {
    						$obj->$key = $value;
    					}
    					$param = $obj;
    				}
    				break;
    		}
    	}

    	return $param;
    }

    /**
     * Returns method params with types from method phpDoc comments
     * @return array
     */
    private function getMethodParamsTypes($methodInfo) {
        return array_reduce($methodInfo['params'], function ($result, $item) {
            $result[$item['name']] = $item['type'];
            return $result;
        }, []);
    }

    /**
     * 解析注释，获取方法信息
     * @param $method \ReflectionMethod
     * @throws Exception
     */
    private function parseMethodDocComment($method) {
        $variableRegex = '\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)';

        $infoTpl = [
            'type' => '',
            'isNullable' => false,
            'restrictions' => [],
        ];

        $methodInfo = [
            'params' => [],
            'return' => []
        ];

        $lines = preg_split ('/$\R?^/m', $method->getDocComment());
        for ($i=0; $i<count($lines); $i++) {
            preg_match("/@param ([\w\\\\\[\]]+) $variableRegex/", $lines[$i], $paramMatches);
            if (!empty($paramMatches)) {
                $subject = &$methodInfo['params'][$paramMatches[2]];
                $subject = $infoTpl;
                $subject['type'] = $paramMatches[1];
                $subject['name'] = $paramMatches[2];
            } else {
                preg_match("/@return ([\w\\\\\[\]]+)/", $lines[$i], $paramMatches);
                if (!empty($paramMatches)) {
                    $subject = &$methodInfo['return'];
                    $subject = $infoTpl;
                    $subject['type'] = $paramMatches[1];
                }
            }
            if (!empty($subject)) {
                for ($j=0; $j<2; $j++) {
                    preg_match("/@(null|inArray(\[(.*)\]))/", $lines[$i+1], $tagMatches);
                    if (empty($tagMatches)) break;

                    if (strpos($tagMatches[0], "@inArray") === 0 && in_array($subject['type'], ['string', 'int'])) {
                        eval("\$parsedData = {$tagMatches[2]};");
                        if (!is_array($parsedData))
                            throw new \Exception(sprintf("Invalid syntax in %s in tag @inArray{$tagMatches[2]}", get_class($this)));
                        $subject['restrictions'] = $parsedData;
                    } elseif (strpos($tagMatches[0], "@null") === 0) {
                        $subject['isNullable'] = true;
                    }

                    $i++;
                }
            }
            unset($subject);
        }

        return $methodInfo;
    }
}