<?php

  class PaymentEngine_Manual extends PaymentEngine {
  
    public static $loaded = 0;
    public function __construct() {

      $this->engineSlug = 'PaymentEngine_Manual';
      if(!self::$loaded) {
        parent::__construct();
        self::$loaded = 1;
      }
    }

    public function getSlug() {
      
      return "PaymentEngine_Manual";
    }
    
    public function getTitle() {
    
      return "Manual Payment Processing";
    }
    
    public function changeSubscription($plan_slug, $schedule_slug) {
    
      # Okay
      return TRUE;
    }
    
    public function renderAdminMenuItem() {
      
      global $ADMIN_SECTION;
      if($ADMIN_SECTION == $this->engineSlug)
        echo " | Payments (manual mode)";
      else
        echo " | <a href=\"".UserConfig::$USERSROOTURL."/modules/".$this->engineSlug."/admin.php\">Payments (manual mode)</a>\n";
    }
    
  }
    