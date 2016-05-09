<?php
namespace StartupAPI;

require_once(__DIR__ . '/global.php');

if (!array_key_exists('call', $_GET)) {
	header('HTTP/1.0 400 Bad Request');
	?>
	<style>
		code {
			font-family: monospace,"Courier New";
			background-color: #f9f9f9;
			padding: 0.2em;
		}

		dl {
			margin-bottom: 2em;
		}

		dt {
			margin-bottom: 0.3em;
		}

		dd {
			margin-bottom: 0.5em;
		}

		b.call {
			color: green;
		}

		b.param {
			color: blue;
		}
	</style>
	<h1>400 Bad Request</h1>
	<p>Required parameter: <b>call</b></p>
	<?php
	$user = StartupAPI::getUser();
	if (!is_null($user) && $user->isAdmin()) {
		?>
		<p>
			Available endpoints:
			<?php
			$all_endpoints = API\Endpoint::getAllEndpointsBySlug();
			foreach (API\Endpoint::getNamespaces() as $namespace) {
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

								$params = $endpoint->getParams();

								$sample_params_urlencoded = '';
								if (count($params) > 0) {
									foreach ($params as $name => $param) {
										if (!$param->isOptional()) {
											$sample_params_urlencoded .= '<b class="param">' . $name . '</b>=';
											$sample_params_urlencoded .= urldecode($param->getSampleValue());
										}
									}

									if (!empty($sample_params_urlencoded)) {
										$sample_params_urlencoded = '&amp;' . $sample_params_urlencoded;
									}
								}
								?>
								<dt>
								<code>
									<?php echo $method; ?>
									<?php
									if ($method == 'GET') {
										?>
										<a href="?call=<?php echo $call . strip_tags($sample_params_urlencoded) ?>"><?php echo UserConfig::$USERSROOTFULLURL ?>/api.php?call=<b class="call"><?php echo $call ?></b><?php echo $sample_params_urlencoded ?></a>
										<?php
									} else {
										echo UserConfig::$USERSROOTFULLURL;
										?>/api.php?call=<b class="call"><?php echo $call; ?></b>
										<?php
									}
									?>
								</code>
								</dt>
								<dd>
									<?php echo $endpoint->getDescription(); ?>
									<?php
									if (count($params) > 0) {
										?>
										<h4>Parameters:</h4>
										<dl>
											<?php
											foreach ($params as $name => $param) {
												?>
												<dt>
												<code><?php echo $name; ?></code>
												<?php
												if ($param->isOptional()) {
													?> (optional)<?php
												} else {
													?> (required)<?php
												}
												?>
												</dt>
												<dd>
													<?php
													echo $param->getDescription();

													if ($param->allowsMultipleValues()) {
														?>
														(allows multiple values)
														<?php
													}
													?>
												</dd>
												<?php
											}
											?>
										</dl>
										<?php
									}
									?>
								</dd>
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
$params = API\Endpoint::parseURLEncoded($query);


$raw_request_body = file_get_contents('php://input');

if (!empty($raw_request_body) && strtolower($_SERVER['CONTENT_TYPE']) == 'application/x-www-form-urlencoded') {
	$params = API\Endpoint::parseURLEncoded($raw_request_body, $params);
}

unset($params['call']); // except for the call parameter

header('Content-type: application/json');

try {
	$endpoint = API\Endpoint::getEndpoint($_SERVER['REQUEST_METHOD'], $_GET['call']);

	// default output format is JSON
	$response = array(
		'meta' => array(
			'success' => true,
			'params' => $params
		),
		'result' => $endpoint->call($params, $raw_request_body)
	);
} catch (Exceptions\API\NotFoundException $ex) {
	header('HTTP/1.0 404 Not Found');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (Exceptions\API\MethodNotAllowedException $ex) {
	header('HTTP/1.0 405 Method not allowed');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (Exceptions\API\UnauthenticatedException $ex) {
	header('HTTP/1.0 401 Authentication Required');
	header('WWW-Authenticate: FormBased');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (Exceptions\API\UnauthorizedException $ex) {
	header('HTTP/1.0 403 Forbidden');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (Exceptions\API\BadParameterException $ex) {
	header('HTTP/1.0 400 Bad Parameter');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (Exceptions\API\APIException $ex) {
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
