<?php



/*
 * SelectQueryTest.php
 */

/**
 * @table hoge_tbl
 */
class DummyEntity {

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

abstract class DummyEntityDAO extends \lasa\db\DAO {

	public $last_query;
	public $last_binds = [];

	/**
	 * @where userId = :userId AND hoge_type = :type
	 */
	abstract function findHogeHoge($userId, $type);

	/**
	 * @where userId = :userId AND hoge_type = :type
	 */
	abstract function findHogeHoge2(DummyEntity $obj);

	public function executeQuery($sql, $binds = array(), $func = null) {
		$this->last_query = $sql . "";
		$this->last_binds = $binds;
		return [];
	}
}

class SelectQueryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @test
	 */
	function SQL生成でwhere句を指定したパターンその１() {
		$dao = DummyEntity::DAO();

		$this->assertTrue($dao instanceof DummyEntityDAO);

		$dao->findHogeHoge(100, 90);

		$this->assertStringContainsString("userId = :userId AND hoge_type = :type", $dao->last_query);
		$this->assertEquals(100, $dao->last_binds[":userId"]);
		$this->assertEquals(90, $dao->last_binds[":type"]);
	}

	/**
	 * @test
	 */
	function SQL生成でwhere句を指定したパターンその２() {
		$dao = DummyEntity::DAO();

		$this->assertTrue($dao instanceof DummyEntityDAO);

		$obj = new DummyEntity();
		$obj->setUserId(100);
		$obj->setType(90);
		$dao->findHogeHoge2($obj);

		$this->assertStringContainsString("userId = :userId AND hoge_type = :type", $dao->last_query);
		$this->assertEquals(100, $dao->last_binds[":userId"]);
		$this->assertEquals(90, $dao->last_binds[":type"]);
	}
}
