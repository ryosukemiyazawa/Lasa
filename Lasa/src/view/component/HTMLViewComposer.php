<?php
namespace lasa\view\component;

class HTMLViewComposer extends HTMLComponent{

	private $config = [];
	private $componentId;

	public function __construct($config){
		$this->config = $config;
	}

	function getContent($id, $tag, $inner, $attributes){

		$this->componentId = "vc_" . $id;
		$contents = $this->compile($id, $this->config);

		if($tag == "!--"){
			return $contents;
		}

		return '<'.$this->getStartTag($tag, $attributes).'>'.$contents.'</'.$tag.'>';
	}

	public function compile($id, $config) {
		$engine = \lasa\view\Engine::currentEngine();
		$loader = $engine->getLoader();
		$template = [];

		$template[] = '<?php $' . $this->componentId . "= new " . VIEWHOLDER_CLASS . '($'.$this->_holderName.'->getArray("'.$id.'")); ?>';

		foreach($config as $id => $conf){
			if(is_string($conf))$conf = [$conf];
			$component = array_shift($conf);
			$builder = $loader->getBuilder($component);
			if(! $builder){
				continue;
			}
			if(is_numeric($id))$id = "_v" . $id;
			
			$builder->setHolderName($id);
			$res = $builder->compile($engine);
			$res = $this->cleanupCode($id, $res, $conf);
			$template[] = $res;
		}

		$template = implode("", $template);

		$obj = new \lasa\view\component\HTMLViewComponent($template);
		$result = $obj->compile();
		return $result;
	}

	protected function cleanupCode($holderName, $result, $defaultValue) {
		$tokens = token_get_all($result);
		$php_tag_open = 0;
		foreach($tokens as $token){
			if(! is_array($token))
				continue;
			$type = $token[0];
			if($type == T_OPEN_TAG){
				$php_tag_open ++;
				continue;
			}
			if($type == T_CLOSE_TAG){
				$php_tag_open --;
				continue;
			}
		}
		$suffix = ($php_tag_open > 0) ? "" : "<?php ";

		//placeholderはvar_exportが一番早い
		$prefix = ($defaultValue) ? '$' . $holderName . '->placeholders('.var_export($defaultValue, true).');' : "";
		$prefix .= '$_ = $' . $holderName . ";";
		$result = '<?php call_user_func(function($' . $holderName . '){ '.$prefix.' ?>' . $result . $suffix . '}, new ' . VIEWHOLDER_CLASS . '($' . $this->componentId . '->getArray("' . $holderName . '")));' . '?>';

		return $result;
	}

}