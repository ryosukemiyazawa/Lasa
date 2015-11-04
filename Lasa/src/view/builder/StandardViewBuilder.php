<?php
/*
 * StandardViewBuilder.php
 */
namespace lasa\view\builder;
use lasa\view\component\HTMLView;
use lasa\view\Engine;
use lasa\view\component\HTMLViewComponent;

class StandardViewBuilder extends ViewBuilder{

	private $conf = [];
	private $template;

	public function __construct(array $config, $template){
		$this->conf = $config;
		$this->template = $template;
	}

	/**
	 * @return compiled_html
	 */
	function compile(Engine $factory){
		$obj = new HTMLViewComponent($this->template);
		$obj->_holderName = $this->getHolderName();

		$func = $this->createClosureWithConfig($this->conf);
		$func($obj);

		return $obj->compile();
	}

	public function createClosureWithConfig($config){
		return function(HTMLView $view) use ($config){
			foreach($config as $key => $value){

				if($value instanceof \Closure){
					$view->apply($value);
					continue;
				}

				if(!is_array($value)){
					$component = $value;
					$value = [];
				}else{
					$component = array_shift($value);
				}

				//View系
				if(in_array($component, ["label","link","image","raw","json"])){
					$funcName = "add" . ucfirst($component);
					$defValue = "";
					if(isset($value[0]) && (is_string($value[0]) || is_numeric($value[0]))){
						$defValue = \array_shift($value);
					}
					$opt = $value;
					$view->$funcName($key,  $defValue, $opt);
					continue;
				}

				//Form系
				if(in_array($component, ["input","check","select","textarea"])){
					if($component == "textarea"){
						$funcName = "addTextArea";
					}else{
						$funcName = "add" . ucfirst($component);
					}
					$name = array_shift($value);
					$opt = $value;
					$view->$funcName($key, $name, "", $opt);
					continue;
				}

				//特殊(view)
				if($component == "view"){
					$viewName = array_shift($value);
					$opt = $value;
					$view->addView($key, $viewName, $opt);
					continue;
				}

				//特殊(list)
				if($component == "list"){
					if(isset($value[0]) && is_array($value[0])){
						$conf = array_shift($value);
					}else{
						$conf = $value;
					}
					$view->addList($key, $this->createClosureWithConfig($conf));
					continue;
				}

				//特殊(Condition)
				if($component == "if" || $component == "condition"){
					$defValue = ($value) ? array_shift($value) : null;
					$view->addCondition($key, $defValue);
					continue;
				}
				
				//特殊(Form)
				if($component == "form"){
					$component = new \lasa\view\component\HTMLFormComponent($value);
					$defValue = ($value) ? array_shift($value) : null;
					if($defValue)$component->setDefault($defValue);
					$view->addComponent($key, $component);
					continue;
				}

				//特殊(composer)
				if($component == "composer"){
					$component = new \lasa\view\component\HTMLViewComposer($value);
					$view->addComponent($key, $component);
					continue;
				}

				throw new \Exception("[" . __CLASS__ . "]unknown component:" . $component);
			}
		};
	}

	public static function loadTemplate($path){

		$path = realpath($path);
		$dir = dirname($path);
		$filename = basename($path);
		list($name, $ext) = explode(".", $filename);

		//同名の.htmlがあればそれを使う
		$templatePath = $dir . DIRECTORY_SEPARATOR . $name . ".html";
		if(file_exists($templatePath)){
			return PHPTokenParser::getParser(file_get_contents($templatePath))->cleanup();
		}

		$codes = file_get_contents($path);

		$parser = PHPTokenParser::getParser($codes);
		$tokens = token_get_all($codes);
		$tmp = "";
		$flag = 0;
		foreach($tokens as $token){
			if(is_array($token)){
				$type = $token[0];
				$code = $token[1];
			}else{
				$type = null;
				$code = $token;
			}

			if($flag > 1){
				$tmp .= $code;
				continue;
			}

			if($flag == 0 && $type == T_RETURN){
				$flag++;
				continue;
			}

			if($flag && $type == T_CLOSE_TAG){
				$flag++;
			}
		}

		return $parser->cleanup($tmp);
	}



}