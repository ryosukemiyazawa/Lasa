<?php

use lasa\view\component\HTMLView;
class ListedLabelTest extends PHPUnit_Framework_TestCase {
	use ViewTestBase;
	
	/**
	 * 単純な出力
	 */
	function testOutput(){
		
		$target_string1 = "テストです1|" . microtime(true);
		$target_string2 = "テストです2|" . microtime(true);
		
		/* コメントに入れる */
		$view = $this->getView('<!-- list:hoge /-->', function($view){
			$view->addLabel("hoge");
		});
		$view->values(["hoge" => [$target_string1, $target_string2]]);
		$content = $view->getContent();
		$this->assertContains($target_string1, $content);
		$this->assertContains($target_string2, $content);
		
		/* pに入れる */
		$view = $this->getView('<p list:hoge>ほげほげ</p>', function($view){
			$view->addLabel("hoge");
		});
		$view->values(["hoge" => [$target_string1, $target_string2]]);
		$content = $view->getContent();
		$this->assertContains($target_string1, $content);
		$this->assertContains($target_string2, $content);
		
	}
	
	
}

