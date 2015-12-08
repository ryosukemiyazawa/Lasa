<?php
/*
 * UpdateQueryTestEntity.php
 */

/**
 * @table hoge_tbl
 */
class UpdateQueryTestEntity{
	
	use \lasa\db\Model;
	
	private $userId;
	
	/**
	 * @column hoge_type
	 */
	private $type;
	
	/**
	 * @column hoge_value
	 */
	private $value;

	public function getUserId() {
		return $this->userId;
	}

	public function setUserId($userId) {
		$this->userId = $userId;
		return $this;
	}

	public function getType() {
		return $this->type;
	}

	public function setType($type) {
		$this->type = $type;
		return $this;
	}

	public function getValue() {
		return $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
		return $this;
	}
	
	
	
}

abstract class UpdateQueryTestEntityDAO extends \lasa\db\DAO{
	
	public $last_query;
	public $last_binds = [];
	
	/**
	 * パターン１　Objectを引数に一個持ったUpdateメソッド
	 * @param UpdateQueryTestEntity $obj
	 */
	abstract function update(UpdateQueryTestEntity $obj);
	
	/**
	 * パターン２　Objectを引数に一個持ったUpdateメソッド（where句で条件を指定）
	 * @where user_id=:userId AND hoge_type=:type
	 */
	abstract function updateType2(UpdateQueryTestEntity $obj);
	
	/**
	 * パターン３　パラメーターを引数に持ったUpdateメソッド（パラメーターにidがある場合）
	 * -> パラメーターにidが無い場合はエラーとするテストを作る
	 */
	abstract function updateParams1($id, $type, $userId);
	
	/**
	 * パターン４　パラメーターを引数に持ったUpdateメソッド（where句指定）
	 * @where user_id=:userId
	 */
	abstract function updateParams2($type, $userId);
	
	/**
	 * パターン４_2　パラメーターを引数に持ったUpdateメソッド（where句指定）
	 * @where user_id=:hogehoge
	 */
	abstract function updateParams3($type, $hogehoge);
	
	/**
	 * パターン４_3　パラメーターを引数に持ったUpdateメソッド（byX指定）
	 */
	abstract function updateParamsByUserId($type, $userId);
	
	/**
	 * パターン5_idが無いからエラーになるパターン
	 * @param $type
	 * @param $userId
	 * @param $value
	 */
	abstract function updateInvalidPattern($type, $userId, $value);
	
	/**
	 * パターン5_全部whereに書いちゃうパターン
	 * @where user_id=:userId AND hoge_type=:type
	 */
	abstract function updateInvalidPattern2($type, $userId);
	
	public function executeQuery($sql, $binds = array(), $func = null){
		$this->last_query = $sql . "";
		$this->last_binds = $binds;
		return [];
	}
	
	public function executeUpdateQuery($sql, $binds = array()){
		$this->last_query = $sql . "";
		$this->last_binds = $binds;
		return true;
	}
	
}