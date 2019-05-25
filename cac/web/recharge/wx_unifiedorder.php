<?php
include_once("../config/config.php");
include_once("../helpers/log_helper.php");
include_once("../helpers/config_helper.php");
include_once("../helpers/curl_helper.php");
include_once("../helpers/db_helper.php");
include_once("../helpers/recharge_helper.php");

$Config = new Config("../config/config.ini");
$WeiXinConfig = new Config("../config/wx_config.ini");
$Logger = new Logger("./log");

if (isset($_GET['id']) && isset($_GET['now']) && isset($_GET['user']) && isset($_GET['sign']))
{
	$id = $_GET['id'];
	$user = $_GET['user'];
	$now = $_GET['now'];
	$sign = md5($id.$now.$user.$Config->get('client_key'));

	if ($sign == $_GET['sign']) 
	{
		$cfg = get_recharge_config($id, "../config/viprecharge.xml");
		
		$url = $WeiXinConfig->get('unifiedorder_url');
		$appid = $WeiXinConfig->get('appid');
		$mch_id = $WeiXinConfig->get('mch_id');
		$nonce_str = "".rand(10000, 99999);
		$out_trade_no = create_transaction($user, 1, 2, $id);
		$total_fee = $cfg["amount"];
		
		$spbill_create_ip = $_SERVER['REMOTE_ADDR'];
		$notify_url = $WeiXinConfig->get('notify_url');
		$trade_type = "APP";
		$body = $cfg["name"];
		
		$array = array(
			"appid" => $appid,
			"mch_id" => $mch_id,
			"nonce_str" => $nonce_str,
			"out_trade_no" => $out_trade_no,
			"total_fee" => $total_fee,
			"spbill_create_ip" => $spbill_create_ip,
			"notify_url" => $notify_url,
			"trade_type" => $trade_type,
			"body" => $body,
		);
		
		ksort($array);
		$sign = strtoupper(md5(http_build_query($array)."&key=".$WeiXinConfig->get('secret')));
		$array["sign"] = $sign;
		
		$data = ArrayToXml($array);
		$result = Curl::post($url, $data);
		if ($result)
		{
			$result = XmlToArray($result);
			if ($result["return_code"] == "FAIL" && $result["result_code"] == "SUCCESS" && $result["appid"] == $appid)
			{
				$ret = array();
				$ret["appid"] = $appid;
				$ret["partnerid"] = $mch_id;
				$ret["prepayid"] = $result["prepay_id"];
				$ret["noncestr"] = $result["nonce_str"];
				$ret["timestamp"] = time();
				$ret["package"] = "Sign=WXPay";
				ksort($ret);
				
				$sign = strtoupper(md5(http_build_query($ret)."&key=".$WeiXinConfig->get('secret')));
				$ret["sign"] = $sign;
				
				echo json_encode($ret);
				/*echo '{"appid":"wxb4ba3c02aa476ea1","partnerid":"1900006771","package":"Sign=WXPay","noncestr":"f301eb10bccf9a3bfdee160dd69c99f4","timestamp":1521718613,"prepayid":"wx201803221936536188470e070647977181","sign":"8B3954D8F03505CD0C800591CEFBB5E9"}';*/
			}
		}
	}
}
?>