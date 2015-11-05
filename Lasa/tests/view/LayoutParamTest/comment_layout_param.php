<?php
/*
 * comment_layout_param.php
 * コメントでレイアウト変数を指定するパターン
 * @layout layout
 * @layout.hoge comment_layout_param
 * @layout.class かきくけこ
 * @layout.page_title あいうえお
 */
return [
	"hoge" => "label",
	function ($values){
		
	}
];
?>
テスト：<!-- :hoge /-->
