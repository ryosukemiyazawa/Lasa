<?php
/*
 * helper.php
 */
function view_section($name, $value){
	return \lasa\view\View::section($name, $value);
}

function view_observe($name, $func){
	return \lasa\view\View::observe($name, $func);
}
function view_path($name, $path = null){
	return \lasa\view\View::path($name, $path);
}

function view_before($name, $value){
	return \lasa\view\View::before($name, $value);
}
function view_after($name, $value){
	return \lasa\view\View::after($name, $value);
}
function view_html($type, $value, $opt = []){
	$func = $type;
	return \lasa\view\html\HTML::$func($value, $opt);
}