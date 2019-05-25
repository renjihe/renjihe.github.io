<?php
/*
CREATE TABLE `t_payment` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PLAYER_ID` int(11) NOT NULL,
  `SERVER_ID` int(11) NOT NULL,
  `SHOP_TYPE` int(11) NOT NULL,
  `TYPE` int(11) NOT NULL,
  `PAYMENT_ID` varchar(35) NOT NULL,
  `POINTS` int(11) NOT NULL,
  `BONUS` int(11) NOT NULL,
  `AMOUNT` float NOT NULL,
  `TIME` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
)
;

CREATE TABLE `t_recharge` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PLAYER_ID` int(11) NOT NULL,
  `PAYMENT_ID` varchar(35) DEFAULT NULL,
  `TYPE` int(11) DEFAULT NULL,
  `POINTS` int(11) NOT NULL,
  `BONUS` int(11) DEFAULT NULL,
  `AMOUNT` float NOT NULL,
  `TIME` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
)
;
*/

define('RECHARGE_DEVIATION', 0.01);

function compare_amount($id, $amount, $config_file)
{
    $doc = new DOMDocument();
    $doc->load($config_file);
    $root = $doc->getElementsByTagName("root")->item(0);
    if (!is_null($root))
    {
        $lst = $root->getElementsByTagName("r");
        foreach ($lst as $recharge)
        {
            if ($recharge->getAttribute("id") == $id) {

                $_float_amount = floatval($recharge->getAttribute("amount"));
                if (0 == $_float_amount)
                    return -3;
                    
                $float_amount = floatval($amount);
                if ($_float_amount == $float_amount)
                    return 0;
                
                $minus = $_float_amount > $float_amount? $_float_amount - $float_amount:$float_amount - $_float_amount;
                
                if ($minus/$_float_amount <= RECHARGE_DEVIATION)
                    return 0;
                    
                return -1;
            }
        }
    }
    return -2;
}

function get_amount_config($id, $config_file)
{
    $doc = new DOMDocument();
    $doc->load($config_file);
    $root = $doc->getElementsByTagName("root")->item(0);
    if (!is_null($root))
    {
        $lst = $root->getElementsByTagName("r");
        foreach ($lst as $recharge)
        {
            if ($recharge->getAttribute("id") == $id) {
                $amount = $recharge->getAttribute("amount");
            }
        }
    }
    return $amount;
}

function get_recharge_config($id, $config_file)
{
    $doc = new DOMDocument();
    $doc->load($config_file);
    $root = $doc->getElementsByTagName("root")->item(0);
    $cfg = array();
    if (!is_null($root))
    {
        $lst = $root->getElementsByTagName("r");
        foreach ($lst as $recharge)
        {
            if ($recharge->getAttribute("id") == $id) {
                $cfg["amount"] = $recharge->getAttribute("amount");
                $cfg["product_id"] = $recharge->getAttribute("productid");
                $cfg["name"] = $recharge->getAttribute("name");
				$cfg["point"] = $recharge->getAttribute("point");
				$cfg["bonus"] = $recharge->getAttribute("bonus");
            }
        }
    }
    return $cfg;
}

function insert_recharge($conn, $player, $payment, $type, $points, $bonus, $amount, $now)
{
    $query="select * from t_payment where payment_id = \"".$payment."\";";

    $result = mysql_query($query, $conn);
    if ($result)
    {
        $row = mysql_num_rows($result);
        if ($row < 1)
        {
            $query = "insert t_recharge(player_id, payment_id, type, points, bonus, amount, time) values(
                            ".$player.",\"".$payment."\",".$type.",".$points.",".$bonus.",".$amount.",".$now.")";
            return mysql_query($query, $conn);
        }else
        {
            $row = mysql_fetch_array($result);
            if ($row["player_id"] == $player)
            {
                return true;
            }
        }
    }
    return false;
}

function insert_payment($conn, $player, $serverId, $shopType, $productId, $paymentId, $points, $bonus, $amount, $now)
{
    $query="select * from t_payment where payment_id = \"".$paymentId."\";";
    $result = mysql_query($query, $conn);
    if ($result)
    {
        $row = mysql_num_rows($result);
        if ($row < 1)
        {
            $query="insert t_payment(player_id, server_id, shop_type, type, payment_id, points, bonus, amount, time) values(
                        ".$player.",".$serverId.",".$shopType.",".$productId.",'".$paymentId."',".$points.",".$bonus.",".$amount.",".$now.")";
            $result = mysql_query($query, $conn);
            if($result)
                return 1;
            else
                return 2;
        }
    }else
    {
        return 3;
    }
}

function create_transaction($player, $serverId, $shopType, $productId)
{
    $transaction = "";
    $transaction .= $serverId."_".$player."_";
    
    if ($shopType < 10)
        $transaction .= "00";
    else if ($shopType < 100)
        $transaction .= "0";
    $transaction .= "".$shopType;
        
    if ($productId < 10)
        $transaction .= "00";
    else if ($productId < 100)
        $transaction .= "0";
        
    $transaction .= "".$productId.time();
    
    return $transaction;
}

function ArrayToXml($arr)
{
    $xml = "<xml>";
    foreach ($arr as $key=>$val)
    {
        if (is_numeric($val)){
            $xml.="<".$key.">".$val."</".$key.">";
        }else{
            $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
    }
    $xml.="</xml>";
    return $xml;
}

function XmlToArray($xml)
{    
    
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
    return $values;
}

?>
