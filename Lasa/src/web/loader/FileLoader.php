<?php
namespace lasa\web\loader;

use lasa\web\Application;
/*
 * FileLoader.php
 */
class FileLoader implements Loader{
	
	private $view;
	private $validator;
	private $module;
	
	private $viewEngine;
	private $validatorLoader;
	
	public function __construct($root, $cache = null){
		if(is_array($root)){
			if(!isset($root["view"]) || !isset($root["validator"]) || !isset($root["validator"])){
				throw new \Exception("[FileLoader]invalid options");
			}
			
			$this->view = $root["view"];
			$this->validator = $root["validator"];
			$this->module = $root["module"];
			
		}else{
			$this->view = $root . "/view";
			$this->validator = $root . "/validator";
			$this->module = $root . "/module";
		}
	}
	
	public function prepare(Application $app, array $opt){
		
		if($this->view instanceof \lasa\view\loader\LoaderInterface){
			$this->viewEngine = new \lasa\view\Engine($this->view,[
				"cache" => (isset($opt["cache"])) ? $opt["cache"] : null,
				"debug" => (isset($opt["debug"])) ? $opt["debug"] : false,
			]);
		}else{
			$this->viewEngine = new \lasa\view\Engine(new \lasa\view\loader\FileLoader($this->view),[
				"cache" => (isset($opt["cache"])) ? $opt["cache"] : null,
				"debug" => (isset($opt["debug"])) ? $opt["debug"] : false,
			]);
		}
		
		if(is_array($this->validator) && count($this->validator) > 0){
			$this->validatorLoader = new \lasa\validator\ValidatorLoader($this->validator[0], $this->validator[1]);
		}else{
			$this->validatorLoader = new \lasa\validator\ValidatorLoader($this->validator);
		}
		
	}
	
	
	/**
	 * view
	 * @see \lasa\web\loader\Loader::view()
	 */
	public function view($name, $values = []) {
		return $this->viewEngine->load($name, $values);
	}

	/**
	 * validator
	 * @see \lasa\web\loader\Loader::validator()
	 */
	public function validator($name, $values = []) {
		return $this->validatorLoader->check($name, $values);
	}

	/**
	 * module
	 * @see \lasa\web\loader\Loader::module()
	 */
	public function module($name) {
		// TODO: Auto-generated method stub

	}

}