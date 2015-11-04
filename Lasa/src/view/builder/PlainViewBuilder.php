<?php

namespace lasa\view\builder;
use lasa\view\component\HTMLView;
use lasa\view\Engine;
use lasa\view\component\HTMLViewComponent;

class PlainViewBuilder extends ViewBuilder{
	
	private $template = null;
	
	function __construct($scriptFile){
		$this->template = file_get_contents($scriptFile);
	}
	
	function compile(Engine $factory){
		
		$template = $this->template;
		
		//コードを綺麗にする
		$template = PHPTokenParser::getParser($template)->cleanup();
		
		//Sectionを変換する
		$template = $this->replaceSections($template);
		
		$prefix = array();
		//引数を変数に展開する
		$prefix[] = '<?php ';
		$prefix[] = 'foreach($'.$this->getHolderName().'->values() as $name => $value){';
		$prefix[] = '	$$name = $value;';
		$prefix[] = '}';
		$prefix[] = '?>';
		
		return implode("\n", $prefix) . $template;
	}
	
}