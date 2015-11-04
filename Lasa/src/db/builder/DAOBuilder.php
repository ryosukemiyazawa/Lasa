<?php
namespace lasa\db\builder;

use lasa\db\DAOBase;
use lasa\db\builder\method\InsertMethodBuilder;
use lasa\db\builder\method\SelectMethodBuilder;
use lasa\db\builder\method\DeleteMethodBuilder;
use lasa\db\builder\method\UpdateMethodBuilder;
use lasa\db\builder\method\CountMethodBuilder;

class DAOBuilder{
	
	/**
	 * DAOBuilderを作ります
	 * @param string $className
	 * @return NULL|\lasa\db\builder\DAOBuilder
	 */
	public static function create($className){
		if(!class_exists($className)){
			return null;
		}
		
		$builder = new DAOBuilder($className);
		return $builder;
	}
	
	
	private $className;
	
	private function __construct($className){
		$this->className = $className;
	}
	
	public function write($dstFilePath){
		
		$reflectionClass = new \ReflectionClass($this->className);
		$originPath = $reflectionClass->getFileName();
		
		$className = $reflectionClass->getShortName();
		$namespace = $reflectionClass->getNamespaceName();
		$comment = $reflectionClass->getDocComment();
		$docProperties = $this->parseComment($comment);
		
		$tableName = (isset($docProperties["table"]))
			? $docProperties["table"]
			: $this->snakize($className);
		
		//Class -> Tableのマッピング
		$modelInfo = $this->getColumnMapping($reflectionClass);
		
		$columnNames = [];
		foreach($modelInfo as $key => $array){
			$columnNames[$array[0]] = $key;
		}
		
		$fullClassName = $this->className . "DAO";
		$daoClassName = $className . "DAOImpl";
		$baseClassName = $className . "DAO";

		$scripts = array();
		$scripts[] = "<?php";
		
		if(strlen($namespace) > 0){
			$scripts[] = "namespace " . $namespace . ";";
		}
		
		//生成日と再生成のためのフラグ
		$scripts[] = "/* ".date("Y-m-d H:i:s")." */";
		$scripts[] = 'if(filemtime("'.$originPath.'") > '.time().'){ return; }else{';

		//DAOの各メソッドを実装する
		$methodScripts = array();
		$daoReflectionClass = null;
		if(!class_exists($fullClassName)){	//DAOが無い場合
			$dynamicBaseClassName = $baseClassName ."_dynamic";
			
			//生成用
			$abstractClass = array();
			$abstractClass[] = "abstract class " . $dynamicBaseClassName . " extends \\".DAOBase::class."{";
			$abstractClass[] = $this->buildDefaultMethods($reflectionClass);
			$abstractClass[] = "}";
			
			eval(implode("\n",$abstractClass));
			$daoReflectionClass = new \ReflectionClass($dynamicBaseClassName);
			
			$scripts[] = "abstract class " . $baseClassName . " extends \\".DAOBase::class."{";
			$scripts[] = "}";
		}else{
			$daoReflectionClass = new \ReflectionClass($fullClassName);
		}
		
		$reflectionMethods = $daoReflectionClass->getMethods();
		foreach($reflectionMethods as $method){ /* @var $method \ReflectionMethod */
			if($method->isPrivate() || !$method->isAbstract()){
				continue;
			}
			$methodInnerScript = $this->buildMethod($method, $tableName, $modelInfo);
			if($methodInnerScript){
				$methodScript = [];
				if($method->isProtected()){
					$methodScript[] = "protected ";
				}
				if($method->isPublic()){
					$methodScript[] = "public ";
				}
				
				$params = [];
				
				foreach($method->getParameters() as $param){ /* @var $param \ReflectionParameter */
					$param_script = "";
					if($param->getClass()){
						if($param->getClass()->getNamespaceName() != $namespace){
							$param_script .= $param->getClass()->getName() . " ";
						}else{
							$param_script .= $param->getClass()->getShortName() . " ";
						}
					}
					$param_script .= '$' . $param->getName();
					if($param->isDefaultValueAvailable()){
						$defValue = $param->getDefaultValue();
						if(is_null($defValue)){
							$defValue = "null";
						}else if(is_string($defValue)){
							$defValue = '"'.addslashes($defValue).'"';
						}else if(is_array($defValue)){
							$defValue = '[]';
						}else if(is_numeric($defValue)){
							//そのまま
						}
						$param_script .= '=' . $defValue;
					}
					$params[] = $param_script;
				}
				
				$methodScript[] = "function " . $method->getName() . "(" . implode(",", $params) . "){" . "\n";
				$methodScript[] = $methodInnerScript;
				$methodScript[] = "\n}";
				$methodScripts[] = implode("", $methodScript);
			}
		}

		$scripts[] = "class " . $daoClassName . " extends " . $baseClassName . "{";
		$scripts[] = 'public function getColumns(){ return '.$this->arrayToCode($modelInfo).';}';
		$scripts[] = 'public function getColumnNames(){ return '.$this->arrayToCode($columnNames).';}';
		$scripts[] = 'public function getModelClass(){ return '.var_export($this->className,true).';}';
		$scripts[] = 'public final function getTableName(){ return "'.$tableName.'";}';
		
		$scripts[] = implode("\n", $methodScripts);
		
		$scripts[] = "}";
		$scripts[] = "}";	//end of check timestamp
		
		if(!file_exists(dirname($dstFilePath))){
			mkdir(dirname($dstFilePath));
		}
		
		file_put_contents($dstFilePath, implode("\n", $scripts));
	}
	
