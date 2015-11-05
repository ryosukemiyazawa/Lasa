<?php
use lasa\validator\Validator;
return function(Validator $v){
	
	$v->required("name");
	$v->required("password");
	$v->required("password_confirm")->matches("password");
	
};