<?php
namespace lasa\db\builder\method;
use lasa\db\builder\DAOMethodBuilder;
use lasa\db\Query;

class UpdateMethodBuilder extends DAOMethodBuilder{
	
	/**
	 * buildMethod
	 */
	public function buildMethod(\ReflectionMethod $method) {
		
		$methodName = $method->getName();
		
		$params = [];
		foreach($method->getParameters() as $param){
			$params[$param->getName()] = $param;
		}
		
		$scripts = [];
		$query = Query::update($this->tableName);
		$getByX = null;
		if(preg_match('/By([a-zA-Z0-9]*)$/',$methodName,$tmp)){
			$getByX = lcfirst($tmp[1]);
		}
		
		$binds = [];
		$columns = [];
		if($getByX){	//getbyXを指定した時は引数のみで作る
			foreach($params as $name => $param){
				if(!isset($this->model[$name])){
					throw new \Exception("unknown property:" . $name);
				}
				$columns[$name] = $this->model[$name];
			}
		}else{
			$columns = $this->model;
		}
		
		foreach($columns as $key => $column_array){
			$value = array_shift($column_array);
			
			if($getByX){
				if($key == "id"){
					continue;
				}else if($key == $getByX){
					$query->where($value."=:".$value);
				}else{
					$query->column($value);
				}
			}else{
				if($key == "id"){
					$query->where("id = :id");
				}else{
					$query->column($value);
				}
			}
			
			if(isset($column_array["serialize"]) && $column_array["serialize"] == "json"){
				$binds[":" . $value] = 'json_encode('.$this->buildBindCode($key, $params) . ")";
			}else{
				$binds[":" . $value] = $this->buildBindCode($key, $params);
			}
		}
		
		$scripts[] = '$query = '.$query->dump().';' . "\n";
		$scripts[] = '$binds = '.$this->buildBindsCode($binds).';' . "\n";
		$scripts[] = 'return $this->executeUpdateQuery($query, $binds);';
		return "\t" . implode("\t", $scripts);
	}

}