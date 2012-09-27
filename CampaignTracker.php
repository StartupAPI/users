<?php
require_once(dirname(__FILE__).'/CookieStorage.php');

/**
 * Tracks sources of marketing campaigns using variables in the incoming URLs
 * as well as incoming links and preserves this information for registered users.
 *
 * @package StartupAPI
 * @subpackage Analytics
 */
class CampaignTracker
{
	/**
	 * @var string Referer URL
	 */
	private static $referer = null;

	/**
	 * @var array Array of campaign tracking paramaters
	 *
	 * @see self::recordCampaignVariables
	 */
	private static $campaign = null;

	/**
	 * Preserves campaign variables
	 *
	 * Preserves original campaign variables in a cookie on first incoming
	 * page view to be saved into user object later upon registration.
	 *
	 * Will populate self::$campaign variable with values for zero or more of the following keys:
	 *
	 * - cmp_source - campaign source ('utm_source' URL parameter is tracked by default)
	 * - cmp_medium - campaign medium ('utm_medium' URL parameter is tracked by default)
	 * - cmp_keywords - campaign keyworkds ('utm_term' URL parameter is tracked by default)
	 * - cmp_content - campaign content ('utm_content' URL parameter is tracked by default)
	 * - cmp_name - campaign name ('utm_campaign' URL parameter is tracked by default)
	 *
	 * You can add more URL parameters to be tracked using UserConfig::$campaign_variables configuration array
	 *
	 * This function is called on every page view, let's keep it fast and simple.
	 *
	 * @throws StartupAPIException
	 *
	 * @see UserConfig::$campaign_variables
	 */
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
			throw new StartupAPIException(implode("\n", $storage->errors));
		}
	}

	/**
	 * Preserves HTTP_REFERER into a cookie
	 *
	 * Preserves original HTTP_REFERER in a cookie on first incoming
	 * page view to be saved into user object later upon registration.
	 *
	 * This function is called on every page view, let's keep it fast and simple.
	 *
	 * @throws StartupAPIException
	 */
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
				throw new StartupAPIException(implode("\n", $storage->errors));
			}

			self::$referer = $referer;
		}
	}

	/**
	 * Returns original referer for this browsing session
	 *
	 * @return string Referer URL
	 */
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

	/**
	 * Returns campaign array for this browser session
	 *
	 * @return array Array of campaign tracking variables
	 */
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

	/**
	 * Upserts campaign source string into database and returns numeric ID for it
	 *
	 * @param string $source Campaign source string
	 *
	 * @return int Numeric campaign source ID
	 *
	 * @throws DBException
	 */
	public static function getCampaignSourceID($source) {
		return self::getCampaignDictionaryID($source, 'cmp_source', 'source');
	}

	/**
	 * Upserts campaign medium string into database and returns numeric ID for it
	 *
	 * @param string $medium Campaign medium string
	 *
	 * @return int Numeric campaign medium ID
	 *
	 * @throws DBException
	 */
	public static function getCampaignMediumID($medium) {
		return self::getCampaignDictionaryID($medium, 'cmp_medium', 'medium');
	}
	/**
	 * Upserts campaign keywords string into database and returns numeric ID for it
	 *
	 * @param string $keywords Campaign keywords string
	 *
	 * @return int Numeric campaign keywords ID
	 *
	 * @throws DBException
	 */
	public static function getCampaignKeywordsID($keywords) {
		return self::getCampaignDictionaryID($keywords, 'cmp_keywords', 'keywords');
	}

	/**
	 * Upserts campaign content string into database and returns numeric ID for it
	 *
	 * @param string $content Campaign content string
	 *
	 * @return int Numeric campaign content ID
	 *
	 * @throws DBException
	 */
	public static function getCampaignContentID($content) {
		return self::getCampaignDictionaryID($content, 'cmp_content', 'content');
	}

	/**
	 * Upserts campaign name into database and returns numeric ID for it
	 *
	 * @param string $name Campaign name string
	 *
	 * @return int Numeric campaign name ID
	 *
	 * @throws DBException
	 */
	public static function getCampaignNameID($name) {
		return self::getCampaignDictionaryID($name, 'cmp', 'name');
	}

	/**
	 * Campaign dictionary management helper function
	 *
	 * Returns numeric ID for campaign string identifiers passed in URLs
	 *
	 * @param string $string_value String to be looked up
	 * @param string $dictionary_table Dictionary table name
	 * @param string $dictionary_column Dictionary column name
	 *
	 * @return int Numeric ID for a dictionary term
	 *
	 * @throws DBException
	 */
	protected static function getCampaignDictionaryID($string_value, $dictionary_table, $dictionary_column) {
		$string_value = mb_convert_encoding($string_value, 'UTF-8');

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('INSERT IGNORE INTO '.UserConfig::$mysql_prefix."$dictionary_table ($dictionary_column)
						VALUES (?)"))
		{
			if (!$stmt->bind_param('s', $string_value))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt, "Can't insert compaign $dictionary_column");
			}
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db, "Can't insert compaign $dictionary_column");
		}

		$id = null;
		if ($stmt = $db->prepare('SELECT id FROM '.UserConfig::$mysql_prefix."$dictionary_table
						WHERE $dictionary_column = ?"))
		{
			if (!$stmt->bind_param('s', $string_value))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($id))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $id;
	}
}
