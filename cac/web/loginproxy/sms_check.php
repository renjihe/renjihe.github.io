<?php
include_once("../config/config.php");
include_once("../helpers/log_helper.php");
include_once("../helpers/config_helper.php");
include_once("../helpers/curl_helper.php");
include_once("../helpers/db_helper.php");

$Config = new Config("../config/config.ini");
$Logger = new Logger("./log");

$ret = 0;
if (isset($_GET['p']) && isset($_GET['code']))
{
	$phoneNumber = $_GET['p'];
	$code = $_GET['code'];

	if(preg_match("/^1\d{10}$/", $phoneNumber) && preg_match("/^\d{4}$/", $code)) 
	{
		$conn = mysql_connect(
			$Config->get('mysql_server_name'), 
			$Config->get('mysql_username'),
			$Config->get('mysql_password')) or die ("数据库连接失败...");
			
		$database = $Config->get('mysql_database_sms');
		
		mysql_query('set names utf8', $conn);
		mysql_select_db($database, $conn);
			
		$query = "select phone, code, time from t_sms where phone = '".$phoneNumber."' and code = '".$code."' order by time desc;";
		$result = mysql_query($query, $conn);
		if ($result)
		{
			$row_num = mysql_num_rows($result);
			if ($row_num > 0) {
				//echo var_dump(mysql_fetch_row($result));
				$ret = 1;
				$expire = (int)($Config->get('sms_expire_time'));
					
				$row = mysql_fetch_row($result);
				if (time() - (int)$row[2] > $expire) {
					$ret = 2;
				}
			}
		}
		
		mysql_close($conn);
	}
}

echo json_encode(array("code" => $ret));
?>
