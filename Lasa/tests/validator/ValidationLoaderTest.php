<?php



use lasa\validator\Validator;
use lasa\validator\ValidatorLoader;

/**
 * ValidatorLoaderをテストします。
 */
class ValidationLoaderTest extends \PHPUnit\Framework\TestCase {

	function getFileLoader() {
		return new ValidatorLoader(__DIR__ . "/validations");
	}

	function testSimple() {
		$loader = $this->getFileLoader();

		$data = [
			"name" => "user name",
			"password" => "user password",
			"password_confirm" => "user password"
		];
		$v = $loader->check("user", $data);
		$this->assertTrue($v->success());
	}

	function testNested() {
		$loader = $this->getFileLoader();

		$data = [
			"User" => [
				"id" => "malformed value",
				"name" => "user name",
				"password" => "user password",
				"password_confirm" => "user password"
			]
		];
		$expected_values = [
			"User" => [
				"name" => "user name",
				"password" => "user password",
				"password_confirm" => "user password"
			]
		];

		$v = new Validator($data);
		$v->isArray("User")->each(function ($v2) use ($loader) {
			$loader->load($v2, "user");
		});
		$this->assertTrue($v->success());
		$cleanuped_value = $v->cleanup();
		$this->assertEquals($expected_values, $cleanuped_value);
	}

	function testUnknownValidator() {
		$loader = $this->getFileLoader();

		try {
			$loader->check("unknown_validator_name|" . microtime(true), []);
			$this->fail("expected exception");
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}
}
