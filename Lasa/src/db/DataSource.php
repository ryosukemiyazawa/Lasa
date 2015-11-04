<?php
/*
 * DataSource.php
 */
namespace lasa\db;

class DataSource{
	
	static $_datasources = array();
	
	/**
	 * @param string $name
	 * @return DataSource
	 */
	public static function getDataSource($name = null){
		
		if(!$name){
			$name = DB::getConfigure("default_connection");
		}
		
		if(!isset(self::$_datasources[$name])){
			$config = DB::getConfigure("connection." . $name);
			$ds = new DataSource($name, $config);
			self::$_datasources[$name] = $ds;
		}
		
		
		return self::$_datasources[$name];
	}
	
	
	private $name;
	private $driver;
	private $dsn;
	private $database;
	private $user;
	private $pass;
	private $pdo;
	
	private function __construct($name, $config){
		
		if(!isset($config["driver"]) || !isset($config["database"])){
			throw new \Exception("[".__CLASS__."]driver and database are required.");
		}
		
		
		$this->name = $name;
		$this->driver = $config["driver"];
		$this->database = $config["database"];
		
		if($this->driver == "mysql"){
			$this->dsn = "mysql:host=" . $config["host"]. ";dbname=" . $config["database"];
			$this->user = $config["username"];
			$this->pass = $config["password"];
			
		}else if($this->driver == "sqlite"){
			$this->dsn = "sqlite:" . $config["database"];
		}else{
			throw new \Exception("[".__CLASS__."]unknown driver:" . $this->driver);
		}
	}
	
	public function getPDO(){
		if(!$this->pdo){
			$options = [
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_EMULATE_PREPARES => true
			];
			if($this->driver == "mysql"){
				$options[] = \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
			}
			try{
				$this->pdo = new \PDO($this->dsn, $this->user, $this->pass, $options);
			}catch(\Exception $e){
				if(DB::$_errorHandler != null){
					$func = DB::$_errorHandler;
					if(is_callable($func)) {
						$func($e);
					}
				}
			}
		}
		
		return $this->pdo;
	}
	
	public function getDriver(){
		return $this->driver;
	}
	public function getDataBaseName(){
		return $this->database;
	}
	
	/**
	 * @param string $sql
	 * @param array $binds
	 * @return PDOStatement
	 */
	private function getStatement($sql, $binds){
		$pdo = $this->getPDO();
		if(!$pdo){
			return null;
		}
		
		try{
			$stmt = $pdo->prepare($sql);
		}catch(\Exception $e){
			if(DB::$_errorHandler != null){
				$func = DB::$_errorHandler;
				if(is_callable($func)) {
					$func($e);
				}
			}
		}
		
		foreach($binds as $key => $bind){
			$type = \PDO::PARAM_STR;
			switch(true){
				case is_null($bind) :
					$type = \PDO::PARAM_NULL;
					break;
				case is_int($bind) :
					$type = \PDO::PARAM_INT;
					break;
				case is_bool($bind) :
					$type = \PDO::PARAM_BOOL;
					break;
				case is_float($bind) :
				case is_numeric($bind) :
				case is_string($bind) :
				default:
					$type = \PDO::PARAM_STR;
					break;
			}
			$stmt->bindValue($key, $binds[$key], $type);
		}
		
		return $stmt;
	}
	
	
	public function executeQuery($sql, $binds = array(), $func = null){
		
		$stmt = $this->getStatement($sql, $binds);
		if(!$stmt)return null;
		
		$stmt->execute();
		
		$resultArray = array();
		
		$counter = 0;
		if($func && is_callable($func)) {
			foreach($stmt as $row) {
				$resultArray[] = $func($row);
				$counter++;
			}
		}else{
			foreach($stmt as $row){
				$resultArray[] = $row;
				$counter++;
			}
		}
		
		return $resultArray;
	}
	
	public function executeUpdateQuery($sql, $binds = array()){
		$stmt = $this->getStatement($sql, $binds);
		return $stmt->execute();
	}
	
	public function lastInsertId($name = null){
		return $this->getPDO()->lastInsertId($name);
	}
	
	public function begin(){
		$this->getPDO()->beginTransaction();
	}
	
	public function commit(){
		$this->getPDO()->commit();
	}
	
	public function rollback(){
		$this->getPDO()->rollBack();
	}
	
	
}