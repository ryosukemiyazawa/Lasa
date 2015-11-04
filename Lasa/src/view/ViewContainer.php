<?php
namespace lasa\view;

use lasa\view\component\HTMLView;
use lasa\view\component\Component;

class Dummy extends HTMLView implements Component{

	private $currentId = null;
	public $values = [];

	private function onView($id, $text, $opt){
		$this->currentId = $id;
		if(is_array($text)){
			$opt = $text;
			$text = null;
			if(is_array($opt) && isset($opt[0])){
				$text = array_shift($opt);
			}
		}
		if(is_array($opt))$this->setOptions($opt);
		if(is_null($text))return $this;
		$this->values[$id] = (string)$text;
		return $this;
	}

	private function onForm($id, $name, $value, $opt){
		$this->currentId = $id;
		if(is_array($name)){
			$opt = $name;
			$name = null;

			if(is_array($opt)){
				if(isset($opt[0])){
					$value = array_shift($opt);
				}else if(isset($opt["value"])){
					$value = $opt["value"];
				}
			}
		}
		if(is_array($opt))$this->setOptions($opt);
		if(is_null($value))return $this;
		$this->values[$id] = (string)$value;
		return $this;
	}

	function addView($id, $viewName, $opt = null){
		//do nothing
		return $this;
	}
	function addLabel($id, $text = null, $opt = null){
		return $this->onView($id, $text, $opt);
	}

	function addImage($id, $text = null, $opt = null){
		return $this->onView($id, $text, $opt);
	}

	function addLink($id, $text = null, $opt = null){
		return $this->onView($id, $text, $opt);
	}
	function addRaw($id, $text = null, $opt = null){
		return $this->onView($id, $text, $opt);
	}

	/* formの各要素 */

	function addInput($id, $name, $value = null, $opt = null){
		return $this->onForm($id, $name, $value, $opt);
	}

	function addCheck($id, $name, $value = null, $opt = null){
		return $this->onForm($id, $name, $value, $opt);
	}

	function addSelect($id, $name, $value = null, $opt = null){
		return $this->onForm($id, $name, $value, $opt);
	}

	function addTextArea($id, $name, $value = null, $opt = null){
		return $this->onForm($id, $name, $value, $opt);
	}

	/* 特殊 */

	function addList($id, \Closure $func, array $list = null){
		if(is_null($func))return $this;
		$this->values[$id] = $list;
		return $this;
	}

	function addCondition($id, $defValue = null){
		return $this;
	}

	/* components */

	function setDefault($value){
		return $this;
	}

	function setAttribute($key, $value) {
		if($this->currentId){
			$this->values[$this->currentId . "@" . $key] = $value;
		}
		return $this;
	}

	function setOptions($options) {
		foreach($options as $key => $value){
			if($key[0] == "@"){
				continue;
			}
			$this->setAttribute($key, $value);
		}
		return $this;
	}
}

class ViewContainer implements \ArrayAccess{

	private $values = array();

	public function __construct($values){
		if(is_array($values)){
			$this->values = $values;
		}else{
			$this->values = [$values];
		}
	}

	function apply(\Closure $func){
		$res = $func($this);
	}

	function forge(\Closure $func){
		$obj = new Dummy();
		$func($obj, $this);
		$this->values = array_merge($this->values, $obj->values);
	}

	function values($values = null){
		if($values)$this->values = $values;
		return $this->values;
	}

	function value($name, $value){
		$this->values[$name] = $value;
	}

	/**
	 * placeholderを登録する
	 * @param string $name
	 * @param any $value
	 */
	function placeholder($name, $value){
		if(!isset($this->values[$name])){
			$this->values[$name] = $value;
		}else if(is_array($this->values[$name]) && empty($this->values[$name])){
			$this->values[$name] = $value;
		}
	}

	function placeholders(array $values){
		foreach($values as $name => $value){
			if(!isset($this->values[$name])){
				$this->values[$name] = $value;
			}
		}
	}

	function out($name, $defValue = null){
		$this->h($this->getString($name, $defValue));
	}

	function h($string){
		echo htmlspecialchars($string, ENT_QUOTES);
	}

	/**
	 * get valu
	 * @param unknown $name
	 * @param string $def
	 * @return multitype:|string
	 */
	function get($name, $def = null){
		if(isset($this->values[$name])){
			return $this->values[$name];
		}
		return $def;
	}

	function getString($name, $defValue = null){
		if(isset($this->values[$name])){
			if(is_string($this->values[$name])){
				return $this->values[$name];
			}else if(is_numeric($this->values[$name])){
				return $this->values[$name] . "";
			}
		}
		return $defValue;
	}

	function getArray($name){
		if(isset($this->values[$name]) && is_array($this->values[$name])){
			return $this->values[$name];
		}
		return [];
	}

	function getArrayItem($arrayKey, $valueKey, $def = null){
		$array = $this->getArray($arrayKey);
		$key = $this->get($valueKey);
		if(isset($array[$key])){
			return $array[$key];
		}
		return $def;
	}

	function check($name){
		if(!isset($this->values[$name]))return false;
		if(is_array($this->values[$name]) && empty($this->values[$name]))return false;
		if(is_string($this->values[$name]) && strlen($this->values[$name]) < 1)return false;
		if(is_null($this->values[$name]))return false;
		if(false === $this->values[$name])return false;

		return true;
	}

	function on($name, \Closure $func){
		$value = (isset($this->values[$name])) ? $this->values[$name] : null;
		$newFunc = \Closure::bind($func, $this);
		$this->values[$name] = $newFunc($this, $value);
	}

	/* array access */

	public function offsetSet($offset, $value){

		if(strpos($offset, ".") !== false){
			$path = $offset;
			$at = &$this->values;
            $keys = explode(".",$path);
			while(count($keys) > 0){
				if(count($keys) === 1){
					if(is_array($at)){
						$at[array_shift($keys)] = $value;
					}else{
						throw new \RuntimeException("Can not set value at this path ($path) because is not array.");
					}
				}else{
					$key = array_shift($keys);
					if(!isset($at[$key])){
						$at[$key] = [];
					}
					$at = & $at[$key];
                }
            }
			return;
		}


		if(is_null($offset)){
			$this->values[] = $value;
		}else{
			$this->values[$offset] = $value;
		}
	}
	public function offsetExists($offset){
		return isset($this->values[$offset]);
	}
	public function offsetUnset($offset){
		unset($this->values[$offset]);
	}
	public function offsetGet($offset){
		return isset($this->values[$offset]) ? $this->values[$offset] : null;
	}

}