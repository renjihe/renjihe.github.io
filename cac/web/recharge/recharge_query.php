<?php 

include("config.php");

include("recharge_function.php");

$shop_array = array(
    1 => "https://buy.itunes.apple.com/verifyReceipt",
    //2 => ""
);

$shop_sandbox_array = array(
    1 => "https://sandbox.itunes.apple.com/verifyReceipt",
    //2 => ""
);

function getCurl($order, $transaction, $amount)
{
    global $ini_items;
    $app_key = get_ini_item($ini_items, 'ios_billing_key'); 
    $url = get_ini_item($ini_items, 'ios_billing_url');
    
    $data = "order=".$order."&transaction=".$transaction."&amount=".$amount."&sign=".substr(md5($order.$transaction.$amount.$app_key), 2, 28);

    $ch = curl_init($url."?".$data) ;  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;

    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

function isReceiptValid($receipt, $shopType, &$response, $isSandBox = false)
{
    global $shop_array, $shop_sandbox_array;
	$post_data = '{"receipt-data" : "'. base64_encode($receipt) .'"}';
    if ($isSandBox)
        $url = $shop_sandbox_array[$shopType];
    else
        $url = $shop_array[$shopType];
    
    if ($url == "")
        return true;
        
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    
	$encodedResponse = curl_exec($ch);
	curl_close($ch);

	if ($encodedResponse == false)
		return false;

	$response = json_decode($encodedResponse);
	if ($response->{'status'} != 0)
		return false;

    $response = $response->{'receipt'};
	return true;
}

if (isset($_GET['receipt']) && isset($_GET['player']) && isset($_GET['shopType']) && isset($_GET['productId']) && isset($_GET['transId']))
{
    do 
    {
        $shopType = (int)$_GET['shopType'];
        if ($shopType <= 0 || $shopType > count($shop_array) ) {
            echo("t=0;err=4;");
            break;
        }
        
        log_only("[arguments]receipt=".$_GET['receipt'].";\r\nplayer=".$_GET['player'].";productId=".$_GET['productId'].";shopType=".$_GET['shopType'].";transId=".$_GET['transId']);
        
        $receipt = str_replace("\\\"", "\"", $_GET['receipt']);

        $ini_items = get_ini_file("config.ini");
        $ios_sandbox_str = get_ini_item($ini_items,'ios_sandbox');
        $ios_sandbox =  ($ios_sandbox_str != "" && $ios_sandbox_str == "true") ? true : false;
        $player= (int)$_GET['player'];
        $type = (int)$_GET['productId'];
        
        $ret = isReceiptValid($receipt, (int)$_GET['shopType'], $response, $ios_sandbox);
        if (!$ret)
        {
            echo "t=0;err=2";
            log_only("err=2;player=$player;productId=$type");
            break;
        }
        log_only("[response]transId=".$response->{'transaction_id'}.";productId=".$response->{'product_id'}.";purchase_date=".$response->{'purchase_date'}.";bid=".$response->{'bid'});
        
        $receipt=$_GET['receipt'];
        $now = time();
        $serverId = get_ini_item($ini_items,'server_id');
        $transId = $_GET['transId'];
        $cfg = get_recharge_config($type);
        $amount = $cfg["amount"];
        $product_id = $cfg["product_id"];
        
        if ($response->{'transaction_id'} != $transId) {
            echo "t=0;err=5";
            log_only("err=5;transaction_id=".$response->{'transaction_id'});
            break;
        }
        
        if ($response->{'product_id'} != $product_id) {
            echo "t=0;err=6";
            log_only("err=6;product_id=".$response->{'product_id'});
            break;
        }
        
        $result = false;
        if ($shopType == 2) {
            $conn = mysql_connect(
                    get_ini_item($ini_items,'mysql_server_name'), 
                    get_ini_item($ini_items,'mysql_username'),
                    get_ini_item($ini_items,'mysql_password')) or die ("connect db failed");
            mysql_query('set names utf8', $conn);
            
            $database = get_ini_item($ini_items,'mysql_database_game'); 
            mysql_select_db($database, $conn);

            $result = insert_recharge($conn, $player, $transId, $type, 0, 0, $amount, $now);
            if($result == true) {
                insert_payment($conn, $player, $serverId, (int)$_GET['shopType'], $type, $transId, 0, 0, $amount, $now);
            }
            
            mysql_close($conn);
        }
        else {
            $order = create_transaction($player, $serverId, (int)$_GET['shopType'], $type);
            $ret = json_decode(getCurl($order, $transId, $amount));
            if (isset($ret->{"result"}) && $ret->{"result"} == 1)
                $result = true;
        }
        
        if($result == true) {
            echo "t=0;err=0;product=".$type;
            //log_only("err=0;player=$player;productId=$type");
        }
        else {
            echo "t=0;err=3";
            //log_only("err=3;player=$player;productId=$type");
        }
    }while(0);
}
else {
	echo("t=0;err=1;");
}

//log_persistent($_GET['shopType']);

?>