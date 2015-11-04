<?php
namespace lasa\web;

use lasa\view\ViewContainer;
use lasa\Container;
use lasa\web\loader\Loader;
use lasa\web\http\Request;
use lasa\web\http\Response;

class Application{
	
	static $_inst;
	
	//webframework
	
	/**
	 * @var \lasa\web\http\Request
	 */
	protected $_request;
	
	/**
	 * @var \lasa\common\Environment
	 */
	protected $_env;
	
	/**
	 * @var \lasa\web\loader\Loader
	 */
	protected $_loader;
	protected $_options = [];
	
	//bind functions
	protected $_lamda = [];
	
	protected $_filter = [];
	protected $_router = [];
	protected $_error = [];
	
	//result
	protected $_response;
	protected $_view;
	protected $_container;
	
	public function __construct(Loader $loader, $opt = null){
		$this->_container = Container::getInstance();
		$this->_request = new Request($_REQUEST, $_SERVER);
		$this->_loader = $loader;
		if(!$opt)$opt = [];
		
		$loader->prepare($this, $opt);
		$this->_options = $opt;
		$this->_response = new Response();
		
		//set default error handler
		$this->error(function(){
			echo "oops";
		},-1);
		
		if(isset($opt["url"])){
			$this->request()->setUrl($opt["url"]);
		}
		
		self::$_inst = $this;
		
	}
	
	/**
	 * getInstance
	 * @return \lasa\web\Application
	 */
	public static function getInstance() {
		return self::$_inst;
	}
	
	/**
	 * @return \lasa\web\http\Response
	 */
	public function response(){
		return $this->_response;
	}
	
	/**
	 *
	 * @return \lasa\web\http\Request
	 */
	public function request(){
		return $this->_request;
	}
	
	public function setRequest(\lasa\web\http\Request $req){
		$this->_request = $req;
		return $this;
	}
	
	public function container(){
		return $this->_container;
	}
	
	/**
	 * @return \lasa\common\Environment
	 */
	public function env(){
		return $this->_env;
	}
	
	/**
	 * ApplicationにControllerを追加する処理
	 * @param $key
	 * @param $lamda
	 */
	public function bind($key, \Closure $lamda){
		//メソッド名と引数で分類する
		$keys = explode("/",$key);
		$method = array_shift($keys);
		$arg_count = 0;
		
		$arg_regex = [];
		foreach($keys as $value){
			if(!$value)continue;
			$value = str_replace("(:num)","([0-9]+)",$value);
			$value = str_replace("(:any)","([^/]+)",$value);
			$arg_regex[] = $value;
			$arg_count++;
		}
		$key = substr($key, strlen($method));
		if(!$key){
			$key = $method;
		}
		
		if(!isset($this->_lamda[$method]))$this->_lamda[$method] = [];
		$this->_lamda[$method][$key] = [
			$lamda,
			$arg_count,
			implode("/", $arg_regex)
		];
	}
	
	/**
	 * Applicationにfilterを追加する処理
	 * @param $filter
	 * @param string $_
	 */
	public function filter($filter, $_ = null){
		$_prefix = "";
		$_filter = null;
		if($filter instanceof \Closure){
			$_filter = $filter;
		}else{
			$_prefix = $filter;
			$_filter = $_;
		}
		
		$this->_filter[] = [
			"prefix" => $_prefix,
			"filter" => $_filter
		];
	}
	
	/**
	 * ルーティング情報を追加する
	 * @param any $router
	 * @param string $_
	 */
	public function route($router, $_ = null){
		
		$_prefix = "";
		$_router = null;
		$_priority = 10;
		
		if($router instanceof \Closure){
			$_router = $router;
			if($_)$_priority = $_;
		}else{
			$_prefix = $router;
			$_router = $_;
			if(func_num_args() > 2)$_priority = \func_get_arg(2);
		}
		
		$this->_router[] = [
			"prefix" => $_prefix,
			"router" => $_router,
			"priority" => $_priority
		];
		
	}
	
	/**
	 * エラーハンドラーを追加する
	 */
	public function error($error, $_ = null){
		$_code = "";
		$_error = null;
		$_priority = 10;
		
		if($error instanceof \Closure){
			$_error = $error;
			if($_)$_priority = $_;
		}else{
			$_code = $_error;
			$_error = $_;
			if(func_num_args() > 2)$_priority = \func_get_arg(2);
		}
		
		$this->_error[] = [
			"code" => $_code,
			"error" => $_error,
			"priority" => $_priority
		];
	}
	
	/**
	 * アプリケーションを実行する
	 * @return \lasa\web\Application
	 */
	public function execute(){
		$this->executeRequest($this->_request);
		
		// 終了処理
		$this->_response->flush();
		
		return $this;
	}
	
