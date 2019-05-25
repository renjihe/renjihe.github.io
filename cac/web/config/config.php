<?php
define("__DEBUG__", 1);

if(defined("__DEBUG__")):
	ini_set('display_errors',1);  
	ini_set('display_startup_errors',1); 
	error_reporting(-1);
endif;

ini_set('date.timezone','Asia/Shanghai');
?>
