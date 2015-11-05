<?php
/*
 * layout.php
 */
return [
	"hoge" => "label",
	function($values){
		return "hogehoge";
	}
];
?>
<html>
<head>
</head>
<body>
	<!-- section:@ /-->
	
	<p :hoge></p>
</body>
</html>
