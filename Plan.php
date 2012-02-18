<?php
/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */

/**
 * Every account is associated with existing Plan using Plan ID and PaymentSchedule using PaymentScheduleID.
 */

require_once (dirname(__FILE__).'/PaymentSchedule.php');

class Plan {
	private $id;
	private $name;
	private $description;
	private $base_price;
	private $base_period;
	private $detail_url;
	private $capabilities;
	private $downgrade_to;
	private $grace_period;
	
	private $payment_schedules;
	private $user_activate_hook;
	private $user_deactivate_hook;

	public function __construct($id,$a) {
	
		# Known parameters and their default values listed here:
		$parameters = array(
			'id' => NULL,
			'name' => NULL,
			'description' => '',
			'base_price' => 0,
			'base_period' => 0,
			'details_url' => NULL,
			'payment_schedules' => array(),
			'capabilities' => array(),
			'downgrade_to' => 'FREE',
			'grace_period' => 0,
			'user_activate_hook' => '',
			'user_deactivate_hook' => '',
		);
		
		if($id === NULL)
  		throw new Exception("id required");
    if(!is_array($a))
      throw new Exception("configuration array required");
		$a['id'] = $id;
	
		# Mandatory parameters are those whose default value is NULL
		$mandatory = array();
		foreach($parameters as $p => $v) {
			if($v === NULL) $mandatory[] = $p;
		}
		
		$missing = array_diff($mandatory,array_keys($a));
		if(count($missing))
			throw new Exception("Following mandatory parameters were not found in init array for plan $id: ".implode(',',$missing));
			
		# Set attributes according to init array
		foreach($parameters as $p => $v)
			if(isset($a[$p])) $this->$p = $a[$p];
			
		# Instantiate PaymentSchedules, replacing stored parameters arrays with actual objects
		if(is_array($this->payment_schedules)) {
  		$schedules = array();
	  	foreach($this->payment_schedules as $id => $s)
		  	$schedules[] = new PaymentSchedule($id, $s);
  		$this->payment_schedules = $schedules;
  		
  		if(!$this->getDefaultPaymentSchedule())
        $this->payment_schedules[0]->setAsDefault();
    }
    
		# Check user hooks
		if($this->user_activate_hook != '' && !function_exists($this->user_activate_hook))
			throw new Exception("Activate hook function ".$this->user_activate_hook." is not defined");
		if($this->user_deactivate_hook != '' && !function_exists($this->user_deactivate_hook))
			throw new Exception("Deactivate hook function ".$this->user_deactivate_hook." is not defined");
		
		# We are set
	}

  # Making private variables visible, but read-only 
	public function __get($v) {
  	return (!in_array($v,array('instance')) && isset($this->$v)) ? $this->$v : false;
	}
	
	public function getPaymentScheduleIDs() {
	
		$ids = array();

		if(is_array($this->payment_schedules))
  		foreach($this->payment_schedules as $x => $s)
	  		$ids[] = $s->id;
	  		
		return $ids;
	}
	
	public function getPaymentSchedule($id) {
	
		if($id === NULL) return FALSE;

		if(is_array($this->payment_schedules))
  		foreach($this->payment_schedules as $x => $s)
	  		if($s->id == $id) return $s;

		return NULL;
	}
	
	public function getDefaultPaymentSchedule() {
	
	  if(is_array($this->payment_schedules))
	    foreach($this->payment_schedules as $x => $s) 
	      if($s->is_default) return $s;
	      
    return NULL;
  }
	
	public function expandTransaction($t) {
		
		return $t->comment;
	}
	
	public function activate_hook($PlanID) {
	
	  if($this->user_activate_hook == '') return;
		call_user_func_array($this->user_activate_hook,array('OldPlanID' => $PlanID, 'NewPlanID' => $this->id));
	}
	
  public function deactivate_hook($PlanID) {
  	
  	if($this->user_deactivate_hook == '') return;
  	call_user_func_array($this->user_deactivate_hook,array('NewPlanID' => $PlanID, 'OldPlanID' => $this->id));
	}
}

class PlanCollection {

  private static $instance;
  private $Plans;
  
  private function __construct() {}
  
  public static function instance() {
  
    if(!isset(self::$instance)) {
    
      $className = __CLASS__;
      self::$instance = new $className;
    }
    return self::$instance;
  }

  public function init($a) {
  
    if(count($this->Plans))
      throw new Exception("Already initialized");
      
    foreach($a as $id => $p)
      $this->Plans[] = new Plan($id,$p);
  }
  
  public function getPlan($id) {
  
    if($id === NULL) return FALSE;
    foreach($this->Plans as $p) {
      if($p->id == $id) return $p;
    }
    return NULL;
  }
  
  public function createPlan($a) {

    $this->Plans[] = new Plan($a);
  }
  
  public function getPlanIDs() {
  
    $ids = array();
    foreach($this->Plans as $p)
      $ids[] = $p->id;
    return $ids;
  }
}
