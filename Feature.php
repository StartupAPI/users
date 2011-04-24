<?php

class Feature {
	private $id;
	private $name;
	private $enabled;
	private $enabled_for_all;

	// moved feature array from users_config to here
	private static $features = array();

	public function __construct($id, $name, $enabled = true, $enabled_for_all = false) {
		$this->id = $id;
		$this->name = $name;
		$this->enabled = $enabled;
		$this->enabled_for_all = $enabled_for_all;

		self::$features[$id] = $this;
	}

	public function getID() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function isEnabled() {
		return $this->enabled;
	}

	public static function getAll() {
		return self::$features;
	}

	public static function getByID($id) {
		if (array_key_exists($id, self::$features)) {
			return self::$features[$id];
		}

		return null;
	}

	// used for backwards compatibility
	public static function init() {
		foreach (UserConfig::$features as $id => $details) {
			new Feature($id, $details[0], $details[1], $details[2]);
		}
	}

	public function isEnabledForAccount($account){
		if (!$this->enabled) {
			return false;
		}

		// if feature is forced, return true
		if (!$this->enabled_for_all) {
			return true;
		}

		// now, let's see if account has it enabled
		$db = UserConfig::getDB();

		$accountid = $account->getID();

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'account_features WHERE account_id = ? AND feature_id = ?'))
		{
			if (!$stmt->bind_param('ii', $accountid, $this->id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($enabled))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();

			return $enabled > 0 ? true : false;
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}

	/*
	 * Returns true if user has requested feature enabled
	 */
	public function isEnabledForUser($user) {
		if (!$this->enabled) {
			return false;
		}

		// if feature is forced, return true
		if ($this->enabled_for_all) {
			return true;
		}

		// if user's account has feature, user has it too
		if (UserConfig::$useAccounts && $user->getCurrentAccount()->hasFeature($this)) {
			return true;
		}

		// now, let's see if user has it enabled
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'user_features WHERE user_id = ? AND feature_id = ?'))
		{
			if (!$stmt->bind_param('ii', $userid, $this->id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($enabled))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();

			return $enabled > 0 ? true : false;
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return false;
	}

	public function disableForUser($user) {
		// now, let's see if user has it enabled
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.'user_features WHERE user_id = ? AND feature_id = ?'))
		{
			if (!$stmt->bind_param('ii', $userid, $this->id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}

	public function enableForUser($user) {
		if (!$this->enabled) {
			return;
		}

		// if feature is forced, return true
		if ($this->enabled_for_all) {
			return;
		}

		// now, let's see if user has it enabled
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('REPLACE INTO '.UserConfig::$mysql_prefix.'user_features (user_id, feature_id) VALUES (?, ?)'))
		{
			if (!$stmt->bind_param('ii', $userid, $this->id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}
}

Feature::init();