	public function parseComment($comment){
		$res = array();
		
		$tmp = array();
		preg_match_all("#@([^\s]+)\s*?(.*)?#m", $comment, $tmp);
		foreach($tmp[1] as $index => $key){
			$res[$key] = trim($tmp[2][$index]);
		}
		
		return $res;
	}
	
	public function snakize($str){
		$str = preg_replace('/[A-Z]/', '_\0', $str);
		$str = strtolower($str);
		return ltrim($str, '_');
	}
	
	/**
	 * カラム名のマッピングを作成する
	 * @param \ReflectionClass $class
	 * @return array()
	 */
	public function getColumnMapping(\ReflectionClass $class){
		
		$properties = $class->getProperties();
		$map = array();
		
		foreach($properties as $property){ /* @var $property \ReflectionProperty */
			
			//_から始まる場合はスキップ
			$propName = $property->getName();
			if($propName[0] == "_")continue;
			
			//コメント
			$comment = $property->getDocComment();
			$docProperties = $this->parseComment($comment);
			
			//カラム名
			$columnName = (isset($docProperties["column"]))
				? $docProperties["column"]
				: $this->snakize($propName);
			
			//カラム
			$serialize = (isset($docProperties["serialize"]))
				? ( ($docProperties["serialize"]) ? $docProperties["serialize"] : "json")
				: null;
			
			if($serialize){
				$map[$propName] = [$columnName, "serialize" => $serialize];
			}else{
				$map[$propName] = [$columnName];
			}
		}
		
		if($class->getParentClass()){
			$properties = $class->getParentClass()->getProperties();
				
			foreach($properties as $property){ /* @var $property \ReflectionProperty */
				
				//_から始まる場合はスキップ
				$propName = $property->getName();
				if($propName[0] == "_")continue;
				
				//コメント
				$comment = $property->getDocComment();
				$docProperties = $this->parseComment($comment);
				
				//カラム名
				$columnName = (isset($docProperties["column"]))
					? $docProperties["column"]
					: $this->snakize($propName);
				
				//カラム
				$serialize = (isset($docProperties["serialize"]))
					? $docProperties["serialize"]
					: null;
				
				//既に追加済みならスキップ
				if(isset($map[$propName])){
					continue;
				}
				
				if($serialize){
					$map[$propName] = [$columnName, "serialize" => $serialize];
				}else{
					$map[$propName] = [$columnName];
				}
			}
		}
		
		return $map;
	}
	
