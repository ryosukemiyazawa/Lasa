<?php
namespace lasa\web\http;
/*
 * Response.php
 */
class Response {
	
	public $_status = ["200","OK"];
	public $_headers = [];
	public $_content = null;
	
	public function setStatus($code, $message = null){
		if(!is_null($message)){
			switch($code){
				case 404:
					$message = "Not Found";
					break;
				case 403:
					$message = "Forbidden";
					break;
				case 500:
					$message = "Internal Server Error";
					break;
				case 503:
					$message = "Service Unavailable";
					break;
			}
		}
		
		$this->_status = [$code, $message];
		
		return $this;
	}
	
	public function location($url, $code = 303){
		$this->setStatus($code,"See other");
		$this->_headers["Location"] = ["Location: " . $url, true, $code];
		
		return $this;
	}
	
	public function header($key, $content){
		$this->_headers[] = ["${key}: " . $content];
		
		return $this;
	}
	
	public function headers(){
		return $this->_headers;
	}
	
	public function getHeader($key){
		return (isset($this->_headers[$key])) ? $this->_headers[$key] : null;
	}
	
	public function checkModified($date, $max_age = 86400){
		if(isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])){
			if($date <= strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"])){
				$this->setStatus(304, "Not Modified");
				$this->header("Cache-Control", 'private, max-age=' . $max_age);
				$this->header("Expires", -1);
				$this->header("Pragma", "cache");
				return false;
			}
			return true;
		}
		
		return true;
	}
	
	public function cacheHeader($date, $max_age = 86400){
		
		$this->header("Cache-Control", 'private, max-age=' . $max_age);
		$this->header("Expires", "-1");
		$this->header("Pragma", "cache");
		if($date){
			$this->header("Last-Modified", gmdate('D, d M Y H:i:s', $date) . " GMT");
		}
		return $this;
	}
	
	public function setContentType($content_type){
		$this->_headers[] = ['Content-Type: ' . $content_type];
		return $this;
	}
	
	public function setContent($content){
		$this->_content = $content;
		return $this;
	}
	
	public function download($file_name, $path, $option = []){
		$ua = (isset($_SERVER["HTTP_USER_AGENT"])) ? $_SERVER["HTTP_USER_AGENT"] : "";
		
		//output download header
		if(preg_match("/MSIE/i",$ua)){
			if (strlen(rawurlencode($file_name)) > 21 * 3 * 3) {
				$file_name = mb_convert_encoding($file_name, "SJIS-win","UTF-8");
				$file_name = str_replace('#', '%23', $file_name);
			}else{
				$file_name = rawurlencode($file_name);
			}
		}elseif(preg_match("/chrome/i",$ua)){
		}elseif(preg_match("/safari/i",$ua)){
			$file_name = mb_convert_encoding($file_name, "Shift_JIS","UTF-8");
		}
		
		//IEでのHTPSエラー対策
		$this->_headers['Pragma'] = ['Pragma: private'];
		$this->_headers['Cache-Control'] = ['Cache-Control: private'];
		
		//Download用のヘッダー
		$this->_headers['Content-Type'] = ['Content-Type: application/octet-stream'];
		$this->_headers['Content-Length'] = ['Content-Length: ' . filesize($path)];
		$this->_headers['Content-Disposition'] = ['Content-Disposition: attachment; filename="'.$file_name.'"'];
		
		$this->flush();
		
		echo file_get_contents($path);
		return $this;
	}
	
	public function flush(){
		
		if(!headers_sent()){
			header("HTTP/1.1 " . $this->_status[0] . " " . $this->_status[1]);
		
			foreach($this->_headers as $arg){
				call_user_func_array("header", $arg);
			}
		}
		
		echo $this->_content;
		
		return $this;
	}
	
	public function getContent(){
		return $this->_content;
	}
}
