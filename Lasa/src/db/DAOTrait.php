<?php
/*
 * DAOTrait.php
 */
namespace lasa\db;

trait DAOTrait{

	private $limit = null;
	private $offset = null;
	private $options = [];

	public function getLimit(){
		return $this->limit;
	}
	public function setLimit($limit){
		$this->limit = $limit;
		return $this;
	}
	public function getOffset(){
		return $this->offset;
	}
	public function setOffset($offset){
		$this->offset = $offset;
		return $this;
	}
	
	public function begin(){
		$this->getDataSource()->begin();
	}
	
	public function commit(){
		$this->getDataSource()->commit();
	}
	
	public function rollback(){
		$this->getDataSource()->rollback();
	}
	
	/**
	 * @param sql $sql
	 * @param array $binds
	 * @param function $func
	 */
	public function executeQuery($sql, $binds = array(), $func = null){
		$dataSource = null;
		if(DB::getConfigure("master_slave") && !$this->getDataSourceName(false)){
			if($this->getOption("force_master")){
				$dataSource = $this->getDataSource("master");
			}else{
				$dataSource = $this->getDataSource("slave");
			}
		}else{
			$dataSource = $this->getDataSource($this->getDataSourceName(false));
		}
		if($sql instanceof \lasa\db\Query){
			$sql = clone($sql);
			if($this->limit){
				$sql->limit($this->limit);
			}
			if($this->offset){
				$sql->offset($this->offset);
			}
		}
		$res = $dataSource->executeQuery($sql, $binds, $func);
		return $res;
	}
	
	public function executeUpdateQuery($sql, $binds = array()){
		if(DB::getConfigure("master_slave") && !$this->getDataSourceName(true))return $this->getDataSource("master")->executeUpdateQuery($sql, $binds);
		return $this->getDataSource($this->getDataSourceName(true))->executeUpdateQuery($sql, $binds);
	}
	
	public function lastInsertId(){
		if(DB::getConfigure("master_slave") && !$this->getDataSourceName(false))return $this->getDataSource("master")->lastInsertId();
		return $this->getDataSource($this->getDataSourceName(false))->lastInsertId();
	}
	
	public function getDataSource($name = null){
		if($name){
			return DataSource::getDataSource($name);
		}
		if(DB::getConfigure("master_slave") && $this->getOption("force_master")){
			return DataSource::getDataSource("master");
		}
		$defaultDataSourceName = DB::getConfigure("default_connection", "default");
		return DataSource::getDataSource($defaultDataSourceName);
	}
	
	/**
	 * 接続先を変える場合はここをoverride
	 */
	public function getDataSourceName($isUpdate){
		return null;
	}
	
	public function setOption($key, $value){
		$this->options[$key] = $value;
	}
	
	public function getOption($key, $defValue = null){
		return (isset($this->options[$key])) ? $this->options[$key] : $defValue;
	}
	
	public function getObject(array $row){
		
		$className = $this->getModelClass();
		$columns = $this->getColumns();
		$columnNames = $this->getColumnNames();
		$obj = new $className();
		
		$vars = get_object_vars($obj);
		
		foreach($row as $key => $value){
			if(!isset($columnNames[$key])){
				$method = "set".ucwords($key);
				if(method_exists($obj,$method)){
					$obj->$method($value);
				}
				continue;
			}
			$prop = $columnNames[$key];
			$column = $columns[$prop];
			
			if(isset($column["serialize"]) && $column["serialize"] == "json"){
				$value = json_decode($value, true);
			}
			
			$method = "set".ucwords($prop);
			if(method_exists($obj,$method)){
				$obj->$method($value);
			}else if(array_key_exists($prop, $vars)){
				$obj->$prop = $value;
			}
		}
		return $obj;
	}

	public function getModelClass(){
		//DAO生成時に自動的に補完されます
	}

	public function getColumns(){
		//DAO生成時に自動的に補完されます
	}
	
	public function getColumnNames(){
		//DAO生成時に自動的に補完されます
	}

	public function getTableName(){
		//DAO生成時に自動的に補完されます
	}
}