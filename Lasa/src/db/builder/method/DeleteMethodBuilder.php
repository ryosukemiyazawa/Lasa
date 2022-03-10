<?php

namespace lasa\db\builder\method;

use lasa\db\builder\DAOMethodBuilder;
use ReflectionMethod;
use lasa\db\Query;

class DeleteMethodBuilder extends DAOMethodBuilder {

	/**
	 * buildMethod
	 * @see \lasa\db\builder\DAOMethodBuilder::buildMethod()
	 */
	public function buildMethod(ReflectionMethod $method) {

		$className = $method->getDeclaringClass()->getName();
		$methodName = $method->getName();

		$annotations = $this->getMethodAnnotations($method);

		$params = [];
		foreach ($method->getParameters() as $param) {
			$params[$param->getName()] = $param;
		}

		$scripts = [];
		$query = Query::delete($this->tableName);
		$getByX = null;
		if (preg_match('/By([a-zA-Z0-9]*)$/', $methodName, $tmp)) {
			$getByX = lcfirst($tmp[1]);
		}

		$binds = [];
		$columns = [];

		if (isset($annotations["where"])) {
			$query->where($annotations["where"]);
			$getByX = null;

			preg_match_all("/:([^\s]+)/", $annotations["where"], $tmp);
			foreach ($tmp[1] as $key) {
				if (isset($params[$key])) {
					$binds[":" . $key] = $this->buildBindCode($key, $params);
					continue;
				}
				$binds[$key] = $key;
			}
		} else {

			if ($getByX) {	//getbyXを指定した時は引数のみで作る
				foreach ($params as $name => $param) {
					if (!isset($this->model[$name])) {
						throw new \Exception("unknown property:" . $name);
					}
					$columns[$name] = $this->model[$name];
				}
			} else {
				if (isset($this->model["id"])) {
					$columns["id"] = $this->model["id"];
				} else {
					throw new \Exception("[" . __CLASS__ . "]delete method is ambigious");
				}
			}

			foreach ($columns as $key => $column_array) {
				$value = array_shift($column_array);
				if ($getByX) {
					if ($key == "id") {
						continue;
					} else if ($key == $getByX) {
						$query->where($value . "=:" . $value);
					} else {
						$query->column($value);
					}
				} else {
					if ($key == "id") {
						$query->where("id = :id");
					} else {
						$query->column($value);
					}
				}
				$binds[":" . $value] = $this->buildBindCode($key, $params);
			}
		}

		$scripts[] = '$query = ' . $query->dump() . ';' . "\n";
		$scripts[] = '$binds = ' . $this->buildBindsCode($binds) . ';' . "\n";
		$scripts[] = 'return $this->executeUpdateQuery($query, $binds);';
		return "\t" . implode("\t", $scripts);
	}
}
