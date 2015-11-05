<?php
/*
 * PHPTokenParser.php
 */
namespace lasa\view\builder;

/**
 * PHP混じりのコードをパースして次の３つに分割する
 * head 宣言部
 * code PHPコード
 * body HTML部分
 */
class PHPTokenParser{
	
	private $index = 0;
	private $tokens = [];
	
	private $_head;
	private $_code;
	private $_body;
	
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
		$head_lines = [];
		$code_lines = [];
		$body_lines = [];
		
		$use_class = [];
		
		$state = "head";
		$line = 0;
		$docComment = null;
		while($this->hasNext()){
			$token = $this->next();
			$line++;
			
			if(!is_array($token)){
				if($state == "head"){
					$head_lines[] = $token;
				}else if($state = "code"){
					$code_lines[] = $token;
				}else if($state == "body"){
					$body_lines[] = $token;
				}
				
				continue;
			}
			
			$type = $token[0];
			$code = $token[1];
			
			//use句
			if($type == T_USE){
				$className = $this->nextToEndLine();
				if($className){
					list($baseClassName, $fullClassName) = $this->explodeUseCode($className);
					$use_class[$baseClassName] = $fullClassName;
				}
			}
			
			//最初のreturnからはcode句
			if($state == "head" && $type == T_RETURN){
				$state = "code";
			}
			
			if($state == "head"){
				$head_lines[] = $code;
			}else if($state == "code"){
				$code_lines[] = $code;
			}else if($state == "body"){
				$body_lines[] = $code;
			}
			
			//最初のclose tagからbody句になります
			if($state != "body" && $type == T_CLOSE_TAG){
				$state = "body";
			}
		}
		
		$this->_head = implode("", $head_lines);
		$this->_code = implode("", $code_lines);
		$this->_body = implode("", $body_lines);
		$this->_use_class = $use_class;
		
	}
	
	/**
	 * 掃除した綺麗なコードを取得する
	 * @param unknown $options
	 * @return number|mixed
	 */
	function cleanup($code = null, $options = []){
		
		if(!$code){
			$code = $this->_head . $this->_code . $this->_body;
		}
		
		$use_class = $this->_use_class;
		
		/*
		 * tabとかをどうするかはここで決める
		 */
		if(isset($options["clean_tab"]) && $options["clean_tab"]){
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
	
	function getHead(){
		return $this->cleanup($this->_head);
	}
	
	function getCode(){
		return $this->cleanup($this->_code);
	}
	
	function getBody(){
		return $this->cleanup($this->_body);
	}
	
	function getDocComment(){
		$tokens = token_get_all($this->_head);
		$docComment = [];
		foreach($tokens as $token){
			if(!is_array($token)){
				continue;
			}
			$type = $token[0];
			$code = $token[1];
			
			if($type == T_COMMENT || $type == T_DOC_COMMENT){
				$docComment[] = $code;
			}
		}
		return implode("", $docComment);
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