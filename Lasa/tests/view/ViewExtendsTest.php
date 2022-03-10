<?php



/*
 * ViewExtendsTest.php
 * Viewの拡張が出来るようにする
 */

class ViewExtendsTest extends \PHPUnit\Framework\TestCase {

	use ViewTestBase;

	private $viewLoader;

	function setUp(): void {
		parent::setUp();
		$this->viewLoader = $this->getViewLoader(__DIR__ . "/ViewExtendsTest", [
			"debug" => true
		]);
	}

	/**
	 * @test
	 */
	function シンプルな拡張のテストする() {

		$view = $this->viewLoader->load("base", [
			"label" => "this is base",
		]);
		$content = $view->getContent();
		$this->assertStringContainsString("<p>this is base</p>", $content);


		$view = $this->viewLoader->load("extends", [
			"label" => "this is extends",
		]);
		$content = $view->getContent();
		$this->assertStringContainsString("<div>this is extends</div>", $content);


		$view = $this->viewLoader->load("extends_annotation", [
			"label" => "アノテーションでextendsした場合",
		]);
		$content = $view->getContent();
		$this->assertStringContainsString("<div>アノテーションでextendsした場合</div>", $content);
	}

	/**
	 * @test
	 */
	function 拡張して機能追加する() {
		$view = $this->viewLoader->load("extends_plus", [
			"label" => "アノテーションでextendsした場合",
			"label2" => "機能追加です"
		]);
		$content = $view->getContent();
		$this->assertStringContainsString("<div>アノテーションでextendsした場合</div>", $content);
		$this->assertStringContainsString("<div>機能追加です</div>", $content);
	}
}
