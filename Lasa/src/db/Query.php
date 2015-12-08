<?php
namespace lasa\db;

class Query{
	
	public static function select($table){
		$obj = new QueryFactory($table);
		return $obj->prefix("select")->table($table);
	}
	
	public static function insert($table){
		$obj = new InsertQueryFactory($table);
		return $obj->prefix("insert")->table($table);
	}
	public static function update($table){
		$obj = new UpdateQueryFactory($table);
		return $obj->prefix("update")->table($table);
	}
	public static function delete($table){
		$obj = new DeleteQueryFactory($table);
		return $obj->prefix("delete")->table($table);
	}
	
	protected $prefix;
	protected $table;
	protected $sql;
	protected $where;
	protected $order;
	protected $group;
	protected $having;	//new!!
	protected $distinct;
	protected $sequence;
	protected $limit;
	protected $offset;
	protected $binds = array();
	
	public function __construct($tableName){
		$this->setTable($tableName);
	}
	
	function __toString(){
	
		switch($this->prefix){
				
			case "insert":
				$sql =  $this->prefix." into ".$this->table." ".$this->sql;
				if(strlen($this->where)){
					$sql .= " where ".$this->where;
				}
				break;
					
			case "select":
				$sql =  $this->prefix." ";
	
				if($this->distinct){
					$sql .= "distinct ";
				}
	
				$sql .= $this->sql." from ".$this->table;
	
				if(strlen($this->where)){
					$sql .= " where ".$this->where;
				}
				if(strlen($this->group)){
					$sql .= " group by ".$this->group;
				}
				if(strlen($this->having)){
					$sql .= " having ".$this->having;
				}
				if(strlen($this->order)){
					$sql .= " order by ".$this->order;
				}
				
				if($this->limit){
					$sql .= " limit " . (int)$this->limit;
				}
				if($this->offset){
					$sql .= " offset " . (int)$this->offset;
				}
	
				break;
					
			case "update":
				$sql =  $this->prefix." ".$this->table." set ".$this->sql;
				if(strlen($this->where)){
					$sql .= " where ".$this->where;
				}
				break;
					
			case "delete":
				$sql =  $this->prefix." from ".$this->table;
				if(strlen($this->where)){
					$sql .= " where ".$this->where;
				}
				break;
		}
	
		return $sql;
	}
	
	/* getter setter */
	
	
	public function getPrefix(){
		return $this->prefix;
	}
	public function setPrefix($prefix){
		$this->prefix = $prefix;
		return $this;
	}
	public function getTable(){
		return $this->table;
	}
	public function setTable($table){
		$this->table = $table;
		return $this;
	}
	public function getSql(){
		return $this->sql;
	}
	public function setSql($sql){
		$this->sql = $sql;
		return $this;
	}
	public function getWhere(){
		return $this->where;
	}
	public function setWhere($where){
		$this->where = $where;
		return $this;
	}
	public function getOrder(){
		return $this->order;
	}
	public function setOrder($order){
		$this->order = $order;
		return $this;
	}
	public function getGroup(){
		return $this->group;
	}
	public function setGroup($group){
		$this->group = $group;
		return $this;
	}
	public function getHaving(){
		return $this->having;
	}
	public function setHaving($having){
		$this->having = $having;
		return $this;
	}
	public function getDistinct(){
		return $this->distinct;
	}
	public function setDistinct($distinct){
		$this->distinct = $distinct;
		return $this;
	}
	public function getSequence(){
		return $this->sequence;
	}
	public function setSequence($sequence){
		$this->sequence = $sequence;
		return $this;
	}
	public function getBinds(){
		return $this->binds;
	}
	public function setBinds($binds){
		$this->binds = $binds;
		return $this;
	}
	
	function limit($limit){
		$this->limit = (int)$limit;
	}
	
	function offset($offset){
		$this->offset = $offset;
	}
	
	public function dump(){
		
	}
}

class QueryFactory extends Query{

	function prefix($prefix){
		$this->prefix = $prefix;
		return $this;
	}

	function table($table){
		$this->_table = $table;
		$this->table = $table;
		return $this;
	}

	function join($join,$on,$type = null){
		$this->joins[] = array($join,$on,$type);
		return $this;
	}

	/**
	 * @param $columnName string
	 * @param _ mixed[optional]
	 * @return \lasa\db\QueryFactory
	 */
	function column($columnName, $_ = null){
		$args = func_get_args();
		foreach($args as $arg){
			$this->columns[] = $arg;
		}
		return $this;
	}
	
	/**
	 * カラムをリセット
	 * @return \lasa\db\QueryFactory
	 */
	function resetColumns(){
		$this->columns = [];
		return $this;
	}

	function where(){
		$args = func_get_args();
		foreach($args as $arg){
			$this->wheres[] = $arg;
		}
		return $this;
	}

	function orWhere(){
		$args = func_get_args();
		foreach($args as $arg){
			$this->orWheres[] = $arg;
		}
		return $this;
	}

