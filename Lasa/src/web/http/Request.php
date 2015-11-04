<?php
namespace lasa\web\http;

/*
 * Request.php
 */
class Request {
	
	public $headers = [];
	public $params = [];
	public $method = "get";
	public $url = "/";
	public $path = "/";
	public $base = "/";
	public $inputs = [];
	
	/**
	 * @return Request
	 */
	public static function postRequest($url, $post_values = []){
		$req = new Request($_REQUEST, $_SERVER);
		$req->setMethod("post")->setUrl($url);
		$req->inputs = $post_values;
		return $req;
	}
	
	/**
	 * @return Request
	 */
	public static function getRequest($url, $params = []){
		$req = new Request($_REQUEST, $_SERVER);
		$req->setMethod("get")->setUrl($url);
		$req->inputs = $params;
		return $req;
	}
	
	function __construct(array $req, array $server){
		$this->params = $req;
		$this->headers = new Headers($server);
		$this->method = strtolower($this->headers->get("REQUEST_METHOD","get"));
		$this->url = $this->headers->get("REQUEST_URI","/");
		list($this->path,) = explode("?", $this->headers->get("REQUEST_URI","/"));
		$this->base = "/";
		$this->inputs = ($this->method == "get") ? $req : $_POST;
		
		$this->path = urldecode(preg_replace("#/+#","/",$this->path));
	}
	
	function header($key = null){
		if($key){
			return $this->headers->get($key);
		}
		return $this->headers;
	}

	
	function isPost(){
		return ($this->method == "post");
	}
	
	function isGet(){
		return ($this->method == "get");
	}
	
	function method(){
		return $this->method;
	}
	
	function url(){
		return $this->url;
	}
	
	function inputs(){
		return $this->inputs;
	}
	
	function params(){
		return $this->params;
	}
	
	function input($key, $defValue = null){
		return (isset($this->inputs[$key])) ? $this->inputs[$key] : $defValue;
	}
	
	function query($key, $defValue = null){
		return (isset($this->params[$key])) ? $this->params[$key] : $defValue;
	}
	
	/* setter */
	
	public function setUrl($url){
		$this->url = preg_replace("#/+#","/",$url);
		if($this->url == "/"){
			$this->base = $this->path;
		}else if(strlen($this->url) > 0 && strpos($this->path, $this->url) !== false){
			$this->base = substr($this->path, 0, -strlen($this->url));
		}else{
			$this->base = $this->path;
		}
	}
	
	public function setMethod($method){
		$this->method = strtolower($method);
		return $this;
	}
	
	public function path(){
		return $this->path;
	}
	
	public function base(){
		return $this->base;
	}
	
}