<?php
namespace lasa\view\builder;

use lasa\view\Engine;
use lasa\view\component\HTMLViewComponent;

class ViewBuilder{
	
	private $holderName = "view";
	
	/**
	 * コンパイル後のコードを返す
	 * @param Engine $factory
	 */
	public function compile(Engine $factory){
		
	}
	
	public function getHolderName(){
		return $this->holderName;
	}
	public function setHolderName($holderName){
		$this->holderName = $holderName;
		return $this;
	}
	
	public function replaceSections($template){
		//sectionを変換する
		$view = new HTMLViewComponent($template);
		$template = $view->compile();
		
		return $template;
	}
	
	public function parseCodeAndGetUserClasses($code){
		
	}
}