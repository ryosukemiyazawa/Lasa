<?php

namespace lasa\db\builder\method;

use lasa\db\builder\DAOMethodBuilder;
use lasa\db\Query;

class UpdateMethodBuilder extends DAOMethodBuilder {

	/**
	 * buildMethod
	 */
	public function buildMethod(\ReflectionMethod $method) {

		$params = [];
		foreach ($method->getParameters() as $param) {
			$params[$param->getName()] = $param;
		}

		if (count($params) == 1) {
			/* @var $param \ReflectionParameter */
			$param = array_shift($params);
			$reflectionType = $param->getType() . "";

			if (!$reflectionType || $reflectionType != $this->modelClassName) {
				throw new \Exception("invalid model assigned");
			}

			return $this->buildModelUpdateMethod($method);
		}

		return $this->buildParameterUpdateMethod($method);
	}

	/**
	 * Objectを引数に一個持ったUpdateメソッドを生成する
	 * @param \ReflectionMethod $method
	 */
	public function buildModelUpdateMethod(\ReflectionMethod $method) {

		/* 準備 */
		$methodName = $method->getName();
		$annotations = $this->getMethodAnnotations($method);

		$params = [];
		foreach ($method->getParameters() as $param) {
			$params[$param->getName()] = $param;
		}

		$scripts = [];
		$query = Query::update($this->tableName);
		$bind_keys = [];
		$binds = [];

		//更新対象のカラム
		$columns = $this->model;

		//where句指定
		if (isset($annotations["where"])) {
			$query->where($annotations["where"]);

			preg_match_all("/([^\s]+)\s*=\s*:([^\s]+)/", $annotations["where"], $tmp);
			foreach ($tmp[2] as $index => $key) {
				$column = $tmp[1][$index];
				$bind_keys[$key] = $column;
			}
		}

		foreach ($columns as $key => $column_array) {
			$value = array_shift($column_array);

			if ($bind_keys) {
				if (isset($bind_keys[$key])) {
					//bind名を上書きしていたパターン
					$value = $key;
				} else if ($key == "id") {
					//ok
				} else {
					$query->column($value);
				}
			} else {
				if ($key == "id") {
					$query->where("id=:id");
				} else {
					$query->column($value);
				}
			}

			if (isset($column_array["serialize"]) && $column_array["serialize"] == "json") {
				$binds[":" . $value] = 'json_encode(' . $this->buildBindCode($key, $params) . ")";
			} else {
				$binds[":" . $value] = $this->buildBindCode($key, $params);
			}
		}

		$scripts[] = '$query = ' . $query->dump() . ';' . "\n";
		$scripts[] = '$binds = ' . $this->buildBindsCode($binds) . ';' . "\n";
		$scripts[] = 'return $this->executeUpdateQuery($query, $binds);';
		return "\t" . implode("\t", $scripts);
	}


	/**
	 * 複数パラメーターをUpdateするメソッド
	 * byXか、引数にidが無い場合はエラーとなる
	 * @param \ReflectionMethod $method
	 */
	public function buildParameterUpdateMethod(\ReflectionMethod $method) {

		/* 準備 */
		$methodName = $method->getName();
		$annotations = $this->getMethodAnnotations($method);

		$params = [];
		foreach ($method->getParameters() as $param) {
			$params[$param->getName()] = $param;
		}

		$scripts = [];
		$query = Query::update($this->tableName);
		$getByX = null;
		$bind_keys = [];
		$binds = [];
		$columns = [];

		if (preg_match('/By([a-zA-Z0-9]*)$/', $methodName, $tmp)) {
			$getByX = lcfirst($tmp[1]);
		}

		//where句指定
		if (isset($annotations["where"])) {
			$query->where($annotations["where"]);
			$getByX = null;

			preg_match_all("/([^\s]+)\s*=\s*:([^\s]+)/", $annotations["where"], $tmp);
			foreach ($tmp[2] as $index => $key) {
				$column = $tmp[1][$index];
				$bind_keys[$key] = $column;
			}
		}

		if ($getByX && isset($this->model[$getByX])) {
			$column_array = $this->model[$getByX];
			$value = array_shift($column_array);
			$bind_keys[$getByX] = $column_array;
			$query->where($value . "=:" . $getByX);
		}

		//更新対象のカラムを作る
		foreach ($params as $key => $value) {

			//パラメーター名が不明な場合、where句で指定していればOK
			if (!isset($this->model[$key])) {
				if (isset($bind_keys[$key])) {
					$value = $key;
					$binds[":" . $value] = $this->buildBindCode($key, $params);
					continue;
				}
				continue;
			}

			//対象のカラム情報
			$column_array = $this->model[$key];
			$value = array_shift($column_array);

			if ($key == "id") {
				$query->where("id=:id");
				$binds[":" . $value] = $this->buildBindCode($key, $params);
				continue;
			}

			if (isset($bind_keys[$key])) {
				//where句で指定していた場合はbindとパラメーター名を揃える
				$value = $key;
			} else {
				//更新対象のカラムに指定
				$query->column($value);
			}

			//自動的にbindする
			if (isset($column_array["serialize"]) && $column_array["serialize"] == "json") {
				$binds[":" . $value] = 'json_encode(' . $this->buildBindCode($key, $params) . ")";
			} else {
				$binds[":" . $value] = $this->buildBindCode($key, $params);
			}
		}

		//エラーになるパターン
		if (!$query->columns) {
			$scripts[] = 'throw new \Exception(__CLASS__ . "#' . $methodName . ' must contain one more columns.");' . "\n";
		}

		if (!$query->wheres) {
			$scripts[] = 'throw new \Exception(__CLASS__ . "#' . $methodName . ' must contain one more where terms.");' . "\n";
		}

		$scripts[] = '$query = ' . $query->dump() . ';' . "\n";
		$scripts[] = '$binds = ' . $this->buildBindsCode($binds) . ';' . "\n";
		$scripts[] = 'return $this->executeUpdateQuery($query, $binds);';
		return "\t" . implode("\t", $scripts);
	}
}
