<?php
namespace lasa\db\builder\method;
use lasa\db\builder\DAOMethodBuilder;
use ReflectionMethod;
use lasa\db\Query;

class DeleteMethodBuilder extends DAOMethodBuilder{
	
	/**
	 * buildMethod
	 * @see \lasa\db\builder\DAOMethodBuilder::buildMethod()
	 */
	public function buildMethod(ReflectionMethod $method) {
		$methodName = $method->getName();
		
		$params = [];
		foreach($method->getParameters() as $param){
			$params[$param->getName()] = $param;
		}
		
		/* 準備 */
		$annotations = $this->getMethodAnnotations($method);
		
		$scripts = [];
		$query = Query::delete($this->tableName);
		$getByX = null;
		$bind_keys = [];
		$binds = [];
		
		if(preg_match('/By([a-zA-Z0-9]*)$/',$methodName,$tmp)){
			$getByX = lcfirst($tmp[1]);
		}
		
		//getByXがない場合は基本的にidを引数とすることにする
		if($getByX == null){
			$getByX = "id";
		}
		
		if(isset($annotations["where"])){
			$query->where($annotations["where"]);
			$getByX = null;
			
			preg_match_all("/:([^\s]+)/", $annotations["where"], $tmp);
			foreach($tmp[1] as $key){
				if(isset($params[$key])){
					$binds[":" . $key] = $this->buildBindCode($key, $params);
					continue;
				}
				$bind_keys[$key] = $key;
			}
		}
		
		//ByXの部分がパラメーターにある場合の引数
		if($getByX && isset($params[$getByX])){
			$binds[":" . $getByX] = $this->buildBindCode($getByX, $params);
		}
		
		//Modelが引数の場合のbindsの処理
		foreach($this->model as $key => $column_array){
			
			$value = array_shift($column_array);
			
			if($getByX && $getByX == $key){
				$query->where($value."=:".$key);
				if(isset($column_array["serialize"]) && $column_array["serialize"] == "json"){
					$binds[":" . $key] = 'json_encode('.$this->buildBindCode($key, $params) . ")";
				}else{
					$binds[":" . $key] = $this->buildBindCode($key, $params);
				}
			}
			
			if(in_array($key, $bind_keys)){
				if(isset($column_array["serialize"]) && $column_array["serialize"] == "json"){
					$binds[":" . $key] = 'json_encode('.$this->buildBindCode($key, $params) . ")";
				}else{
					$binds[":" . $key] = $this->buildBindCode($key, $params);
				}
			}
			
		}
		
		$scripts[] = '$query = '.$query->dump().';' . "\n";
		$scripts[] = '$binds = '.$this->buildBindsCode($binds).';' . "\n";
		$scripts[] = 'return $this->executeUpdateQuery($query, $binds);';
		return "\t" . implode("\t", $scripts);
	}

}