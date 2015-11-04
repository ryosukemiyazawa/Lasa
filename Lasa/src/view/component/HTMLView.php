<?php
namespace lasa\view\component;

if(!defined("VIEWHOLDER_CLASS")){
	define("VIEWHOLDER_CLASS", "\\lasa\\view\\ViewContainer");
}

/**
 * addXXX系は全て「id, <実体>, option」というルールで実行する必要がある
 * 表示用の要素（label,image,link)の実態はOutputするべき文字列
 * フォーム用の要素（input,check,select)はnameとvalueが必須要素となる
 */
class HTMLView{

	/**
	 * @param string $id
	 * @param HTMLComponent $component
	 * @param string $opt
	 * @return HTMLComponent
	 */
	function addComponent($id, HTMLComponent $component, $opt = null){
		return $component;
	}

	function addView($id, $viewName, $opt = null){
		return new ComponentBase();
	}
	function addLabel($id, $text = null, $opt = null){
		return new ComponentBase();
	}

	function addImage($id, $url = null, $opt = null){
		return new ComponentBase();
	}

	function addLink($id, $url = null, $opt = null){
		return new ComponentBase();
	}
	function addRaw($id, $text = null, $opt = null){
		return new ComponentBase();
	}
	function addJson($id, $text = null, $opt = null){
		return new ComponentBase();
	}

	/* formの各要素 */

	function addInput($id, $name, $value = null, $opt = null){
		return new ComponentBase();
	}

	function addCheck($id, $name, $value = null, $opt = null){
		return new ComponentBase();
	}

	function addSelect($id, $name, $value = null, $opt = null){
		return new ComponentBase();
	}

	function addTextArea($id, $name, $value = null, $opt = null){
		return new ComponentBase();
	}

	/* 特殊 */

	function addList($id, \Closure $func, array $list  = null){
		return new HTMLComponent();
	}

	function addCondition($id, $defValue = null){
		return new HTMLComponent();
	}

	function apply(\Closure $func){

	}

}

interface Component{

	/**
	 * @return Component
	 * @param $value
	 */
	function setDefault($value);

	/**
	 * @return Component
	 * @param string $key
	 * @param $value
	 */
	function setAttribute($key, $value);

	function setOptions($options);

}

class ComponentBase implements Component{
	function setDefault($value){
		return $this;
	}
	/**
	 * setAttribute
	 * @see \lasa\view\component\Component::setAttribute()
	 */
	function setAttribute($key, $value) {
		return $this;
	}

	function setOptions($options){
		return $this;
	}

}