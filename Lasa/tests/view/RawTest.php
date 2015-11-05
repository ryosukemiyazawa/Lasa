<?php

class RawTest extends PHPUnit_Framework_TestCase {
	
	use ViewTestBase;
	
	/**
	 * 単純な出力
	 */
	function testOutput(){
		
		$target_string = "<b>テストです|" . microtime(true) . "</b>";
		
		/* コメントに入れる */
		$view = $this->getView('<!-- :hoge /-->', function($view){
			$view->addRaw("hoge");
		});
		$view->values(["hoge" => $target_string]);
		$this->assertEquals($target_string, $view->getContent());
		
		/* 普通のタグに入れる場合 */
		$view = $this->getView('<span :hoge>これはどうなる</span>', function($view){
			$view->addRaw("hoge");
		});
		$view->values(["hoge" => $target_string]);
		$this->assertEquals("<span>" . $target_string . "</span>", $view->getContent());
		
	}
	
	
}

