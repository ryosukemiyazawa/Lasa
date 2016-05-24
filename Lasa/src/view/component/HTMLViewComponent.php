<?php
/*
 * HTMLViewComponent.php
 */
namespace lasa\view\component;

use lasa\view\ViewContainer;
use lasa\view\builder\PHPTokenParser;

class HTMLViewComponent extends HTMLView {

	public $_template = "";

	public $_applyFunc = null;

	public $_forgeFunc = null;

	public $_components = array();

	public $_holderName = "view";

	public $_layout = null;
	
	public $_layoutParams = [];

	static $EMPTY_TAG_LIST = array(
		"area",
		"base",
		"basefont",
		"bgsound",
		"br",
		"embed",
		"hr",
		"img",
		"input",
		"link",
		"meta",
		"param"
	);

	public function __construct($template, $_ = null) {
		$this->_template = $template;
		
		if($_ && $_ instanceof \Closure){
			$this->_forgeFunc = $_;
		}
	}

	/**
	 *
	 * @param string $id
	 * @param HTMLComponent $component
	 * @param string $opt
	 * @return HTMLComponent
	 */
	function addComponent($id, HTMLComponent $component, $opt = null) {
		$component->setOptions($opt);
		$this->_components[$id] = $component;
		return $this->_components[$id];
	}

	function addView($id, $viewName, $opt = null) {
		$component = new ViewComponent($viewName);
		if(is_array($opt) && isset($opt[0]))
			$opt = array_shift($opt);
		return $this->addComponent($id, $component, $opt);
	}

	function addLabel($id, $text = null, $opt = null) {
		$component = new LabelComponent();
		if($text)
			$component->setDefault($text);
		return $this->addComponent($id, $component, $opt);
	}

	function addImage($id, $url = null, $opt = null) {
		$component = new ImageComponent();
		return $this->addComponent($id, $component, $opt);
	}

	function addLink($id, $url = null, $opt = null) {
		$component = new LinkComponent();
		if($url){
			$component->setDefault($url);
		}
		return $this->addComponent($id, $component, $opt);
	}

	function addRaw($id, $text = null, $opt = null) {
		$component = new RawLabelComponent();
		return $this->addComponent($id, $component, $opt);
	}

	function addJson($id, $text = null, $opt = null) {
		$component = new JSONComponent();
		return $this->addComponent($id, $component, $opt);
	}

	/* formの各要素 */
	
	function addInput($id, $name, $value = null, $opt = null) {
		$component = new InputComponent();
		if(is_array($name)){
			$opt = $name;
			$name = $opt["name"];
		}
		$component->setAttribute("name", $name);
		$component->setOptions($opt);
		return $this->addComponent($id, $component, $opt);
	}

	function addCheck($id, $name, $value = null, $opt = null) {
		$component = new CheckComponent();
		if(is_array($name)){
			$opt = $name;
			$name = (isset($opt["name"])) ? $opt["name"] : null;
		}
		if($name)$component->setAttribute("name", $name);
		
		$component->setOptions($opt);
		
		return $this->addComponent($id, $component, $opt);
	}

	function addSelect($id, $name, $value = null, $opt = null) {
		$component = new SelectComponent();
		if(is_array($name)){
			$opt = $name;
			$name = (isset($opt["name"])) ? $opt["name"] : null;
		}
		if($name)$component->setAttribute("name", $name);
		$component->setOptions($opt);
		return $this->addComponent($id, $component, $opt);
	}

	function addTextArea($id, $name, $value = null, $opt = null) {
		$component = new TextAreaComponent();
		if(is_array($name)){
			$opt = $name;
			$name = (isset($opt["name"])) ? $opt["name"] : null;
		}
		if($name)$component->setAttribute("name", $name);
		$component->setOptions($opt);
		return $this->addComponent($id, $component, $opt);
	}

	/* 特殊 */
	
	/**
	 *
	 * @param string $id
	 * @param \Closure $func
	 * @param string $opt
	 * @return ListComponent
	 */
	function addList($id, \Closure $func, array $list = null) {
		$component = new ListComponent($func);
		return $this->addComponent($id, $component);
	}

	function addCondition($id, $defValue = null) {
		$component = new ConditionalComponent();
		$component->setDefault($defValue);
		return $this->addComponent($id, $component);
	}

	/**
	 * 値を変換するためのクロージャーを登録する
	 *
	 * @param \Closure $func
	 * @throws \Exception
	 */
	function apply(\Closure $func) {
		
		$code = $this->getClosureCode($func);
		
		if(preg_match("#^function\(.*\)\s*use\s*\(.*\)#", $code)){
			throw new \Exception("Convert func can not allow use()");
		}
		
		$this->_applyFunc = $func;
	
	}
	
