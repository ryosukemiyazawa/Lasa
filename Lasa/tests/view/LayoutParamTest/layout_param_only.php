<?php
return [
	"hoge" => "label",
	"@layout" => [
		"hoge" => "layout_param_only"
	],
	function ($values){
		
	}
];
?>
テスト：<!-- :hoge /-->

レイアウト変数は@layoutで追加する

動的には指定出来ない？
