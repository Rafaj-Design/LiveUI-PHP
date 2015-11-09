<?php


if (!defined('(bool)LUI_DEBUG')) {
	define('(bool)LUI_DEBUG', 0);
}


require_once('LuiException.php');
require_once('LuiGrab.php');


class LUI {
	
	private static $apiKey = null;
	private static $tmpFolderPath = null;
	private static $translations = array();
	private static $languageCode = null;
	private static $buildNumber;
	private static $missingKeys = array();
	
	// Constructor (kinda)
	
	public static function init($apiKey, $buildNumber=0, $tmpFolderPath='./tmp') {
		self::$tmpFolderPath = $tmpFolderPath;
		self::$apiKey = $apiKey;
		self::$languageCode = null;
		self::$buildNumber = $buildNumber;
		
		self::loadCache();
	}
	
	// Public methods
	
	public static function get($key) {
		if ((bool)LUI_DEBUG > 1) {
			if ((bool)LUI_DEBUG == 2) {
				return $key;
			}
			elseif ((bool)LUI_DEBUG == 3) {
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
			if ((bool)LUI_DEBUG) {
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
		if ((bool)LUI_DEBUG && !empty(self::$missingKeys)) {
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
		return self::$tmpFolderPath.'/LUICache.json';
	}
	
	private function loadRemoteData($path) {
		if (!is_writable(self::$tmpFolderPath)) {
			throw new LuiException('Temporary folder ('.self::$tmpFolderPath.') is not writable!');
		}
		else {
			$dataString = LuiGrab::getApi('translations', self::$apiKey, self::$buildNumber);
			self::processData($dataString);
			file_put_contents($path, $dataString);
		}
	}
	
	private static function loadCache() {
		$path = self::cachePath();
		if (file_exists($path) && !(bool)LUI_DEBUG) {
			$time = (time() - filemtime($path));
			if ($time > 3600) {
				self::loadRemoteData($path);
			}
			$dataString = file_get_contents($path);
			self::processData($dataString);
		}
		else {
			self::loadRemoteData($path);
		}
		
		self::$languageCode = self::bestSuitableLanguage();
	}
	
	// Language selection
	
	private static $userSelectedLanguageCode = null;
	
	public static function availableLangs() {
		return array_keys(self::$translations);
	}
	
	private static function bestSuitableLanguage() {
		$remoteLangs = self::availableLangs();
		
	    if (self::$userSelectedLanguageCode) {
	        foreach ($remoteLangs as $languageCode) {
	            if ($languageCode == self::$userSelectedLanguageCode) {
	                return self::$userSelectedLanguageCode;
	            }
	        }
	    }
	    
	    $systemLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	    foreach ($systemLangs as $key=>$langCode) {
	    	$langCode = explode(';', $langCode);
		    $systemLangs[$key] = $langCode[0];
	    }
	    
	    $suitableLangs = array();
	    $defaultRemoteLang = array();
	    foreach ($systemLangs as $sysCode) {
	        foreach (self::$translations as $remCode=>$remLang) {
	            if ((bool)$remLang['default']) $defaultRemoteLang = $remLang;
	            if ($sysCode == $remCode) {
	                return $sysCode;
	            }
	            else {
	                $sysLangComponents = explode('-', $sysCode);
	                $remLangComponents = explode('-', $remCode);
				    
	                if ($remLangComponents[0] == $sysCode) {
	                    $suitableLangs[] = $remCode;
	                }
	                else if ($sysLangComponents[0] == $remLangComponents[0]) {
	                    $suitableLangs[] = $remCode;
	                }
	            }
	        }
	    }
	    
	    if (count($suitableLangs) > 0) {
	        $code = $suitableLangs[0];
	        return $code;
	    }
	    
    	return $defaultRemoteLang['code'];
	}
	
}