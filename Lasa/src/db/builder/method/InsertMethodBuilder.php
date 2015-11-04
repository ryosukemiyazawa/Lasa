<?php
namespace lasa\db\builder\method;
use lasa\db\builder\DAOMethodBuilder;
use lasa\db\Query;

class InsertMethodBuilder extends DAOMethodBuilder{
	

	/**
	 * buildMethod
	 * @see \lasa\db\builder\DAOMethodBuilder::buildMethod()
	 */
	public function buildMethod(\ReflectionMethod $method) {
		
		$params = [];
		foreach($method->getParameters() as $param){
			$params[$param->getName()] = $param;
		}
		
		$scripts = [];
		$query = Query::insert($this->tableName);
		$binds = [];
		
		foreach($this->model as $key => $array){
			if($key == "id")continue;
			$value_name = array_shift($array);
			$query->column($value_name);
			
			if(isset($array["serialize"]) && $array["serialize"] == "json"){
				$binds[":" . $value_name] = 'json_encode('.$this->buildBindCode($key, $params) . ")";
			}else{
				$binds[":" . $value_name] = $this->buildBindCode($key, $params);
			}
		}
		$scripts[] = '$query = '.$query->dump().';' . "\n";
		$scripts[] = '$binds = '.$this->buildBindsCode($binds).';' . "\n";
		$scripts[] = '$this->executeUpdateQuery($query, $binds);'. "\n";
		$scripts[] = '$id = $this->lastInsertId();'. "\n";
		$scripts[] = 'return $id;';
		
		return "\t" . implode("\t", $scripts);
	}

}
