<?php

/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */

/**
 * Every account is associated with existing Plan using Plan ID and PaymentSchedule using PaymentScheduleID.
 */
class PaymentSchedule {

	/**
	 * @var string Payment schedule slug
	 */
	private $slug;

	/**
	 * @var string Payment schedule name
	 */
	private $name;

	/**
	 * @var string Patment schedule description
	 */
	private $description;

	/**
	 * @var int Charge amount
	 */
	private $charge_amount;

	/**
	 * @var int Number of days between charges
	 */
	private $charge_period;

	/**
	 * Creates a new schedule based on parameters passed
	 *
	 * @param string $slug Schedule slug
	 * @param mixed[] $a Array of schedule parameters
	 *
	 * @throws Exception
	 */
	public function __construct($slug, $a) {

		# Known parameters and their default values listed here:
		$parameters = array(
			'slug' => NULL,
			'name' => NULL,
			'description' => '',
			'charge_amount' => NULL,
			'charge_period' => NULL,
			'is_default' => 0,
		);

		if ($slug === NULL)
			throw new Exception("slug required");
		if (!is_array($a))
			throw new Exception("configuration array required");
		$a['slug'] = $slug;

		# Mandatory parameters are those whose default value is NULL
		$mandatory = array();
		foreach ($parameters as $p => $v) {
			if ($v === NULL)
				$mandatory[] = $p;
		}

		$missing = array_diff($mandatory, array_keys($a));
		if (count($missing))
			throw new Exception("Following mandatory parameters were not found in init array: " . implode(',', $missing));

		# Set attributes according to init array
		foreach ($parameters as $p => $v)
			if (isset($a[$p]))
				$this->$p = $a[$p];
	}

	/**
	 * Returns private property values
	 *
	 * @param string $v Property name
	 *
	 * @return mixed Property value
	 */
	public function __get($v) {
		return (!in_array($v, array('instance')) && isset($this->$v)) ? $this->$v : false;
	}

	/**
	 * Sets this schedule as default
	 */
	public function setAsDefault() {
		$this->is_default = 1;
	}

}
