<?php
/*
 * UpdateQueryTest.php
 */
class UpdateQueryTest extends PHPUnit_Framework_TestCase {
	
	protected function setUp() {
		require_once __DIR__ . "/inc/UpdateQueryTestEntity.php";
	}

	
	/**
	 * @test
	 */
	function パターン１_Objectを引数に一個持ったUpdateメソッド(){
		$dao = UpdateQueryTestEntity::DAO();
		
		$this->assertTrue($dao instanceof UpdateQueryTestEntityDAO);
		
		$obj = new UpdateQueryTestEntity();
		$obj->setId(80);
		$obj->setUserId(100);
		$obj->setType(90);
		$dao->update($obj);
		
		//update hoge_tbl set hoge_value=:hoge_value,user_id=:userId,hoge_type=:type where id=:id
		list($query, $where) = explode(" where ", $dao->last_query);
		
		$this->assertContains("hoge_value=:hoge_value", $query);
		$this->assertContains("id=:id", $where);
		$this->assertContains("user_id=:user_id", $query);
		$this->assertContains("hoge_type=:hoge_type", $query);
		$this->assertContains("hoge_value=:hoge_value", $query);
		$this->assertEquals(100, $dao->last_binds[":user_id"]);
		$this->assertEquals(90, $dao->last_binds[":hoge_type"]);
		$this->assertEquals($obj->getId(), $dao->last_binds[":id"]);
	}
	
	
	/**
	 * @test
	 */
	function パターン２_Objectを引数に一個持ったUpdateメソッド（where句で条件を指定）(){
		$dao = UpdateQueryTestEntity::DAO();
		$this->assertTrue($dao instanceof UpdateQueryTestEntityDAO);
		
		$obj = new UpdateQueryTestEntity();
		$obj->setId(80);
		$obj->setUserId(100);
		$obj->setType(90);
		$dao->updateType2($obj);
		
		//update hoge_tbl set hoge_value=:hoge_value where user_id=:userId AND hoge_type=:type
		list($query, $where) = explode(" where ", $dao->last_query);
		
		$this->assertContains("hoge_value=:hoge_value", $query);
		
		$this->assertNotContains("id=:id", $where);
		$this->assertContains("user_id=:userId", $where);
		$this->assertContains("hoge_type=:type", $where);
		$this->assertEquals(80, $dao->last_binds[":id"]);
		$this->assertEquals(100, $dao->last_binds[":userId"]);
		$this->assertEquals(90, $dao->last_binds[":type"]);
	}
	
	
	/**
	 * @test
	 */
	function パターン３_パラメーターを引数に持ったUpdateメソッド(){
		$dao = UpdateQueryTestEntity::DAO();
		$this->assertTrue($dao instanceof UpdateQueryTestEntityDAO);
		
		$obj = new UpdateQueryTestEntity();
		$obj->setId(80);
		$obj->setUserId(100);
		$obj->setType(90);
		
		$dao->updateParams1($obj->getId(), $obj->getType(), $obj->getUserId());
		
		//update hoge_tbl set hoge_type=:hoge_type,user_id=:user_id where id=:id
		list($query, $where) = explode(" where ", $dao->last_query);
		
		$this->assertContains("hoge_type=:hoge_type", $query);
		$this->assertContains("user_id=:user_id", $query);
		$this->assertContains("id=:id", $where);
		
		$this->assertEquals($obj->getId(), $dao->last_binds[":id"]);
		$this->assertEquals($obj->getUserId(), $dao->last_binds[":user_id"]);
		$this->assertEquals($obj->getType(), $dao->last_binds[":hoge_type"]);
	}
	
