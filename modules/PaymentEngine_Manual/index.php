<?php

  class PaymentEngine_Manual extends PaymentEngine 
  {
  
    public static $loaded = 0;
    public function __construct() 
    {

      $this->engineSlug = 'PaymentEngine_Manual';
      if(!self::$loaded) {
        parent::__construct();
        self::$loaded = 1;
      }
    }

    public function getSlug() 
    {
      
      return "PaymentEngine_Manual";
    }
    
    public function getTitle() 
    {
    
      return "Manual Payment Processing";
    }
    
    public function changeSubscription($account_id, $plan_slug, $schedule_slug) 
    {
    
      // Okay
      return TRUE;
    }
    
    public function storeTransactionDetails($transaction_id,$details) 
		{
		  
		  if(is_null($transaction_id)) {
		    return FALSE;
      }
		  
		  // Extract data from array
		  foreach(array('operator_id','funds_source','comment') as $i) {
		    $$i = isset($details[$i]) ? $details[$i] : NULL;
      }
    
      $db = UserConfig::getDB();

      if (!($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.
        'transaction_details_'.$this->getSlug().
        ' (transaction_id, operator_id, funds_source, comment) VALUES(?,?,?,?)' )))
      {
        throw new Exception("Can't prepare statement: ".$db->error);
      }

      if (!$stmt->bind_param('iiss', $transaction_id,$operator_id,$funds_source,$comment)) {
        throw new Exception("Can't bind parameters: ".$stmt->error);
      }

      if (!$stmt->execute()) {
        throw new Exception("Can't execute statement: ".$stmt->error);
      }

      return TRUE;
    }
    
    public function expandTransactionDetails($transaction_id) 
    {
		  if(is_null($transaction_id)) {
		    return FALSE;
      }
		  
      $db = UserConfig::getDB();

      if (!($stmt = $db->prepare('SELECT operator_id, funds_source, comment FROM '.
        UserConfig::$mysql_prefix.'transaction_details_'.$this->getSlug().
        ' WHERE transaction_id = ?' )))
      {
        throw new Exception("Can't prepare statement: ".$db->error);
      }

      if (!$stmt->bind_param('i', $transaction_id)) {
        throw new Exception("Can't bind parameters: ".$stmt->error);
      }

      if (!$stmt->execute()) {
        throw new Exception("Can't execute statement: ".$stmt->error);
      }
      
      if (!$stmt->bind_result($operator_id, $funds_source, $comment)) {
        throw new Exception("Can't bind result: ".$stmt->error);
      }
        
      $details = array();    
      if ($stmt->fetch() === TRUE) {
  		  foreach(array('operator_id','funds_source','comment') as $i) {
	  	    $details[$i] = $$i;
        }
        return $details;
      }
      
      return FALSE;

    }
    
    // Admin UI functions
    
    public function renderAdminMenuItem() 
    {
      
      global $ADMIN_SECTION;
      if($ADMIN_SECTION == $this->engineSlug)
        echo " | Payments (manual mode)";
      else
        echo " | <a href=\"".UserConfig::$USERSROOTURL."/modules/".$this->engineSlug."/admin.php\">Payments (manual mode)</a>\n";
    }
    
  }
    