	/**
	 * コンパイル実行
	 *
	 * @param string $path
	 * @return number|string
	 */
	function compile($path = null) {
		
		// sectionをチェックする
		if(preg_match_all("#<(!--|[a-z]+).*\ssection:([a-z0-9\-_@]*)(\s+[^>]*|)>#", $this->_template, $tmp)){
			foreach($tmp[2] as $sectionName){
				$section = new SectionComponent();
				$section->setAutoMode();
				$this->addComponent($sectionName, $section);
			}
		}
		
		// pathをチェックする
		if(preg_match_all("#<(!--|[a-z]+).+path:([a-z0-9\-_@]+)=\"[^\"]+\"(\s+[^>]*|)>#", $this->_template, $tmp)){
			foreach($tmp[2] as $sectionName){
				$section = new PathResolverComponent();
				$section->setAutoMode();
				$this->addComponent($sectionName, $section);
			}
		}
		
		// body,headのinjectionを処理する
		if(preg_match_all("#<(/?)(head|body)[^>]*>#i", $this->_template, $tmp, PREG_OFFSET_CAPTURE)){
			$template = $this->_template;
			foreach($tmp[0] as $index => $each){
				$text = $each[0];
				
				$tag = strtolower($tmp[2][$index][0]);
				$isEndTag = $tmp[1][$index][0] == "/";
				
				$funcName = ($isEndTag) ? "outputAfter" : "outputBefore";
				$name = "@" . $tag;
				$code = "<?php \\lasa\\view\\View::" . $funcName . "('" . $name . "'); ?>";
				
				$replace = ($isEndTag) ? $code . "\n" . $text : $text . "\n" . $code;
				$template = str_replace($text, $replace, $template);
			}
			$this->_template = $template;
		}
		
		if($this->_forgeFunc){
			$values = new ViewContainer([]);
			$func = $this->_forgeFunc;
			$func($this, $values);
		}
		
		$prefix = "";
		
		//レイアウトの指定
		if($this->_layout || $this->_layoutParams){
			$prefix .= '$' . $this->_holderName . "->layout(" . var_export($this->_layout, true) . ", ".var_export($this->_layoutParams, true) .", false);";
		}
		
		//forgeFunc
		if($this->_forgeFunc){
			$code = $this->getClosureCode($this->_forgeFunc);
			$prefix .= '$' . $this->_holderName . "->forge(" . $code . ");";
		}
		
		if($this->_applyFunc){
			$code = $this->getClosureCode($this->_applyFunc);
			$prefix .= '$' . $this->_holderName . "->apply(" . $code . ");";
		}
		
		// 長い順に設定する
		uksort($this->_components, function ($a, $b) {
			if(strlen($a) > strlen($b))
				return -1;
			if(strlen($a) < strlen($b))
				return 1;
			return strcmp($a, $b);
		});
		
		foreach($this->_components as $id => $component){
			$this->parse($id, $component);
			$prefix .= $this->parsePrefix($id, $component);
			
			// ifをパースする
			$toggleComponent = new ConditionalComponent();
			$toggleComponent->setBaseClass(get_class($component));
			$this->parse($id, $toggleComponent);
			
			// listをパースする
			$loopComponent = new LoopComponent();
			$loopComponent->setBaseComponent($component);
			$this->parse($id, $loopComponent);
		}
		
		if(strlen($prefix) > 0){
			$prefix = "<?php " . $prefix . "?>\n";
		}
		
		// @表現を置換する
		foreach($this->_components as $id => $component){
			$this->parseAttributeExpression($id, $component);
		}
		
		if($path){
			file_put_contents($path, $prefix . $this->_template);
		}
		
		return $prefix . $this->_template;
	}

