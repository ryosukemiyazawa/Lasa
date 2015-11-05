<?php
/*
 * DynamicViewTest.php
 */
class DynamicViewTest extends PHPUnit_Framework_TestCase {
	
	use ViewTestBase;
	
	private $viewLoader;
	
	function setUp(){
		parent::setUp();
		$this->viewLoader = $this->getViewLoader(__DIR__ . "/DynamicViewTest",[
			"debug" => false
		]);
	}
	
	/**
	 * @test
	 */
	function 動的にViewを書き換えるテスト(){
		
		$view = $this->viewLoader->load("parent",[
			"child_view_name" => "child_a",
		]);
		$content = $view->getContent();
		$this->assertContains("This is child_a", $content);
		
		$view = $this->viewLoader->load("parent",[
			"child_view_name" => "child_b",
			"label" => date("Y-m-d H:i:s")
		]);
		$content = $view->getContent();
		$this->assertContains("This is child_b", $content);
	}
	
	/**
	 * @test
	 * ただしdebug=trueの時はexceptionが発生する
	 */
	function 知らないViewを指定した時(){
		$view = $this->viewLoader->load("parent",[
			"child_view_name" => "unknown_view",
		]);
		
		$content = $view->getContent();
		$this->assertContains("render=", $content);
	}
	
	/**
	 * @test
	 */
	function 動的にViewを渡しつつ値を書き換えるテスト(){
		
		$view = $this->viewLoader->load("parent",[
			"child_view_name" => ["child_a", "label" => "hogehoge"],
		]);
		$content = $view->getContent();
		
		$this->assertContains("This is child_a", $content);
		$this->assertContains("hogehoge", $content);
	}
	
}