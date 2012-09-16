<?php
/**
 * @package StartupAPI
 * @subpackage Analytics
 *
 * This file should be included on all pages if referer tracking is required
 */
require_once(dirname(__FILE__).'/CookieStorage.php');
class CampaignTracker
{
	private static $referer = null;
	private static $campaign = null;

	public static function recordCampaignVariables() {
		$campaign = array();

		foreach (UserConfig::$campaign_variables as $variable => $urlparams) {
			foreach ($urlparams as $param) {
				if (array_key_exists($param, $_GET)) {
					$campaign[$variable] = $_GET[$param];
				}
			}
		}

		if (count($campaign) == 0) {
			return;
		}

		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'expire' => 0,
			'httponly' => true
		));

		if (!$storage->store(UserConfig::$entry_cmp_key, serialize($campaign))) {
			throw new Exception(implode("\n", $storage->errors));
		}
	}

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

	public static function getCampaign() {
		// use static one if we're on an entry page
		if (!is_null(self::$campaign)) {
			return self::$campaign;
		}

		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));

		return(unserialize($storage->fetch(UserConfig::$entry_cmp_key)));
	}

	public static function getCampaignSourceID($source) {
		$source = mb_convert_encoding($source, 'UTF-8');

		$db = UserConfig::getDB();

		$cmp_source_id = null;
		if ($stmt = $db->prepare('INSERT IGNORE INTO '.UserConfig::$mysql_prefix.'cmp_source (source) VALUES (?)'))
		{
			if (!$stmt->bind_param('s', $source))
			{
				throw new Exception("Can't bind parameter");
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't insert compaign source");
			}
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't insert compaign source");
		}

		if ($stmt = $db->prepare('SELECT id FROM '.UserConfig::$mysql_prefix.'cmp_source
						WHERE source = ?'))
		{
			if (!$stmt->bind_param('s', $source))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($cmp_source_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $cmp_source_id;
	}

	public static function getCampaignMediumID($medium) {
		$medium = mb_convert_encoding($medium, 'UTF-8');

		$db = UserConfig::getDB();

		$cmp_medium_id = null;
		if ($stmt = $db->prepare('INSERT IGNORE INTO '.UserConfig::$mysql_prefix.'cmp_medium (medium) VALUES (?)'))
		{
			if (!$stmt->bind_param('s', $medium))
			{
				throw new Exception("Can't bind parameter");
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't insert compaign medium");
			}
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't insert compaign medium");
		}

		if ($stmt = $db->prepare('SELECT id FROM '.UserConfig::$mysql_prefix.'cmp_medium
						WHERE medium = ?'))
		{
			if (!$stmt->bind_param('s', $medium))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($cmp_medium_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $cmp_medium_id;
	}

	public static function getCampaignKeywordsID($keywords) {
		$keywords = mb_convert_encoding($keywords, 'UTF-8');

		$db = UserConfig::getDB();

		$cmp_keywords_id = null;
		if ($stmt = $db->prepare('INSERT IGNORE INTO '.UserConfig::$mysql_prefix.'cmp_keywords (keywords) VALUES (?)'))
		{
			if (!$stmt->bind_param('s', $keywords))
			{
				throw new Exception("Can't bind parameter");
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't insert compaign keywords");
			}
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't insert compaign keywords");
		}

		if ($stmt = $db->prepare('SELECT id FROM '.UserConfig::$mysql_prefix.'cmp_keywords
						WHERE keywords = ?'))
		{
			if (!$stmt->bind_param('s', $keywords))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($cmp_keywords_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $cmp_keywords_id;
	}

	public static function getCampaignContentID($content) {
		$content = mb_convert_encoding($content, 'UTF-8');

		$db = UserConfig::getDB();

		$cmp_content_id = null;
		if ($stmt = $db->prepare('INSERT IGNORE INTO '.UserConfig::$mysql_prefix.'cmp_content (content) VALUES (?)'))
		{
			if (!$stmt->bind_param('s', $content))
			{
				throw new Exception("Can't bind parameter");
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't insert compaign content");
			}
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't insert compaign content");
		}

		if ($stmt = $db->prepare('SELECT id FROM '.UserConfig::$mysql_prefix.'cmp_content
						WHERE content = ?'))
		{
			if (!$stmt->bind_param('s', $content))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($cmp_content_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $cmp_content_id;
	}

	public static function getCampaignNameID($name) {
		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();

		$cmp_name_id = null;
		if ($stmt = $db->prepare('INSERT IGNORE INTO '.UserConfig::$mysql_prefix.'cmp (name) VALUES (?)'))
		{
			if (!$stmt->bind_param('s', $name))
			{
				throw new Exception("Can't bind parameter");
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't insert compaign name");
			}
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't insert compaign name");
		}

		if ($stmt = $db->prepare('SELECT id FROM '.UserConfig::$mysql_prefix.'cmp
						WHERE name = ?'))
		{
			if (!$stmt->bind_param('s', $name))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($cmp_name_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $cmp_name_id;
	}
}