	function parse($id, HTMLComponent $component) {
		
		$component->_holderName = $this->_holderName;
		$content = $this->_template;
		$EMPTY_TAG_LIST = self::$EMPTY_TAG_LIST;
		
		$counter = 0;
		$markers = [];
		while(true){
			$counter++;
			$whiteSpace = ($component->isWrappedComponent()) ? "[ \t]*" : "";
			$start_regex = '#' . $whiteSpace . '<(([a-zA-Z0-9]+|!--)[^<>]*\s(' . $component->getTagPrefix() . ')(:' . $id . ')(\s+[^>]*|))>#i';
			$start_regex_with_value = '#' . $whiteSpace . '<(([a-zA-Z0-9]+|!--)[^<>]*\s(' . $component->getTagPrefix() . ')(:' . $id . ')="([^"]+)"(\s+[^>]*|))>#i';
			$maxLength = strlen($content);
			$tmp1 = array();
			$tmp1_value = array();
			$tmp2 = array();
			$option_value = null;
			
			if($counter > 1000){ // block infinite loop
				break;
			}
			
			// 取得出来るまでやり続ける
			if(!preg_match($start_regex, $content, $tmp1, PREG_OFFSET_CAPTURE) && !preg_match($start_regex_with_value, $content, $tmp1_value, PREG_OFFSET_CAPTURE)){
				break;
			}
			
			if($tmp1_value){
				$tmp1[0] = $tmp1_value[0];
				$tmp1[1] = $tmp1_value[1];
				$tmp1[2] = $tmp1_value[2];
				$tmp1[3] = $tmp1_value[3];
				$tmp1[4] = $tmp1_value[4];
				$tmp1[5] = $tmp1_value[6];
				$option_value = $tmp1_value[5][0];
			}
			
			$start = $tmp1[0][1];
			$line = $tmp1[1][0];
			$tag = $tmp1[2][0];
			$prefix = $tmp1[3][0];
			$mark = $tmp1[4][0];
			$startIndent = "";
			$endIndent = "";
			if($component->isWrappedComponent() && preg_match("#^([ \t]+)#", $tmp1[0][0], $indent_tmp)){
				$startIndent = $indent_tmp[1];
			}
			
			$outer = null;
			$inner = null;
			$innerOffset = $start + strlen($tmp1[0][0]);
			
			$outerStartNewLine = false;
			$innerStartNewLine = false;
			$outerEndNewLine = false;
			$innerEndNewLine = false;
			
			$attributes = $this->parseAttributes($line);
			
			if($tag == "!--"){
				$end_regex = '#<((!--)[^<>]*\s/?(' . $prefix . ':' . $id . ')(\s+[^>]*|))>#i';
			}else{
				$end_regex = '#</' . $tag . '(>|[^<>]*(' . $prefix . ':' . $id . ')(\s+[^>]*|)>)#i';
			}
			
			// タグが閉じてる
			if(preg_match('/\/(--)?$/', $line) || in_array(strtolower($tag), $EMPTY_TAG_LIST)){
				$outer = $tmp1[0][0];
				
				if($innerOffset < $maxLength && $content[$innerOffset] == "\n"){
					// 改行で終わってる
					$outerEndNewLine = true;
				}
				
				// 終了タグにマッチ
			}else if(preg_match($end_regex, $content, $tmp2, PREG_OFFSET_CAPTURE, $innerOffset)){
					
					if($content[$innerOffset] == "\n"){
						$innerStartNewLine = true;
					}
					
					$outer = substr($content, $start, $tmp2[0][1] + strlen($tmp2[0][0]) - $start);
					$inner = substr($content, $innerOffset, $tmp2[0][1] - $innerOffset);
					$innerOffset = $tmp2[0][1] + strlen($tmp2[0][0]);
					
					if($inner && $inner[strlen($inner) - 1] == "\n"){
						$innerEndNewLine = true;
					}
					
					if($innerOffset < $maxLength && $content[$innerOffset] == "\n"){
						$outerEndNewLine = true;
					}
					
					// 普通にタグを閉じている場合（最小マッチになる）
				}else
					if($tag != "!--" && preg_match("#</" . $tag . ">#", $content, $tmp2, PREG_OFFSET_CAPTURE, $innerOffset)){
						
						$outer = substr($content, $start, $tmp2[0][1] + strlen($tmp2[0][0]) - $start);
						$inner = substr($content, $innerOffset, $tmp2[0][1] - $innerOffset);
						$innerOffset = $tmp2[0][1] + strlen($tmp2[0][0]);
						
					
					// 閉じるタグが存在しなかった場合は開始タグのみが対象とする
					}else{
						$outer = $tmp1[0][0];
					}
			
			if($outer){
				
				if(preg_match("#([ \t]+)$#", $inner, $indent_tmp)){
					$endIndent = $indent_tmp[1];
				}
				
				$component->_prefix = $prefix;
				$component->_startIndent = $startIndent;
				$component->_endIndent = $endIndent;
				
				$component->_innerStartNewLine = $innerStartNewLine;
				$component->_innerEndNewLine = $innerEndNewLine;
				
				$component->_outerStartNewLine = $outerStartNewLine;
				$component->_outerEndNewLine = $outerEndNewLine;
				
				$component->_optionValue = $option_value;
				
				$replace = $component->getContent($id, $tag, $inner, $attributes);
				$marker = "@@" . md5($id . "-" . $start . "-" . strlen($outer)) . "@@";
				$markers[$marker] = $replace;
				
				// １回だけ変換かけるためにsubstr_replaceを利用
				// $content = str_replace($outer, $replace, $content, 1);
				$content = substr_replace($content, $marker, $start, strlen($outer));
				
			}
		
		}
		
		//マーカー部分を入れ替える
		foreach ($markers as $key => $value){
			$content = str_replace($key, $value, $content);
		}
		
		$this->_template = $content;
	}

	function parseAttributeExpression($id, $component) {
		
		$content = $this->_template;
		
		// 属性値のチェック
		while(true){
			$start_regex = '#"(@' . $id . ')"#i';
			$tmp = array();
			
			// 取得出来るまでやり続ける
			if(!preg_match($start_regex, $content, $tmp, PREG_OFFSET_CAPTURE)){
				break;
			}
			
			$outer = $tmp[1][0];
			$replace = '<?php $' . $component->_holderName . '->out("' . $id . '"); ?>';
			$content = str_replace($outer, $replace, $content);
		}
		
		// braceでの表現のチェック
		while(true){
			$start_regex = '#{{\s*(' . $id . ')\s*}}#i';
			$tmp = array();
			
			// 取得出来るまでやり続ける
			if(!preg_match($start_regex, $content, $tmp, PREG_OFFSET_CAPTURE)){
				break;
			}
			
			$outer = $tmp[0][0];
			$replace = '<?php $' . $component->_holderName . '->out("' . $id . '"); ?>';
			$content = str_replace($outer, $replace, $content);
		}
		
		$this->_template = $content;
	
	}

