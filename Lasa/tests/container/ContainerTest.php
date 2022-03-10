<?php

use lasa\Container;


/**
 * コンテナのテスト
 */
class ContainerTest extends \PHPUnit\Framework\TestCase {

	function testObject() {

		$container = new Container();

		$container->bind(Hoge::class, function () {
			return new Hoge("assign value", 1000);
		});

		//自動的にコンストラクタを補完してくれるパターン
		$fuga = $container->make(Fuga::class);

		$this->assertNotNull($fuga->hoge);
		$this->assertEquals("assign value", $fuga->hoge->value);
		$this->assertEquals(1000, $fuga->hoge->value2);
	}

	function testSingleton() {

		$container = new Container();

		$container->share(Hoge::class, function () {
			return new Hoge("default value");
		});

		/* @var $hoge Hoge */
		$hoge = $container->make(Hoge::class);
		$hoge->value = "書き換えました";

		//singletonパターンで副作用のある動作ってのはちょっとアレだけど

		$hoge2 = $container->make(Hoge::class);
		$this->assertEquals("書き換えました", $hoge2->value);
		$this->assertEquals($hoge, $hoge2);

		//destoryで作り直しが出来ます
		$container->destory();

		$hoge3 = $container->make(Hoge::class);
		$this->assertNotEquals($hoge2, $hoge3);
		$this->assertEquals("default value", $hoge3->value);
	}
}

class Hoge {
	var $value;
	var $value2;

	function __construct($value, $value2 = -1) {
		$this->value = $value;
		$this->value2 = $value2;
	}
}

class Fuga {

	var $hoge;

	function __construct(Hoge $hoge) {
		$this->hoge = $hoge;
	}
}
