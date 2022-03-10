<?php

namespace lasa\view;

class Render extends ViewContainer {

	private $path = null;
	private $name = null;

	public function __construct($name, $path, $values = array()) {
		$this->name = $name;
		$this->path = $path;
		$this->values($values);
	}

	function getPath() {
		return $this->path;
	}

	function getName() {
		return $this->name;
	}

	/**
	 * Viewを表示する
	 */
	public function render() {

		$view = $this;

		if ($this->path && file_exists($this->path)) {
			include($this->path);
		}
	}

	public function getContent() {
		ob_start();
		$this->render();
		$html = ob_get_clean();
		return $html;
	}
}
