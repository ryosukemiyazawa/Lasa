<?php
namespace lasa\db\builder\method;
use lasa\db\builder\DAOMethodBuilder;
use lasa\db\Query;

/**
 * countXXX系のメソッドを生成する
 */
class CountMethodBuilder extends DAOMethodBuilder{
	
	/**
	 * buildMethod
	 * @see \lasa\db\builder\DAOMethodBuilder::buildMethod()
	 */
	public function buildMethod(\ReflectionMethod $method) {
		$methodName = $method->getName();
		
		$params = [];
		foreach($method->getParameters() as $param){
			$params[$param->getName()] = $param;
		}
		
		/* modelClassShortName */
		$namespaceName = $method->getDeclaringClass()->getNamespaceName();
		
		/* 準備 */
		$annotations = $this->getMethodAnnotations($method);
		
		/* パラメーター */
		
		$scripts = [];
		$binds = [];
		$query = Query::select($this->tableName);
		$getByX = null;
		$returnColumnName = "count_" . strtolower($methodName);
		
		if(isset($this->model["id"])){
			$columnName = $this->model["id"];
			$query->column("count(".$columnName[0].") as " . $returnColumnName);
		}else{
			$query->column("count(*) as " . $returnColumnName);
		}
		
		if(preg_match('/By([a-zA-Z0-9]*)$/',$methodName,$tmp)){
			$getByX = lcfirst($tmp[1]);
		}
		
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
		}
		
		$scripts[] = '$query = '.$query->dump().';' . "\n";
		$scripts[] = '$binds = '.$this->buildBindsCode($binds).';' . "\n";
		
		//objectの時は1件のみ
		$scripts[] = '$oldLimit = $this->getLimit();';
		$scripts[] = '$this->setLimit(1);';
	
		$scripts[] = '$oldOffset = $this->getOffset();';
		$scripts[] = '$this->setOffset(0);';
		
		$scripts[] = '$result=$this->executeQuery($query, $binds);' . "\n";
		
		$scripts[] = '$this->setLimit($oldLimit);';
		$scripts[] = '$this->setOffset($oldOffset);';
		$scripts[] = 'if(count($result)<1)return 0;'  . "\n";
		$scripts[] = 'return (int)$result[0]["'.$returnColumnName.'"];';
		
		
		return "\t" . implode("\t", $scripts);

	}

}