	function parsePrefix($id, HTMLComponent $component) {
		$prefix = "";
		
		if($component->_default){
			$prefix .= '$' . $component->_holderName . '->placeholder("' . $id . '",' . var_export($component->_default, true) . ');';
		}
		
		if($component->_convert){
			
			$closure = $component->_convert;
			$code = $this->getClosureCode($closure);
			
			$prefix .= '$' . $component->_holderName . '->on("' . $id . '",' . $code . ');' . "\n";
		}
		
		$tmp = $component->getPrefix($id);
		if($tmp){
			$prefix .= $tmp;
		}
		
		return $prefix;
	
	}

	function parseAttributes($line) {
		$attributes = array();
		$regex = '/([a-zA-Z_:\-]*)\s*=\s*"([^"]*)"/';
		$tmp = array();
		if(preg_match_all($regex, $line, $tmp)){
			$keys = $tmp[1];
			$values = $tmp[2];
			foreach($keys as $i => $key){
				// 属性は全て小文字に
				$key = strtolower($key);
				
				// 値のエスケープを戻す
				$value = html_entity_decode($values[$i], ENT_QUOTES);
				$attributes[$key] = new HTMLAttributeComponent($value);
			}
		}
		
		return $attributes;
	}

	/**
	 * クロージャーの宣言部分を取得する
	 *
	 * @param \Closure $closure
	 * @return string
	 */
	private function getClosureCode(\Closure $closure) {
		
		$reflection = new \ReflectionFunction($closure);
		$startLine = $reflection->getStartLine() - 1;
		
		// Open file and seek to the first line of the closure
		$file = new \SplFileObject($reflection->getFileName());
		
		// Closureの開始コードまで移動
		$file->seek($startLine);
		
		$params = [];
		foreach($reflection->getParameters() as $param){ /* @var $param \ReflectionParameter */
			if($param->getClass()){
				$params[] = "\\" . $param->getClass()->getName() . ' $' . $param->getName();
			}else{
				$params[] = '$' . $param->getName();
			}
		}
		$code_prefix = "function(" . implode(",", $params) . ")";
		
		// Retrieve all of the lines that contain code for the closure
		$code = '';
		while($file->key() < $reflection->getEndLine()){
			$line = $file->current();
			$line = ltrim($line);
			// skip comment
			if(preg_match("#^//#", $line)){
				$file->next();
				continue;
			}
			$code .= $line;
			$file->next();
		}
		
		// Only keep the code defining that closure
		$begin = strpos($code, 'function');
		$begin = strpos($code, '{', $begin);
		$end = strrpos($code, '}');
		$code = substr($code, $begin, $end - $begin + 1);
		
		$code = $code_prefix . preg_replace("/([\t ]+)}$/m", "}", $code);
		
		// use句のreplaceを行う
		$parser = PHPTokenParser::getParser(file_get_contents($reflection->getFileName()));
		$code = $parser->cleanup($code);
		
		return $code;
	}

	/**
	 * set default layout name
	 *
	 * @param string $layoutName
	 */
	function layout($layoutName) {
		$this->_layout = $layoutName;
		return $this;
	}
	
	/**
	 * レイアウト変数を登録する
	 * @param array $params
	 */
	function layoutParams($params){
		$this->_layoutParams = $params;
	}

}

class HTMLAttributeComponent {

	private $_value;
	private $_dynamic = false;

	function __construct($value, $isDynamic = false) {
		$this->_value = $value;
		$this->_dynamic = $isDynamic;
	}

	function getValue() {
		return $this->_value;
	}
	
	function isDynamic(){
		return $this->_dynamic;
	}
}

class HTMLComponent implements Component {

	public $_prefix = '';

	public $_holderName = 'view';

	public $_startIndent = "";

	public $_innerStartNewLine = false;

	public $_innerEndNewLine = false;

	public $_outerStartNewLine = false;

	public $_outerEndNewLine = false;

	public $_optionValue = null;

	public $_endIndent = "";

	public $_convert = null;

	public $_default = null;

	public $_attributes = array();

	function setAttribute($key, $value) {
		if($value instanceof HTMLAttributeComponent){
			$this->_attributes[$key] = $value;
		}else{
			$this->_attributes[$key] = new HTMLAttributeComponent($value, true);
		}
		return $this;
	}

	function setConvert(\Closure $func) {
		$this->_convert = $func;
		return $this;
	}

	function setDefault($value) {
		$this->_default = $value;
		return $this;
	}

	function setOptions($opt) {
		if($opt instanceof \Closure)
			$this->setConvert($opt);
		if(is_string($opt)){
			$this->setDefault($opt);
		}
		if(is_array($opt)){
			if(isset($opt["convert"]))
				$this->setConvert($opt["convert"]);
			if(isset($opt["default"]))
				$this->setDefault($opt["default"]);
			
			foreach($opt as $key => $value){
				if($key[0] == "@"){
					$this->setAttribute(substr($key, 1), new HTMLAttributeComponent($value, true));
				}
			}
		}
		return $this;
	}

	function value($value) {
		$this->setDefault($value);
		return $this;
	}

	function getPrefix($id) {
		return null;
	}

