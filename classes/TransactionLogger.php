<?php

/**
 * Helper class to facilitate logging for subscription transactions
 *
 * @package StartupAPI
 * @subpackage Subscriptions
 */
class TransactionLogger {

	/**
	 * Log a subscription transaction
	 *
	 * @param int $account_id Account ID this transaction is related to
	 * @param string $engine_slug Payment Engine slug
	 * @param int $amount Amount of the transaction
	 * @param string $message Descriptive message
	 *
	 * @return int Transaction ID
	 *
	 * @throws Exception
	 */
	public static function Log($account_id, $engine_slug, $amount, $message) {

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('INSERT INTO ' . UserConfig::$mysql_prefix . 'transaction_log
			(date_time, account_id, engine_slug, amount, message) VALUES (NOW(), ?, ?, ?, ?)'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('isds', $account_id, $engine_slug, $amount, $message)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		$id = $db->insert_id;
		$stmt->close();
		return $id;
	}

	/**
	 * Returns account transactions
	 *
	 * @param int $account_id Account ID
	 * @param string $from Date range start
	 * @param string $to Date range end
	 * @param boolean $order Sort order (true for ASC, false for DESC)
	 * @param int $limit Limit number of transactions to return
	 * @param int $offset Result number to start with
	 *
	 * @return array[] Array of transactions
	 *
	 * @throws Exception
	 */
	public static function getAccountTransactions($account_id, $from = NULL, $to = NULL, $order = false, $limit = NULL, $offset = NULL) {

		$db = UserConfig::getDB();

		$query = 'SELECT transaction_id, date_time, engine_slug, amount, message FROM ' .
				UserConfig::$mysql_prefix . 'transaction_log WHERE account_id = ?' .
				(is_null($from) ? '' : ' AND date_time >= ?') .
				(is_null($to) ? '' : ' AND date_time - INTERVAL 1 DAY <= ?') .
				' ORDER BY date_time ' . ($order ? 'ASC' : 'DESC') .
				(is_null($limit) ? '' : ' LIMIT ?') .
				(is_null($offset) ? '' : ' OFFSET ?');

		if (!($stmt = $db->prepare($query))) {
			throw new DBPrepareStmtException($db);
		}

		$params = array();
		$types = 'i' . (is_null($from) ? '' : 's') . (is_null($to) ? '' : 's') . (is_null($limit) ? '' : 'i') . (is_null($offset) ? '' : 'i');
		$params[] = &$types;
		$params[] = &$account_id;
		if (!is_null($from)) {
			$params[] = &$from;
		}
		if (!is_null($to)) {
			$params[] = &$to;
		}
		if (!is_null($limit)) {
			$params[] = &$limit;
		}
		if (!is_null($offset)) {
			$params[] = &$offset;
		}

		if (!call_user_func_array(array($stmt, 'bind_param'), $params)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->bind_result($t_id, $date_time, $engine_slug, $amount, $message)) {
			throw new DBBindResultException($db, $stmt);
		}

		$t = array();
		while ($stmt->fetch() === TRUE)
			$t[] = array(
				'transaction_id' => $t_id,
				'date_time' => $date_time,
				'account_id' => $account_id,
				'engine_slug' => $engine_slug,
				'amount' => $amount,
				'message' => $message);

		$stmt->close();

		return $t;
	}

}