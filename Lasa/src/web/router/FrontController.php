<?php
/*
 * FileRouter.php
 */
namespace lasa\web\router;
use lasa\web\router\Router;
use lasa\web\Application;

class FrontController implements Router{
	
	private $path;
	
	public function __construct($filePath){
		$this->path = $filePath;
	}
	
	
	public function load(Application $app, $url){
		
		$path = $this->path;
		$args = explode("/", $url);
			
		if(file_exists($path)){
			$func = \Closure::bind(function($path) use($app){
				include($path);
			},$app);
			
			call_user_func($func, $path);
		}
		
		return Route::found($this->path, $args);
	}
	
}