	/**
	 * Requestを実行する
	 * @param \lasa\web\http\Request $request
	 * @return \lasa\web\Application
	 */
	public function executeRequest(\lasa\web\http\Request $request){
		
		//リセット処理
		$this->_response = new Response();
		$this->setRequest($request);
		$this->_view = null;
		
		$url = $request->url();
		
		/*
		 * フィルターを実行する
		 */
		foreach($this->_filter as $array){
			$prefix = $array["prefix"];
			$this->_options["prefix"] = $prefix;
			
			if(strlen($prefix) < 1 || strpos($url, $prefix) === 0){
				$_url = substr($url, strlen($prefix)) . "";
				$filter = $array["filter"];
				$filter($this, $_url);
			}
		}
		
		
		/*
		 * ルーティングからコントローラーを取得する
		 */
		
		uasort($this->_router, function($a, $b){
			
			//優先度が高い順
			if($a["priority"] < $b["priority"]) return 1;
			if($a["priority"] > $b["priority"]) return -1;
			
			//prefixが長い順に指定する
			$ap = strlen($a["prefix"]);
			$bp = strlen($b["prefix"]);
			if($ap < $bp)return 1;
			if($ap > $bp)return -1;
			
			return 0;
		});
		
		//Filterで変換がかかっているかもしれないので再取得する
		$url = $request->url();
		
		$controller_exists = false;
		foreach($this->_router as $array){
			$prefix = $array["prefix"];
			
			if(strlen($prefix) < 1 || strpos($url, $prefix) === 0){
				$router = $array["router"];
				$_url = substr($url, strlen($prefix)) . "";
				$res = $router($this, $_url);
				
				//Router
				if($res instanceof \lasa\web\router\Router){
					$res = $res->load($this, $_url);
				}
				
				if($res instanceof \lasa\web\router\Route && $res->success()){
					$controller_exists = true;
					$this->_options["prefix"] = $prefix;
					$this->run($request->method(), $res->getArgments());
					break;
				}else if($res === true){
					$controller_exists = $res;
					$this->_options["prefix"] = $prefix;
					$this->run($request->method());
					break;
				}else if($res === false){
					continue;
				}
			}
		}
		
		// エラー表示
		if(!$controller_exists){
			$this->abort(404, "Not Found");
		}
		
		if($this->_view){
			$this->display($this->_view);
		}
		
		return $this;
	}
	
	public function display(\lasa\view\View $view){
		ob_start();
		$view->display();
		$content = ob_get_contents();
		ob_end_clean();
		$this->response()->setContent($content);
	}
	
	/**
	 * HTTPリダイレクトを行う
	 * @param string $suffix
	 * @param array $query
	 */
	public function redirect($suffix, $query = []){
		$url = "";
		$baseUrl = $this->request()->base();
		if($baseUrl == "/")$baseUrl = "";
		$suffix = ltrim($suffix, "/");
		
		if(strpos($suffix, "://") !== false){
			$url = $suffix;
		}else if($this->option("prefix")){
			$url = $baseUrl . "/" . $this->option("prefix") . "/" . $suffix;
		}else{
			$url = $baseUrl . "/" . $suffix;
		}
		
		if($query){
			$query_str = (is_array($query)) ? http_build_query($query) : $query;
			if(strpos($url, "?") === false){
				$url .= "?" . $query_str;
			}else{
				$url .= "&" . $query_str;
			}
		}
		
		$this->_response->location($url);
		$this->_response->flush();
	}
	
	public function jump($url){
		$this->_response->location($url);
		$this->_response->flush();
	}
	
	public function run($type, $args = null){
		
		if(isset($this->_lamda[$type])){
			
			$arg_count = 0;
			$arg_value = "";
			
			if($args &&isset($args[0])){
				$arg_count = count($args);
				$arg_value = implode("/", $args);
			}
			
			//強制0個の取得
			if($arg_count == 0 && isset($this->_lamda[$type]["/"])){
				$func = $this->_lamda[$type]["/"][0];
				return $func($this, $args);
			}
			
			//getしか指定していない時
			if(count($this->_lamda[$type]) === 1 && isset($this->_lamda[$type][$type])){
				$func = $this->_lamda[$type][$type][0];
				return $func($this, $args);
			}
			
			foreach($this->_lamda[$type] as $array){
				list($func, $count, $arg_regex) = $array;
				
				//引数が同じのを取得
				if($count == $arg_count){
					if($arg_regex){
						if(preg_match("#^" . $arg_regex . "$#", $arg_value)){
							return $func($this, $args);
						}
					}else{
						return $func($this, $args);
					}
				}
			}
			
			//無指定(例:get,post)があればそれを使う
			if(isset($this->_lamda[$type][$type])){
				$func = $this->_lamda[$type][$type][0];
				return $func($this, $args);
			}
			
		}
		
		return $this->abort(405, "Method is not supported");
	}
	
