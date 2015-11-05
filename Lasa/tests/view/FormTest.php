<?php

use lasa\view\component\HTMLView;
class FormTest extends PHPUnit_Framework_TestCase {

	use ViewTestBase;
	
	
	function testSelect(){
		$view = $this->getView('<select :myselect></select>'."\n".'<p :myselect></p>', function(HTMLView $view){
			$items = [
				"100" => "<p>100 value</p>",
				"200" => "<strong>200 value</strong>",
				"300" => date("Y-m-d H:i:s")
			];
			$view->addSelect("myselect", "myselect-name")->setAttribute("items", $items);
		});
		$view->values(["myselect" => 200]);
		
		$content = $view->getContent();
		$this->assertRegExp("#name=\"myselect-name\"#", $content);
		$this->assertRegExp("#value=\"100\"#", $content);
		$this->assertRegExp("#value=\"200\" selected#", $content);
		
		//書き方２
		$view = $this->getView('<select :myselect></select>'."\n".'<p :myselect></p>', function($view){
			$items = [
				"100" => "<p>100 value</p>",
				"200" => "<strong>200 value</strong>"
			];
			$view->addSelect("myselect", ["@class" => "form select", "name" => "myselect-name2", "items" => $items]);
		});
		$view->values(["myselect" => 100]);
		
		$content = $view->getContent();
		$this->assertRegExp("#name=\"myselect-name2\"#", $content);
		$this->assertRegExp("#value=\"100\" selected#", $content);
		$this->assertRegExp("#value=\"200\"#", $content);
		
	}
	
	function testInput(){
		$view = $this->getView('<input :myinput>', function($view){
			$view->addInput("myinput", "myinput-name");
		});
		
		$content = $view->getContent();
		$this->assertRegExp("#name=\"myinput-name\"#", $content);
		$this->assertRegExp("#value=\"\"#", $content);
		
		//valueを指定
		$view->values(["myinput" => "<myinput-value>"]);
		$content = $view->getContent();
		$this->assertRegExp("#name=\"myinput-name\"#", $content);
		$this->assertRegExp("#value=\"&lt;myinput-value&gt;\"#", $content);
		
		//valueで指定
		$view = $this->getView('<input :myinput>', function($view){
			$view->addInput("myinput", "myinput-name")->setDefault("myinput-dvalue");
		});
		$content = $view->getContent();
		$this->assertRegExp("#value=\"myinput-dvalue\"#", $content);
		
		//valueで指定した上で上書きする
		$view = $this->getView('<input :myinput>', function($view){
			$view->addInput("myinput", "myinput-name")->setDefault("myinput-dvalue");
		});
		$view->values(["myinput" => "myinput-value-overwrite"]);
		$content = $view->getContent();
		$this->assertRegExp("#value=\"myinput-value-overwrite\"#", $content);
		
		//inputタグ以外に出力
		$view = $this->getView('value=<!-- :myinput -->', function($view){
			$view->addInput("myinput", "myinput-name");
		});
		$view->values(["myinput" => "myinput-value"]);
		$content = $view->getContent();
		$this->assertRegExp("#value=myinput-value#", $content);
	}
	
	
	function testCheck(){
		$view = $this->getView('<input type="checkbox" value="fuga" :myinput>', function($view){
			$view->addCheck("myinput", "myinput-name");
		});
		$view->values(["myinput" => "hoge"]);
		$content = $view->getContent();
		$this->assertRegExp("#name=\"myinput-name\" value=\"fuga\"#", $content);
		$this->assertNotRegExp("#checked#", $content);
		
		$view->values(["myinput" => "fuga"]);
		$content = $view->getContent();
		$this->assertRegExp("#checked#", $content);
		
		//valueをコードで指定（arrayで指定）
		$view = $this->getView('<input type="checkbox" value="fuga" :myinput>', function($view){
			$view->addCheck("myinput", ["@name" => "myinput-name","@value" => "neko"]);
		});
		$view->values(["myinput" => "inu"]);
		$content = $view->getContent();
		$this->assertRegExp("#name=\"myinput-name\" value=\"neko\"#", $content);
		$this->assertNotRegExp("#checked#", $content);
		
		$view->values(["myinput" => "neko"]);
		$content = $view->getContent();
		$this->assertRegExp("#checked#", $content);
		
		
		
	}
	
	function testTextarea(){
		$view = $this->getView("\t\t<textarea :myinput>", function($view){
			$view->addTextArea("myinput", "myinput-name","<test1\ntest2>");
		});
		$content = $view->getContent();
		
		$this->assertRegExp("#name=\"myinput-name\"#", $content);
		$this->assertRegExp("#>&lt;test1\ntest2&gt;</textarea>#", $content);
		
		//textareaタグ以外
		$view = $this->getView("\t\t<p :myinput>", function($view){
			$view->addTextArea("myinput", "myinput-name","<test1\ntest2>");
		});
		$content = $view->getContent();
		$this->assertRegExp("#<p>&lt;test1\ntest2&gt;</p>#", $content);
		
		//inputタグを指定した時はinputとして振る舞う
		$view = $this->getView("<input :myinput>", function($view){
			$view->addTextArea("myinput", "myinput-name","<test1\ntest2>");
		});
		$content = $view->getContent();
		$this->assertRegExp("#<input[^>]+/>#", $content);
		$this->assertRegExp("#name=\"myinput-name\"#", $content);
		$this->assertRegExp("#value=\"&lt;test1\ntest2&gt;\"#", $content);
	}
	
}