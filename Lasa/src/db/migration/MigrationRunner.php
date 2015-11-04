<?php
/*
 * MigrationLoader.php
 */
namespace lasa\db\migration;

class MigrationRunner{
	
	private $directory = null;
	private $migrations = [];
	
	/**
	 * @return MigrationLoader
	 * @param string $directory
	 */
	public static function run($directory){
		$loader = new MigrationRunner($directory);
		$loader->execute();
		return $loader;
	}
	
	public function __construct($path){
		$this->directory = $path;
	}
	
	private function execute(){
		
		$directory = $this->directory;
		
		$migrations = [];
		foreach(glob($directory . "/*.php") as $path){
			$res = require $path;
			if(is_array($res)){
				$migrations = array_merge($migrations, $res);
			}else{
				$migrations[] = $res;
			}
		}
		$this->migrations = $migrations;
		
		Migration::run($this->migrations);
	}
}