<?php
/*
 * Session.php
 */
namespace lasa\web;

class Session{
	
	private $_key = "";
	private $_values = [];
	
	private static $_session;
	
	public function __construct($key = "@app"){
		$this->_key = $key;
		
		if(!isset($_SESSION) && !headers_sent()){
			session_start();
		}
		
		if(is_null(self::$_session)){
			self::$_session = (isset($_SESSION)) ? $_SESSION : [];
		}
		
		$key = $this->_getSessionKey();
		if(isset(self::$_session[$key])){
			$this->_values = self::$_session[$key];
		}
	}
	
	function __destruct(){
		$key = $this->_getSessionKey();
		self::$_session[$key] = $this->_values;
		if(isset($_SESSION))$_SESSION[$key] = self::$_session[$key];
	}
	
	function start(){
		$this->_values = [];
		return $this;
	}
	
	function flush(){
		$res = $this->_values;
		$key = $this->_getSessionKey();
		$this->_values = [];
		unset(self::$_session[$key]);
		return $res;
	}
	
	/**
	 * set session value
	 * @param unknown $key
	 * @param unknown $value
	 */
	public function put($key, $_ = null){
		
		if(func_num_args() == 1){
			if(is_array($key)){
				$this->_values = $key;
			}else if(is_object($key)){
				$this->_values = $key;
			}
			return $this;
		}
		
		$this->_values[$key] = $_;
		return $this;
	}
	
	/**
	 * get session value
	 * @param unknown $key
	 * @param unknown $defValue
	 */
	public function get($key = null, $defValue = null){
		if(!$key)return $this->_values;
		if(!isset($this->_values[$key]))return $defValue;
		return $this->_values[$key];
	}
	
	private function _getSessionKey(){
		return "lasa\\web\\session\\" . $this->_key;
	}
	
	
}