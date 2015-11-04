<?php
/*
 * Router.php
 */
namespace lasa\web\router;

use lasa\web\Application;
interface Router{
	
	/**
	 * @param Application $app
	 * @param string $url
	 * @return Route
	 */
	public function load(Application $app, $url);
	
}