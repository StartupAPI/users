<?php

  class PaymentEngine_Manual extends PaymentEngine {
  
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
  }
    