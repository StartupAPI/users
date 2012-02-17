<?php
/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */

/**
 * Every account is associated with existing Plan using Plan ID and PaymentSchedule using PaymentScheduleID.
 */
class PaymentSchedule {
	public $id;
	public $name;
	public $description;
	public $charge_amount;
	public $charge_period;

	public function __construct($id,$a) {

    # Known parameters and their default values listed here:
    $parameters = array(
      'id' => NULL,
      'name' => NULL,
      'description' => '',
      'charge_amount' => NULL,
      'charge_period' => NULL,
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
      throw new Exception("Following mandatory parameters were not found in init array: ".implode(',',$missing));

    # Set attributes according to init array
    foreach($parameters as $p => $v)
      if(isset($a[$p])) $this->$p = $a[$p];
	}

}
