<?php
namespace lasa\view\loader;

use lasa\view\Render;

class FileLoader implements LoaderInterface{
	
	protected $base = null;
	
	/**
	 * @param string $baseDir
	 */
	public function __construct($baseDir){
		$this->base = $baseDir;
	}
	
	
	/* (non-PHPdoc)
	 * @see \lasa\view\loader\LoaderInterface::getBuilder()
	 */
	public function getBuilder($name) {
		list($scriptPath, $templatePath) = $this->getPath($name);
		
		//dummy view object
		$view = new BlankRender();
		
		//種類を調べるために一度読み込みを行う
		ob_start();
		$res = include($scriptPath);
		ob_end_clean();
		
		if(is_array($res)){
			if(file_exists($templatePath)){
				$template = file_get_contents($templatePath);
			}else{
				$template = \lasa\view\builder\StandardViewBuilder::loadTemplate($scriptPath);
			}
			$compiler = new \lasa\view\builder\StandardViewBuilder($res, $template);
			return $compiler;
		}
		
		if($res instanceof \Closure){
			$compiler = new \lasa\view\builder\ClosureViewBuilder($res);
			if(file_exists($templatePath)){
				$compiler->setTemplate(file_get_contents($templatePath));
			}else{
				$compiler->loadTemplate();
			}
			return $compiler;
		}
		
		if($res === 1){
			$compiler = new \lasa\view\builder\PlainViewBuilder($scriptPath);
			return $compiler;
		}
		
		return null;
	}

	/* (non-PHPdoc)
	 * @see \lasa\view\loader\LoaderInterface::getCacheName()
	 */
	public function getCacheName($name) {
		return $name . "_" . md5($this->base . $name) . ".php";
	}

	/* (non-PHPdoc)
	 * @see \lasa\view\loader\LoaderInterface::isChanged()
	 */
	public function isChanged($name, $time) {
		list($scriptPath, $templatePath) = $this->getPath($name);
		$mtime = filemtime($scriptPath);
		if($mtime > $time){ return true; }
		
		$mtime = ($templatePath) ? filemtime($templatePath) : 0;
		if($mtime > $time){ return true; }
		
		return false;
	}
	
	protected function getPath($name){
		
		$dir = $this->base . DIRECTORY_SEPARATOR;
		$scriptPath = $dir . $name . ".php";
		$templatePath = $dir . $name . ".html";
		
		//HTMLだけでも可
		if(!file_exists($scriptPath)){
			$scriptPath = $templatePath;
			$templatePath = null;
		}
		
		if(!file_exists($templatePath)){
			$templatePath = null;
		}
		
		return [$scriptPath, $templatePath];
	}

}

class BlankRender extends Render{
	public function __construct(){
		
	}
}