<?php

namespace StartupAPI\API\v1;

/**
 * @package StartupAPI
 * @subpackage API
 */
require_once(dirname(__DIR__) . '/Endpoint.php');

/**
 * Returns modules available in the system
 *
 * @package StartupAPI
 * @subpackage API
 */
class Modules extends \StartupAPI\API\Endpoint {

	public function __construct() {
		parent::__construct('/v1/modules', 'Returns a list of modules configured in the system');
	}

	public function call($values, $raw_request_body = null) {
        $results = [];

        foreach (\UserConfig::$all_modules as $module) {
            $result = [
                'id' => $module->getID(),
                'title' => $module->getTitle()
            ];

            if (is_subclass_of($module, '\AuthenticationModule', false)) {
                $result['type'] = 'authentication';
                $result['is_compact'] = $module->isCompact();
            } else if (is_subclass_of($module, '\PaymentEngine', false)) {
                $result['type'] = 'payment';
            } else if (is_subclass_of($module, '\EmailModule', false)) {
                $result['type'] = 'email';
            } else if (is_subclass_of($module, '\StartupAPIModule', false)) {
                $result['type'] = 'other';
            }

            $results[] = $result;
        }

		return $results;
	}

}
