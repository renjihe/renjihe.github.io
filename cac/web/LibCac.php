<?php
$db_host = "localhost";
$db_user = "tw";
$db_password = "debug^__^";
$db_name = "cac_head";
$db_gm_name = "cac_gm_head";

/*function send_cmd($json)
{
	global $server_ip, $server_port;
	$ret = false;
	$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));
    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 6, "usec" => 0));

    if(socket_connect($socket, $server_ip, $server_port) == false){
        echo 'connect fail massege:'.socket_strerror(socket_last_error());
    }
	else
	{
		echo $json.strlen($json);
        if(socket_write($socket, $json, strlen($json)) == false){
            echo 'fail to write'.socket_strerror(socket_last_error());
        }
		else
		{
            while($callback = socket_read($socket, 1024))
			{
				echo $callback;
                $ret = json_decode($callback);
				if ($ret["code"] == 1) {
					$ret = true;
				}
            }
        }
    }
    socket_close($socket);
	return $ret;
}*/

function send_cmd($cmd, $x = 0, $y = 0, $z = 0, $w = 0, $sv = "", $conn = null)
{	
	global $db_host, $db_user, $db_password, $db_gm_name;
	if ($conn == null)
	{
		$conn = mysql_connect($db_host, $db_user, $db_password) or die ("connect mysql faild...");
		mysql_query('set names utf8', $conn);
	}
	
	mysql_select_db($db_gm_name, $conn);

	$query = "insert into t_cmd (cmd, x, y, z, w, sv) values (".$cmd.", ".$x.", ".$y.", ".$z.", ".$w.", '".$sv."');";
    $result = mysql_query($query, $conn);
	if ($result && mysql_affected_rows($conn) > 0) {
		return true;
	}
	
	return false;
}

function lock_player($dbid)
{
	global $db_host, $db_user, $db_password, $db_name;
	$conn = mysql_connect($db_host, $db_user, $db_password) or die ("connect mysql faild...");
	
    mysql_query('set names utf8', $conn);
    mysql_select_db($db_name, $conn);
	
	$query = "select flags from kbe_accountinfos where entityDBID = ".$dbid.";";
    $result = mysql_query($query, $conn);

    if ($result)
    {
		$row = mysql_fetch_array($result);
		$flags = (int)$row["flags"];

		$query = "update kbe_accountinfos set flags = '".($flags | 1)."' where entityDBID = ".$dbid.";";
		$result = mysql_query($query, $conn);
		if ($result) {
			return send_cmd(1, $dbid, 0, 0, 0, "", $conn);
		}
	}
	
	return false;
}

function unlock_player($dbid)
{
	global $db_host, $db_user, $db_password, $db_name;
	$conn = mysql_connect($db_host, $db_user, $db_password) or die ("connect mysql faild...");
	
    mysql_query('set names utf8', $conn);
    mysql_select_db($db_name, $conn);
	
	$query = "select flags from kbe_accountinfos where entityDBID = ".$dbid.";";
    $result = mysql_query($query, $conn);

    if ($result)
    {
		$row = mysql_fetch_array($result);
		$flags = (int)$row["flags"];

		$query = "update kbe_accountinfos set flags = '".($flags & (~1))."' where entityDBID = ".$dbid.";";
		$result = mysql_query($query, $conn);
		if ($result && mysql_affected_rows($conn) > 0) {
			return true;
		}
	}
	
	return false;
}
?>