<?php
require_once(dirname(__FILE__).'/CookieStorage.php');

/*
 * This file should be included on all pages if referer tracking is required
 */

class CampaignTracker
{
	private static $referer = null;

	// sets the referrer into a cookie
	public static function preserveReferer() {
		if (array_key_exists('HTTP_REFERER', $_SERVER)) {
			$referer = $_SERVER['HTTP_REFERER'];

			// only set if referrer is not on this site
			if (strpos($referer, UserConfig::$SITEROOTFULLURL) === 0) {
				return;
			}

			$storage = new MrClay_CookieStorage(array(
				'secret' => UserConfig::$SESSION_SECRET,
				'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
				'path' => UserConfig::$SITEROOTURL,
				'expire' => 0,
				'httponly' => true
			));

			if (!$storage->store(UserConfig::$entry_referer_key, $referer)) { 
				throw new Exception(implode("\n", $storage->errors));
			}

			self::$referer = $referer;
		}
	}

	public static function getReferer() {
		// use static one if we're on an entry page
		if (!is_null(self::$referer)) {
			return self::$referer;
		}

		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));
		
		return($storage->fetch(UserConfig::$entry_referer_key));
	}
}
