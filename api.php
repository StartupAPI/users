<?php
require_once(__DIR__ . '/global.php');

if (!array_key_exists('call', $_GET)) {
	// @TODO Add interactive docs here
	// (for public methods or if user is authenticated, with private methods and so on)
	header('HTTP/1.0 400 Bad Request');
	?>
	<h1>400 Bad Request</h1>
	<p>Required parameter: <b>call</b></p>
	<p>Example: <a href="?call=/startupapi/v1/user">api.php?<b>call</b>=<i>/startupapi/v1/user</i></a></p>
	<?php
	exit;
}

if (!is_array(UserConfig::$api) || !array_key_exists($_GET['call'], UserConfig::$api)) {
	header('HTTP/1.0 400 Bad Request');
	?>
	<h1>400 Bad Request</h1>
	<p>API call not found: <b><?php echo UserTools::escape($_GET['call']) ?></b></p>
	<?php
	exit;
}

$endpoint = UserConfig::$api[$_GET['call']];

// parameters come from query string
$params = $_GET;
unset($params['call']); // except for the call parameter

if (!is_subclass_of($endpoint, '\StartupAPI\API\StartupAPIEndpoint')) {
	header('HTTP/1.0 400 Bad Request');
	?>
	<h1>400 Bad Request</h1>
	<p>Endpoint object must be a subclass of \StartupAPI\API\StartupAPIEndpoint</p>
	<?php
	exit;
}

try {
	// default output format is JSON
	$response = array(
		'meta' => array(
			'success' => true
		),
		'result' => $endpoint->call($params)
	);
} catch (\StartupAPI\API\UnauthenticatedException $ex) {
	header('HTTP/1.0 401 Authentication Required');
	header('WWW-Authenticate: FormBased');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (\StartupAPI\API\APIException $ex) {
	header('HTTP/1.0 500 Server Error');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
}

$global_response = array(
	'meta' => array(
		'call' => $_GET['call']
	)
);

echo json_encode(array_merge_recursive($global_response, $response));
