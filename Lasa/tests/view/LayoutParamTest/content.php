<?php
return [
	"hoge" => "label",
	"@layout" => [
		"layout",	//先頭にレイアウト名を指定する
		"hoge" => "ほげほげ"
	],
	function ($values){
		
	}
];
?>
テスト：<!-- :hoge /-->

レイアウト変数は@layoutで追加する

動的には指定出来ない？