	function order(){
		$args = func_get_args();
		foreach($args as $arg){
			if(is_array($arg)){
				$this->orders = array_merge($this->orders,$arg);
			}else{
				$this->orders[] = $arg;
			}
		}
		return $this;
	}

	function group(){
		$args = func_get_args();
		foreach($args as $arg){
			if(is_array($arg)){
				$this->groups = array_merge($this->groups,$arg);
			}else{
				$this->groups[] = $arg;
			}
		}
		return $this;
	}

	function distinct($flag = true){
		$this->disinct = $flag;
		return $this;
	}

	function having($having){
		$args = func_get_args();
		foreach($args as $arg){
			$this->havings[] = $arg;
		}
		return $this;
	}

	public $columns = array();
	public $wheres = array();
	public $orWheres = array();
	public $joins = array();
	public $groups = array();
	public $havings = array();
	public $orders = array();

	protected function finalize(){
		if(!empty($this->columns)){
			$this->sql = implode(",",$this->columns);
		}
		
		$this->where = "";

		if(!empty($this->wheres)){
			$this->where = implode(" AND ",$this->wheres);
		}
		if(!empty($this->orWheres)){
			if(strlen($this->where)){
				$this->where .= " OR ";
			}
			$this->where .= implode(" OR ",$this->orWheres);
		}

		if(!empty($this->havings)){
			$this->having = implode(" AND ",$this->havings);
		}

		if(!empty($this->orders)){
			$this->order = implode(",",$this->orders);
		}
		if(!empty($this->groups)){
			$this->group = implode(",",$this->groups);
		}

		if(!empty($this->joins)){
			$table = $this->_table;
			foreach($this->joins as $array){
				$join = ($array[2]) ? " " . $array[2] . " join " : " join ";
				$table .= " " . $join . $array[0] . " on (".$array[1].")";
			}
			$this->table = $table;
		}
	}
	
	
	/**
	 * __toString
	 * @see \lasa\db\Query::__toString()
	 */
	public function __toString() {
		$this->finalize();
		return parent::__toString();
	}

	public function dump(){
		
		$this->finalize();
		
		$scripts = [];
		$scripts[] = "(new \\".Query::class."(" . var_export($this->table, true) . "))";
		$scripts[] = "->setPrefix('select')";
		$scripts[] = "->setSql(" . var_export($this->sql, true) . ")";
		if($this->distinct)$scripts[] = "->setDistinct(" . var_export($this->distinct, true) . ")";
		if($this->group)$scripts[] = "->setGroup(" . var_export($this->group, true) . ")";
		if($this->having)$scripts[] = "->setHaving(" . var_export($this->having, true) . ")";
		if($this->order)$scripts[] = "->setOrder(" . var_export($this->order, true) . ")";
		if($this->where)$scripts[] = "->setWhere(" . var_export($this->where, true) . ")";
		return implode("",$scripts);
	}
}


class InsertQueryFactory extends QueryFactory{
	
	function dump(){
		$this->finalize();
		
		$scripts = [];
		$scripts[] = "(new \\".Query::class."(" . var_export($this->table, true) . "))";
		$scripts[] = "->setPrefix('insert')";
		$scripts[] = "->setSql(" . var_export($this->sql, true) . ")";
		if($this->where)$scripts[] = "->setWhere(" . var_export($this->where, true) . ")";
		return implode("",$scripts);
	}

	function finalize(){
		parent::finalize();
		
		$columns = array();
		foreach($this->columns as $column){
			if($column == "id")continue;
			$columns[] = $column;
		}
		
		$sql = "(" . implode(",", $columns) . ")";
		$sql .= " values(" . implode(",", array_map(function($a){ return ":" . $a; }, $columns)) . ")";
		$this->sql = $sql;
	}
}

class UpdateQueryFactory extends QueryFactory{
	
	function dump(){
		$this->finalize();
	
		$scripts = [];
		$scripts[] = "(new \\".Query::class."(" . var_export($this->table, true) . "))";
		$scripts[] = "->setPrefix('update')";
		$scripts[] = "->setSql(" . var_export($this->sql, true) . ")";
		if($this->where)$scripts[] = "->setWhere(" . var_export($this->where, true) . ")";
		return implode("",$scripts);
	}
	
	function finalize(){
		parent::finalize();
		
		$columns = [];
		foreach($this->columns as $column){
			if($column == "id")continue;
			
			$columns[] = $column . "=:" . $column;
		}
		$this->sql = implode(",", $columns);
	}
	
}

class DeleteQueryFactory extends QueryFactory{

	function dump(){
		$this->finalize();

		$scripts = [];
		$scripts[] = "(new \\".Query::class."(" . var_export($this->table, true) . "))";
		$scripts[] = "->setPrefix('delete')";
		if($this->where)$scripts[] = "->setWhere(" . var_export($this->where, true) . ")";
		return implode("",$scripts);
	}

}