<?php
function update_password_to_db_impl($username, $password)
{
	global $Config;	
	$conn = mysql_connect(
        $Config->get('mysql_server_name'), 
        $Config->get('mysql_username'),
        $Config->get('mysql_password')) or die ("数据库连接失败...");
        
    $database = $Config->get('mysql_database_account'); 

    mysql_query('set names utf8', $conn);
    mysql_select_db($database, $conn);
    
	$ret = array();
    $query = "select ACCOUNT from t_mall_account where ACCOUNT = '$username';";
    $result = mysql_query($query, $conn);
    if ($result)
    {
        $row = mysql_num_rows($result);
        if ($row > 0)
        {
            $query = "update t_mall_account set PASSWORD = '".md5($password)."' where ACCOUNT = '$username';";
        }
        else
        {
            $time=date("Y-m-d H:i:s" ,time());
            $query = "insert into t_mall_account (ACCOUNT, PASSWORD, POINT, BONUS, CREATETIME) 
                    values ('$username','".md5($password)."', 0, 0, '$time')";
        }
        $result = mysql_query($query, $conn);
        if (!$result)
        {
            sleep(1);
            $result = mysql_query($query, $conn);
            if($result)
            {
				$ret[0] = 0;
				$ret[1] = $username;
				$ret[2] = $password;
            }else
            {
				$ret[0] = 3;
            }
        }
		else 
		{
			$ret[0] = 0;
			$ret[1] = $username;
			$ret[2] = $password;
		}
		
        mysql_close($conn);
    }
    else {
    	$ret[0] = 11;
    }
	
	return $ret;
}

function update_password_to_db($username, $ex_info = "")
{
	$username = strtolower($username); //mysql may case-sensitive.
	$password = time() + rand(0,99999);

    $result = update_password_to_db_impl($username, $password);
	
	$ret = "error=".$result[0].";";
	unset($result[0]);
	
	foreach ($result as $key => $val) {
		$ret .= $val.";";
	}
	
	echo $ret;
}
?>
