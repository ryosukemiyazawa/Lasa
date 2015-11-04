<?php

namespace lasa\view\loader;

interface LoaderInterface{
	
	/**
	 * @param string name
	 * @return \lasa\view\builder\ViewBuilder
	 */
	public function getBuilder($name);
	
	/**
	 * @param string $name
	 * @return string
	 */
	public function getCacheName($name);
	
	/**
	 *
	 * @param string $name
	 * @param int $time
	 * @return boolean
	 */
	public function isChanged($name, $time);
	
}