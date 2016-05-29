<?php

/**
 * Shallow authentication module (UNFINISHED)
 *
 * This module is used for creating shallow profiles
 * It is useful for signing up people without knowing any credentials
 *
 * @todo Finish the implementation ;)
 *
 * @package StartupAPI
 * @subpackage Authentication\Shallow
 */
class ShallowAuthenticationModule extends StartupAPIModule {

	public function getID() {
		return "shallow";
	}

	public function getLegendColor() {
		return "f0f0f0";
	}

	public static function getModulesTitle() {
		return "Shallow";
	}

	public static function getModulesDescription() {
		return "<p>Shallow authentication module (UNFINISHED)</p>
				<p>This module is used for creating shallow profiles.</p>
				<p>It is useful for signing up people without knowing any credentials.</p>";
	}

	public function getDescription() {
		return self::getModulesDescription();
	}

	/**
	 * Returns a number of users with who provided email address
	 *
	 * @return int Number of users with email address
	 *
	 * @throws DBException
	 */
	public function getTotalConnectedUsers() {
		$db = UserConfig::getDB();

		$conns = 0;

		if ($stmt = $db->prepare("SELECT count(*) AS conns FROM u_users WHERE regmodule='shallow'")) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($conns)) {
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $conns;
	}
}
