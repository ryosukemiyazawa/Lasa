<?php
namespace lasa\db;

use lasa\db\DAO;
use lasa\db\builder\DAOBuilder;

trait Model{

	private $id;

	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = (int)$id;
		return $this;
	}

	public function save(){
		$id = $this->getId();

		if($id){
			if(method_exists($this, "isUseTimeStamps")){
				$this->setUpdate(time());
			}
			self::DAO()->update($this);
		}else{
			$id = self::DAO()->insert($this);
			$this->setId($id);
		}
	}
	
	public function delete(){
		self::DAO()->delete($this->getId());
	}

	public static function all(){
		return self::DAO()->get();
	}

	/**
	 * @return DAOBase
	 */
	public static function DAO(){

		$daoClassName = __CLASS__ . "DAO";
		
		if(class_exists($daoClassName . "Impl")){	//check implemented
			return DAO::get(__CLASS__);
		}

		$filepath = DB::getConfigure("cache_dir"). DIRECTORY_SEPARATOR . $daoClassName . ".php";
// 		if(file_exists($filepath)){
// 			require $filepath;
// 			return DAO::get(__CLASS__);
// 		}

		//DAOを作成する
		if(class_exists(DAO::class)){
			DAOBuilder::create(__CLASS__)->write($filepath);
			require $filepath;
		}
		
		return DAO::get(__CLASS__);
	}

	/* relation */

	/**
	 * @return OneToOne
	 */
	public function hasOne(){
		return null;
	}

	/**
	 * @return OneByOne
	 */
	public function hasMany(){
		return null;
	}

	public function belongsTo(){
		return null;
	}

	public function hasManyAndBelongsTo(){
		return null;
	}

}

trait TimeStamps{

	/**
	 * @column created_at
	 */
	private $create;

	/**
	 * @column updated_at
	 */
	private $update;
	
	public function isUseTimeStamps(){
		return true;
	}

	public function getCreate(){
		if(!$this->create)$this->create = time();
		return $this->create;
	}
	public function setCreate($create){
		$this->create = (int)$create;
		return $this;
	}
	public function getUpdate(){
		if(!$this->update)$this->update = time();
		return $this->update;
	}
	public function setUpdate($update){
		$this->update = (int)$update;
		return $this;
	}

}