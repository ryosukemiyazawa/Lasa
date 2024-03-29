<?php



class SameNameTest extends \PHPUnit\Framework\TestCase {

	use ViewTestBase;

	private $viewLoader;

	function setUp(): void {
		parent::setUp();
		$this->viewLoader = $this->getViewLoader(__DIR__ . "/SameNameTest");
	}

	/**
	 * @test
	 */
	function 入れ子にした時の同じ名前の処理() {

		$view = $this->viewLoader->load("same_name_view_parent", [
			"same_name_view" => ["same_name_view" => "hoge"],
			"hoge" => 100,
		]);
		$content = $view->getContent();

		$this->assertStringContainsString("same_name_view=hoge", $content);
	}

	/**
	 * @test
	 */
	function 同じ名前でリストを作る() {
		$view = $this->viewLoader->load("same_name_list_parent", [
			"same_name_list" => [
				"same_name_list" => [
					["hoge" => 1],
					["hoge" => 2],
					["hoge" => 3],
				]
			]
		]);

		$content = $view->getContent();
		$this->assertStringContainsString("<p>1</p>", $content);
		$this->assertStringContainsString("<p>2</p>", $content);
		$this->assertStringContainsString("<p>3</p>", $content);
	}
}
