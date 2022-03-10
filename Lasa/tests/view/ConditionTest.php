<?php



class ConditionTest extends \PHPUnit\Framework\TestCase {
	use ViewTestBase;

	function testTrue() {

		//数値の0
		$view = $this->getView('<!-- if:hoge -->表示<!-- /if:hoge -->', function ($view) {
			$view->addLabel("hoge");
		});
		$view->values(["hoge" => 0]);
		$content = $view->getContent();
		$this->assertMatchesRegularExpression("#^表示$#", $content);

		//文字列false
		$view = $this->getView('<!-- if:hoge -->表示<!-- /if:hoge -->', function ($view) {
			$view->addLabel("hoge");
		});
		$view->values(["hoge" => "false"]);
		$content = $view->getContent();
		$this->assertMatchesRegularExpression("#^表示$#", $content);

		//falseを指定した時
		$view = $this->getView('<!-- if:hoge -->表示<!-- /if:hoge -->', function ($view) {
			$view->addLabel("hoge");
		});
		$view->values(["hoge" => false]);
		$content = $view->getContent();
		$this->assertEquals(0, strlen($content));

		//空文字
		$view = $this->getView('<!-- if:hoge -->表示<!-- /if:hoge -->', function ($view) {
			$view->addLabel("hoge");
		});
		$view->values(["hoge" => '']);
		$content = $view->getContent();
		$this->assertEquals(0, strlen($content));
	}
}
