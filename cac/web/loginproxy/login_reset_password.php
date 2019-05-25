<?php
include_once("../config/config.php");
include_once("../helpers/log_helper.php");
include_once("../helpers/config_helper.php");
include_once("../helpers/curl_helper.php");
include_once("../helpers/db_helper.php");

$Config = new Config("../config/config.ini");
$Logger = new Logger("./log");

$ret = 0;
if (isset($_GET['p']) && isset($_GET['code']) && isset($_GET['psw']) && isset($_GET['sign']))
{
	$phoneNumber = $_GET['p'];
	$code = $_GET['code'];
	$psw = $_GET['psw'];
	
	$sign = md5($phoneNumber.$code.$psw.$Config->get('client_key'));
	if ($sign == $_GET['sign']) 
	{
		if(preg_match("/^1\d{10}$/", $phoneNumber) 
			&& preg_match("/^\d{4}$/", $code)
			&& preg_match("/^([0-9A-Za-z]){5,64}$/", $psw))
		{
			$conn = mysql_connect(
				$Config->get('mysql_server_name'), 
				$Config->get('mysql_username'),
				$Config->get('mysql_password')) or die ("数据库连接失败...");
			
			$database_sms = $Config->get('mysql_database_sms');
			$database_account = $Config->get('mysql_database_account');
			
			mysql_query('set names utf8', $conn);
			mysql_select_db($database_sms, $conn);
				
			$query = "select phone, code, time from t_sms where phone = '".$phoneNumber."' and code = '".$code."' order by time desc;";
			$result = mysql_query($query, $conn);
			if ($result)
			{
				$row_num = mysql_num_rows($result);
				if ($row_num > 0) 
				{
					$expire = (int)($Config->get('sms_expire_time'));
					$row = mysql_fetch_row($result);
					if (time() - (int)$row[2] > $expire) {
						$ret = 2;
					}
					else {
						mysql_select_db($database_account, $conn);
						
						$query = "update kbe_accountinfos set password = '".md5($psw)."' where accountName = '".$phoneNumber."';";
						$result = mysql_query($query, $conn);
						
						if ($result && mysql_affected_rows($conn) > 0) {
							$ret = 1;
						}
						else {
							$ret = 3;
						}
					}
				}
			}
			
			mysql_close($conn);
		}
	}
}

echo $ret;
?>
