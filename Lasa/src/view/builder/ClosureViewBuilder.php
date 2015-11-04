<?php

namespace lasa\view\builder;

use lasa\view\component\HTMLViewComponent;
use lasa\view\Engine;
use lasa\view\ViewContainer;

class ClosureViewBuilder extends ViewBuilder{
	
	private $func;
	private $template;
	
	function __construct(\Closure $func){
		$this->func = $func;
	}
	
	function setTemplate($template){
		$this->template = $template;
	}
	
	function compile(Engine $factory){
		$closure = $this->func;
		
		$obj = new HTMLViewComponent($this->template, $closure);
		$obj->_holderName = $this->getHolderName();
		
		return $obj->compile();
	}
	
	function loadTemplate($func = null){
		if(!$func)$func = $this->func;
		
		$ref = new \ReflectionFunction($func);
		
		$codes = file_get_contents($ref->getFileName());
		$parser = PHPTokenParser::getParser($codes);
		
		$tmp = file($ref->getFileName());
		$start_template = false;
		$template = "";
		for($i=$ref->getEndLine();;$i++){
			if(!isset($tmp[$i])){
				break;
			}
			$line = $tmp[$i];
		
			//宣言部分の終了
			if(!$start_template && strpos($line, "?>") !== false){
				$start_template = true;
				$diff = substr($line, strpos($line,"?>") + strlen("?>"));
				$template .= ltrim($diff);
				continue;
			}
		
			if($start_template){
				$template .= $tmp[$i];
			}
		}
		
		$template = $parser->cleanup($template);
		$this->setTemplate($template);
	}
	
}