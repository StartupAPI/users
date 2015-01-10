<?php
require_once(__DIR__ . '/global.php');

if (!array_key_exists('call', $_GET)) {
	// @TODO Add interactive docs here
	// (for public methods or if user is authenticated, with private methods and so on)
	header('HTTP/1.0 400 Bad Request');
	?>
	<h1>400 Bad Request</h1>
	<p>Required parameter: <b>call</b></p>
	<?php
	$user = StartupAPI::getUser();
	if (!is_null($user) && $user->isAdmin()) {
		?>
		<p>
			Available endpoints:
			<?php
			$all_endpoints = \StartupAPI\API\Endpoint::getAllEndpointsBySlug();
			foreach (\StartupAPI\API\Endpoint::getNamespaces() as $namespace) {
				?>
			<h2><?php echo $namespace->getName(); ?></h2>
			<ul>
				<?php
				$namespace_slug = $namespace->getSlug();
				foreach ($all_endpoints[$namespace_slug] as $endpoint_slug => $endpoints) {
					?>
					<li>
						<h3><?php echo $endpoint_slug ?></h3>
						<dl>
							<?php
							foreach ($endpoints as $method => $endpoint) {
								$call = "/$namespace_slug$endpoint_slug";
								?>
								<dt style="font">
								<?php echo $method; ?>
								<?php
								if ($method == 'GET') {
									?>
									<a href="?call=<?php echo $call; ?>"><?php echo UserConfig::$USERSROOTFULLURL ?>/api.php?call=<b><?php echo $call; ?></b></a>
									<?php
								} else {
									echo UserConfig::$USERSROOTFULLURL;
									?>/api.php?call=<b><?php echo $call; ?></b>
									<?php
								}
								?>
								</dt>
								<dd><?php echo $endpoint->getDescription(); ?></dd>
								<?php
							}
							?>
						</dl>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		}
		?>
		</p>
		<?php
	}
	exit;
}

// parameters come from query string
$query = $_SERVER['QUERY_STRING'];
$params = array();
foreach (explode('&', $query) as $pair) {
	list($key, $value) = explode('=', $pair);

	$key = urldecode($key);

	// support PHP arrays as well
	if (substr($key, -2) == '[]') {
		$key = substr($key, 0, strlen($key) - 2);
	}

	// if empty parameter name is passed, throw an error
	if ($key == '') {
		header('HTTP/1.0 400 Bad Request');
		?>
		<h1>400 Bad Request</h1>
		<p>Parameter name is required: <b><span style="font-style: italic; color: red">name</span>=<?php echo rawurlencode($value) ?></b></p>
		<?php
		exit;
	}

	$value = urldecode($value);

	if (array_key_exists($key, $params)) {
		// convert existing value to array if not an array yet
		if (!is_array($params[$key])) {
			$params[$key] = array($params[$key]);
		}
		$params[$key][] = $value;
	} else {
		$params[$key] = $value;
	}
}

unset($params['call']); // except for the call parameter

header('Content-type: application/json');

try {
	$endpoint = \StartupAPI\API\Endpoint::getEndpoint($_SERVER['REQUEST_METHOD'], $_GET['call']);

	// default output format is JSON
	$response = array(
		'meta' => array(
			'success' => true
		),
		'result' => $endpoint->call($params)
	);
} catch (\StartupAPI\API\NotFoundException $ex) {
	header('HTTP/1.0 404 Not Found');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (\StartupAPI\API\MethodNotAllowedException $ex) {
	header('HTTP/1.0 405 Method not allowed');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
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
} catch (\StartupAPI\API\UnauthorizedException $ex) {
	header('HTTP/1.0 403 Forbidden');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (\StartupAPI\API\RequiredParameterException $ex) {
	header('HTTP/1.0 400 Parameter Required');
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
