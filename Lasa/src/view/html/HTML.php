<?php
/*
 * HTML.php
 */
namespace lasa\view\html;

class HTML{
	
	public static function style($href, $opt = []){
		
		if(!$href)return "";
		
		$media = (isset($opt["media"])) ? $opt["media"] : "all";
		$type = (isset($opt["type"])) ? $opt["type"] : "text/css";
		$rel = (isset($opt["rel"])) ? $opt["rel"] : "stylesheet";
		
		if($href[0] == "@"){
			
			list($name, $suffix) = explode("/", $href, 2);
			$prefix = \lasa\view\View::path(substr($name, 1));
			$href = $prefix . "/" . $suffix;
		}
		
		return '<link media="'.$media.'" type="'.$type.'" rel="'.$rel.'" href="'.$href.'" />';
	}
	
	public static function script($href, $opt = []){
		
		if(!$href)return "";
		
		$type = (isset($opt["type"])) ? $opt["type"] : "text/javascript";
		
		if($href[0] == "@"){
			
			list($name, $suffix) = explode("/", $href, 2);
			$prefix = \lasa\view\View::path(substr($name, 1));
			$href = $prefix . "/" . $suffix;
		}
		
		return '<script type="'.$type.'" src="'.$href.'"></script>';
	}
	
}