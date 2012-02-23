<?php

  class TransactionLogger {
  
    public static Log($user_id, $account_id, $amount, $message) {
    
      $db = UsersConfig::getDB();
      
      if (!($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.
        'transaction_log (date_time, user_id, account_id, amount, message) VALUES (?, ?, ?, ?, ?)')))
          throw new Exception("Can't prepare statement: ".$db->error);
          
      if (!$stmt->bind_param('siids',date('Y-m-d H:i:s'),$user_id,$account_id,$amount,$message))
        throw new Exception("Can't bind parameter".$stmt->error);
        
      if (!$stmt->execute())
        throw new Exception("Can't execute statement: ".$stmt->error);
        
      $id = $db->insert_id;
      $stmt->close();
      return $id;
    }
    
    public static getUserTransactions($user_id, $from = NULL, $to = NULL) {
    
    }
    
    public static getAccountTransactions($acccount_id, $from = NULL, $to = NULL) {
    
    }
  }
