<?php
/*
 * PHPTokenParser.php
 */
namespace lasa\view\builder;

class PHPTokenParser{
	
	private $index = 0;
	private $tokens = [];
	
	private $_code;
	private $_use_class = [];
	
	/**
	 * @return PHPTokenParser
	 * @param unknown $path
	 */
	public static function getParserFromPath($path){
		return self::getParser(file_get_contents($path));
	}
	
	/**
	 * @param string $code
	 * @return \lasa\view\builder\PHPTokenParser
	 */
	public static function getParser($code){
		$parser = new PHPTokenParser();
		$parser->index = 0;
		$parser->tokens = token_get_all($code);
		$parser->count = count($parser->tokens);
		$parser->parse();
		return $parser;
	}
	
	function parse(){
		$res = [];
		$use_class = [];
		
		while($this->hasNext()){
			$token = $this->next();
			
			if(!is_array($token)){
				$res[] = $token;
				continue;
			}
			
			$type = $token[0];
			$code = $token[1];
			
			if($type == T_USE){
				$className = $this->nextToEndLine();
				if($className){
					list($baseClassName, $fullClassName) = $this->explodeUseCode($className);
					$use_class[$baseClassName] = $fullClassName;
					continue;
				}
			}
			
			if($type == T_COMMENT){
				continue;
			}
			
			$res[] = $code;
		}
		
		
		$this->_code = implode("", $res);
		$this->_use_class = $use_class;
	}
	
	/**
	 * 掃除した綺麗なコードを取得する
	 * @param unknown $options
	 * @return number|mixed
	 */
	function cleanup($code = null, $options = []){
		
		if(!$code){
			$code = $this->_code;
		}
		
		$use_class = $this->_use_class;
		
		/*
		 * tabとかをどうするかはここで決める
		 */
		if(isset($option["clean_tab"]) && $option["clean_tab"]){
			//文頭のtab、文末のtabを除く
			$code = preg_replace('#^[\t]+#m', "", $code);
			$code = preg_replace('#[\t]+$#m', "", $code);
			
			//空行を除く
			$code = preg_replace("#^[\r\n]+#m", "", $code);
		}
		
		/*
		 * use句を除去
		 */
		
		//長い順に処理する
		uksort($use_class, function($a, $b){
			return (strlen($a) > strlen($b)) ? -1 : 1;
		});
		
		foreach($use_class as $baseClassName => $className){
			$code = str_replace(" " . $baseClassName, $className, $code);
		}
		
		return $code;
	}
	
	function next(){
		$res = $this->tokens[$this->index];
		$this->index++;
		return $res;
	}
	
	function prev(){
		$this->index--;
		$res = $this->tokens[$this->index];
		return $res;
	}
	
	function nextToEndLine(){
		$tmp = "";
		while(true){
			$token = $this->next();
			if(!is_array($token) && $token == ";"){
				break;
			}
			
			if(!is_array($token)){
				$this->prev();
				return null;
			}
			
			$tmp .= $token[1];
		}
		
		return $tmp;
	}
	
	function hasNext(){
		return ($this->index < $this->count);
	}
	
	/* helper */
	
	function explodeUseCode($className){
		$useAs = explode(" as ", $className);
		if(count($useAs) > 1){
			return [$useAs[1], $useAs[0]];
		}
		
		$list = explode("\\", $className);
		return [$list[count($list)-1], $className];
	}
}