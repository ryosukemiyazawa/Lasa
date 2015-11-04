<?php
namespace lasa\view;

/*
 * View.php
 */
class View extends Render{
	
	public static $_injection = ["before" => [], "after" => [], "section" => [], "path" => []];
	public static $_observer = [];
	
	/**
	 *
	 * @param string $name
	 * @param \Closure $func
	 */
	public static function observe($observer, $_ = null){
		$_name = null;
		$_func = null;
		
		if($observer instanceof \Closure){
			$_name = "*";
			$_func = $observer;
		}else if($_ instanceof \Closure){
			$_name = $observer;
			$_func = $_;
		}else{
			throw new \Exception("[View]invalid observer");
		}
		
		if(!isset(self::$_observer[$_name]))self::$_observer[$_name] = [];
		self::$_observer[$_name][] = $_func;
	}
	
	public static function before($name, $content){
		if(!isset(self::$_injection["before"][$name])){
			self::$_injection["before"][$name] = [];
		}
		self::$_injection["before"][$name][] = $content;
	}
	
	public static function after($name, $content){
		if(!isset(self::$_injection["after"][$name])){
			self::$_injection["after"][$name] = [];
		}
		self::$_injection["after"][$name][] = $content;
	}
	
	public static function section($name, $content){
		self::$_injection["section"][$name] = $content;
	}
	
	public static function path($name, $path = null){
		
		if($path){
			self::$_injection["path"][$name] = $path;
		}
		
		if(isset(self::$_injection["path"][$name])){
			return self::$_injection["path"][$name];
		}
		return null;
	}

	
	public static function notifyObserver($name, $view){
		//root observer
		if(isset(self::$_observer["*"])) foreach(self::$_observer["*"] as $func){
			$func($view);
		}
		
		if(!isset(self::$_observer[$name]))return;
		
		foreach(self::$_observer[$name] as $func){
			if($func instanceof \Closure){
				$func($view);
			}
		}
	}
	
	public static function outputBefore($name){
		if(!isset(self::$_injection["before"][$name])){
			return;
		}
		
		foreach(self::$_injection["before"][$name] as $inject){
			if($inject instanceof \Closure){
				$inject();
			}else if(is_array($inject)){
				echo implode("\n",$inject) . "\n";
			}else{
				echo $inject . "\n";
			}
		}
	}
	
	public static function outputAfter($name){
		if(!isset(self::$_injection["after"][$name])){
			return;
		}
		
		foreach(self::$_injection["after"][$name] as $inject){
			if($inject instanceof \Closure){
				$inject();
			}else if(is_array($inject)){
				echo implode("\n",$inject) . "\n";
			}else{
				echo $inject . "\n";
			}
		}
	}
	
	public static function checkSection($name){
		if(!isset(self::$_injection["section"][$name])){
			return false;
		}
		
		$inject = self::$_injection["section"][$name];
		return !empty($inject);
	}
	
	public static function outputSection($name){
		if(!isset(self::$_injection["section"][$name])){
			return;
		}
		
		$inject = self::$_injection["section"][$name];
		if($inject instanceof \Closure){
			$inject();
		}else{
			echo $inject;
		}
	}
	
	public static function outputPath($name){
		if(!isset(self::$_injection["path"][$name])){
			return;
		}
		echo self::$_injection["path"][$name];
	}
	
	private $_layout = null;
	
	public function layout($layout, $values = []){
		$this->_layout = [$layout, $values];
		return $this;
	}
	
	/**
	 * @return string of null
	 */
	public function getLayout(){
		return ($this->_layout) ? $this->_layout[0] : null;
	}
	
	/**
	 * @return array
	 */
	public function getLayoutValues(){
		return ($this->_layout) ? $this->_layout[1] : [];
	}
	
	/**
	 *
	 * render
	 * @see \lasa\view\Render::render()
	 */
	public function display($layout = null, $values = null){
		
		View::notifyObserver($this->getName(), $this);
		
		if($layout){
			$html = $this->getContent();
			View::section("@", $html);
			$view = Engine::currentEngine()->load($layout, $values);
			$view->display();
		}else if($this->_layout){
			$content = $this->getContent();
			View::section("@", $content);
			$view = Engine::currentEngine()->load($this->_layout[0], $this->_layout[1]);
			$view->display();
		}else{
			$this->render();
		}
	}
}