	function getStartTag($id, $tag, $attributes) {
		
		$attributes = array_merge($attributes, $this->_attributes);
		
		if(empty($attributes)){
			return $tag;
		}
		
		$names = [
			"id",
			"class",
			"style",
			"href",
			"src",
			"title",
			"type",
			"name",
			"value",
			"onX",
			"data-"
		];
		
		uksort($attributes, function ($keya, $keyb) use($names) {
			
			$aindex = array_search($keya, $names);
			$bindex = array_search($keyb, $names);
			
			if($aindex !== false && $bindex !== false){
				return ($aindex > $bindex) ? 1 : -1;
			}
			
			if($aindex !== false){
				return -1;
			}
			if($bindex !== false){
				return 1;
			}
			
			return strcmp($keya, $keyb);
		});
		
		$attr = array();
		foreach($attributes as $key => $value){
			if($key[0] == "_"){
				$attr[] = $value;
				continue;
			}
			
			if($value instanceof HTMLAttributeComponent){	/* HTMLに書かれたコード*/
				if($value->isDynamic()){
					$code = '<?php $' . $this->_holderName . '->out("' . $id . '@'.$key.'","'.htmlspecialchars($value->getValue(), ENT_QUOTES).'"); ?>';
					$attr[] = '' . $key . '="' . $code . '"';
				}else{
					$attr[] = '' . $key . '="' . htmlspecialchars($value->getValue(), ENT_QUOTES) . '"';
				}
			}else{
				$attr[] = '' . $key . '="' . $value . '"';
			}
		}
		
		return $tag . " " . implode(" ", $attr);
	}

	function getTagPrefix() {
		return "";
	}

	function isWrappedComponent() {
		return false;
	}

	function getContent($id, $tag, $inner, $attributes) {
		if($tag == "!--"){
			return '<?php $' . $this->_holderName . '->out("' . $id . '"); ?>' . (($this->_outerEndNewLine) ? "\n" : "");
		}
		
		$tags = HTMLViewComponent::$EMPTY_TAG_LIST;
		$isEmptyTag = in_array($tag, $tags);
		
		if($isEmptyTag){
			return '<' . $this->getStartTag($id, $tag, $attributes) . ' />';
		}
		
		return '<' . $this->getStartTag($id, $tag, $attributes) . '>' . $inner . '</' . $tag . '>';
	}
}

class OutputComponent extends HTMLComponent {

}

/**
 * Formを作るためのコンポーネント
 * \lasa\web\Applicationに依存してしまうのが
 */
class HTMLFormComponent extends HTMLComponent {

	public $method = "post";

	public function __construct($opt = []) {
		if(isset($opt["method"]))$this->method = $opt["method"];
		$this->setAttribute("method", $this->method);
	}

	function getContent($id, $tag, $inner, $attributes) {
		if($tag == "form"){
			$attributes["action"] = '<?php $' . $this->_holderName . '->out("' . $id . '", $_SERVER["REQUEST_URI"]); ?>';
			$app = \lasa\web\Application::getInstance();
			if($app){
				$key = $app->option("csrf_key","__csrf_token");
				$token = '<?php $app = \lasa\web\Application::getInstance(); if($app){ echo $app->createToken("'.$id.'"); } ?>';
				
				$hiddenInput = '<input type="hidden" name="'.$key.'" value="'.$token.'" />';
				$inner = $hiddenInput . $inner;
			}
		}
		return parent::getContent($id, $tag, $inner, $attributes);
	}
}

class HTMLFormElementComponent extends HTMLComponent {

	function setAttribute($key, $value) {
		if($key == "value"){
			$this->_default = ($value instanceof HTMLAttributeComponent) ? $value->getValue() : $value;
			return;
		}
		parent::setAttribute($key, $value);
	}

	public static function getLabeledContent($id, $tag, $inner, $attributes){
		$labelComponent = new LabelComponent();
		return $labelComponent->getContent($id, $tag, $inner, $attributes);
	}
	
}

class ConditionalComponent extends HTMLComponent {

	private $baseClass;

	function setBaseClass($baseClass) {
		$this->baseClass = $baseClass;
	}

	function isWrappedComponent() {
		return true;
	}

	function getContent($id, $tag, $inner, $attributes) {
		
		list($start, $end) = $this->getWrapTag($id);
		
		if($tag == "!--"){
			return $start . $this->_startIndent . trim($inner) . $end;
		}
		return $start . $this->_startIndent . '<' . $this->getStartTag($id, $tag, $attributes) . '>' . trim($inner) . '</' . $tag . '>' . $end;
	}

	function getTagPrefix() {
		return "ifn?";
	}

	function getWrapTag($id) {
		
		$op = ($this->_prefix == "if") ? "===" : "!==";
		
		if($this->baseClass == SectionComponent::class){
			$start = '<?php if(true' . $op . '\lasa\view\View::checkSection("' . $id . '")){ ?>' . "\n";
		}else{
			$start = '<?php if(true' . $op . '($' . $this->_holderName . '->check("' . $id . '"))){ ?>' . "\n";
		}
		$end = "\n" . '<?php } ?>';
		
		return [
			$start,
			$end
		];
	}
}

class LoopComponent extends HTMLComponent {

	private $baseComponent;

	function setBaseComponent($baseComponent) {
		$this->baseComponent = $baseComponent;
	}

