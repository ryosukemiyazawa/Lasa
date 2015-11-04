<?php
/*
 * ArrayHelper.php
 */
namespace lasa\common;

class ArrayHelper {
	
	public static function toArray($object, $recursive = true){
		
		if(is_array($object)){
			$res = [];
			foreach($object as $key => $each){
				if(is_array($each) || is_object($each)){
					$res[$key] = ArrayHelper::toArray($each, $recursive);
				}else{
					$res[$key] = $each;
				}
			}
			return $res;
		}
		
		if(!is_object($object)){
			return [$object];
		}
		
		if($object instanceof stdClass){
			return (array)$object;
		}
		
		$res = [];
		
		$reflectionClass = new \ReflectionClass($object);
		$properties = $reflectionClass->getProperties();
		foreach($properties as $prop){	/* @var $prop ReflectionProperty */
			$name = $prop->getName();
			if($name[0] == "_")continue;
			
			$getter = "get" . ucfirst(lcfirst(strtr(ucwords(strtr($name, ['_' => ' '])), [' ' => ''])));
			if(is_callable([$object, $getter])){
				$getter_value = $object->$getter();
				if($recursive && (is_array($getter_value) || is_object($getter_value))){
					$res[$name] = ArrayHelper::toArray($getter_value, $recursive);
				}else{
					$res[$name] = $getter_value;
				}
			}
		}
		
		return $res;
	}
	
}