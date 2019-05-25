<?php
include_once("../config/config.php");
include_once("../helpers/log_helper.php");
include_once("../helpers/config_helper.php");
include_once("../helpers/recharge_helper.php");
include_once("../helpers/curl_helper.php");

$Config = new Config("../config/config.ini");
$WeiXinConfig = new Config("../config/wx_config.ini");
$Logger = new Logger("./log");

function PayReturn($code, $msg)
{
	echo ArrayToXml(array("return_code" => $code, "return_msg" => $msg));
}

$xml = file_get_contents('php://input');
$ret = XmlToArray($xml);

$url = $WeiXinConfig->get('order_url');
$appid = $WeiXinConfig->get('appid');
$mch_id = $WeiXinConfig->get('mch_id');
$secret = $WeiXinConfig->get('secret');

$data = array("appid" => $appid, "mch_id" => $mch_id, "out_trade_no" => $ret["out_trade_no"], "nonce_str" => "".rand(10000, 99999));

ksort($data);
$sign = strtoupper(md5(http_build_query($data)."&key=".$WeiXinConfig->get('secret')));
$data["sign"] = $sign;

$xml = Curl::post($url, ArrayToXml($data), true);
if ($xml) 
{
	$ret = XmlToArray($xml);
	if ($ret["return_code"] == "SUCCESS")
	{
		$order = $ret["out_trade_no"];
		$transaction = $ret["transaction_id"];
		$player = 0;
		$server = 1;
		$amount = $ret["total_fee"] / 100;
		
		do
		{
			$order_list = explode("_", $order);
			if (count($order_list) < 3)
			{
				PayReturn("FAIL", "4");
				break;
			}
			
			$server = $order_list[0];
			$player = $order_list[1];
			$order = $order_list[2];
			
			$Logger->log_only("order=".$ret["out_trade_no"].",transaction=".$ret["transaction_id"].",char=".$player.",amount=".$ret["total_fee"]);
			
			$shopType = (int)substr($order, 0, 3);
			$type = (int)substr($order, 3, 3); //productId
			$order .= "-".$player;

			$points=0;
			$bonus=0;
			
			if (($ret = compare_amount($type, $amount, "../config/viprecharge.xml")) != 0)
			{
				$points = $amount;
				
				$recharge_bonus_rate = $Config->get('recharge_bonus_rate');
				if (is_null($recharge_bonus_rate))
					$recharge_bonus_rate = 0.1;
					
				$bonus = $points * $recharge_bonus_rate;
			}
			else {
				$c = get_recharge_config($type, "../config/viprecharge.xml");
				$points = (int)$c["point"];
				$bonus = (int)$c["bonus"];
			}
			
			$conn = mysql_connect(
					$Config->get('mysql_server_name'), 
					$Config->get('mysql_username'),
					$Config->get('mysql_password')) or die ("connect db failed");
					
			$database = $Config->get('mysql_database_recharge'); 
			
			mysql_query('set names utf8', $conn);
			mysql_select_db($database, $conn);

			$now = time();
			$result = insert_recharge($conn, $player, $order, $type, $points, $bonus, $amount, $now);
			if($result == true)
			{
				insert_payment($conn, $player, $server, (int)$shopType, $type, $order, $points, $bonus, $amount, $now);
			}
			
			mysql_close($conn);
			
			if($result == true)
				PayReturn("SUCCESS", "OK");
			else
				PayReturn("FAIL", "1");
		}
		while(0);	
	}
	else {
		PayReturn("FAIL", "2");
	}
}
else {
	PayReturn("FAIL", "3");
}

?>