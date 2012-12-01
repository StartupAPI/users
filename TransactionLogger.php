<?php

  class TransactionLogger {

    public static function Log($account_id, $engine_slug, $amount, $message) {

      $db = UserConfig::getDB();

      if (!($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.
        'transaction_log (date_time, account_id, engine_slug, amount, message) VALUES (NOW(), ?, ?, ?, ?)')))
          throw new Exception("Can't prepare statement: ".$db->error);

      if (!$stmt->bind_param('isds',$account_id,$engine_slug,$amount,$message))
        throw new Exception("Can't bind parameter".$stmt->error);

      if (!$stmt->execute())
        throw new Exception("Can't execute statement: ".$stmt->error);

      $id = $db->insert_id;
      $stmt->close();
      return $id;
    }

    public static function getAccountTransactions($account_id, $from = NULL, $to = NULL, $limit = NULL, $offset = NULL) {

      $db = UserConfig::getDB();

      $query = 'SELECT transaction_id, date_time, engine_slug, amount, message FROM '.
        UserConfig::$mysql_prefix.'transaction_log WHERE account_id = ?'.
        (is_null($from)   ? '' : ' AND date_time >= ?').
        (is_null($to)     ? '' : ' AND date_time - INTERVAL 1 DAY <= ?').
        ' ORDER BY date_time'.
        (is_null($limit)  ? '' : ' LIMIT ?').
        (is_null($offset) ? '' : ' OFFSET ?');

      if (!($stmt = $db->prepare($query)))
        throw new Exception("Can't prepare statement: ".$db->error);

      $params = array();
      $types = 'i'.(is_null($from) ? '' : 's').(is_null($to) ? '' : 's').(is_null($limit) ? '' : 'i').(is_null($offset) ? '' : 'i');
      $params[] = &$types;
      $params[] = &$account_id;
      if(!is_null($from))   $params[] = &$from;
      if(!is_null($to))     $params[] = &$to;
      if(!is_null($limit))  $params[] = &$limit;
      if(!is_null($offset)) $params[] = &$offset;

      if (!call_user_func_array(array($stmt, 'bind_param'), $params))
        throw new Exception("Can't bind parameter".$stmt->error);

      if (!$stmt->execute())
        throw new Exception("Can't execute statement: ".$stmt->error);

      if(!$stmt->bind_result($t_id, $date_time, $engine_slug, $amount, $message))
        throw new Exception("Can't bind result: ".$stmt->error);

      $t = array();
      while($stmt->fetch() === TRUE)
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
