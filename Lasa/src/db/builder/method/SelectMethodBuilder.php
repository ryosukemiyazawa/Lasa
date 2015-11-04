<?php
namespace lasa\db\builder\method;
use lasa\db\builder\DAOMethodBuilder;
use lasa\db\Query;

/**
 * @TODO return系をどうにかする
 */
class SelectMethodBuilder extends DAOMethodBuilder{
	
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
		$returnType = "array";
		if(isset($annotations["return"])){
			$annotationReturn = $annotations["return"];
			if(class_exists($annotationReturn) && $annotationReturn == $this->modelClassName){
				$returnType = "object";
			}if(class_exists($namespaceName ."\\" . $annotationReturn) && $namespaceName . "\\" .$annotationReturn == $this->modelClassName){
				$returnType = "object";
			}else if($annotationReturn == "list"){
				$returnType = "list";
			}else if($annotationReturn == "[" . $this->modelClassName . "]"){
				$returnType = "list";
			}else if($annotationReturn == "row"){
				$returnType = "row";
			}else if(strpos($annotationReturn, "column_") === 0){
				$returnColumnName = substr($annotationReturn, strlen("column_"));
			}
		}
		
		/* パラメーター */
		
		$scripts = [];
		$binds = [];
		$query = Query::select($this->tableName);
		$getByX = null;
		$index = null;
		
		if(isset($annotations["index"])){
			$index = $annotations["index"];
		}
		
		if(preg_match('/By([a-zA-Z0-9]*)$/',$methodName,$tmp)){
			$getByX = lcfirst($tmp[1]);
			
			//getByIdでreturnを指定していない時は自動補完
			if($getByX == "id" && !(isset($annotations["return"]))){
				$returnType = "object";
			}
		}
		
		foreach($this->model as $key => $column_array){
			$value = array_shift($column_array);
			$query->column($value);
			
			if($getByX && $getByX == $key){
				$query->where($value."=:".$key);
				if(isset($column_array["serialize"]) && $column_array["serialize"] == "json"){
					$binds[":" . $key] = 'json_encode('.$this->buildBindCode($key, $params) . ")";
				}else{
					$binds[":" . $key] = $this->buildBindCode($key, $params);
				}
			}
		}
		
		if(isset($annotations["order"])){
			$query->setOrder($annotations["order"]);
		}
		
		$scripts[] = '$query = '.$query->dump().';' . "\n";
		$scripts[] = '$binds = '.$this->buildBindsCode($binds).';' . "\n";
		
		//objectの時は1件のみ
		if($returnType == "object"
				|| $returnType == "column"
				|| $returnType == "row"
		){
			$scripts[] = '$oldLimit = $this->getLimit();';
			$scripts[] = '$this->setLimit(1);';
		
			$scripts[] = '$oldOffset = $this->getOffset();';
			$scripts[] = '$this->setOffset(0);';
		}
		
		$scripts[] = '$result=$this->executeQuery($query, $binds);' . "\n";

		switch($returnType){
			/* 一行のパターン */
			
			case "object":
				$scripts[] = '$this->setLimit($oldLimit);' . "\n";
				$scripts[] = '$this->setOffset($oldOffset);' . "\n";
				$scripts[] = 'if(count($result)<1)return null;'  . "\n";
				$scripts[] = '$obj = $this->getObject($result[0]);';
				$scripts[] = 'return $obj;';
				break;
			case "row":
				$scripts[] = '$this->setLimit($oldLimit);';
				$scripts[] = '$this->setOffset($oldOffset);';
				$scripts[] = 'if(count($result)<1)return null;'  . "\n";
				$scripts[] = 'return $result[0];';
				break;
			case "column":
				$scripts[] = '$this->setLimit($oldLimit);';
				$scripts[] = '$this->setOffset($oldOffset);';
				$scripts[] = 'if(count($result)<1)return null;'  . "\n";
				$scripts[] = '$row = $result[0];';
				$scripts[] = 'return $row["'.$returnColumnName.'"];';
				break;
		
			/* 複数行のパターン */
			case "list":
			default:
				$scripts[] = '$array = array();';
				$scripts[] = 'if(is_array($result)){';
				$scripts[] = 'foreach($result as $row){';
					
				if($index){
					$func = "get".ucfirst($index);
					$scripts[] = '$obj = $this->getObject($row);';
					$scripts[] = '$array[$obj->'.$func.'()] = $obj;';
				}else{
					$scripts[] = '$array[] = $this->getObject($row);';
				}
					
				$scripts[] = '}';
				$scripts[] = '}';
				$scripts[] = 'return $array;';
					
				break;
		}
		
		
		return "\t" . implode("\t", $scripts);

	}

}