<?php

namespace lasa\validator;

use ReflectionClass;

class ValidatorLoader {

	private $_base = null;
	private $_validatorClassName = Validator::class;

	/**
	 *
	 * @param string $directory
	 */
	function __construct($base_directory, $className = null) {
		$this->_base = $base_directory;
		if ($className) {
			$this->_validatorClassName = $className;
		}
	}

	/**
	 *
	 * @param string $name
	 * @param [] $values
	 * @throws \Exception
	 * @return \lasa\validator\Validator
	 */
	function check($name, $values) {
		$class = $this->_validatorClassName;
		$func = $this->_getFunc($name);

		$reflection = new \ReflectionFunction($func);
		$params = $reflection->getParameters();
		if (count($params) > 0) {
			/* @var $param \ReflectionParameter */
			$param = $params[0];
			$reflectionType = $param->getType() . "";
			if ($reflectionType) {
				$reflectionClass = new ReflectionClass($reflectionType);
				if ($reflectionClass->isInstantiable()) {
					$refClassName = $reflectionClass->getName();
					if ($refClassName != Validator::class) {
						$class = $refClassName;
					}
				}
			}
		}

		$v = new $class($values);
		if (is_callable($func)) {
			$func($v);
		}

		return $v;
	}

	function load(Validator $v, $name) {
		$func = $this->_getFunc($name);
		if (is_callable($func)) {
			$func($v);
		}

		return $v;
	}

	function _getFunc($name) {
		$path = $this->_base . DIRECTORY_SEPARATOR . $name . ".php";
		if (strpos($name, ".") !== false) {
			$path = null;
		}

		$func = null;
		if (!$path || !file_exists($path)) {
			throw new \Exception("unknown validation: ${name}");
		}

		$func = include($path);
		if (!($func instanceof \Closure)) {
			throw new \Exception("invalid validation: ${name}");
		}
		return $func;
	}
}
