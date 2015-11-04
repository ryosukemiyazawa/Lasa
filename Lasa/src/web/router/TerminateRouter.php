<?php
/*
 * TerminateRouter.php
 */
namespace lasa\web\router;

use lasa\web\Application;
class TerminateRouter implements Router{
	
	/**
	 * @var Router
	 */
	private $router;
	
	/**
	 * @param Router $router
	 */
	function __construct(Router $router, \Closure $onerror = null){
		$this->router = $router;
	}
	
	
	/**
	 * load
	 * @see \lasa\web\router\Router::load()
	 */
	public function load(Application $app, $url) {
		$res = $this->router->load($app, $url);
		
		if($res){
			return $res;
		}
		return Route::success();
	}
}