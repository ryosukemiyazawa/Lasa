<?php
namespace lasa\web\http;

/*
 * Headers.php
 */
class Headers{
	
	private $_headers = [];
	
	function __construct(array $headers){
		$this->_headers = $headers;
	}
	
	public function get($key, $defValue = null){
		if(isset($this->_headers[$key])){
			return $this->_headers[$key];
		}
		
		return $defValue;
	}
	
}