<?php

namespace test\testnamespace;

use lasa\db\migration\Migration;
use lasa\db\Model;
use lasa\db\TimeStamps;
use lasa\db\DataSource;

/**
 * namespaceのあるentityの場合
 */
class NameSpaceTableTest extends \PHPUnit_Framework_TestCase {
	
	use \DBTestBase;
	
	public $tableName = "sample_table2";
	
	function setUp(){
		$tableName = $this->tableName;
		Migration::run(Migration::create("sample_table2", function(Migration $m) use($tableName){
			$m->table($tableName, function($table){
				$table->id();
				$table->varchar("title", 25);
				$table->text("content");
				$table->timesptamps();
					
			});
		}));
	}
	
	function testCrud(){
		
		$obj = new TestTable2();
		$obj->title = "this is title";
		$obj->content = "this is content";
		$obj->save();
		
		$obj2 = TestTable2::DAO()->getById($obj->getId());
		$this->assertEquals($obj2, $obj);
		
		$obj2->title = "title is updated";
		$obj2->save();
		
		$obj3 = TestTable2::DAO()->getById($obj->getId());
		$this->assertEquals($obj3->title, $obj2->title);
		
		$obj3->delete();
		
		$obj4 = TestTable2::DAO()->getById($obj->getId());
		$this->assertNull($obj4);
		
	}
	
	function tearDown(){
		DataSource::getDataSource()->executeUpdateQuery("drop table if exists " . $this->tableName);
	}
	
}

/**
 * @table sample_table2
 */
class TestTable2{
	use Model,Timestamps;
	
	var $title;
	var $content;
}


