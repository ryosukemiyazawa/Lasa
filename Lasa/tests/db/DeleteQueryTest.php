<?php


/*
 * DeleteQueryTest.php
 */

/**
 * @table hoge_tbl
 */
class DeleteQueryTestEntity {

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

abstract class DeleteQueryTestEntityDAO extends \lasa\db\DAO {

	public $last_query;
	public $last_binds = [];

	/**
	 * @where userId = :userId AND hoge_type = :type
	 */
	abstract function deleteHogeHoge($userId, $type);

	abstract function delete($id);

	abstract function deleteByType($type);

	public function executeQuery($sql, $binds = array(), $func = null) {
		$this->last_query = $sql . "";
		$this->last_binds = $binds;
		return [];
	}

	public function executeUpdateQuery($sql, $binds = array()) {
		$this->last_query = $sql . "";
		$this->last_binds = $binds;
		return true;
	}
}

class DeleteQueryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @test
	 */
	function SQL生成でwhere句を指定したパターンその１() {
		$dao = DeleteQueryTestEntity::DAO();

		$this->assertTrue($dao instanceof DeleteQueryTestEntityDAO);

		$dao->deleteHogeHoge(100, 90);

		$this->assertStringContainsString("userId = :userId AND hoge_type = :type", $dao->last_query);
		$this->assertEquals(100, $dao->last_binds[":userId"]);
		$this->assertEquals(90, $dao->last_binds[":type"]);
	}

	/**
	 * @test
	 */
	function SQL生成でwhere句を指定したパターンByXその２() {
		$dao = DeleteQueryTestEntity::DAO();

		$this->assertTrue($dao instanceof DeleteQueryTestEntityDAO);

		$obj = new DeleteQueryTestEntity();
		$obj->setUserId(100);
		$obj->setType(90);
		$dao->deleteByType($obj->getType());

		$this->assertStringContainsString("hoge_type=:hoge_type", $dao->last_query);
		$this->assertEquals($obj->getType(), $dao->last_binds[":hoge_type"]);
	}
}
