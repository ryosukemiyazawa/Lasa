<?php

namespace lasa\auth;

class Auth{
	
	private $_name;
	private $_key;
	private $_attempt;
	private $_factory;
	private $_roles = array();
	private $_params = array();
	
	/**
	 * @param string $name
	 * @param string $path
	 * @return \lasa\auth\Auth
	 */
	public static function load($name, \Closure $closure = null){
		
		if(!isset($_SESSION))session_start();
		
		$key = "lasa_auth_" . $name;
		
		$auth = new Auth($name);
		$auth->_key = $key;
		
		if($closure){
			if($closure instanceof \Closure){
				$closure($auth);
			}
		}
		
		if(isset($_SESSION[$key])){
			//認証済み
			return $auth->_userWithSession($_SESSION[$key]);
		}
		
		
		return $auth;
		
	}
	
	public static function clearSession($sessionName){
		$_SESSION[$sessionName] = null;
		unset($_SESSION[$sessionName]);
	}
	
	/**
	 * 設定ファイルを読み込む
	 * @param string $configPath
	 */
	public function loadConfigure($configPath){
		$closure = include($configPath);
		
		if(!$closure || !($closure instanceof \Closure)){
			throw new \Exception("[Auth-".$this->_name."]invalid configure file:" . $configPath);
		}
		
		$closure($this);
	}
	
	private function __construct($name){
		$this->name = $name;
	}
	
	function __call($method, $args){
		if($this->_user){
			return call_user_func_array([$this->_user, $method], $args);
		}
		
		throw new \Exception("Call to undefined method ". __CLASS__ . ":" . $method . "()");
	}
	
	/**
	 * 【設定】ログイン処理を登録
	 */
	public function attempt(\Closure $closure){
		$this->_attempt = $closure;
	}
	
	/**
	 * 【設定】認証済状態のクロージャーを指定する
	 * @param \Closure $closure
	 */
	public function factory(\Closure $closure){
		$this->_factory = $closure;
	}
	
	/**
	 * 【設定】パラメーター
	 * @param $name パラメーター名
	 * @param $require 必須項目かどうか
	 */
	public function param($name, $require = true){
		$this->_params[$name] = $require;
	}
	
	/**
	 * 【設定】ロールを設定する
	 */
	public function roles(array $roles){
		$this->_roles = $roles;
	}
	
	/**
	 * 【設定】イベントを行う
	 * @param $type
	 * @param $closure
	 */
	public function hook($type, $closure){
		if(isset($this->_hook[$type]))$this->_hook[$type] = array();
		$this->_hook[$type][] = $closure;
	}
	
	/**
	 * 認証済か判定
	 */
	public function check(){
		if(!$this->_user)return false;
		return $this->_user->check();
	}
	
	private function _notify($event){
		
		if(!isset($this->_hook[$event]))return;
		
		foreach($this->_hook[$event] as $closure){
			$closure($this);
		}
		
	}
	
	/**
	 * ログイン後の状態が正しいかを判定する
	 * @param User $user
	 */
	private function _checkValidAuth(User $user){
		
		if(!$user->getId()){
			throw new \Exception("[Auth-".$this->_name."]id is require");
		}
		
		$params = $user->getParams();
		foreach($this->_params as $key => $require){
			if($require){
				
				if(!isset($params[$key]) || is_null($params[$key])){
					throw new \Exception("[Auth-".$this->_name."]".$key." is require");
				}
				
			}
		}
		
		
	}
	
	private function _user(){

		if($this->_user){
			return $this->_user;
		}
		
		if($this->_factory){
			$user = $this->_factory->__invoke($this);
			if(!$user instanceof User){
				throw new \Exception("[Auth]factory must return " . __NAMESPACE__ . "\User.");
			}
		}else{
			$user = new User();
		}
		
		$params = array();
		foreach($this->_params as $key => $value){
			$params[$key] = null;
		}
		$user->setParams($params);
		$user->sessionKey($this->_key);
		
		return $user;
	}
	
	private function _userWithSession($data){
		$user = $this->_user();
		$user->setId($data["id"]);
		$user->setParams($data["params"]);
		$user->setRoles($data["roles"]);
		
		return $user;
	}
	
	/* 以下 attempt内で使用する */
	
	private $_user;
	
	/**
	 * ユーザーIDとパスワードで認証する
	 * @param string $userId
	 * @param string $password
	 */
	public function authenticate($userId, $password){
		
		$this->_user = $this->_user();
		$result = false;
		
		//ログイン処理実行
		if($this->_attempt){
			$result = $this->_attempt->__invoke($userId, $password);
		}
		
		//パラメーターの確認などを行う
		$this->_checkValidAuth($this->_user);
		
		if($result){
			$this->login($this->_user);
		}else{
			$this->_notify("login_failed");
		}
		
		return $result;
		
	}
	
	/**
	 * ユーザーを指定してログイン処理を行う
	 * @param User $user
	 */
	public function login(User $user){
		
		$_SESSION[$this->_key] = array(
			"id" => $user->getId(),
			"params" => $user->getParams(),
			"roles" => $user->getRoles()
		);
		
		$this->_notify("login");
	}
	
	public function logout(){
		
	}
	
	public function setId($id){
		$this->_user->setId($id);
	}
	
	public function setParam($key, $value){
		$this->_user->setParam($key, $value);
	}
	
	public function addRole($role){
		$this->_user->addRole($role);
	}
	
}