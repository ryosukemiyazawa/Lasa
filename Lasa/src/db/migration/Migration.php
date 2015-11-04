<?php

namespace lasa\db\migration;
use lasa\db\migration\schema\Table;
class Migration{
	
	/**
	 * @param Migration[]
	 * @param string $connectionName
	 */
	public static function run($migrations, $connectionName = null){
		
		if(!is_array($migrations)){
			$migrations = array($migrations);
		}
		
		$dataSource =\lasa\db\DataSource::getDataSource($connectionName);
		
		foreach($migrations as $migration){	/* @var $migration Migration */
			
			//変更対象
			$dropTables = array();
			$createTables = array();
			$addColumns = array();
			$modifyColumns = array();
			$deleteColumns = array();
			
			//テーブルの定義を取得する
			$schema = Schema::get($dataSource);
		
			//実行する
			$migration->execute();
			
			foreach($migration->tableList as $table){
				
				if($table->delete){
					$dropTables[] = $table;
					continue;
				}
				
				//テーブル作成時のみ
				if($table->check && !$schema->tableExists($table->getName())){
					continue;
				}
				
				//テーブル作成
				if(!$schema->tableExists($table->getName())){
					$createTables[] = $table;
					continue;
				}
				
				//modify column
				$currentTable = $schema->table($table->getName());
				$lastColumnName = null;
				foreach($table->columnNames() as $columnName){
					$column = $table->column($columnName);
					$column->table = $table->getName();
					$currentColumn = $currentTable->column($columnName);
					
					//削除カラム
					if($column->delete){
						if($currentColumn){
							$deleteColumns[] = $column;
						}
						continue;
					}
					
					//追加カラム
					if(!$currentColumn){
						$addColumns[] = array($column, $lastColumnName);
						$lastColumnName = $columnName;
						continue;
					}
					
					$lastColumnName = $columnName;
					
					if($column->changed){
						$modifyColumns[] = $column;
						continue;
					}
					
					//比較する
					if(false == $column->compare($currentColumn)){
						$modifyColumns[] = $column;
						continue;
					}
					
					
					
				}
				
			}
			
			foreach($dropTables as $table){
				$sql = $schema->buildDeleteTableQuery($table);
				$dataSource->executeUpdateQuery($sql);
			}
			
			foreach($createTables as $table){
				$sql = $schema->buildCreateTableQuery($table);
				$dataSource->executeUpdateQuery($sql);
			}
			
			foreach($addColumns as $array){
				list($column, $prevous) = $array;
				$position = "";
				if($prevous)$position = "AFTER " . $prevous;
					
				$sql = $schema->buildAddColumnQuery($column, $position);
				$dataSource->executeUpdateQuery($sql);
					
			}
			
			foreach($modifyColumns as $column){
				$sql = $schema->buildModifyColumnQuery($column);
				$dataSource->executeUpdateQuery($sql);
			}
			
			foreach($deleteColumns as $column){
				$sql = $schema->buildDeleteColumnQuery($column);
				$dataSource->executeUpdateQuery($sql);
			}
		}
		
	}
	
	/**
	 * @param unknown $name
	 * @param \Closure $func
	 * @return Migration
	 */
	public static function create($name, \Closure $func){
		$migration = new Migration($name);
		$migration->_execute = \Closure::bind($func, $migration, $migration);
		return $migration;
	}
	
	private $name = null;
	
	/**
	 * @var Table[]
	 */
	private $tableList = array();
	private $indexList = array();
	private $_execute = null;
	
	/**
	 * @param string $name
	 */
	public function __construct($name){
		$this->name = $name;
	}
	
	public function execute(){
		if($this->_execute){
			$func = $this->_execute;
			if(is_callable($func)) {
				$func($this);
			}
		}
	}
	
	/**
	 * テーブルを定義する
	 * @param string $tableName
	 * @param closure $func
	 */
	final public function table($tableName, $func){
		if(isset($this->tableList[$tableName])){
			if(is_callable($func)) {
				$func($this->tableList[$tableName]);
			}
			return;
		}
		$tableObject = new Table($tableName);
		if(is_callable($func)) {
			$func($tableObject);
		}
		$this->tableList[$tableName] = $tableObject;
	}
	
	final public function checkTable($tableName, $func){
		if(isset($this->tableList[$tableName])){
			$func($this->tableList[$tableName]);
			return;
		}
		$tableObject = new Table($tableName);
		$tableObject->check = true;
		$func($tableObject);
		$this->tableList[$tableName] = $tableObject;
	}
	
	/**
	 * indexの作成を確認する
	 * @param $indexName
	 * @param $func
	 */
	final public function index($indexName, $func){
		if(isset($this->indexList[$indexName])){
			$func($this->indexList[$indexName]);
			return;
		}
		$indexObject = new Table($indexName);
		$func($indexObject);
		$this->indexList[$indexName] = $indexObject;
	}
	
	
}
