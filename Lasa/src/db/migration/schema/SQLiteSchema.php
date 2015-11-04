<?php

namespace lasa\db\migration\schema;

use lasa\db\migration\Schema;

class SQLiteSchema extends Schema{
	
	
	/**
	 * prepare
	 * @see \lasa\db\migration\Schema::prepare()
	 */
	protected function prepare() {
		
		$ds = $this->getDataSource();
		
		$res = $ds->executeQuery("select * from sqlite_master where type = 'table'");
		
		$tables = array();
		foreach($res as $row){
			
			$tableName = $row["name"];
			$tableObject = new Table($tableName);
			
			$this->prepareTable($tableObject);
			
			$tables[$tableName] = $tableObject;
		}
		
		$this->setTableList($tables);
	}
	
	private function prepareTable(Table $table){
		$ds = $this->getDataSource();
		
		$res = $ds->executeQuery("PRAGMA table_info(".$table->getName().")");
		
		foreach($res as $row){
			$columnName = $row["name"];
			$type = $row["type"];
			$length = null;
			if(preg_match("#(.+)\(([0-9]+)\)#", $type, $tmp)){
				$type = $tmp[1];
				$length = $tmp[2];
			}
			
			//SQLiteのバージョンによってはpkに上手く入らないパターンが有る
			if(strpos($type, " ") !== false){
				list($type, $suffix) = explode(" ", $type);
				if($suffix == "primary_id"){
					$row["pk"] = 1;
				}
			}
			
			switch($type){
				case "integer":
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
				default:
					$column = $table->text($columnName);;
					break;
			}
			
			$primary = ($row["pk"] == "1");
			$nullable = ($row["notnull"] == "0");
			$default = $row["dflt_value"];
			$unique = false;
			
			$column->length = $length;
			$column->primary = $primary;
			$column->nullable = $nullable;
			$column->default = $default;
			$column->unique = $unique;
			
		}
		
		$res = $ds->executeQuery("select * from sqlite_master where tbl_name = :table",[":table" => $table->getName()]);
		
		foreach($res as $row){
			$type = $row["type"];
			if($type == "table"){
				
				$tableName = $row["name"];
				$sql = str_replace(["\r","\n"],"",$row["sql"]);
				preg_match("#(".$tableName.")\((.*)\)#im", $sql, $tmp);
				$lines = explode(",", $tmp[2]);
				foreach($lines as $line){
					$line = strtolower(trim($line));
					$each = explode(" ", $line);
					$columnName = $each[0];
					
					$column = $table->column($columnName);
					if(!$column){
						continue;
					}
					
					if(in_array("unique", $each)){
						$column->unique = true;
					}
				}
				
			}
		}
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
			if(!$column->primary){
				$query .= " PRIMARY KEY";
			}
			$query .= "";
		}
		return $query;
	}

}