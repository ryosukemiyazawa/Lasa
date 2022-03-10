<?php


use lasa\view\component\HTMLView;

class LabelTest extends \PHPUnit\Framework\TestCase {
	use ViewTestBase;

	/**
	 * 単純な出力
	 */
	function testOutput() {

		$target_string = "テストです|" . microtime(true);

		/* コメントに入れる */
		$view = $this->getView('<!-- :hoge /-->', function ($view) {
			$view->addLabel("hoge");
		});
		$view->values(["hoge" => $target_string]);
		$this->assertEquals($target_string, $view->getContent());

		/* 普通のタグに入れる場合 */
		$view = $this->getView('<span :hoge>これはどうなる</span>', function ($view) {
			$view->addLabel("hoge");
		});
		$view->values(["hoge" => $target_string]);
		$this->assertEquals("<span>" . $target_string . "</span>", $view->getContent());
	}

	/**
	 * 属性値を書き換えるテスト
	 */
	function testAttributes() {
		$target_string = "テストです|" . microtime(true);

		/* コメントに入れる */
		$view = $this->getView('<!-- :hoge /-->', function ($view) {
			$view->addLabel("hoge");
		});
		$view->values(["hoge" => $target_string]);
		$this->assertEquals($target_string, $view->getContent());

		/* 普通のタグに入れる場合 */
		$view = $this->getView('<span :hoge>これはどうなる</span>', function ($view) {
			$view->addLabel("hoge")->setOptions(["@class" => "hoge_class_name"]);
		});
		$view->values(["hoge" => $target_string]);
		$this->assertEquals("<span class=\"hoge_class_name\">" . $target_string . "</span>", $view->getContent());

		/* 別パターン */
		$view = $this->getView('<span :hoge>これはどうなる</span>', function ($view) {
			$view->addLabel("hoge")->setAttribute("class", "hoge_class_name");
		});
		$view->values(["hoge" => $target_string]);
		$this->assertEquals("<span class=\"hoge_class_name\">" . $target_string . "</span>", $view->getContent());
	}

	/**
	 * エンコードされるか確認
	 */
	function testHtmlencode() {
		$target_string = "<script>alert(0)</script>";

		$view = $this->getView('<!-- :hoge /-->', function ($view) {
			$view->addLabel("hoge");
		});
		$view->values(["hoge" => $target_string]);
		$this->assertNotEquals($target_string, $view->getContent());
		$this->assertTrue(strpos($view->getContent(), "<script>") === false);
	}

	/**
	 * 初期値を入れる場合
	 */
	function testDefaultValue() {
		$target_string = "初期値";

		$view = $this->getView('<!-- :hoge /-->', function (HTMLView $view) use ($target_string) {
			$view->addLabel("hoge")->setDefault("初期値");
		});
		$this->assertEquals($target_string, $view->getContent());

		//別パターン initで初期値を指定するパターン
		//valueで書いた方がわかりやすいけど、initで書くと与えられた値に対しての加工が出来る
		$view = $this->getView('<!-- :test /-->', function ($view) {
			$view->addLabel("test");

			$view->apply(function ($view) {
				$view->value("test", "初期値");
			});
		});
		$this->assertEquals($target_string, $view->getContent());
	}
}
