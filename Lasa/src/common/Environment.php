<?php
/*
 * Enviroment.php
 */
namespace lasa\common;

class Environment{
	
	private $_path;
	private $_env = "develop";
	private $_values = [];
	
	public function __construct($env, $baseDir){
		$this->_path = $baseDir;
		$this->setEnv($env);
	}
	
	function setEnv($env){
		$this->_env = $env;
	}
	
	function import($name){
		$path = $this->_path . "/" . $this->_env . "/" . $name . ".php";
		
		if(!file_exists($path)){
			throw new \Exception("[" . __CLASS__ . "]invalid env: " . $name);
		}
		
		$res = include($path);
		$this->_values[$name] = $res;
		
		return $res;
	}
	
	function get($key, $defValue = null){
		$keys = explode(".", $key);
		$res = null;
		$target = $this->_values;
		foreach($keys as $key){
			if(isset($target[$key])){
				$target = $target[$key];
				continue;
			}else{
				return $defValue;
			}
		}
		
		return $target;
	}
}