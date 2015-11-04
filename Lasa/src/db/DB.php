<?php

namespace lasa\db;

use \PDO;

class DB{
	
	static $_configure = null;
	static $_errorHandler = null;
	
	public static function configure($name, $value){
		if(is_null(self::$_configure))self::__prepare();
		self::$_configure[$name] = $value;
	}
	
	public static function getConfigure($name, $defValue = null){
		if(is_null(self::$_configure))self::__prepare();
		return (isset(self::$_configure[$name])) ? self::$_configure[$name] : $defValue;
	}
	
	public static function connection($name, $values = array()){
		if(is_array($name)){
			$values = $name;
			$name = "default";
		}
		
		self::configure("connection." . $name, $values);
		
	}
	
	public static function connections($values){
		foreach($values as $name => $conf){
			self::connection($name, $conf);
		}
	}
	
	public static function error(\Closure $func){
		self::$_errorHandler = $func;
	}
	
	private static function __prepare(){
		self::$_configure = [
			"default_connection" => "default",
			"cache_dir" => sys_get_temp_dir()
		];
	}
	
}

