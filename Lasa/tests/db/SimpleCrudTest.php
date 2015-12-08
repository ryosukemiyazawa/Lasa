<?php
use lasa\db\migration\Migration;
use lasa\db\migration\schema\Table;

/*
 * SimpleCrudTest.php
 */
class SimpleCrudTest extends PHPUnit_Framework_TestCase{
	
	use DBTestBase;
	
	/**
	 * setUp
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		Migration::run(Migration::create("migration1", function(Migration $m){
			$m->table("sample_table_simple_crud", function(Table $table){
				$table->id();
				$table->integer("user_id")->notnull();
				$table->varchar("title", 25);
				$table->text("content");
				$table->timesptamps();
					
			});
		}));
	}
	
	function tearDown(){
		SimpleCrudTest_SampleTable::DAO()->executeUpdateQuery("delete from " . SimpleCrudTest_SampleTable::DAO()->getTableName());
	}
	
	/**
	 * @test
	 */
	function 挿入テスト(){
		
		//全て削除
		SimpleCrudTest_SampleTable::DAO()->deleteByUserId(100);
		$res = SimpleCrudTest_SampleTable::DAO()->countByUserId(100);
		$this->assertEquals(0, $res);
		
		$obj = new SimpleCrudTest_SampleTable();
		$obj->setUserId(100);
		$obj->setTitle("obj01");
		$obj->setContent("obj01 content");
		$obj->save();
		
		$obj = new SimpleCrudTest_SampleTable();
		$obj->setUserId(100);
		$obj->setTitle("obj02");
		$obj->setContent("obj02 content");
		$obj->save();
		
		$obj = new SimpleCrudTest_SampleTable();
		$obj->setUserId(100);
		$obj->setTitle("obj03");
		$obj->setContent("obj03 content");
		$obj->save();
		
		//３件登録
		$res = SimpleCrudTest_SampleTable::DAO()->countByUserId(100);
		$this->assertEquals(3, $res);
		
		$result = SimpleCrudTest_SampleTable::DAO()->getByUserId(100);
		foreach ($result as $obj) {
			$this->assertContains("obj", $obj->getTitle());
			$this->assertContains("content", $obj->getContent());
		}
		
		//タイトルを一括で変更
		SimpleCrudTest_SampleTable::DAO()->updateTitleByUserId(100, "猫大好き");
		$result = SimpleCrudTest_SampleTable::DAO()->getByUserId(100);
		foreach ($result as $obj) {
			$this->assertEquals("猫大好き", $obj->getTitle());
		}
		
		
	}
	
	/**
	 * @test
	 */
	function 挿入更新削除テスト(){
		
		/* @var $dao SimpleCrudTest_SampleTableDAO */
		$dao = SimpleCrudTest_SampleTable::DAO();
	
		$obj = new SimpleCrudTest_SampleTable();
		$obj->setUserId(100);
		$obj->setTitle("新規作成");
		$obj->setContent("新規作成しました");
		$obj->save();
		
		$checkObj = $dao->getById($obj->getId());
		$this->assertEquals($obj->getTitle(), $checkObj->getTitle());
		$this->assertEquals($obj->getContent(), $checkObj->getContent());
		
		//更新テスト
		$obj->setTitle("更新");
		$obj->setContent("更新しました");
		$obj->save();
		
		$checkObj = $dao->getById($obj->getId());
		$this->assertEquals($obj->getTitle(), $checkObj->getTitle());
		$this->assertEquals($obj->getContent(), $checkObj->getContent());
		
		//削除
		$obj->delete();
		$checkObj = $dao->getById($obj->getId());
		$this->assertNull($checkObj);
		
	}
}

/**
 * @table sample_table_simple_crud
 */
class SimpleCrudTest_SampleTable{
	
	use \lasa\db\Model,\lasa\db\TimeStamps;
	
	private $title;
	private $userId;
	private $content;

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	public function getUserId() {
		return $this->userId;
	}

	public function setUserId($userId) {
		$this->userId = $userId;
		return $this;
	}

	public function getContent() {
		return $this->content;
	}

	public function setContent($content) {
		$this->content = $content;
		return $this;
	}
	
}

abstract class SimpleCrudTest_SampleTableDAO extends \lasa\db\DAO{
	
	abstract function insert(SimpleCrudTest_SampleTable $obj);
	abstract function update(SimpleCrudTest_SampleTable $obj);
	abstract function delete($id);
	abstract function deleteByUserId($userId);
	
	abstract function countByUserId($userId);
	
	/**
	 * @return SimpleCrudTest_SampleTable
	 * @param $id
	 */
	abstract function getById($id);
	
	/**
	 * @return SimpleCrudTest_SampleTable[]
	 */
	abstract function getByUserId($userId);
	
	abstract function updateTitleByUserId($userId, $title);
	
	
}