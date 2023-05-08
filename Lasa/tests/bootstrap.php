<?php

use lasa\Container;
use lasa\db\DB;
use lasa\db\DataSource;

define("__TESTS_DIR__", __DIR__);
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';


call_user_func(function () {

	$c = Container::getInstance();

	/**
	 * テスト用のDBの設定
	 */
	DB::connection([
		"driver" => "mysql",
		"host" => "127.0.0.1",
		"database" => "phpunit_test",
		"port" => "3305",
		"username" => "root",
		"password" => "root",
		"encoding" => "utf-8"
	]);

	DB::configure("cache_dir", sys_get_temp_dir() . "/lasaphptest");

	$c->share("pdo", function () {
		$pdo = new PDO('mysql:host=localhost; dbname=phpunit_test', 'root', '');
		return $pdo;
	});
});


trait DBTestBase {

	function getDataSource() {
		return DataSource::getDataSource();
	}

	function getColumns($tableName) {
		$res = $this->getDataSource()->executeQuery("show columns from sample_table");
		$columns = [];
		foreach ($res as $row) {
			$columns[$row["Field"]] = $row;
		}
		return $columns;
	}

	function getTableNames() {
		$res = $this->getDataSource()->executeQuery("show tables");
		$tables = [];
		foreach ($res as $row) {
			foreach ($row as $tableName) {
				$tables[] = $tableName;
			}
		}
		return $tables;
	}

	function deleteAllTables() {
		$tables = $this->getTableNames();
		foreach ($tables as $table) {
			$this->getDataSource()->executeUpdateQuery("drop table if exists {$table}");
		}
	}
}

trait ViewTestBase {

	private $loader;

	/**
	 *
	 * @param unknown $loader
	 * @return \lasa\view\Engine
	 */
	function getEngine($loader, $options = []) {
		return new \lasa\view\Engine($loader, [
			"cache" => sys_get_temp_dir() . "/lasaphptest",
			"debug" => (isset($options["debug"])) ? $options["debug"] : false,
		]);
	}

	function getViewLoader($dir, $options = []) {
		return $this->getEngine(new \lasa\view\loader\FileLoader($dir), $options);
	}

	function getView($template, Closure $func) {

		if (!$this->loader) {
			$this->loader = new \lasa\view\loader\ArrayLoader();
		}
		$loader = $this->loader;
		$key = "view-" . count($loader) . "-" . rand();
		$loader[$key] = [$template, $func];

		$engine = $this->getEngine($loader);
		$view = $engine->load($key);
		return $view;
	}
}

function clean_tmp_dir($dir) {
	$files = scandir($dir);
	foreach ($files as $file) {
		if ($file[0] == ".") continue;
		$path = $dir . DIRECTORY_SEPARATOR . $file;
		if (is_file($path)) {
			unlink($path);
		}
	}
}
