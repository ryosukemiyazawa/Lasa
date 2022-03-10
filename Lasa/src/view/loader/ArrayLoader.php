<?php

namespace lasa\view\loader;

use ArrayAccess;
use Countable;

class ArrayLoader implements LoaderInterface, ArrayAccess, Countable {

	private $container = array();


	/* (non-PHPdoc)
	 * @see \lasa\view\loader\LoaderInterface::getBuilder()
	 */
	public function getBuilder($name) {

		if (isset($this->container[$name])) {
			list($template, $func) = $this->container[$name];

			if ($func instanceof \Closure) {
				$compiler = new \lasa\view\builder\ClosureViewBuilder($func);
				$compiler->setTemplate($template);
			} else if (is_array($func)) {
				$compiler = new \lasa\view\builder\StandardViewBuilder($func, $template);
			}

			return $compiler;
		}


		return null;
	}

	private function getClosureCode(\Closure $closure) {
		$reflection = new \ReflectionFunction($closure);

		// Open file and seek to the first line of the closure
		$file = new \SplFileObject($reflection->getFileName());
		$file->seek($reflection->getStartLine() - 1);

		// Retrieve all of the lines that contain code for the closure
		$code = '';
		while ($file->key() < $reflection->getEndLine()) {
			$line = $file->current();
			$line = ltrim($line);
			$code .= $line;
			$file->next();
		}

		return $code;
	}

	/* (non-PHPdoc)
	 * @see \lasa\view\loader\LoaderInterface::getCacheName()
	 */
	public function getCacheName($name) {

		if (isset($this->container[$name])) {
			return $name . "_" . md5($name . "|" . $this->container[$name][0] . "|" . spl_object_hash($this->container[$name][1])) . ".php";
		}

		return $name . "_" . md5(self::class . $name) . ".php";
	}

	/* (non-PHPdoc)
	 * @see \lasa\view\loader\LoaderInterface::isChanged()
	 */
	public function isChanged($name, $time) {
		return false;
	}

	/* array access */

	public function offsetSet($offset, $value): void {
		if (is_null($offset)) {
			$this->container[] = $value;
		} else {
			$this->container[$offset] = $value;
		}
	}

	public function offsetExists($offset): bool {
		return isset($this->container[$offset]);
	}

	public function offsetUnset($offset): void {
		unset($this->container[$offset]);
	}

	public function offsetGet($offset): mixed {
		return isset($this->container[$offset]) ? $this->container[$offset] : null;
	}

	public function count(): int {
		return count($this->container);
	}
}
