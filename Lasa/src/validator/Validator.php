<?php
/*
 *	エラーメッセージのフォーマット
 *	:messsage
 *	:key
 *	:name
 */
namespace lasa\validator;

use Exception;
use Closure;

interface IValidator{
	
	/**
	 * @return IValidator
	 */
	function required();
	
	/**
	 * @return IValidator
	 */
	function ifExists();
	
	/**
	 * @return IValidator
	 */
	function equal();
	
	/**
	 * @return IValidator
	 */
	function matches();
	
	/**
	 * @return IValidator
	 */
	function regex();
	
	/**
	 * @return IValidator
	 */
	function isArray();
	
	/**
	 * @return IValidator
	 */
	function each();
	
	/**
	 * @return IValidator
	 */
	function isNumber();
	
	/**
	 * @return IValidator
	 */
	function isString();
	
	/**
	 * @return IValidator
	 */
	function min();
	
	/**
	 * @return IValidator
	 */
	function max();
	
	/**
	 * @return IValidator
	 */
	function check();
}

class BaseValidator implements IValidator{
	
	function required(){ return $this; }
	function ifExists(){ return $this; }
	function filter(){ return $this; }
	
	function equal(){ return $this; }
	function matches(){ return $this; }
	function regex(){ return $this; }
	function isArray(){ return $this; }
	function each(){ return $this; }
	
	function isNumber(){ return $this; }
	function isString(){ return $this; }
	function min(){ return $this; }
	function max(){ return $this; }
	function check(){ return $this; }
	
	function __call($method, $args){
		
	}
}

class Validator extends BaseValidator{
	
	private $data;
	private $dataIndex = [];
	private $_errors = array();
	
	private $_labels = [];
	private $_cleanupedData = [];
	private $_messages = [
		"required" => ":label is required.",
		"regex" => ":label is not match",
		"equal" => ":label is not match",
		"is_array" => ":label is not array",
		"is_number" => ":label is not number",
		"is_string" => ":label is not string",
		"min" => [
			"number" => ":label is smaller",
			"string" => ":label is shorter",
			"array" => "the number of :label is smaller"
		],
		"max" => [
			"number" => ":label is larger",
			"string" => ":label is longer",
			"array" => "the number of :label is larger"
		]
	];
	private $_errorItems = [];
	
	/**
	 * @param [] $data
	 * @param [string] $messages = null
	 */
	public function __construct($data = [], $messages = null){
		$this->data = $data;
		
		if($messages){
			$this->_messages = $messages;
		}
	}
	
	/**
	 * cleanupした結果からObjectを作成する
	 */
	public function apply($obj, $closure = null){
		if(!is_object($obj)){
			if(!class_exists($obj)){
				throw new \Exception("[" . __CLASS__ . "] unknown class ${obj}");
			}
			$obj = new $obj;
		}
		
		foreach($this->cleanup() as $key => $value){
			$setter = "set" . ucfirst(lcfirst(strtr(ucwords(strtr($key, ['_' => ' '])), [' ' => ''])));
			if(method_exists($obj, $setter)){
				$obj->$setter($value);
			}else if($closure){
				$closure($obj, $key, $value);
			}
		}
		
		return $obj;
	}
	