	function isWrappedComponent() {
		return true;
	}

	function getContent($id, $tag, $inner, $attributes) {
		
		/* @var $obj HTMLComponent */
		$obj = clone($this->baseComponent);
		$obj->_holderName = $id . "_list";
		
		$start = '<?php foreach($' . $this->_holderName . '->getArray("' . $id . '") as $array): $' . $id . '_list = new ' . VIEWHOLDER_CLASS . '(["'.$id.'" => $array]); ?>';
		if($this->_innerStartNewLine)
			$start .= "\n";
		$end = '<?php endforeach; ?>';
		if($this->_innerEndNewLine)$end = "\n" . $end;
		if($this->_outerEndNewLine)$end .= "\n";
		
		$inner = $obj->getContent($id, $tag, $inner, $attributes);
		
		return $start . $inner . $end;
	}

	function getTagPrefix() {
		return "list";
	}
}

class ViewComponent extends OutputComponent {

	private $view;

	function __construct($view) {
		$this->view = $view;
	}

	function setOptions($opt) {
		parent::setOptions($opt);
		if(is_array($opt) && !$this->_default){
			$this->setDefault($opt);
		}
	}

	function getContent($id, $tag, $inner, $attributes) {
		
		if(!$this->view){
			return $this->getDynamicViewContent($id, $tag, $inner, $attributes);
		}
		
		$holderName = "view";
		$engine = \lasa\view\Engine::currentEngine();
		$loader = $engine->getLoader();
		$builder = $loader->getBuilder($this->view);
		
		if(!$builder){ // 見つからなかった時はそのままinnerを返す
			return $inner;
		}
		
		$builder->setHolderName($holderName);
		$result = $builder->compile($engine);
		
		$tokens = token_get_all($result);
		$php_tag_open = 0;
		foreach($tokens as $token){
			if(!is_array($token))
				continue;
			$type = $token[0];
			if($type == T_OPEN_TAG){
				$php_tag_open++;
				continue;
			}
			if($type == T_CLOSE_TAG){
				$php_tag_open--;
				continue;
			}
		}
		$suffix = ($php_tag_open > 0) ? "" : "<?php ";
		$prefix = '$_ = $' . $holderName . ";";
		$result = '<?php call_user_func(function($' . $holderName . '){ ' . $prefix . '?>' . $result . $suffix . '}, new ' . VIEWHOLDER_CLASS . '($' . $this->_holderName . '->getArray("' . $id . '")));' . '?>';
		
		if($this->_outerEndNewLine){
			$result .= "\n";
		}
		
		return $result;
	}
	
	/**
	 * 動的にViewを取得する場合
	 * @return string
	 */
	function getDynamicViewContent($id, $tag, $inner, $attributes){
		$res = [];
		$res[] = '<?php call_user_func(function($values){ ';
		$res[] = 'if(!$values)return;';
		$res[] = 'if(is_string($values))$values=[$values];';
		$res[] = '$view=\lasa\view\Engine::currentEngine()->load(array_shift($values), $values);';
		$res[] = 'if(!$view)return;';
		$res[] = '$view->display();';
		$res[] = '}, $' . $this->_holderName . '->get("'.$id.'")); ?>';
		return implode("", $res);
	}

}

class LabelComponent extends OutputComponent {

	function getContent($id, $tag, $inner, $attributes) {
		if($tag == "!--"){
			return '<?php $' . $this->_holderName . '->out("' . $id . '"); ?>' . (($this->_outerEndNewLine) ? "\n" : "");
		}
		
		return '<' . $this->getStartTag($id, $tag, $attributes) . '><?php $' . $this->_holderName . '->out("' . $id . '"); ?></' . $tag . '>';
	}
}

class RawLabelComponent extends OutputComponent {

	function getContent($id, $tag, $inner, $attributes) {
		if($tag == "!--"){
			return '<?php echo $' . $this->_holderName . '->getString("' . $id . '"); ?>' . (($this->_outerEndNewLine) ? "\n" : "");
		}
		
		return '<' . $this->getStartTag($id, $tag, $attributes) . '><?php echo $' . $this->_holderName . '->getString("' . $id . '"); ?></' . $tag . '>';
	}
}

class LinkComponent extends OutputComponent {

	function getContent($id, $tag, $inner, $attributes) {
		if($tag == "a"){
			$attributes["href"] = '<?php $' . $this->_holderName . '->out("' . $id . '"); ?>';
		}
		return parent::getContent($id, $tag, $inner, $attributes);
	}

}

class ImageComponent extends OutputComponent {

	function getContent($id, $tag, $inner, $attributes) {
		if($tag == "img"){
			$attributes["src"] = '<?php $' . $this->_holderName . '->out("' . $id . '"); ?>';
			return '<' . $this->getStartTag($id, $tag, $attributes) . ' />';
		}
		return parent::getContent($id, $tag, $inner, $attributes);
	}

}

class InputComponent extends HTMLFormElementComponent {

