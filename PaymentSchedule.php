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
	public $chargeAmount;
	public $chargePeriod;

	public function __construct($a) {

    # Known parameters and their default values listed here:
    $parameters = array(
      'id' => NULL,
      'name' => NULL,
      'description' => '',
      'chargeAmount' => NULL,
      'chargePeriod' => NULL,
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
	}

}