	/**
	 * メソッドを生成する
	 * @param \ReflectionMethod $method
	 * @return string|NULL
	 */
	public function buildMethod(\ReflectionMethod $method, $tableName, $model){
		$methodName = $method->getName();
		$builder = null;
		if(preg_match("#^insert#", $methodName)){
			$builder = new InsertMethodBuilder();
		}
		if(preg_match("#^get#", $methodName) || preg_match("#^find#", $methodName)){
			$builder = new SelectMethodBuilder();
		}
		if(preg_match("#^count#", $methodName)){
			$builder = new CountMethodBuilder();
		}
		if(preg_match("#^delete#", $methodName)){
			$builder = new DeleteMethodBuilder();
		}
		if(preg_match("#^update#", $methodName)){
			$builder = new UpdateMethodBuilder();
		}
		
		if($builder){
			$builder->tableName = $tableName;
			$builder->model = $model;
			$builder->modelClassName = $this->className;
			return $builder->buildMethod($method);
		}
		
		return null;
		
	}
	
	/**
	 * CRUDが可能になるように、標準のメソッドを作る
	 * @param \ReflectionClass $func
	 * @return string
	 */
	public function buildDefaultMethods(\ReflectionClass $func){
		$methodString = array();
		
		$className = $func->getName();
		
		$methodString[] = 'abstract function insert(' . $className . ' $obj);';
		$methodString[] = 'abstract function update(' . $className . ' $obj);';
		$methodString[] = 'abstract function delete($id);';
		$methodString[] = 'abstract function get();';
		$methodString[] = '/**';
		$methodString[] = ' * @hoge fuga';
		$methodString[] = ' * @return ' . $className;
		$methodString[] = '*/';
		$methodString[] = 'abstract function getById($id);';
		
		return implode("\n", $methodString);
	}
	
	
	public function arrayToCode($array){
		$res = "[";
		foreach($array as $key => $value){
			if(is_array($value)){
				$res .= '"' . $key . '" => ' .$this->arrayToCode($value) . ',';
			}else{
				$res .= '"' . $key . '" => "' .$value . '",';
			}
		};
		$res .= "]";
		return $res;
	}
}


class DAOMethodBuilder{
	
	public $tableName;
	public $modelClassName = null;
	public $model = null;
	
	public function buildMethod(\ReflectionMethod $method){
		return "";
	}
	
	function getMethodAnnotations(\ReflectionMethod $method){
		$values = [];
		$docComment = $method->getDocComment();
		$tmp = [];
		if(preg_match_all("#@([^\s]+)\s?(.*)#", $docComment, $tmp)){
			foreach($tmp[1] as $index => $key){
				$value = $tmp[2][$index];
				if(strlen($value) < 1)$value = true;
				$values[$key] = $value;
			}
		}
		
		return $values;
	}
	
	/**
	 * bindのコードを生成する
	 * @param string $key
	 * @param [] $params
	 * @return string
	 */
	function buildBindCode($key, $params){
		foreach($params as $paramName => $param){ /* @var $param \ReflectionParameter */
			if($param->getClass()){
				$ref = $param->getClass(); /* @var $ref \ReflectionClass */
	
				try{
					$prop = $ref->getProperty($key);
						
					if($prop->isPublic()){
						return '$' . $paramName . "->" . $key;
					}
						
					$method = "get". ucwords($key);
					if($ref->hasMethod($method)){
						return '$' . $paramName . "->" . $method . "()";
					}
						
				}catch(\Exception $e){
					
				}
				
				if($ref->getParentClass()){
					$ref = $ref->getParentClass();
					try{
						$prop = $ref->getProperty($key);
							
						if($prop->isPublic()){
							return '$' . $paramName . "->" . $key;
						}
							
						$method = "get". ucwords($key);
						if($ref->hasMethod($method)){
							return '$' . $paramName . "->" . $method . "()";
						}
							
					}catch(\Exception $e){
						
					}
				}
				
				continue;
			}
				
			if($paramName == $key){
				return '$' . $paramName;
			}
		}
		
		return "null";
	}
	
	function buildBindsCode(array $binds){
		$bind_codes = [];
		foreach($binds as $key => $code){
			$bind_codes[] = '"' . addslashes($key) . '" => ' . $code;
		}
		return "[" . implode(",", $bind_codes) . "]";
	}
}
