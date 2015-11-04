<?php
namespace lasa\view;

include(__DIR__ . "/helpers.php");

use lasa\view\loader\LoaderInterface;

class Engine{
	
	static $_instance;
	
	private $loader;
	private $_debug = false;
	private $_cacheDir = null;
	private $_cacheDirPrefix = "view/";
	
	public function __construct(LoaderInterface $loader, $options = []){
		$this->loader = $loader;
		
		if(isset($options["debug"]))$this->_debug = $options["debug"];
		if(isset($options["cache"]))$this->_cacheDir = $options["cache"];
		if(isset($options["cache_prefix"]))$this->_cacheDirPrefix = $options["cache_prefix"];
		
		$this->prepare();
	}
	
	private function prepare(){
		
		self::$_instance = $this;
		
		$this->_cacheDir = ($this->_cacheDir) ? realpath($this->_cacheDir) : null;
		if(!$this->_cacheDir)$this->_cacheDir = sys_get_temp_dir();
		
	}
	
	public static function currentEngine(){
		return self::$_instance;
	}
	
	/**
	 *
	 * @return LoaderInterface
	 */
	public function getLoader(){
		return $this->loader;
	}
	
	/**
	 * @return View
	 * @param string $name
	 * @param array $values
	 */
	public function load($name, $values = []){
		
		$cName = str_replace(DIRECTORY_SEPARATOR, "\\", $this->loader->getCacheName($name));
		$cPath = $this->_cacheDir . DIRECTORY_SEPARATOR . $this->_cacheDirPrefix . $cName;
		
		if(false == $this->_debug && file_exists($cPath) && false === $this->loader->isChanged($name, filemtime($cPath))){
			return $this->_createView($name, $cPath, $values);
		}
		
		$builder = $this->loader->getBuilder($name);
		
		if($builder){
			$result = $builder->compile($this);
			if(!\file_exists(dirname($cPath)))mkdir(\dirname($cPath));
			file_put_contents($cPath, $result);
				
			return $this->_createView($name, $cPath, $values);
		}
		
		
		if($this->_debug){
			throw new \Exception("unknown view:" . $name);
		}
		
		return $this->_createView($name, null, $values);
	}
	
	/**
	 * @return Render
	 * @param string $name
	 * @param array $values
	 */
	public function create($name){
		
		$cName = str_replace(DIRECTORY_SEPARATOR, "\\", $this->loader->getCacheName($name));
		$cPath = $this->_cacheDir . DIRECTORY_SEPARATOR . $cName;
		
		if(false == $this->_debug && file_exists($cPath) && false === $this->loader->isChanged($name, filemtime($cPath))){
			return $this->_createView($name, $cPath, []);
		}
		
		$builder = $this->loader->getBuilder($name);
		
		if($builder){
			$result = $builder->compile($this);
			file_put_contents($cPath, $result);
		
			return $this->_createView($name, $cPath, []);
		}
		
		
		if($this->_debug){
			throw new \Exception("unknown view:" . $name);
		}
		
		return $this->_createView($name, null, []);
		
	}
	
	/* internal */
	
	private function _createView($name, $cPath, $values){
		$view = new View($name, $cPath, $values);
		return $view;
	}
}