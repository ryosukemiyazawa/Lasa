<?php
namespace lasa\db\migration\schema;

class Column{

	public $name;
	public $table;
	public $type;
	public $length;
	public $default;
	public $nullable = true;
	public $comment;
	public $primary = false;
	public $unique = false;
	public $autoincrement = false;
	public $reference;
	public $delete = false;
	public $changed = false;

	public function __construct($name){
		$this->name = $name;
	}

	public function reference($table, $columnName){
		$this->reference = array($table, $columnName);
	}

	public function change(){
		$this->changed = true;
	}
	
	public function unique($flag = true){
		$this->unique = $flag;
		return $this;
	}
	
	public function defaultValue($value){
		$this->default = $value;
		return $this;
	}
	
	public function notnull($flag = false){
		$this->nullable = $flag;
		return $this;
	}

	/**
	 * カラムを比較する
	 * @param Column $column
	 * @return boolean
	 */
	public function compare(Column $column){

		if($this->type != $column->type){
			return false;
		}
		if($this->length != $column->length){
			return false;
		}
		if($this->default != $column->default){
			return false;
		}
		if($this->comment != $column->comment){
			return false;
		}
		if($this->unique != $column->unique){
			return false;
		}

		return true;
	}

	public function dump(){
		$res = $this->name . " " . $this->type;

		if($this->length){
			$res .= "(" . $this->length . ")";
		}
		if($this->unique){
			$res .= " unique";
		}
		if(!$this->nullable){
			$res .= " NOT NULL";
		}
		if($this->default !== null){
			$res .= " DEFAULT " . $this->default;
		}
		if($this->autoincrement){
			$res .= " AUTO_INCREMENT";
		}

		return $res;
	}

}