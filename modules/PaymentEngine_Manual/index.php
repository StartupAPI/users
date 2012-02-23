<?php

  class PaymentEngine_Manual extends PaymentEngine {
  
    public static $loaded = 0;
    public function __construct() {

      $this->engineID = 'PaymentEngine_Manual';
      if(!self::$loaded) {
        parent::__construct();
        self::$loaded = 1;
      }
    }

    public function getID() {
      
      return "PaymentEngine_Manual";
    }
    
    public function getTitle() {
    
      return "Manual Payment Processing";
    }
    
    public function changeSubscription($plan_id, $schedule_id) {
    
      # Okay
      return TRUE;
    }
    
    public function renderAdminMenuItem() {
      
      global $ADMIN_SECTION;
      if($ADMIN_SECTION == $this->engineID)
        echo " | Payments (manual mode)";
      else
        echo " | <a href=\"".UserConfig::$USERSROOTURL."/modules/".$this->engineID."/admin.php\">Payments (manual mode)</a>\n";
    }
    
  }
    