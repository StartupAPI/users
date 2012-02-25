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
	private $details_url;
	private $capabilities;
	private $downgrade_to;
	private $grace_period;
	
	private $payment_schedules;
	private $user_activate_hook;
	private $user_deactivate_hook;

	private static $Plans = array();

	public function __construct($id,$a) {
	
		# Known parameters and their default values listed here:
		$parameters = array(
			'id' => NULL,
			'name' => NULL,
			'description' => NULL,
			'base_price' => NULL,
			'base_period' => NULL,
			'details_url' => NULL,
			'payment_schedules' => array(),
			'capabilities' => array(),
			'downgrade_to' => UserConfig::$default_plan,
			'grace_period' => NULL,
			'user_activate_hook' => NULL,
			'user_deactivate_hook' => NULL,
		);
		
		if ($id === NULL)
  		throw new Exception("id required");
    if (!is_array($a))
      throw new Exception("configuration array required");
		$a['id'] = $id;
	
		# Mandatory parameters are those whose default value is NULL
		$mandatory = array('id','name');
		
		$missing = array_diff($mandatory,array_keys($a));
		if (count($missing))
			throw new Exception("Following mandatory parameters were not found in init array for plan $id: ".implode(',',$missing));
			
		# Set attributes according to init array
		foreach($parameters as $p => $v)
			if (isset($a[$p])) $this->$p = $a[$p];
			else $this->$p = $v;
			
    # If downgrade_to has the same id as we have, reset it to null
    if ($this->id == $this->downgrade_to)
      $this->downgrade_to = NULL;

		# Instantiate PaymentSchedules, replacing stored parameters arrays with actual objects
		if (is_array($this->payment_schedules)) {
  		$schedules = array();
	  	foreach($this->payment_schedules as $id => $s)
		  	$schedules[] = new PaymentSchedule($id, $s);
  		$this->payment_schedules = $schedules;
  		
  		if (!$this->getDefaultPaymentSchedule() && count($this->payment_schedules))
        $this->payment_schedules[0]->setAsDefault();
    }
    
		# Check user hooks
		if ($this->user_activate_hook != '' && !function_exists($this->user_activate_hook))
			throw new Exception("Activate hook function ".$this->user_activate_hook." is not defined");
		if ($this->user_deactivate_hook != '' && !function_exists($this->user_deactivate_hook))
			throw new Exception("Deactivate hook function ".$this->user_deactivate_hook." is not defined");
		
		# We are set
	}

  # Making private variables visible, but read-only 
	public function __get($v) {
  	return (!in_array($v,array('instance')) && isset($this->$v)) ? $this->$v : false;
	}
	
	public function getPaymentScheduleIDs() {
	
		$ids = array();

		if (is_array($this->payment_schedules))
  		foreach($this->payment_schedules as $x => $s)
	  		$ids[] = $s->id;
	  		
		return $ids;
	}
	
	public function getPaymentSchedule($id) {
	
		if ($id === NULL) return FALSE;

		if (is_array($this->payment_schedules))
  		foreach($this->payment_schedules as $x => $s)
	  		if ($s->id == $id) return $s;

		return NULL;
	}
	
	public function getDefaultPaymentSchedule() {
	
	  if (is_array($this->payment_schedules))
	    foreach($this->payment_schedules as $x => $s) 
	      if ($s->is_default) return $s;
	      
    return NULL;
  }
	
	public function expandTransaction($t) {
		
		return $t->comment;
	}
	
	public function activate_hook($PlanID) {
	
	  if ($this->user_activate_hook == '') return;
		call_user_func_array($this->user_activate_hook,array('OldPlanID' => $PlanID, 'NewPlanID' => $this->id));
	}
	
  public function deactivate_hook($PlanID) {
  	
  	if ($this->user_deactivate_hook == '') return;
  	call_user_func_array($this->user_deactivate_hook,array('NewPlanID' => $PlanID, 'OldPlanID' => $this->id));
	}

  public static function init($a) {
  
    if (count(self::$Plans))
      throw new Exception("Already initialized");
      
    if (!is_array($a))
      throw new Exception("Parameter is not an array");

    foreach($a as $id => $p)
      self::$Plans[] = new self($id,$p);
  }
  
  public static function getPlan($id) {
  
    if ($id === NULL || !count(self::$Plans)) return FALSE;
    foreach(self::$Plans as $p) {
      if ($p->id == $id) return $p;
    }
    return NULL;
  }
  
  public static function getPlanIDs() {
  
    if (!count(self::$Plans)) return FALSE;
    $ids = array();
    foreach(self::$Plans as $p)
      $ids[] = $p->id;
    return $ids;
  }
}
