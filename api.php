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
			$namespaces = array();
			foreach (UserConfig::$api as $call => $endpoint) {
				list($ignore, $namespace, $ignore2) = explode('/', $call, 3);

				$namespaces[$namespace][] = array($call, $endpoint);
			}

			foreach ($namespaces as $namespace => $calls) {
				?>
			<h2><?php echo $namespace; ?></h2>
			<ul>
				<?php
				foreach ($calls as $pair) {
					$call = $pair[0];
					$endpoint = $pair[1];
					?>
					<li>
						<h3><?php echo $endpoint->getEndpointDescription() ?></h3>
						<a href="?call=<?php echo $call; ?>"><?php echo UserConfig::$USERSROOTFULLURL ?>/api.php?call=<b><?php echo $call; ?></b></a>
						<p>Methods:</p>
						<ul>
							<?php
							if ($endpoint instanceof \StartupAPI\API\EndpointAllowsRead) {
								?>
								<li>
									<dl>
										<dt>GET<dt>
										<dd><?php echo $endpoint->getReadDescription(); ?></dd>
									</dl>
								</li>
								<?php
							}
							if ($endpoint instanceof \StartupAPI\API\EndpointAllowsCreate) {
								?>
								<li>
									<dl>
										<dt>PUT<dt>
										<dd><?php echo $endpoint->getCreateDescription(); ?></dd>
									</dl>
								</li>
								<?php
							}
							if ($endpoint instanceof \StartupAPI\API\EndpointAllowsUpdate) {
								?>
								<li>
									<dl>
										<dt>POST<dt>
										<dd><?php echo $endpoint->getUpdateDescription(); ?></dd>
									</dl>
								</li>
								<?php
							}
							if ($endpoint instanceof \StartupAPI\API\EndpointAllowsDelete) {
								?>
								<li>
									<dl>
										<dt>DELETE<dt>
										<dd><?php echo $endpoint->getDeleteDescription(); ?></dd>
									</dl>
								</li>
							<?php }
							?>
						</ul>
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

if (!is_subclass_of($endpoint, '\StartupAPI\API\StartupAPIEndpoint')) {
	header('HTTP/1.0 400 Bad Request');
	?>
	<h1>400 Bad Request</h1>
	<p>Endpoint object must be a subclass of \StartupAPI\API\StartupAPIEndpoint</p>
	<?php
	exit;
}

header('Content-type: application/json');

try {
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		if ($endpoint instanceof \StartupAPI\API\EndpointAllowsRead) {
			$result = $endpoint->read($params);
		} else {
			throw new \StartupAPI\API\MethodNotAllowedException('GET');
		}
	} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if ($endpoint instanceof \StartupAPI\API\EndpointAllowsUpdate) {
			$result = $endpoint->update($params);
		} else {
			throw new \StartupAPI\API\MethodNotAllowedException('POST');
		}
	} else if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
		if ($endpoint instanceof \StartupAPI\API\EndpointAllowsCreate) {
			$result = $endpoint->create($params);
		} else {
			throw new \StartupAPI\API\MethodNotAllowedException('PUT');
		}
	} else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
		if ($endpoint instanceof \StartupAPI\API\EndpointAllowsDelete) {
			$result = $endpoint->delete($params);
		} else {
			throw new \StartupAPI\API\MethodNotAllowedException('DELETE');
		}
	} else {
		throw new \StartupAPI\API\MethodNotAllowedException();
	}

	// default output format is JSON
	$response = array(
		'meta' => array(
			'success' => true
		),
		'result' => $result
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
