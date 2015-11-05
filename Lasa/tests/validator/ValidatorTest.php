<?php
use lasa\validator\Validator;
class ValidatorTest extends PHPUnit_Framework_TestCase {
	
	function testRequire(){
		
		$data = [
			"aaa" => 10,
			"bbb" => 20,
			"ccc" => 0
		];
		$v = new Validator($data);
		$v->required("aaa");
		$v->required("bbb");
		$v->required("ccc");
		$this->assertTrue($v->success());
		$this->assertEmpty($v->errors());
		
		//キーが存在しない場合
		$v->required("ddd");
		$this->assertTrue($v->fails());
		$this->assertArrayHasKey("ddd.required", $v->errors());
		
		//空文字はOK
		$v = new Validator(["empty" => ""]);
		$v->required("empty")->isString();
		$this->assertTrue($v->success());
		
		//nullは不可
		$v = new Validator(["empty" => null]);
		$v->required("empty")->isString();
		$this->assertTrue($v->fails());
		
		//エラーメッセージをカスタマイズするパターン
		$v = new Validator([]);
		//requiredの場合は次のバリデーションを実行しない
		$v->required("test","エラーメッセージをカスタマイズ")->regex("test","#[0-9]+#","正規表現マッチ");
		$this->assertEquals("エラーメッセージをカスタマイズ", $v->message("test"));
		$this->assertEquals(["エラーメッセージをカスタマイズ"], $v->messages("test"));
		
		//フォーマットのテスト
		$this->assertEquals("<p>エラーメッセージをカスタマイズ</p>", $v->message("test","<p>:message</p>"));
		$this->assertEquals(["<p>エラーメッセージをカスタマイズ</p>"], $v->messages("test","<p>:message</p>"));
		
		//matchのテスト
		$v = new Validator(["string1" => "<some text>","string2" => "<some text>"]);
		$v->matches("string1","string2");
		$this->assertTrue($v->success());
		
		$v = new Validator(["string1" => "<some text>","string2" => "<wrong text>"]);
		$v->matches("string1","string2");
		$this->assertTrue($v->fails());
		
		$v = new Validator(["string1" => "<some text>","string2" => "<wrong text>"]);
		$v->required("string1")->matches("string2");
		$this->assertTrue($v->fails());
		
		
	}
	
	function testCustomLabelMessage(){
		
		//エラーメッセージをカスタマイズするパターン
		$v = new Validator(["test" => "あいうえおあいうえお"],["regex" => ":labelのフォーマットが無効です"]);
		$v->item("test","項目１")->required()->regex("#^[0-9]+$#");
		$this->assertEquals("<p>項目１のフォーマットが無効です</p>", $v->message("test","<p>:message</p>"));
		
	}
	
	function testCustomValidation(){
	
		//クロージャーでカスタマイズする
		$v = new Validator(["test" => "あいうえおあいうえお"]);
		$v->item("test","項目１")->check("nyaa",function($value){
			return false;
		},":labelはエラーになりました");
		$this->assertEquals("<p>項目１はエラーになりました</p>", $v->message("test","<p>:message</p>"));
		
		//エラーメッセージを指定する
		$v = new Validator(["test" => "あいうえおあいうえお","test2" => "test2の値"],["nyaa" => ":labelはエラーです"]);
		$v->item("test","項目１")->check("nyaa", function($value) use ($v){
			return $v->value("test") == "test2";
		});
		$this->assertEquals("<p>項目１はエラーです</p>", $v->message("test","<p>:message</p>"));
	
	}
	
	function testArrayValidation(){
		$v = new Validator(["test" => 100]);
		$v->isArray("test");
		$this->assertTrue($v->fails());
		
		$v = new Validator(["test" => [0,1,2]]);
		$v->isArray("test")->max(3)->min(3);
		$this->assertTrue($v->success());
		
		//クロージャーを指定することで各要素のチェックが可能
		$v = new Validator(["test" => [0,1,2]]);
		$v->isArray("test",function($value){
			return is_numeric($value) && $value > 0;
		});
		$this->assertTrue($v->fails());
		
		$v = new Validator(["test" => [1,2,3]]);
		$v->isArray("test",function($value){
			return is_numeric($value) && $value > 0 && $value < 4;
		});
		$this->assertTrue($v->success());
	}
	
	
	function testNestedValidation(){
		
		//よくあるこういう配列をテストする感じです
		$values = [
			"User" => [
				"name" => "User Name",
				"password" => "password_test",
				"password_confirm" => "password_test"
			]
		];
		$v = new Validator($values);
		
		$v->isArray("User")->each(function(Validator $v2){
			$v2->required("name");
			$v2->required("password");
			$v2->required("password_confirm")->matches("password");
		});
		$this->assertTrue($v->success());
		
		$v->isArray("User")->each(function(Validator $v2){
			$v2->required("name");
			$v2->required("password");
			$v2->required("password_confirm")->matches("password");
			$v2->required("unknown_item");
		});
		$this->assertTrue($v->fails());
		$this->assertArrayHasKey("User.unknown_item.required", $v->errors());
		
	}
	
	function testMessageFormat(){
		$v = new Validator([]);
		$v->item("test1","項目１")->required("error1");
		$v->item("test2","項目２")->required("error2");
		
		$format = "<li data-item=\":key\" class=\"error-:type\">[:label]:message</li>";
		$test1error = '<li data-item="test1" class="error-required">[項目１]error1</li>';
		$test2error = '<li data-item="test2" class="error-required">[項目２]error2</li>';
		
		$this->assertEquals($test1error, $v->message("test1", $format));
		$this->assertEquals($test2error, $v->message("test2", $format));
		
	}
	
	function testCheckedValue(){
		$values = [
			"User" => [
				"id" => "this is malformed value",
				"name" => "User Name",
				"password" => "password_test",
				"password_confirm" => "password_test"
			]
		];
		$expected_values = [
			"User" => [
				"name" => "User Name",
				"password" => "password_test",
				"password_confirm" => "password_test"
			]
		];
		
		$v = new Validator($values);
		$v->isArray("User")->each(function(Validator $v2){
			$v2->required("name");
			$v2->required("password");
			$v2->required("password_confirm")->matches("password");
		});
		$cleanuped_value = $v->cleanup();
		$this->assertEquals($expected_values, $cleanuped_value);
		
	}
	
	
}