	/**
	 * @test
	 */
	function パターン４_パラメーターを引数に持ったUpdateメソッドでwhere句指定(){
		$dao = UpdateQueryTestEntity::DAO();
		$this->assertTrue($dao instanceof UpdateQueryTestEntityDAO);
		
		$obj = new UpdateQueryTestEntity();
		$obj->setId(80);
		$obj->setUserId(100);
		$obj->setType(90);
		
		$dao->updateParams2($obj->getType(), $obj->getUserId());
		
		//update hoge_tbl set hoge_type=:hoge_type where user_id=:userId
		list($query, $where) = explode(" where ", $dao->last_query);
		
		$this->assertContains("hoge_type=:hoge_type", $query);
		$this->assertContains("user_id=:userId", $where);
		$this->assertNotContains("id=:id", $where);
		
		$this->assertEquals($obj->getUserId(), $dao->last_binds[":userId"]);
		$this->assertEquals($obj->getType(), $dao->last_binds[":hoge_type"]);
	}
	
	/**
	 * @test
	 */
	function パターン４_2_パラメーターを引数に持ったUpdateメソッドでwhere句指定(){
		$dao = UpdateQueryTestEntity::DAO();
		$this->assertTrue($dao instanceof UpdateQueryTestEntityDAO);
		
		$obj = new UpdateQueryTestEntity();
		$obj->setId(80);
		$obj->setUserId(100);
		$obj->setType(90);
		
		$dao->updateParams3($obj->getType(), $obj->getUserId());
		
		//update hoge_tbl set hoge_type=:hoge_type where user_id=:userId
		list($query, $where) = explode(" where ", $dao->last_query);
		
		$this->assertContains("hoge_type=:hoge_type", $query);
		$this->assertContains("user_id=:hogehoge", $where);
		$this->assertNotContains("id=:id", $where);
		
		$this->assertEquals($obj->getUserId(), $dao->last_binds[":hogehoge"]);
		$this->assertEquals($obj->getType(), $dao->last_binds[":hoge_type"]);
	}
	
	/**
	 * @test
	 */
	function パターン４_3_パラメーターを引数に持ったUpdateメソッドでbyX指定(){
		$dao = UpdateQueryTestEntity::DAO();
		$this->assertTrue($dao instanceof UpdateQueryTestEntityDAO);
		
		$obj = new UpdateQueryTestEntity();
		$obj->setId(80);
		$obj->setUserId(100);
		$obj->setType(90);
		
		$dao->updateParamsByUserId($obj->getType(), $obj->getUserId());
		
		//update hoge_tbl set hoge_type=:hoge_type where user_id=:userId
		list($query, $where) = explode(" where ", $dao->last_query);
		
		$this->assertContains("hoge_type=:hoge_type", $query);
		$this->assertContains("user_id=:userId", $where);
		$this->assertNotContains("id=:id", $where);
		
		$this->assertEquals($obj->getUserId(), $dao->last_binds[":userId"]);
		$this->assertEquals($obj->getType(), $dao->last_binds[":hoge_type"]);
	}
	
	
	/**
	 * @test
	 */
	function パターン5_エラーになるパターン１(){
		
		$dao = UpdateQueryTestEntity::DAO();
		$this->assertTrue($dao instanceof UpdateQueryTestEntityDAO);
		
		$obj = new UpdateQueryTestEntity();
		$obj->setId(80);
		$obj->setUserId(100);
		$obj->setType(90);
		
		try{
			$dao->updateInvalidPattern($obj->getType(), $obj->getUserId(), $obj->getValue());
			$this->assertFalse(true, "エラーが呼ばれなかった");
		}catch(\Exception $e){
			$this->assertTrue(true);
			$this->assertContains("where", $e->getMessage());
			$this->assertContains("updateInvalidPattern", $e->getMessage());
		}
		
	}
	
	/**
	 * @test
	 */
	function パターン5_エラーになるパターン２(){
		
		$dao = UpdateQueryTestEntity::DAO();
		$this->assertTrue($dao instanceof UpdateQueryTestEntityDAO);
		
		$obj = new UpdateQueryTestEntity();
		$obj->setId(80);
		$obj->setUserId(100);
		$obj->setType(90);
		
		try{
			$dao->updateInvalidPattern2($obj->getType(), $obj->getUserId(), $obj->getValue());
			$this->assertFalse(true, "エラーが呼ばれなかった");
		}catch(\Exception $e){
			$this->assertTrue(true);
			$this->assertContains("column", $e->getMessage());
			$this->assertContains("updateInvalidPattern", $e->getMessage());
		}
		
	}
	
}
