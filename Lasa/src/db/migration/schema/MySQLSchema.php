<?php

namespace lasa\db\migration\schema;

use lasa\db\migration\Schema;

class MySQLSchema extends Schema{

	protected function prepare() {
		$ds = $this->getDataSource();

		$res = $ds->executeQuery("show tables");
		$tables = array();
		foreach($res as $row){
			$tableName = array_shift($row);
			$tableObject = new Table($tableName);
			
			$this->prepareTable($tableObject);
			
			$tables[$tableName] = $tableObject;
		}
		
		$this->setTableList($tables);
	}
	
	private function prepareTable(Table $table){
		
		$ds = $this->getDataSource();
		
		$res = $ds->executeQuery("show table status where name = :table",[":table" => $table->getName()]);
		$res = $res[0];
		
		$table->engine($res["Engine"]);
		$table->comment($res["Comment"]);
		
		$res = $ds->executeQuery("show create table " . $table->getName());
		$res = $res[0];
		$createSql = $res["Create Table"];
		
		$tmp = array();
		if(preg_match("/DEFAULT CHARSET=(.+)/", $createSql, $tmp)){
			$table->charset($tmp[1]);
		}
		
		$res = $ds->executeQuery("show columns from " . $table->getName());
		
		foreach($res as $row){
			$columnName = $row["Field"];
			$type = $row["Type"];
			$nullable = $row["Null"] == "YES";
			$default = $row["Default"];
			$key = $row["Key"];
			$length = null;
			
			$primary = $key == "PRI";
			$unique = $key == "UNI";
			
			if($columnName == "id"){
				$table->id();
				continue;
			}
			
			if(preg_match("/(.+)\((\d+)\)/", $type, $tmp)){
				$type = $tmp[1];
				$length = $tmp[2];
			}
			
			$column = null;
			
			switch($type){
				case "int":
					$column = $table->integer($columnName);
					$length = null;
					break;
				case "varchar":
					$column = $table->varchar($columnName);
					break;
				case "text":
					$column = $table->text($columnName);
					break;
				case "longtext":
					$column = $table->text($columnName, "long");
					break;
				
			}
			
			if(!$column){
				die("unknown type:" . $type);
			}
			
			$column->length = $length;
			$column->primary = $primary;
			$column->nullable = $nullable;
			$column->default = $default;
			$column->unique = $unique;
			
		}
		
		$res = $ds->executeQuery("select column_name, " .
				"referenced_table_name,referenced_column_name ".
				"from information_schema.key_column_usage " .
				"where referenced_table_name is not null " .
				"and table_schema = :dbname and table_name = :table",[":dbname" => $ds->getDataBaseName(),":table" => $table->getName()]);
		
		foreach($res as $row){
			$column = $row["column_name"];
			$targetTable = $row["referenced_table_name"];
			$targetColumn = $row["referenced_column_name"];
			
			$table->column($column)->reference($targetTable, $targetColumn);
		}
	}
	
	
	/**
	 * buildCreateTableQuery
	 * @see \lasa\db\migration\Schema::buildCreateTableQuery()
	 */
	public function buildCreateTableQuery(Table $table) {
		$res = parent::buildCreateTableQuery($table);

		if($table->getEngine()){
			$res .= " ENGINE=" . $table->getEngine();
		}
		if($table->getCharset()){
			$res .= " DEFAULT CHARSET=" . $table->getCharset();
		}
		
		return $res;
	}
	
}