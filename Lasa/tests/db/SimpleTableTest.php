<?php



use lasa\db\migration\Migration;
use lasa\db\Model;
use lasa\db\TimeStamps;

/**
 * 結合とかの無いシンプルなテーブルでのCRUDのテスト
 */
class SimpleTableTest extends \PHPUnit\Framework\TestCase {

	use DBTestBase;


	/**
	 * setUp
	 * @see TestCase::setUp()
	 */
	protected function setUp(): void {
		Migration::run(Migration::create("migration1", function (Migration $m) {
			$m->table("sample_table", function ($table) {
				$table->id();
				$table->varchar("title", 25);
				$table->text("content");
				$table->timesptamps();
			});
		}));
	}

	function testInsert() {
		$obj = new SimpleTableTest_SampleTable();
		$obj->setTitle("this is title");
		$obj->setContent("this is content");
		$obj->save();

		if ($obj->getId() > 0) {
			$testObj = SimpleTableTest_SampleTable::DAO()->getById($obj->getId());
			$this->assertEquals($obj, $testObj);
		} else {
			$this->fail("save failed");
		}
	}

	function testUpdate() {
		$obj = new SimpleTableTest_SampleTable();
		$obj->setTitle("this is title");
		$obj->setContent("this is content");
		$obj->save();

		$testObj = SimpleTableTest_SampleTable::DAO()->getById($obj->getId());
		$this->assertEquals($obj, $testObj);
		$testObj->setTitle("title is updated");
		$testObj->save();

		$checkObj = SimpleTableTest_SampleTable::DAO()->getById($obj->getId());
		$this->assertEquals($testObj, $checkObj);
		$this->assertEquals($checkObj->getTitle(), $testObj->getTitle());
	}

	function testDelete() {
		$obj = new SimpleTableTest_SampleTable();
		$obj->setTitle("this is title");
		$obj->setContent("this is content");
		$obj->save();

		$objId = $obj->getId();

		$obj->delete();
		$res = SimpleTableTest_SampleTable::DAO()->getById($objId);
		$this->assertNull($res);
	}

	function tearDown(): void {
		SimpleTableTest_SampleTable::DAO()->executeUpdateQuery("delete from " . SimpleTableTest_SampleTable::DAO()->getTableName());
	}
}

/**
 * @table sample_table
 */
class SimpleTableTest_SampleTable {
	use Model, TimeStamps;

	private $title;
	private $content;

	public function getTitle() {
		return $this->title;
	}
	public function setTitle($title) {
		$this->title = $title;
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