	function getContent($id, $tag, $inner, $attributes) {
		if($tag == "input"){
			$attributes["value"] = '<?php $' . $this->_holderName . '->out("' . $id . '"); ?>';
			return '<' . $this->getStartTag($id, $tag, $attributes) . ' />';
		}
		
		// inputタグ以外はLabelComponentと同じ振る舞いをする
		unset($attributes["name"]);
		unset($attributes["value"]);
		$inner = '<?php $' . $this->_holderName . '->out("' . $id . '"); ?>';
		
		return parent::getLabeledContent($id, $tag, $inner, $attributes);
	}

}

class CheckComponent extends HTMLFormElementComponent {

	function getContent($id, $tag, $inner, $attributes) {
		if($tag == "input"){
			if($this->_default){
				$value = $this->_default;
			}else{
				$value = (isset($attributes["value"])) ? $attributes["value"] : @$this->_attributes["value"];
			}
			if(!isset($this->_attributes["value"])){
				$this->_attributes["value"] = $value;
			}
			if($value instanceof HTMLAttributeComponent)
				$value = $value->getValue();
			$attributes["_checked"] = '<?php if($' . $this->_holderName . '->getString("' . $id . '") == "' . addslashes($value) . '"){ echo "checked"; } ?>';
			return '<' . $this->getStartTag($id, $tag, $attributes) . '/>';
		}
		
		// inputタグ以外はLabelComponentと同じ振る舞いをする
		unset($attributes["name"]);
		unset($attributes["value"]);
		$inner = '<?php $' . $this->_holderName . '->out("' . $id . '"); ?>';
		return parent::getLabeledContent($id, $tag, $inner, $attributes);
	}

}

class TextAreaComponent extends HTMLFormElementComponent {

	function getContent($id, $tag, $inner, $attributes) {
		if($tag == "textarea"){
			$inner = '<?php $' . $this->_holderName . '->out("' . $id . '"); ?>';
			return '<' . $this->getStartTag($id, $tag, $attributes) . '>' . $inner . "</textarea>";
		}
		if($tag == "input"){
			$attributes["value"] = '<?php $' . $this->_holderName . '->out("' . $id . '"); ?>';
			return '<' . $this->getStartTag($id, $tag, $attributes) . ' />';
		}
		
		// inputタグ以外はLabelComponentと同じ振る舞いをする
		unset($attributes["name"]);
		unset($attributes["value"]);
		$inner = '<?php $' . $this->_holderName . '->out("' . $id . '"); ?>';
		return parent::getLabeledContent($id, $tag, $inner, $attributes);
	}

}

class SelectComponent extends HTMLFormElementComponent {

	private $items = array();

	function setOptions($opt) {
		if(isset($opt["items"])){
			$this->setItems($opt["items"]);
		}
	}

	function setAttribute($key, $value) {
		if($key == "items"){
			$this->setItems($value);
			return $this;
		}
		return parent::setAttribute($key, $value);
	}

	function setItems(array $items) {
		if(is_array($items)){
			$this->items = $items;
		}
		return $this;
	}

	/**
	 * optionを登録する
	 *
	 * @see \lasa\view\component\HTMLComponent::getPrefix()
	 */
	function getPrefix($id) {
		return '$' . $this->_holderName . '->placeholder("' . $id . '@items",' . var_export($this->items, true) . ');';
	}

	function getContent($id, $tag, $inner, $attributes) {
		
		if($tag == "select"){
			
			$scripts = [];
			$scripts[] = '<?php ';
			$scripts[] = '$_key=$' . $this->_holderName . '->get("' . $id . '");';
			$scripts[] = 'foreach($' . $this->_holderName . '->getArray("' . $id . '@items") as $key => $value){ ';
			$scripts[] = '$selected=(strcmp($key,$_key)==0)?" selected":"";';
			$scripts[] = '$key=htmlspecialchars($key);$value=htmlspecialchars($value);';
			$scripts[] = 'echo "<option value=\"${key}\"${selected}>${value}</option>";';
			$scripts[] = '} ';
			$scripts[] = '?>';
			$inner .= implode("", $scripts);
			return '<' . $this->getStartTag($id, $tag, $attributes) . '>' . $inner . "</select>";
		}
		
		// selectタグ以外はLabelComponentと同じ振る舞いをする
		unset($attributes["name"]);
		unset($attributes["value"]);
		$inner = '<?php $' . $this->_holderName . '->h($' . $this->_holderName . '->getArrayItem("' . $id . '@items", "' . $id . '")); ?>';
		return parent::getLabeledContent($id, $tag, $inner, $attributes);
	}

}

class SectionComponent extends HTMLComponent {

	private $isAutoMode = false;

	function setAutoMode() {
		$this->isAutoMode = true;
	}

	function getTagPrefix() {
		if($this->isAutoMode){
			return "section";
		}
		return parent::getTagPrefix();
	}

	function isWrappedComponent() {
		return true;
	}

	function getContent($id, $tag, $inner, $attributes) {
		$res = '<?php \lasa\view\View::outputSection("' . $id . '"); ?>';
		if($this->_outerEndNewLine)
			$res .= "\n";
		return $res;
	}
}

class PathResolverComponent extends HTMLComponent {

	private $isAutoMode = false;

	function setAutoMode() {
		$this->isAutoMode = true;
	}

