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
	private $basePrice;
	private $period;
	private $detailsURL;
	private $capabilities;
	private $downgradeTo;
	private $gracePeriod;
	
	private $PaymentSchedules;
	private $user_activate_hook;
	private $user_deactivate_hook;

	public function __construct($a) {
	
		# Known parameters and their default values listed here:
		$parameters = array(
			'id' => NULL,
			'name' => NULL,
			'description' => '',
			'basePrice' => 0,
			'period' => NULL,
			'detailsURL' => NULL,
			'PaymentSchedules' => NULL,
			'capabilities' => array(),
			'downgradeTo' => 'FREE',
			'gracePeriod' => 0,
			'user_activate_hook' => '',
			'user_deactivate_hook' => '',
		);
		
		# Mandatory parameters are those whose default value is NULL
		$mandatory = array();
		foreach($parameters as $p => $v) {
			if($v === NULL) $mandatory[] = $p;
		}
		
		$missing = array_diff($mandatory,array_keys($a));
		if(count($missing))
			throw new Exception("Following mandatory parameters were not found in init array: ".implode(',',$missing));
			
		# Set attributes according to init array
		foreach($parameters as $p)
			if(isset($a[$p])) $this->$p = $a[$p];
			
		# Instantiate PaymentSchedules
		$schedules = array();
		foreach($this->PaymentSchedules as $s)
			$schedules[] = new PaymentSchedule($s);
		$this->PaymentSchedules = $schedules;
		
		# Check user hooks
		if(!function_exists($this->user_activate_hook))
			throw new Exception("Activate hook function ".$this->user_activate_hook." is not defined");
		if(!function_exists($this->user_deactivate_hook))
			throw new Exception("Deactivate hook function ".$this->user_deactivate_hook." is not defined");
		
		# We are set
	}

  # Making private variables visible, but read-only 
	public function __get($v) {
  	return (!in_array($var,array('instance') && isset($this->$var)) ? $this->$var : false;
	}
	
	public function getPaymentScheduleIDs() {
	
		$ids = array();
		foreach($this->PaymentSchedules as $s) {
			$ids[] = $s->ID;
		}
		return $ids;
	}
	
	public function getPaymentSchedule($id) {
	
		if($id === NULL) return FALSE;
		foreach($this->PaymentSchedules as $s) {
			if($s->ID == $id) return $s;
		}
		return NULL;
	}
	
	public function expandTransaction($t) {
		
		return $t->comment;
	}
	
	public function activate_hook($PlanID) {
	
		call_user_func_array($this->user_activate_hook,array('PlanID' => $PlanID));
	}
	
  public function deactivate_hook($PlanID) {
  	
  	call_user_func_array($this->user_deactivate_hook,array('PlanID' => $PlanID));
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
      
    foreach($a as $p)
      $this->Plans[] = new Plan($p);
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
