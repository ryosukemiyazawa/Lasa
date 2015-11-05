<?php

use lasa\db\migration\Migration;
use lasa\db\DataSource;
use lasa\db\migration\schema\Table;

/**
 * Migrationを使って
 * 	1.テーブルを作る
 * 	2.現在の状況を確認する
 *  3.書き換える
 *  をやってみるテストです
 */
class MigrationTest extends PHPUnit_Framework_TestCase {
	
	use DBTestBase;
	
	function testCreateTableA(){
		
		$createTestTable = Migration::create("migration1", function(Migration $m){
			$m->table("sample_table", function($table){
				$table->id();
				$table->varchar("title", 25);
				$table->text("content");
				$table->timesptamps();
					
			});
		});
		
		$createTestTable2 = Migration::create("migration2", function(Migration $m){
			$m->table("sample_table2", function($table){
				$table->id();
				$table->varchar("title", 25);
				$table->text("content");
				$table->timesptamps();
					
			});
		});
		
		Migration::run([$createTestTable, $createTestTable2]);
		
		try{
			$res = $this->getDatasource()->executeQuery("select * from sample_table");
		}catch(Exception $e){
			$this->fail("sample_tableの作成に失敗");
		}
		
		try{
			$res = $this->getDatasource()->executeQuery("select * from sample_table2");
		}catch(Exception $e){
			$this->fail("sample_table2の作成に失敗");
		}
		
	}
	
	function testModifyTable(){
		
		$createTestTable = Migration::create("migration1", function(Migration $m){
			$m->table("sample_table", function($table){
				$table->id();
				$table->varchar("title", 25);
				$table->text("content");
				$table->timesptamps();
					
			});
		});
		
		$modifyTable = Migration::create("migration2", function(Migration $m){
			$m->table("sample_table", function($table){
				$table->text("fugafuga");
			});
		});
		
		//テーブル作成
		Migration::run([$createTestTable, $modifyTable]);
		
		$columns = $this->getColumns("sample_table");
		$this->assertArrayHasKey("id", $columns);
		$this->assertArrayHasKey("title", $columns);
		$this->assertArrayHasKey("content", $columns);
		$this->assertArrayHasKey("fugafuga", $columns);
		$this->assertArrayHasKey("created_at", $columns);
		$this->assertArrayHasKey("updated_at", $columns);
		
		//カラムの削除
		$createTestTable = Migration::create("migration1", function(Migration $m){
			$m->table("drop_column_table", function($table){
				$table->id();
				$table->varchar("title", 25);
				$table->text("content");
				$table->timesptamps();
					
			});
		});
		$deleteColumn = Migration::create("migration3", function(Migration $m){
			$m->table("sample_table", function(Table $table){
				$table->dropColumn("fugafuga");
			});
		});
		Migration::run($createTestTable);
		Migration::run($deleteColumn);
		$columns = $this->getColumns("sample_table");
		$this->assertArrayNotHasKey("fugafuga", $columns, "カラムの削除に失敗");
	}
	
	
	/**
	 * 全てのテーブルを削除する
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		$this->deleteAllTables();
	}

	
}