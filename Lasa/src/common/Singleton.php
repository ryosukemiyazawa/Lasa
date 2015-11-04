<?php
/*
 * Singleton.php
 */
trait Singleton{
	
	public static function getInstance(){
		static $_inst;
		
		$className = __CLASS__;
		
		if(is_null($_inst)){
			$_inst = new $className();
		}

		return $_inst;
	}
	
}