<?php
/*
 * FileRouter.php
 */
namespace lasa\web\router;
use lasa\web\router\Router;
use lasa\web\Application;

class FileRouter implements Router{
	
	private $path;
	private $default = "default";
	private $_missing = null;
	
	public function __construct($filePath, $default = null){
		$this->path = $filePath;
		if($default)$this->default = $default;
	}
	
	public function missing(\Closure $func){
		$this->_missing = $func;
	}
	
	public function load(Application $app, $url){
		if(strlen($url) < 1 || $url == "/")$url = $this->default;
		if($url[0] == "/")$url = substr($url, 1);
		if($url[strlen($url)-1] == "/")$url = substr($url, 0, -1);
		$controller_name = $url;
		$route = null;
		$args = [];
		
		while($controller_name){
			$path = $this->path . DIRECTORY_SEPARATOR . $controller_name . ".php";
			
			if(file_exists($path)){
				$route = $controller_name;
				$func = \Closure::bind(function($path) use($app){
					include($path);
				},$app);
				
				call_user_func($func, $path);
				break;
			}
			
			array_unshift($args, basename($controller_name));
			$controller_name = dirname($controller_name);
			if($controller_name == ".")break;
		}
		
		if(!$route && $this->_missing){
			$func = $this->_missing;
			if(is_callable($func)) {
				return $func($app, $url);
			}
		}
		
		if($route){
			return Route::found($route, $args);
		}
		
		return Route::missing();
	}
	
}