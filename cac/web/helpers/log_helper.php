<?php
class Logger
{
	private $log_mem;
	private $tag;
	private $enable;
	private $dir;
	function __construct($dir) 
	{
		$this->log_mem = "";
		$this->tag = "log";
		$this->enable = false;
		$this->dir = $dir;
		if (substr($dir, 0, 1) == '.') {
			$this->dir = getcwd()."/".$dir;
		}
		
		if(defined("__DEBUG__")):
			$this->enable = true;
		endif;
	}
	
	function __destruct()
	{
		if($this->enable):
			$this->log_mem = str_replace("::__TAG__::", $this->tag, $this->log_mem);
			
			$log_file = $this->dir."/".date("Y-m-d", time()).".log";
			if (!is_dir($this->dir."/")) {
				mkdir($this->dir."/");
			}
			
			$fd = fopen($log_file, "a+");
			fwrite($fd, $this->log_mem);
			fclose($fd);
		endif;
	}
	
	function init($tag)
	{
		$this->tag = $tag;
	}
	
	function log_only($info)
	{
		if($this->enable):
			$this->log_mem .= "[".date("H:i:s", time())."][::__TAG__::]".$info."\r\n";
		endif;
	}
	
	function log_echo($info)
	{
		$this->log_only($info);
		echo $info;
	}
	
	function print_stack_trace($endline = "\n", $log_handler = null, $exit = false)
	{
		$trace = debug_backtrace();
		$num = 0;
		$ans = "";
		foreach($trace as $line) 
		{
			$ans .= '#'.$num.' '.$line['file'].'['.$line['line'].'] ';
			if($line['type'] == '->' || $line['type'] == '::') {
				$ans .= $line['class'].$line['type'].$line['function'].'()';
			}
			else{
				$ans .= $line['function'].'()';
			}
			$ans .= $endline; 
			$num++;
		}
		
		if($log_handler != null && function_exists($log_handler)) {
			$log_handler($ans);
		}
		else {
			print $ans;
		}
		
		if($exit) {
			exit(1);
		}
	}
}

?>