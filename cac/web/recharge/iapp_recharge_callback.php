<?php
include_once("../config/config.php");
include_once("../helpers/log_helper.php");
include_once("../helpers/config_helper.php");
include_once("../helpers/recharge_helper.php");
include_once("../helpers/curl_helper.php");
include_once("iapp_base.php");

$Config = new Config("../config/config.ini");
$IappConfig = new Config("../config/iapp_config.ini");
$Logger = new Logger("./log");

$string = $_POST;
if($string == null) {
	echo 'failed'."\n";
}
else
{
	$transdata = $string['transdata'];
	if(stripos("%22", $transdata)){
		$string= array_map ('urldecode', $string);
	}
	
	$respData = 'transdata='.$string['transdata'].'&sign='.$string['sign'].'&signtype='.$string['signtype'];

	$appkey = $IappConfig->get('appkey');
	$platpkey = $IappConfig->get('platpkey');
	
	if(!parseResp($respData, $platpkey, $respJson)) {
		echo 'failed'."\n";
	}
	else 
	{	
		$transdata = $string['transdata'];
		$arr = json_decode($transdata);
	
		$order = $arr->cporderid;
		$transaction = $arr->transid;
		$player = 0;
		$server = 1;
		$amount = $arr->money;
		
		do
		{
			$order_list = explode("_", $order);
			if (count($order_list) < 3)
			{
				echo 'failed'."\n";
				break;
			}
			
			$server = $order_list[0];
			$player = $order_list[1];
			$order = $order_list[2];
			
			$Logger->log_only("order=".$arr->cporderid.",transaction=".$transaction.",char=".$player.",amount=".$amount);
			
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
				echo 'success'."\n";
			else
				echo 'failed'."\n";
		}
		while(0);	
	}
}

?>