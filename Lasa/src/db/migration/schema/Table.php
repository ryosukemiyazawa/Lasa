<?php
namespace lasa\db\migration\schema;

class Table{

	private $table;
	private $columnList = array();
	private $comment = "";
	private $engine = "InnoDB";
	private $charset = "UTF8";


	public $check = false;
	public $delete = false;

	public function __construct($tableName){
		$this->table = $tableName;
	}

	public function dump(){
		$columns = array();
		$constraint = array();
		foreach($this->columnList as $column){
			$columns[] = $column->dump();
				
			if($column->reference){
				$constraint[] = "foreign key(" . $column->name . ") references " . $column->reference[0] . "(" . $column->reference[1] . ")";
			}
		}

		if($constraint){
			$columns = array_merge($columns, $constraint);
		}

		$res = "table " . $this->table . "(\n\t" . implode(",\n\t",$columns) . "\n)";

		if($this->engine){
			$res .= " ENGINE=" . $this->engine;
		}
		if($this->charset){
			$res .= " DEFAULT CHARSET=" . $this->charset;
		}

		return $res;
	}

	/**
	 * @return Column
	 */
	public function id(){
		$column = new Column("id");
		$column->autoincrement = true;
		$column->type = "integer";
		$column->nullable = false;
		$column->primary = true;

		$this->columnList[$column->name] = $column;
		return $column;
	}

	/**
	 * @return Column
	 */
	public function integer($columnName){
		$column = new Column($columnName);
		$column->type = "integer";
		$this->columnList[$column->name] = $column;
		return $column;
	}

	/**
	 * @return Column
	 */
	public function text($columnName, $length = null){
		$column = new Column($columnName);
		$column->type = "text";
		$column->length = $length;
		$column->nullable = true;
		$this->columnList[$column->name] = $column;
		return $column;
	}

	/**
	 * @return Column
	 */
	public function longtext($columnName){
		$column = new Column($columnName);
		$column->type = "longtext";
		$column->nullable = true;
		$this->columnList[$column->name] = $column;
		return $column;
	}

	/**
	 * @return Column
	 */
	public function varchar($columnName, $length = 255){
		$column = new Column($columnName);
		$column->type = "varchar";
		$column->length = $length;
		$column->nullable = true;
		$this->columnList[$column->name] = $column;
		return $column;
	}

	/**
	 * @return Column
	 */
	public function float($columnName){
		$column = new Column($columnName);
		$column->type = "float";
		$column->nullable = true;
		$this->columnList[$column->name] = $column;
		return $column;
	}

	/**
	 * @return Column
	 */
	public function binary($columnName, $length = null){
		$column = new Column($columnName);
		$column->type = "binary";
		$column->length = $length;
		$this->columnList[$column->name] = $column;
		return $column;
	}

	/**
	 * @return Column
	 */
	public function timesptamps(){
		$this->integer("created_at");
		$this->integer("updated_at");
	}

	/**
	 * @param string $columnName
	 * @return Column|null
	 */
	public function column($columnName){

		if(isset($this->columnList[$columnName])){
			return $this->columnList[$columnName];
		}

		return null;
	}

	public function dropTable(){
		$this->delete = true;
	}

	/**
	 * @return Column
	 */
	public function dropColumn($columnName){
		$column = $this->column($columnName);
		if(!$column){
			$column = new Column($columnName);
			$this->columnList[$columnName] = $column;
		}
		
		$column->delete = true;
		return $column;
	}

	public function columnNames(){
		return array_keys($this->columnList);
	}


	/**
	 * MyIsam or InnoDB
	 * Only active SCHEMA is MySQL
	 * @param string $engine
	 */
	public function engine($engine){
		$this->engine = $engine;
	}

	public function charset($charset){
		$this->charset = $charset;
	}

	public function comment($comment){
		$this->comment = $comment;
	}

	public function getName(){
		return $this->table;
	}
	public function getEngine(){
		return $this->engine;
	}
	public function setEngine($engine){
		$this->engine = $engine;
		return $this;
	}
	public function getCharset(){
		return $this->charset;
	}
	public function setCharset($charset){
		$this->charset = $charset;
		return $this;
	}

}