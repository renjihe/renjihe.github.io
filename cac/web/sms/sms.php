<?php
require __DIR__ . "/autoload.php";

use Qcloud\Sms\SmsSingleSender;
use Qcloud\Sms\SmsMultiSender;
use Qcloud\Sms\SmsVoiceVerifyCodeSender;
use Qcloud\Sms\SmsVoicePromptSender;
use Qcloud\Sms\SmsStatusPuller;
use Qcloud\Sms\SmsMobileStatusPuller;


function SendSms($phoneNumber, $code, $expire = "2")
{
	global $SmsConfig;
	$appid = $SmsConfig->get('appid');
	$appkey = $SmsConfig->get('appkey');
	$templateId = $SmsConfig->get('templateid');
	$smsSign = $SmsConfig->get('sign');

	$ret = false;
	try 
	{
		$ssender = new SmsSingleSender($appid, $appkey);
		$params = [$code, $expire];
		$result = $ssender->sendWithParam("86", $phoneNumber, $templateId, $params, $smsSign, "", "");
		$rsp = json_decode($result);
		if ($rsp->result == 0)
			$ret = true;
		else 
			echo var_dump($rsp);
	} 
	catch(\Exception $e) {
		echo var_dump($e);
	}
	return $ret;
}

include_once("../config/config.php");
include_once("../helpers/log_helper.php");
include_once("../helpers/config_helper.php");
include_once("../helpers/curl_helper.php");
include_once("../helpers/db_helper.php");

$Config = new Config("../config/config.ini");
$SmsConfig = new Config("../config/sms_config.ini");
$Logger = new Logger("./log");

$ret = 0;
if (isset($_GET['p']) && isset($_GET['sign']))
{
	$phoneNumber = $_GET['p'];
		
	$sign = md5($phoneNumber.$Config->get('client_key'));
	if ($sign == $_GET['sign']) 
	{
		if(preg_match("/^1\d{10}$/", $phoneNumber)) 
		{
			$code = "".rand(1000, 9999);
			if (SendSms($phoneNumber, $code)) {
				$conn = mysql_connect(
					$Config->get('mysql_server_name'), 
					$Config->get('mysql_username'),
					$Config->get('mysql_password')) or die ("数据库连接失败...");
				
				$database = $Config->get('mysql_database_sms');
			
				mysql_query('set names utf8', $conn);
				mysql_select_db($database, $conn);
				
				$query = "insert into t_sms (phone, code, time) values ('".$phoneNumber."', '".$code."', ".time().");";
				$result = mysql_query($query, $conn);
				if ($result && mysql_affected_rows($conn) > 0) {
					$ret = 1;
				}
				else {
					$ret = 3;
				}
				
				mysql_close($conn);
			}
			else {
				$ret = 2;
			}
		}
	}
}

echo $ret;

?>
