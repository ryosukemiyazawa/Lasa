<?php

namespace lasa\auth;

class User{
	
	private $id;
	private $key;
	private $params = array();
	private $roles = array();
	
	function __call($method, $args){
		
		if(array_key_exists($method, $this->params)){
			if(count($args) < 1){
				return $this->getParam($method);
			}else{
				return $this->setParam($method, $args[0]);
			}
		}
		
		if(strpos($method, "is") === 0){
			$role = strtolower(substr($method, 2));
			return $this->hasRole($role);
		}
		
		throw new \Exception("Call to undefined method ". __CLASS__ . ":" . $method . "()");
		
	}
	
	
	/**
	 * ログイン済
	 */
	public function authenticate(){
		throw new \Exception("already logged-in");
	}
	
	public function check(){
		return true;
	}
	
	public function logout(){
		if(isset($this->_key)){
			Auth::clearSession($this->_key);
		}
	}
	
	public function hasRole($roleName){
		return in_array($roleName, $this->roles);
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function sessionKey($key){
		$this->_key = $key;
	}
	
	public function setParam($key, $value){
		$this->params[$key] = $value;
	}
	
	public function setParams(array $arr){
		$this->params = $arr;
	}
	
	public function setRoles(array $roles){
		$this->roles = $roles;
	}
	
	public function getParam($name, $defValue = null){
		return (isset($this->params[$name])) ? $this->params[$name] : $defValue;
	}
	
	public function addRole($role){
		$this->roles[] = $role;
	}
	public function getId(){
		return $this->id;
	}
	public function getParams(){
		return $this->params;
	}
	public function getRoles(){
		return $this->roles;
	}
	
}