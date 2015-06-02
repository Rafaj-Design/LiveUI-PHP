<?php


class LuiGrab {
	
	
	// Public methods
	
	public static function getApi($path, $apiKey, $build=0, $postData=null) {
		$url = 'http://api.liveui.io/'.$path.'.json';
		return self::get($url, $apiKey, $build, $postData);
	}
	
	// Private methods
	
	private static function get($url, $apiKey, $build, $postData=null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		if (!empty($postData)) {
			if (is_array($postData)) {
				$postData = json_encode($postData);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$headers = array();
		$headers[] = 'X-ApiKey: '.$apiKey;
		$headers[] = 'X-ApiVersion: '.$build;
		if (!empty($postData)) {
			$headers[] = 'Content-Type: application/json';
			$headers[] = 'Content-Length: '.strlen($postData);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$response = curl_exec($ch);
		curl_close($ch);
		
		return $response;
	}
	
}