	/**
	 * エラーを発生して終了する
	 * @param string $code
	 * @param string $message
	 */
	public function abort($code, $message = ""){
		
		//エラーコードを設定する
		if(preg_match("#[3-5][0-9]{2}#", $code)){
			$this->response()->setStatus($code);
		}
		
		//ソートする
		uasort($this->_error, function($a, $b){
			
			//優先度が高い順
			if($a["priority"] < $b["priority"]) return 1;
			if($a["priority"] > $b["priority"]) return -1;
			
			//prefixが長い順に指定する
			$ap = $a["code"];
			$bp = $b["code"];
			if($ap < $bp)return 1;
			if($ap > $bp)return -1;
			
			//追加順
			return 1;
			
		});
		
		//エラーを実行する
		foreach($this->_error as $error){
			$func = $error["error"];
			$res = $func($code, $message);
			if($res === false){
				continue;
			}else{
				break;
			}
		}
		
	}
	
	/**
	 * Queryを取得する
	 * @param string $key
	 * @param string $defValue
	 * @return Ambigous <string, multitype:>
	 */
	public function query($key, $defValue = null){
		return $this->request()->query($key, $defValue);
	}
	
	/**
	 * @param string $template
	 * @param string $layout
	 * @param [] $args
	 * @return \lasa\view\View
	 */
	public function view($name, $values = []){
		if(func_num_args() > 2){
			for($i=2;$i<func_num_args();$i++){
				$tmp = func_get_arg($i);
				if(is_array($tmp)){
					$values = array_merge($values, $tmp);
				}
			}
		}
		
		$this->_view = $this->_loader->view($name, $values);
		return $this->_view;
	}
	
	/**
	 * @param string $name
	 * @param [] $values
	 * @return \lasa\validator\Validator
	 */
	public function validate($name, $values = []){
		return $this->_loader->validator($name, $values);
	}
	
	/**
	 * loading module
	 * @param unknown $moduleId
	 * @param string $path
	 * @return unknown
	 */
	public function module($moduleId, $path = null){
		$moduleName = $moduleId;
		if($path){
			$moduleName = $path;
		}
		
		$module = $this->_loader->module($moduleName);
		$module->setApplication($this);
		$this->$moduleId = $module;
		return $module;
	}
	
	public function getLoader(){
		return $this->_loader;
	}
	
	public function setLoader($_loader){
		$this->_loader = $_loader;
		return $this;
	}
	
	public function setEnv($env){
		if($env instanceof \Closure){
			$env = $env($this);
		}
		
		if($env instanceof \lasa\common\Environment){
			$this->_env = $env;
		}else{
			throw new \Exception("[" . __CLASS__ . "]invalid env");
		}
		
	}
	
	public function option($key, $defValue = null){
		if(isset($this->_options[$key]))return $this->_options[$key];
		return $defValue;
	}
	
	/* CSRFトークン */
	
	/**
	 * Create CSRF Token
	 * @param string $tokenName
	 */
	public function createToken($tokenName = "app"){
		$secret = $this->option("csrf_secret", md5(php_uname()));
		$time = time();
		return $time . "|" . md5($secret . $time . session_id() . $tokenName);
	}
	
	/**
	 * Check CSRF Token
	 * @param string $tokenName
	 */
	public function isValidToken($tokenName = "app", $options = []){
		
		$secret = $this->option("csrf_secret", md5(php_uname()));
		$key = $this->option("csrf_key", "__csrf_token");
		$token = $this->request()->input($key);
		$tokens = explode("|", $token);
		if(count($tokens) != 2){
			return false;
		}
		
		list($time, $hash) = $tokens;
		$time_limit = time() - $this->option("csrf_lifetime", 1800);	//30minutes token life
		if($time < $time_limit){
			return false;
		}
		
		if($hash != md5($secret . $time . session_id() . $tokenName)){
			return false;
		}
		
		return true;
	}
	
	/* コンテナとしての振る舞い */
	
	public function put($key, $value){
		$this->container()->put($key, $value);
	}
	
	public function get($key, $defValue = null){
		return $this->container()->get($key, $defValue);
	}
	
	/* セッション */
	
	public function session($key = "app"){
		if(!isset($_SESSION) && !headers_sent())session_start();
		$session = $this->container()->get("session@" . $key);
		if(!$session){
			$session = new Session($key);
			$this->container()->put("session@" . $key, $session);
		}
		return $session;
	}
	
}