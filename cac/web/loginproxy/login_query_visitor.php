<?php

class LoginProxyVisitor extends LoginProxy
{
	//protected $config_file = "login_query_aquick_config.ini";
	function run($session_id, $user_id, $p, $extra)
	{
		if (strlen($user_id) <= 6) 
		{
			echo json_encode(array("code" => 0));
			return;
		}

		if (substr($user_id, -4) != "@0.0")
		{
			echo json_encode(array("code" => 0));
			return;
		}
		
		//$real_account = substr($user_id, 0, -4);
		
		global $Config;	
		$conn = mysql_connect(
			$Config->get('mysql_server_name'), 
			$Config->get('mysql_username'),
			$Config->get('mysql_password')) or die ("数据库连接失败...");
			
		$database = $Config->get('mysql_database_account');
		
		mysql_query('set names utf8', $conn);
		mysql_select_db($database, $conn);
		
		$query = "select accountName, email from kbe_accountinfos where email = '$user_id';";
		$result = mysql_query($query, $conn);
		$ret = 0;
		if ($result)
		{
			$row = mysql_num_rows($result);
			if ($row > 0)
			{
				$ret = 2;
			}
			else {
				$ret = 1;
			}
		}
		
		echo json_encode(array("code" => $ret));
		mysql_close($conn);
	}
}

?>