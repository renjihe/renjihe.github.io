<?php
include_once("../config/config.php");
include_once("../helpers/log_helper.php");
include_once("../helpers/config_helper.php");
include_once("../helpers/curl_helper.php");
include_once("../helpers/db_helper.php");
include_once("../helpers/recharge_helper.php");
include_once("iapp_base.php");

$Config = new Config("../config/config.ini");
$IappConfig = new Config("../config/iapp_config.ini");
$Logger = new Logger("./log");

if (isset($_GET['id']) && isset($_GET['now']) && isset($_GET['user']) && isset($_GET['sign']))
{
	$id = $_GET['id'];
	$user = $_GET['user'];
	$now = $_GET['now'];
	$sign = md5($id.$now.$user.$Config->get('client_key'));
	
	//$Logger->log_only($id.":".$user.":".$now.":".$sign);
	if ($sign == $_GET['sign']) 
	{
		$cfg = get_recharge_config($id, "../config/viprecharge.xml");
		$url = $IappConfig->get('unifiedorder_url');
		$appkey = $IappConfig->get('appkey');
		$platpkey = $IappConfig->get('platpkey');
		
		$orderReq['appid'] = $IappConfig->get('appid');
		$orderReq['waresid'] = (int)$id;
		$orderReq['cporderid'] = create_transaction($user, 1, 3, $id);
		$orderReq['price'] = (float)$cfg["amount"];
		$orderReq['currency'] = 'RMB';
		$orderReq['appuserid'] = $user;
		$orderReq['cpprivateinfo'] = $orderReq['cporderid'];
		$notifyurl = $IappConfig->get('notifyurl');
		
		if ($notifyurl) {
			$orderReq['notifyurl'] = $notifyurl;//'http://58.250.160.241:8888/IapppayCpSyncForPHPDemo/TradingResultsNotice.php';
		}
		
		$reqData = composeReq($orderReq, $appkey);
		$respData = request_by_curl($url, $reqData, 'order');
	
		if(!parseResp($respData, $platpkey, $respJson)) {
			echo "failed";
		}
		else {
			echo "transid=".$respJson->transid."&appid=".$orderReq['appid'];
			//$Logger->log_only("transid=".$respJson->transid."&appid=".$orderReq['appid']);
		}
	}
}

?>