	function getTagPrefix() {
		if($this->isAutoMode){
			return "path";
		}
		return parent::getTagPrefix();
	}

	function getContent($id, $tag, $inner, $attributes) {
		// 元の目印を削除する
		$key = $this->getTagPrefix() . ":" . $id;
		unset($attributes[$key]);
		
		preg_match("#@([^/]+)(.*)#", $this->_optionValue, $tmp);
		$attributes[$id] = '<?php \lasa\view\View::outputPath("' . $tmp[1] . '"); ?>' . $tmp[2];
		
		return parent::getContent($id, $tag, $inner, $attributes);
	}
}

class JSONComponent extends HTMLComponent {

	private $options = [];

	function setOptions($opt) {
		$val = array_shift($opt);
		if(is_array($opt)){
			$this->options = $val;
		}
		return $this;
	}

	function getTagPrefix() {
		return "json";
	}

	function getContent($id, $tag, $inner, $attributes) {
		$codes = [];
		foreach($this->options as $key => $opt){
			if(is_numeric($key))
				$key = $opt;
			$codes[] = '"' . $key . '" => $' . $this->_holderName . '->get("' . $opt . '")';
		}
		
		$start = '<' . $this->getStartTag($id, $tag, $attributes) . '><?php echo json_encode([';
		$end = ']); ?></' . $tag . '>';
		
		return $start . implode(",", $codes) . $end;
	}
}

class ListComponent extends HTMLComponent {

	private $func = null;

	function __construct(\Closure $func) {
		$this->func = $func;
	}

	function isWrappedComponent() {
		return true;
	}

	function getContent($id, $tag, $inner, $attributes) {
		
		// インデントを揃える
		if(preg_match("/\n?([\t ]+)/", $inner, $tmp)){
			$indent = $tmp[1];
			$inner = $indent . trim($inner);
		}
		
		$obj = new HTMLViewComponent($inner);
		$obj->_holderName = $id . "_list";
		
		$func = $this->func;
		$func($obj);
		
		$inner = $obj->compile();
		
		$start = '<?php foreach($' . $this->_holderName . '->getArray("' . $id . '") as $array): $' . $id . '_list = new ' . VIEWHOLDER_CLASS . '($array); ?>';
		if($this->_innerStartNewLine)
			$start .= "\n";
		$end = '<?php endforeach; ?>';
		if($this->_innerEndNewLine)
			$end = "\n" . $end;
		if($this->_outerEndNewLine)
			$end .= "\n";
		
		if($tag == "!--"){
			return $start . $inner . $end;
		}
		
		return $start . "<" . $this->getStartTag($id, $tag, $attributes) . ">" . $inner . "\n</" . $tag . ">" . $end;
	}

	function getTagPrefix() {
		return "list";
	}

}

class PagerComponent extends HTMLComponent {

	function isWrappedComponent() {
		return true;
	}

	public function getPagerFunction() {
		return function ($view) {
			$view->init(function ($view, $data) {
				$current = (isset($data["current"])) ? (int)$data["current"] : 1;
				$start = (isset($data["start"])) ? (int)$data["start"] : 1;
				if($start < 1)
					$start = 1;
				$end = (isset($data["end"])) ? (int)$data["end"] : -1;
				$max = (isset($data["max"])) ? (int)$data["max"] : $end;
				$link = (isset($data["link"])) ? $data["link"] : "";
				
				$view->value("first_page", $current == 1);
				$view->value("last_page", $current == $max);
				
				$view->value("first_page_link", $link . 1);
				$view->value("last_page_link", $link . $max);
				
				$view->value("next_page_link", ($current < $max) ? $link . ($current + 1) : false);
				$view->value("previous_page_link", ($current > 1) ? $link . ($current - 1) : false);
				
				$pages = array();
				for($i = $start; $i <= $end; $i++){
					$pages[] = array(
						"page_link" => $link . $i,
						"page" => $i,
						"current_page" => ($i == $current)
					);
				}
				$view->value("pages", $pages);
			});
			
			$view->addCondition("first_page");
			$view->addCondition("last_page");
			
			$view->addLink("first_page_link");
			$view->addLink("last_page_link");
			$view->addLink("next_page_link");
			$view->addLink("previous_page_link");
			
			$view->addList("pages", function ($list) {
				$list->addLink("page_link");
				$list->addLabel("page");
				$list->addCondition("current_page");
			});
		};
	}

	public function getContent($id, $tag, $inner, $attributes) {
		
		if($tag == "!--"){
			$inner = trim($inner);
		}
		
		$obj = new HTMLViewComponent($inner);
		$obj->_holderName = $id . "_pager";
		
		$func = $this->getPagerFunction();
		$func($obj);
		$inner = $obj->compile();
		$start = '<?php $' . $id . '_pager = new ' . VIEWHOLDER_CLASS . '($' . $this->_holderName . '->get("' . $id . '")); ?>';
		$end = "";
		
		if($tag == "!--"){
			return $start . $this->_startIndent . $inner . $end;
		}
		
		return $start . $this->_startIndent . "<" . $this->getStartTag($id, $tag, $attributes) . ">" . trim($inner) . "\n" . $this->_endIndent . "</" . $tag . ">" . $end;
	}

}
