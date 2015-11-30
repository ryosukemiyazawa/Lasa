<?php
namespace lasa;

use Closure;
use ReflectionClass;
use Exception;

class Container{
	
	static $_instance;
	
	public static function getInstance(){
		if(is_null(self::$_instance)){
			self::$_instance = new Container();
		}
		return self::$_instance;
	}
	
	public static function setInstance(Container $instance){
		self::$_instance = $instance;
	}
	
	private $_bindings = [];
	private $_instances = [];
	private $_values = [];
	
	/**
	 * @param unknown $name
	 * @param \Closure|string|null $target
	 */
	public function bind($name, $target){
		$this->_bindings[$name] = $target;
	}
	
	public function share($name, $func = null){
		
		if(!$func){
			$func = function() use($name){
				return new $name;
			};
		}
		
		if(false == $func instanceof \Closure){
			return $this->instance($name, $func);
		}
		
		
		$this->_bindings[$name] = function($container, $params = []) use ($name, $func){
			$obj = $func($container, $params);
			$container->_instances[$name] = $obj;
			return $obj;
		};
	}
	
	public function alias($aliasName, $name){
		$container = $this;
		$this->_bindings[$aliasName] = function($aliasName, $params) use($name, $container){
			return $container->make($name, $params);
		};
	}
	
	public function instance($name, $instance){
		$this->_bindings[$name] = function() use($instance){
			return $instance;
		};
	}
	
	public function is($name){
		return isset($this->_bindings[$name]);
	}
	public function put($name, $value){
		$this->_values[$name] = $value;
	}
	public function get($name,$defValue = null){
		if(!isset($this->_values[$name])){
			return $defValue;
		}
		return $this->_values[$name];
	}
	/**
	 *
	 * @param unknown $name
	 */
	public function make($name, $_ = null){
		
		$params = [];
		if(func_num_args() > 1){
			for($i=1;$i<func_num_args(); $i++){
				$arg = func_get_arg($i);
				if(is_array($arg)){
					$params = array_merge($params, $arg);
				}else if(is_object($arg)){
					$params[get_class($arg)] = $arg;
				}else{
					$params[] = $arg;
				}
			}
		}
		
		if(isset($this->_instances[$name])){
			return $this->_instances[$name];
		}
		
		if(isset($this->_bindings[$name])){
			$closure = $this->_bindings[$name];
			if ($closure instanceof Closure){
				return $closure($this, $params);
			}
		}
		
		if(class_exists($name)){
			$ref = new ReflectionClass($name);
			
			if(!$ref->isInstantiable()){
				$message = "[$name] is not instantiable.";
				throw new Exception($message);
			}
			
			$constructor = $ref->getConstructor();
			if(is_null($constructor)){
				$instance = new $name;
			}else{
				$instanceParams = array();
				foreach($constructor->getParameters() as $param){ /* @var $param \ReflectionParameter */
					$paramClass = $param->getClass();
					
					if(isset($params[$param->getName()])){
						$instanceParams[] = $params[$param->getName()];
					}else if($paramClass && isset($params[$paramClass->getName()])){
						$instanceParams[] = $params[$paramClass->getName()];
					}else if($paramClass){
						$instanceParams[] = $this->make($paramClass->getName());
					}else if($param->isDefaultValueAvailable()){
						$instanceParams[] = $param->getDefaultValue();
					}else if($param->isOptional()){
						break;
					}
				}
				
				$instance = $ref->newInstanceArgs($instanceParams);
			}
			
			foreach($params as $key => $value){
				$setter = "set" . ucwords($key);
				if(method_exists($instance, $setter)){
					$instance->$setter($value);
				}
			}
			
			return $instance;
		}
		
		//unknown class
		$message = "[$name] is not exist.";
		throw new Exception($message);
		
	}
	
	/**
	 * キャッシュを削除する
	 */
	public function destory(){
		$this->_instances = [];
	}
	
}
