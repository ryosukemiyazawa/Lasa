<?php
/*
 * CountQueryTest.php
 */

/**
 * @table hoge_tbl
 */
class CountQueryTestDummyEntity{
	
	use \lasa\db\Model;
	
	private $userId;
	
	/**
	 * @column hoge_type
	 */
	private $type;

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
	
	
}

abstract class CountQueryTestDummyEntityDAO extends \lasa\db\DAO{
	
	public $last_query;
	public $last_binds = [];
	
	/**
	 * @where userId = :userId AND hoge_type = :type
	 */
	abstract function countHogeHoge($userId, $type);
	
	/**
	 * @where userId = :userId AND hoge_type = :type
	 */
	abstract function countHogeHoge2(CountQueryTestDummyEntity $obj);
	
	public function executeQuery($sql, $binds = array(), $func = null){
		$this->last_query = $sql . "";
		$this->last_binds = $binds;
		return [];
	}
	
}

class CountQueryTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * @test
	 */
	function SQL生成でwhere句を指定したパターンその１(){
		$dao = CountQueryTestDummyEntity::DAO();
		
		$this->assertTrue($dao instanceof CountQueryTestDummyEntityDAO);
		
		$dao->countHogeHoge(100, 90);
		
		$this->assertContains("userId = :userId AND hoge_type = :type", $dao->last_query);
		$this->assertEquals(100, $dao->last_binds[":userId"]);
		$this->assertEquals(90, $dao->last_binds[":type"]);
	}
	
	/**
	 * @test
	 */
	function SQL生成でwhere句を指定したパターンその２(){
		$dao = CountQueryTestDummyEntity::DAO();
		
		$this->assertTrue($dao instanceof CountQueryTestDummyEntityDAO);
		
		$obj = new CountQueryTestDummyEntity();
		$obj->setUserId(100);
		$obj->setType(90);
		$dao->countHogeHoge2($obj);
		
		$this->assertContains("userId = :userId AND hoge_type = :type", $dao->last_query);
		$this->assertEquals(100, $dao->last_binds[":userId"]);
		$this->assertEquals(90, $dao->last_binds[":type"]);
	}
	
}