	/**
	 * @return boolean
	 */
	public function success(){
		if(count($this->_errors) == 0){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * @return boolean
	 */
	public function fails(){
		return !$this->success();
	}
	
	public function errors(){
		return $this->_errors;
	}
	
	public function filter(){
		
		list($key, $func) = func_get_args();
		
		if(!isset($this->data[$key])){
			return new ChainedValidator($key, $this);
		}
		
		
		$val = $this->data[$key];
		
		if($func instanceof \Closure){
			$val = $func($val);
		}else if(function_exists($func)){
			$val = call_user_func($func, $val);
		}
		$this->data[$key] = $val;
		$this->_cleanuped($key);
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * メッセージを１件取得
	 * @param name $key
	 * @return string
	 */
	public function message($key, $format = null){
		
		foreach($this->_errors as $errorKey => $message){
			if(strpos($errorKey, $key . ".") !== false){
				return $this->formatMessage($message, $format);
			}
		}
		
		return "";
	}
	
	/**
	 * メッセージを複数件取得
	 * @param string $key
	 * @param string $format
	 * @return [string]
	 */
	public function messages($key, $format = null){
		$res = [];
		foreach($this->_errors as $errorKey => $message){
			if(strpos($errorKey, $key . ".") !== false){
				$res[] = $this->formatMessage($message, $format);
			}
		}
		return $res;
	}
	
	/**
	 * 全てのメッセージを取得
	 * @param string $format
	 * @return [string]
	 */
	public function allMessages($format = null){
		$res = [];
		foreach($this->_errors as $key => $message){
			$res[] = $this->formatMessage($message, $format);
		}
		return $res;
	}
	
	public function isError($key){
		return (isset($this->_errorItems[$key])) ? true : false;
	}
	
	public function value($key){
		return (isset($this->data[$key])) ? $this->data[$key] : null;
	}
	
	public function valueType($key){
		if(!isset($this->data[$key]) || is_null($this->data[$key])){
			return "null";
		}
		if(is_string($this->data[$key])){
			return "string";
		}
		if(is_numeric($this->data[$key])){
			return "number";
		}
		if(is_array($this->data[$key])){
			return "array";
		}
		
		return "unknown";
	}
	
	public function index(){
		$this->dataIndex = func_get_args();
	}
	
	public function valueAtIndex($index){
		if(!isset($this->dataIndex[$index])){
			trigger_error("[Validator]Unknown index:" . $index, E_USER_NOTICE);
		}
		$name = $this->dataIndex[$index];
		return $this->value($name);
	}
	
	public function cleanup(){
		return $this->_cleanupedData;
	}
	
	/**
	 * バリデーション実行
	 * @param unknown $key
	 * @param unknown $validatorType
	 * @param unknown $options
	 * @throws \Exception
	 * @return boolean
	 */
	public function execute($key, $validatorType, $options){
		
		$value = $this->value($key);
		$valueType = $this->valueType($key);
		$valueSize = $this->_getSize($value, $valueType);
		$validatorTypeKey = $validatorType;
		$messageRequireType = false;
		
		$this->_cleanuped($key);
		
		if($validatorType == "required"){
			if(isset($this->data[$key]) && !is_null($this->data[$key])){
				return true;
			}
		}
		
		if($validatorType == "equal"){
			$check_value = array_shift($options);
			if(isset($this->data[$key]) && $this->data[$key] == $check_value){
				return true;
			}
		}
		
		if($validatorType == "regex"){
			$regex = array_shift($options);
			if(preg_match($regex, $value)){
				return true;
			}
			
		}
		
		if($validatorType == "matches"){
			$target = array_shift($options);
			$targetValue = (isset($this->data[$target])) ? $this->data[$target] : null;
			
			if(strcmp($targetValue, $value) === 0){
				return true;
			}
		}
		
		
		if($validatorType == "is_array"){
			if(is_array($value)){
				if(count($options) && $options[0] instanceof Closure){
					$optionalFunc = array_shift($options);
					$checkResult = true;
					foreach($value as $_value){
						if(is_callable($optionalFunc) && $optionalFunc($_value) === false){
							$checkResult = false;
							break;
						}
					}
					if($checkResult){
						return true;
					}
				}else{
					return true;
				}
			}
		}
		if($validatorType == "is_string"){
			if(is_string($value)){
				return true;
			}
		}
		
		if($validatorType == "is_number"){
			if(is_numeric($value)){
				return true;
			}
		}
		
		if($validatorType == "max"){
			$messageRequireType = true;
			$maxSize = array_shift($options);
			if($maxSize >= $valueSize){
				return true;
			}
		}
		if($validatorType == "min"){
			$messageRequireType = true;
			$minSize = array_shift($options);
			if($minSize <= $valueSize){
				return true;
			}
		}
		if($validatorType == "function"){
			$func = array_shift($options);
			if(is_string($func)){
				$validatorTypeKey = $func;
				$func = array_shift($options);
			}
			if($func instanceof  \Closure){
				$res = $func($value, $options);
				if($res === true){
					return true;
				}
				if($res === false){
					//error
				}else{
					$this->data[$key] = $res;
					return true;
				}
			}else{
				throw new \Exception("[".__CLASS__."]invalid function assigned.");
			}
		}
		
		//メッセージは絶対に最後で指定する
		$message = (isset($this->_messages[$validatorTypeKey])) ? $this->_messages[$validatorTypeKey] : "error in :label";
		if(count($options) > 0 && is_string($options[0])){
			$message =  $options[0];
		}
		if($messageRequireType && is_array($message)){
			$message = (isset($message[$valueType])) ? $message[$valueType] : "error in :label";
		}
		$message = $this->formatMessage([
			"key" => $key,
			"validation" => $validatorTypeKey,
			"message" => $message
		],$message);
		$this->setError($key, $message, $validatorTypeKey);
		
		return false;
	}
	
	public function setError($key, $message, $type = null){
		$_key = ($type) ? $key . "." . $type : $key;
		$this->_errors[$_key] = [
			"key" => $key,
			"validation" => $type,
			"message" => $message
		];
		$this->_errorItems[$key] = true;
	}
	
	/**
	 * 項目名を指定する
	 * @param string $key
	 * @param string $label
	 * @return IValidator
	 */
	public function item($key, $label = null){
		if(!$label)$label = $key;
		$this->_labels[$key] = $label;
		if(isset($this->data[$key])){
			$this->_cleanupedData[$key] = $this->data[$key];
		}
		return new ChainedValidator($key, $this);
	}

	/**
	 * @return IValidator
	 */
	public function required(){
		$args = func_get_args();
		$key = array_shift($args);
		$res = $this->execute($key, "required", $args);
		if(!$res)return new BaseValidator();
		
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * @return IValidator
	 */
	public function ifExists(){
		$args = func_get_args();
		$key = array_shift($args);
		if(!isset($this->data[$key])){
			return new BaseValidator();
		}
		if(empty($this->data[$key])){
			return new BaseValidator();
		}
		return new ChainedValidator($key, $this);
	}
	
	
	
	/**
	 * @return IValidator
	 */
	public function equal(){
		$args = func_get_args();
		$key = array_shift($args);
		$res = $this->execute($key, "equal", $args);
		if(!$res)return new BaseValidator();
		
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * @return IValidator
	 */
	public function matches(){
		$args = func_get_args();
		$key = array_shift($args);
		$res = $this->execute($key, "matches", $args);
		if(!$res)return new BaseValidator();
		
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * @return IValidator
	 */
	public function regex(){
		$args = func_get_args();
		$key = array_shift($args);
		$res = $this->execute($key, "regex", $args);
		if(!$res)return new BaseValidator();
		
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * @return IValidator
	 */
	public function isArray(){
		$args = func_get_args();
		$key = array_shift($args);
		$res = $this->execute($key, "is_array", $args);
		if(!$res)return new BaseValidator();
		
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * @return IValidator
	 */
	public function isJson(){
		$args = func_get_args();
		$key = array_shift($args);
		$this->execute($key, "is_json", $args);
		
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * @return IValidator
	 */
	function isNumber(){
		$args = func_get_args();
		$key = array_shift($args);
		$this->execute($key, "is_number", $args);
		
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * @return IValidator
	 */
	function isString(){
		$args = func_get_args();
		$key = array_shift($args);
		$this->execute($key, "is_string", $args);
		
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * @return IValidator
	 */
	function min(){
		$args = func_get_args();
		$key = array_shift($args);
		$this->execute($key, "min", $args);
		
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * @return IValidator
	 */
	function max(){
		$args = func_get_args();
		$key = array_shift($args);
		$this->execute($key, "max", $args);
		
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * クロージャーでカスタマイズする
	 * @see \lasa\validator\BaseValidator::check()
	 * @return IValidator
	 */
	function check(){
		$args = func_get_args();
		$key = array_shift($args);
		$this->execute($key, "function", $args);
		return new ChainedValidator($key, $this);
	}
	
	/**
	 * @return IValidator
	 */
	public function each(){
		list($key, $func) = func_get_args();
		
		$data = (isset($this->data[$key])) ? $this->data[$key] : null;
		if(!is_array($data)){
			return new BaseValidator();
		}
		$childValidaotr = new Validator($data);
		$func($childValidaotr);
		$cleanupedData = $childValidaotr->cleanup();
		
		$errors = $childValidaotr->errors();
		foreach($errors as $c_key => $error){
			$this->_errors[$key . "." . $c_key] = $error;
		}
		if($errors){
			$this->_errorItems[$key] = true;
		}
		$this->_cleanupedData[$key] = $cleanupedData;
		
		return new ChainedValidator($key, $this);
	}
	
	
	/* internal */
	
	/**
	 * メッセージの書式を変更する
	 * @param string $key
	 * @param string $value
	 * @return Validator
	 */
	public function setMessageFormat($key, $value){
		if(strpos($key, ".") !== false){
			list($key, $type) = explode(".", $key);
			if(isset($this->_messages[$key]) && is_array($this->_messages[$key])){
				$this->_messages[$key][$type] = $value;
			}
			return $this;
		}
		$this->_messages[$key] = $value;
		return $this;
	}
	
	/**
	 * メッセージのフォーマットを設定する
	 * @param array $message
	 * @param string $format
	 * @return string
	 */
	protected function formatMessage($message, $format = null){
		if($format){
			$valueKey = $message["key"];
			$value = $this->value($valueKey);
			if(!is_string($value))$value = "";
			return str_replace(
				[":message", ":key", ":type",":value",":label"],
				[
					$message["message"],
					$message["key"],
					$message["validation"],
					$value,
					(isset($this->_labels[$valueKey])) ? $this->_labels[$valueKey] . "" : $valueKey
				],
				$format
			);
		}
		
		return $message["message"];
	}
	
	/**
	 * お掃除
	 * @param unknown $key
	 */
	private function _cleanuped($key){
		$this->_cleanupedData[$key] = $this->value($key);
	}
	
	private function _getSize($value, $type){
		if($type == "null"){
			return 0;
		}
		if($type == "string"){
			return strlen($value);
		}
		if($type == "number"){
			return $value;
		}
		if($type == "array"){
			return count($value);
		}
		
		return 1;
	}
}

class ChainedValidator extends BaseValidator{
	
	private $key;
	private $validator;
	
	public function __construct($key, Validator $validator){
		$this->key = $key;
		$this->validator = $validator;
	}
	
	function __call($method, $args){
		
		if(method_exists($this->validator, $method)){
			$v = $this->validator;
			$func = function($input) use($v, $method){
				return call_user_func_array([$v, $method], [$input]);
			};
			array_unshift($args, $func);
			return $this->_execute("function", $args);
		}
		
		trigger_error("Call to undefined method");
	}
	
	function _execute($type, $args){
		$res = $this->validator->execute($this->key, $type, $args);
		if($res){
			return $this;
		}
		
		return new BaseValidator();
	}
	
	function filter(){
		$func = func_get_arg(0);
		$this->validator->filter($this->key, $func);
		return $this;
	}
	
	function required(){
		return $this->_execute(__FUNCTION__, func_get_args());
	}
	
	function equal(){
		return $this->_execute(__FUNCTION__, func_get_args());
	}
	
	function matches(){
		return $this->_execute(__FUNCTION__, func_get_args());
	}
	function regex(){
		return $this->_execute(__FUNCTION__, func_get_args());
	}
	function max(){
		return $this->_execute(__FUNCTION__, func_get_args());
	}
	function min(){
		return $this->_execute(__FUNCTION__, func_get_args());
	}
	function isArray(){
		return $this->_execute("is_array", func_get_args());
	}
	function isJson(){
		return $this->_execute("is_json", func_get_args());
	}
	function isNumber(){
		return $this->_execute("is_number", func_get_args());
	}
	function check(){
		$args = func_get_args();
		return $this->_execute("function", $args);
	}
	function each(){
		$closure = func_get_arg(0);
		$this->validator->each($this->key, $closure);
		return $this;
	}
	
}
