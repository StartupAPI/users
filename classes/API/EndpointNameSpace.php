<?php
namespace StartupAPI\API;

/**
 * Endpoint namespace used to group endpoints together
 *
 * @package StartupAPI
 * @subpackage API
 */
class EndpointNameSpace {

	/**
	 * @var string Slug to be used in the URLs
	 */
	private $slug;

	/**
	 * @var string Human readable name of the namespace
	 */
	private $name;

	/**
	 * @var string Description of the namespace
	 */
	private $description;

	public function __construct($slug, $name, $description) {
		$this->slug = $slug;
		$this->name = $name;
		$this->description = $description;
	}

	public function getSlug() {
		return $this->slug;
	}

	public function getName() {
		return $this->name;
	}

	public function getDescription() {
		return $this->description;
	}

}
