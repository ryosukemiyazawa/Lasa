<?php
namespace lasa\web\loader;

use lasa\web\Application;
/**
 * ApplicationLoader
 *
 */
interface Loader{
	
	public function prepare(Application $app, array $opt);
	
	public function view($name, $values = []);
		
	public function validator($name, $values = []);
	
	public function module($name);
	
}
