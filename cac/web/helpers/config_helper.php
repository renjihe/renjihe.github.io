<?php
class Config 
{
	private $config_list;
	private $inited;
	function __construct($file) 
	{
		$this->inited = false;
		$this->config_list = array();
		if ($this->init($file)) {
			$this->inited = true;
		}
	}
	
	private function init($file_name)
	{
		if (!file_exists($file_name))
			return false;
		
		$content = file_get_contents($file_name);
		if (strpos($content, "\r\n") == false) {
			$ini_list = explode("\n", $content);
		}
		else {
			$ini_list = explode("\r\n", $content);
		}
		
		$items = array();
		foreach($ini_list as $item)
		{
			$items = explode("=",$item);
	  
			if(isset($items[0]) && isset($items[1]))
			{
				$key = $items[0];
				if (count($items) > 2)
				{
					$items = array_slice($items, 1);
					$items = join("=", $items);
				}
				else {
					$items = $items[1];
				}
				
				$this->config_list[trim($key)] = trim($items);
			}
		}
		return true;
	}
	
	function isInited()
	{
		return $this->inited;
	}
	
	function get($key = '')
	{
		if(!$this->isInited() || empty($this->config_list) || !isset($this->config_list[$key])) 
			return "";
		
		return $this->config_list[$key];
	}
}

?>