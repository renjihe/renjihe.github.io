<?php 
include_once("../config/config.php");
include_once("../helpers/log_helper.php");
include_once("../helpers/config_helper.php");
include_once("../helpers/curl_helper.php");
include_once("../helpers/db_helper.php");

$Config = new Config("../config/config.ini");
$Logger = new Logger("./log");

class LoginProxy
{
	protected $config_file = "";
	private $config_ins = null;
	function __construct()
	{
		if (strlen($this->config_file) > 0) {
			$this->config_ins = new Config($this->config_file);
		}
	}
	
	function config($key)
	{
		if ($this->config_ins != null && $this->config_ins->isInited()) {
			return $this->config_ins->get($key);	
		}
		return "";
	}
		
	function run($session_id, $user_id, $p, $extra)
	{
		echo "error=1;not implement run!";
	}
}

if (isset($_GET['p']))
{
	$p = strtolower($_GET['p']);
    
	$class = "LoginProxy".ucfirst($p);
	if (!class_exists($class)) {
		include("login_query_".$p.".php");
	}
	
	if (class_exists($class)) 
	{
		$proxy = new $class();
		
		$sid = "";
		if (isset($_GET['session_id'])) {
			$sid = $_GET['session_id'];
		}
		
		$extra = "";
		if (isset($_GET['extra'])) {
			$extra = $_GET['extra'];
		}
		
		$user_id = "";
		if (isset($_GET['user_id'])) {
			$user_id = $_GET['user_id'];
		}
		
		$proxy->run($sid, $user_id, $p, $extra);
	}
}

?>