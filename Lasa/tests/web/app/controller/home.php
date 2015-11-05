<?php
use lasa\web\Application;
/*
 * home.php
 * @var $app lasa\web\Application
 */
$app = lasa\web\Application::getInstance();

$app->bind("get",function(){
	var_dump(Application::getInstance()->get("app.debug"));
	
	if(app_config("app.debug")){
		echo "デバッグモードです";
	}else{
		echo "デバッグモードではありません";
	}
	
	echo "<hr />";
	echo date("Y-m-d H:i:s");
	
	
});