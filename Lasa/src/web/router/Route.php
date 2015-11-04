<?php
/*
 * Rout.php
 */
namespace lasa\web\router;

class Route{
	
	private $_url = [];
	private $_args = [];
	
	public static function found($url = "", $args = []){
		return new Route($url, $args);
	}
	
	public static function missing(){
		return new MissingRoute("", []);
	}
	
	private function __construct($url, $args){
		$this->_url = $url;
		$this->_args = $args;
	}
	
	function getUrl(){
		return $this->_url;
	}
	
	function getArgments(){
		return $this->_args;
	}
	
	function success(){
		return true;
	}
	
}

class MissingRoute extends Route{
	
	function success(){
		return false;
	}
	
}