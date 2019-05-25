<?php

class LoginProxyWx extends LoginProxy
{
	protected $config_file = "../config/wx_config.ini";
	function run($session_id, $user_id, $p, $extra)
	{
		$url = $this->config('auth_url');
		$appid = $this->config('appid');
		$secret = $this->config('secret');
		$result = Curl::get($url."?appid=".$appid."&secret=".$secret."&code=".$session_id."&grant_type=authorization_code");
		$ret_code = 0;
		$return_user = "";
		if ($result)
		{
			$result = json_decode($result);
			if ($result && isset($result->access_token)) {
				$ret_code = 1;
				$return_user = $result->openid."@wx";
			}
		}
		
		echo json_encode(array("code" => $ret_code, "user" => $return_user));
	}
}

?>