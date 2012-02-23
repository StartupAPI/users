<?php

  class PaymentEngine_Manual extends PaymentEngine {
  
    public function __construct() {

      parent::__construct();
      $this->engineID = 'PaymentEngine_Manual';
    }

    public function getID() {
      
      return "PaymentEngine_Manual";
    }
    
    public function getTitle() {
    
      return "Payment Engine for handling manual processing";
    }
    
    public function changeSubscription($plan_id, $schedule_id) {
    
      # Okay
      return TRUE;
    }
    
    public function renderAdminMenuItem() {
      
      global $ADMIN_SECTION;
      if($ADMIN_SECTION == $this->engineID)
        echo "Payments (manual mode)";
      else
        echo " | <a href=\"".UserConfig::$USERSROOTURL."/modules/paymentengine_manual/admin.php\">Payments (manual mode)</a>\n";
    }
    
  }
    