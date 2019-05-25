<?php
class Curl
{
	private function __construct($dir) 
	{
	}
	
	static function post($url, $data = null, $ssl = false)
	{
		if (!$ssl) {
			$ssl = substr($url, 0, 8) == "https://" ? true : false;
		}
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
		
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		
		if ($ssl)
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}
	
	static function get($url, $ssl = false)
	{
		if (!$ssl) {
			$ssl = substr($url, 0, 8) == "https://" ? true : false;
		}
		
		$ch = curl_init($url) ;  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
		
		if ($ssl)
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
}
?>