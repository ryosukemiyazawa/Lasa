<?php
namespace lasa\db;
use PDO;
use lasa\db\builder\DAOBuilder;

class DAO{
	
	use DAOTrait;
	
	/**
	 * @return DAO
	 * @param string $name
	 */
	public static function get($name){
		
		static $_daos;
		if(!$_daos){
			$_daos = array();
		}
		
		if(!isset($_daos[$name])){
			//自動生成を行う
			$implClassName = $name . "DAOImpl";
			if(!class_exists($implClassName)){
				
				$filepath = DB::getConfigure("cache_dir") . "/" . $name . "DAO.php";
				DAOBuilder::create($name)->write($filepath);
				require $filepath;
				
				if(!class_exists($implClassName)){
					throw new \Exception("failed to load " . $name . "DAO");
				}
			}
			
			$dao = new $implClassName();
			$_daos[$name] = $dao;
		}
		
		$dao  = $_daos[$name];
		$dao->setLimit(null);
		$dao->setOffset(null);
		
		return $dao;
		
	}
	
}


