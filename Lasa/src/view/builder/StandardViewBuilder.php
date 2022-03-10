<?php
/*
 * StandardViewBuilder.php
 */

namespace lasa\view\builder;

use lasa\view\component\HTMLView;
use lasa\view\Engine;
use lasa\view\component\HTMLViewComponent;

class StandardViewBuilder extends ViewBuilder {

	private $conf = [];
	private $template;
	private $annotations = [];

	public function __construct(array $config, $template = null) {
		$this->conf = $config;
		if ($template) {
			$this->template = $template;
		}
	}

	public function loadTemplate($scriptPath) {
		$this->template = $this->parseTemplate($scriptPath);
	}

	/**
	 * @return compiled_html
	 */
	function compile(Engine $factory) {
		$obj = new HTMLViewComponent($this->template);
		$obj->_holderName = $this->getHolderName();

		$func = $this->_createClosureWithConfig($obj, $this->conf);
		$func($obj);

		$this->compileAnnotations($obj);

		return $obj->compile();
	}

	public function _createClosureWithConfig(HTMLViewComponent $viewComponent, $config) {
		return function (HTMLView $view) use ($viewComponent, $config) {

			if (isset($config["@extends"])) {
				$loader = \lasa\view\Engine::currentEngine()->getLoader();
				$builder = $loader->getBuilder($config["@extends"]);
				if (!$builder || !$builder instanceof self) {
					return;
				}

				//先に読み込んで拡張を行う
				$func = $this->_createClosureWithConfig($viewComponent, $builder->conf);
				$func($viewComponent);
			}

			//まずはプロパティの処理を行う
			foreach ($config as $key => $value) {

				if (empty($key)) continue;

				//制御命令系
				if ($key[0] != "@") {
					continue;
				}
				//レイアウト変数
				if ($key == "@layout") {
					if (is_string($value)) {
						$viewComponent->layout($value);
					} else if (is_array($value)) {
						if (isset($value[0])) {
							$layout_name = array_shift($value);
							$viewComponent->layout($layout_name);
						}
						$viewComponent->layoutParams($value);
					}
				}
			} /* アノテーションの処理 */

			foreach ($config as $key => $value) {

				//制御命令系
				if (is_string($key) && $key[0] == "@") {
					continue;
				}

				if ($value instanceof \Closure) {
					$view->apply($value);
					continue;
				}

				if (!is_array($value)) {
					$component = $value;
					$value = [];
				} else {
					$component = array_shift($value);
				}

				//View系
				if (in_array($component, ["label", "link", "image", "raw", "json"])) {
					$funcName = "add" . ucfirst($component);
					$defValue = "";
					if (isset($value[0]) && (is_string($value[0]) || is_numeric($value[0]))) {
						$defValue = \array_shift($value);
					}
					$opt = $value;
					$view->$funcName($key,  $defValue, $opt);
					continue;
				}

				//Form系
				if (in_array($component, ["input", "check", "select", "textarea"])) {
					if ($component == "textarea") {
						$funcName = "addTextArea";
					} else {
						$funcName = "add" . ucfirst($component);
					}
					$name = array_shift($value);
					$opt = $value;
					$view->$funcName($key, $name, "", $opt);
					continue;
				}

				//特殊(view)
				if ($component == "view") {
					$viewName = array_shift($value);
					$opt = $value;
					$view->addView($key, $viewName, $opt);
					continue;
				}

				//特殊(list)
				if ($component == "list") {
					if (isset($value[0]) && is_array($value[0])) {
						$conf = array_shift($value);
					} else {
						$conf = $value;
					}
					$view->addList($key, $this->_createClosureWithConfig($view, $conf));
					continue;
				}

				//特殊(Condition)
				if ($component == "if" || $component == "condition") {
					$defValue = ($value) ? array_shift($value) : null;
					$view->addCondition($key, $defValue);
					continue;
				}

				//特殊(Form)
				if ($component == "form") {
					$component = new \lasa\view\component\HTMLFormComponent($value);
					$defValue = ($value) ? array_shift($value) : null;
					if ($defValue) $component->setDefault($defValue);
					$view->addComponent($key, $component);
					continue;
				}

				//特殊(composer)
				if ($component == "composer") {
					$component = new \lasa\view\component\HTMLViewComposer($value);
					$view->addComponent($key, $component);
					continue;
				}

				throw new \Exception("[" . __CLASS__ . "]unknown component:" . $component);
			}
		};
	}

	/**
	 * コードを解析する
	 * @param string $path
	 */
	private function parseTemplate($path) {
		//parserの作成
		$codes = file_get_contents($path);
		$parser = PHPTokenParser::getParser($codes);

		//コメントがあるかどうか判定する
		$comment = $parser->getDocComment();
		if ($comment) {
			$this->parseAnnotations($comment);
		}

		return $parser->getBody();
	}

	/**
	 * アノテーションを解析する
	 */
	private function parseAnnotations($docComment) {
		$tmp = [];
		$values = [];
		if (preg_match_all("#@([^\s]+)\s?(.*)#", $docComment, $tmp)) {
			foreach ($tmp[1] as $index => $key) {
				$value = $tmp[2][$index];
				if (strlen($value) < 1) $value = true;
				$values[$key] = $value;
			}
		}

		$this->annotations = $values;

		//extendsの処理
		if (isset($this->annotations["extends"])) {
			$this->conf["@extends"] = $this->annotations["extends"];
		}
	}

	private function compileAnnotations(HTMLViewComponent $viewComponent) {
		$layout_name = null;
		$layout_params = [];
		foreach ($this->annotations as $key => $value) {
			if ($key == "layout") {
				$layout_name = $value;
			}
			if (strpos($key, "layout.") !== false) {
				$layout_key = substr($key, strlen("layout."));
				$layout_params[$layout_key] = $value;
			}
		}

		if ($layout_name) {
			$viewComponent->layout($layout_name);
		}

		if ($layout_params) {
			$viewComponent->layoutParams($layout_params);
		}
	}
}
