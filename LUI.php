<?php


if (!defined('LUI_DEBUG')) {
	define('LUI_DEBUG', 0);
}


require_once('LuiException.php');
require_once('LuiGrab.php');


class LUI {
	
	private static $apiKey = null;
	private static $tmpFolderPath = null;
	private static $translations = array();
	private static $languageCode;
	private static $buildNumber;
	private static $missingKeys = array();
	
	// Constructor (kinda)
	
	public static function init($apiKey, $languageCode='en', $buildNumber=0, $tmpFolderPath='./tmp') {
		self::$tmpFolderPath = $tmpFolderPath;
		self::$apiKey = $apiKey;
		self::$languageCode = $languageCode;
		self::$buildNumber = $buildNumber;
		
		self::loadCache();
	}
	
	// Public methods
	
	public static function get($key) {
		if (LUI_DEBUG > 1) {
			if (LUI_DEBUG == 2) {
				return $key;
			}
			elseif (LUI_DEBUG == 3) {
				$out = '';
				$x = 0;
				for ($i = 0; $i < strlen($key); $i++) {
					if ($x == 7) {
						$x = 0;
						$out .= ' ';
					}
					else {
						$out .= '_';
					}
					
					$x++;
				}
				return $out;
			}
		}
		if (isset(self::$translations[self::$languageCode]['translations'][$key])) {
			return self::$translations[self::$languageCode]['translations'][$key];
		}
		else {
			if (LUI_DEBUG) {
				self::$missingKeys['General'][] = $key;
			}
			return $key;
		}
	}
	
	public static function availableLanguageCodes() {
		if (!empty(self::$translations)) {
			return array_keys(self::$translations);
		}
		else {
			return array();
		}
	}
	
	public static function reportMissingKeys() {
		if (LUI_DEBUG && !empty(self::$missingKeys)) {
			LuiGrab::getApi('translations/debug', self::$apiKey, self::$buildNumber, self::$missingKeys);
			self::$missingKeys = array();
		}
	}
	
	// Private helpers
	
	private static function processData($dataString) {
		$translations = json_decode($dataString, true);
		if ($translations && isset($translations['data'])) {
			self::$translations = $translations['data'];
		}
		else {
			if (isset($translations['name']) && isset($translations['message'])) {
				throw new LuiException($translations['name'].': '.$translations['message']);
			}
			else {
				throw new LuiException('Unable to process translations!');
			}
		}
	}
	
	private static function cachePath() {
		return self::$tmpFolderPath.'/cache.json';
	}
	
	private static function loadCache() {
		$path = self::cachePath();
		if (file_exists($path) && !LUI_DEBUG) {
			$dataString = file_get_contents($path);
			self::processData($dataString);
		}
		else {
			if (!is_writable(self::$tmpFolderPath)) {
				throw new LuiException('Temporary folder ('.self::$tmpFolderPath.') is not writable!');
			}
			else {
				$dataString = LuiGrab::getApi('translations', self::$apiKey, self::$buildNumber);
				self::processData($dataString);
				file_put_contents($path, $dataString);
			}
		}
	}
	
}