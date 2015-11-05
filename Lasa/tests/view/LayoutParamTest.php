<?php

class LayoutParamTest extends PHPUnit_Framework_TestCase {
	
	use ViewTestBase;
	
	private $viewLoader;
	
	function setUp(){
		parent::setUp();
		$this->viewLoader = $this->getViewLoader(__DIR__ . "/LayoutParamTest");
	}
	
	/**
	 * @test
	 */
	function 基本的なレイアウト変数(){
		$view = $this->viewLoader->load("content");
		
		ob_start();
		$view->display();
		$content = ob_get_contents();
		ob_end_clean();
		
		$this->assertNotEmpty($content);
		$this->assertContains("<p>ほげほげ</p>", $content);
	}
	
	/**
	 * @test
	 */
	function レイアウト変数だけの指定(){
		$view = $this->viewLoader->load("layout_param_only")->layout("layout");
		
		ob_start();
		$view->display();
		$content = ob_get_contents();
		ob_end_clean();
		
		$this->assertNotEmpty($content);
		$this->assertContains("<p>layout_param_only</p>", $content);
	}
	
	/**
	 * @test
	 */
	function レイアウトを親から上書きする場合(){
		$view = $this->viewLoader->load("content")->layout("layout2");
		
		ob_start();
		$view->display();
		$content = ob_get_contents();
		ob_end_clean();
		
		$this->assertNotEmpty($content);
		$this->assertContains("<div>ほげほげ</div>", $content);
		$this->assertNotContains("<p>ほげほげ</p>", $content);
	}
	
	/**
	 * @test
	 */
	function コメントでのレイアウト変数(){
		$view = $this->viewLoader->load("comment_layout_param")->layout("layout");
		
		ob_start();
		$view->display();
		$content = ob_get_contents();
		ob_end_clean();
		
		$this->assertNotEmpty($content);
		$this->assertContains("<p>comment_layout_param</p>", $content);
	}
}

