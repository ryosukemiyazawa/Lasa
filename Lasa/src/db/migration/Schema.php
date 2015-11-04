<?php

namespace lasa\db\migration;

use lasa\db\DataSource;
use lasa\db\migration\schema\MySQLSchema;
use lasa\db\migration\schema\SQLiteSchema;
use lasa\db\migration\schema\Table;
use lasa\db\migration\schema\Column;

abstract class Schema{
	
	/**
	 *
	 * @param DataSource $ds
	 * @return Schema
	 */
	public static function get(DataSource $ds){
		
		if($ds->getDriver() == "mysql"){
			return self::mysql($ds);
		}
		
		if($ds->getDriver() == "sqlite"){
			return self::sqlite($ds);
		}
		
	}
	public static function mysql($ds){
		$schema = new MySQLSchema();
		$schema->dataSource = $ds;
		$schema->prepare();
		return $schema;
	}
	
	public static function sqlite($ds){
		$schema = new SQLiteSchema();
		$schema->dataSource = $ds;
		$schema->prepare();
		return $schema;
	}
	
	private $tableList = array();
	private $indexList = array();
	
	
	/**
	 * @var DataSource
	 */
	private $dataSource;
	
	protected function getDataSource(){
		return $this->dataSource;
	}
	
	public function tableExists($tableName){
		return (isset($this->tableList[$tableName]));
	}
	
	/**
	 * @param unknown $tableName
	 * @return Table
	 */
	public function table($tableName){
		return (isset($this->tableList[$tableName])) ? $this->tableList[$tableName] : null;
	}
	
	protected function setTableList($list){
		$this->tableList = $list;
	}
	
	
	abstract protected function prepare();
	
	public function buildDeleteTableQuery(Table $table){
		return "drop table if exists " . $table->getName();
	}
	
	public function buildCreateTableQuery(Table $table){
		
		$columnQuery = array();
		
		foreach($table->columnNames() as $columnName){
			$column = $table->column($columnName);
			$query = $this->buildColumnQuery($column);
			$columnQuery[] = $query;
		}
		
		$res = "create table " . $table->getName();
		$res .= "(\n";
		$res .= "\t" . implode(",\n\t", $columnQuery);
		$res .= "\n)";
		
		return $res;
	}
	
	public function buildAddColumnQuery(Column $column, $position = null){
		$res = "alter table " . $column->table;
		$res .= " add column ";
		$res .= $this->buildColumnQuery($column);
		if($position)$res .= " " . $position;
		return $res;
	}
	
	public function buildModifyColumnQuery(Column $column){
		$res = "alter table " . $column->table;
		$res .= " modify column ";
		$res .= $this->buildColumnQuery($column);
		return $res;
	}
	
	public function buildDeleteColumnQuery(Column $column){
		$res = "alter table " . $column->table;
		$res .= " drop column " . $column->name;
		return $res;
	}
	
	public function buildColumnQuery(Column $column){
		$query = $column->name . " " . $this->buildTypeQuery($column);
			
		if($column->primary){
			$query .= " PRIMARY KEY";
		}
		if(false == $column->nullable){
			$query .= " NOT NULL";
		}
		if($column->default !== null){
			$query .= " DEFAULT " . $column->default;
		}
		if($column->unique){
			$query .= " UNIQUE";
		}
		if($column->autoincrement){
			$query .= " AUTO_INCREMENT";
		}
		return $query;
	}
	
	public function buildTypeQuery(Column $column){
		$type = $column->type;
		$length = $column->length;
		
		switch($column->type){
			case "integer":
				return $type;
				break;
		}
		
		if($length){
			return $type . "(" . $length . ")";
		}
		
		return $type;
	}
	
}