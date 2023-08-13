<?php
require_once(__DIR__ . '/global.php');

if (!array_key_exists('call', $_GET) && array_key_exists('swagger-spec', $_GET)) {
	$openapi_spec = array(
		'openapi' => '3.1.0',
		'info' => array(
			'contact' => array(
				'email' => UserConfig::$supportEmailFromEmail
			),
			'version' => UserConfig::$apiSpecVersion
		),
		'servers' => array(
			array(
				"url" => UserConfig::$USERSROOTURL . '/api.php'
			)
		)
	);

	if (UserConfig::$appName) {
		$openapi_spec['info']['title'] = UserConfig::$appName . " API";
	}

	if (UserConfig::$termsOfServiceFullURL) {
		$openapi_spec['info']['termsOfService'] = UserConfig::$termsOfServiceFullURL;
	}

	// Swagger tags, e.g. API namespaces
	foreach (\StartupAPI\API\Endpoint::getNamespaces() as $namespace) {
		$openapi_spec['tags'][] = array(
			'name' => $namespace->getSlug(),
			'description' => $namespace->getName()
		);
	}

	// Swagger paths, e.g. API Endpoints groupped by path
	$all_endpoints = \StartupAPI\API\Endpoint::getAllEndpointsBySlug();
	foreach ($all_endpoints as $namespace_slug => $namespace_endpoints) {
		foreach ($namespace_endpoints as $endpoint_slug => $endpoints) {
			foreach ($endpoints as $method => $endpoint) {
				$operation = array(
					'tags' => array(
						$namespace_slug
					),
					'summary' => $endpoint->getDescription(),
					'description' => $endpoint->getDescription(),
					'operationId' => get_class($endpoint),
					'responses' => array(
						'200' => array(
							'description' => 'success',
							'content' => array(
								'application/json' => array(
									'schema' => array(
										'type' => 'object'
									)
								)
							)
						),
						'400' => array(
							'description' => 'invalid input'
						)
					),
				);

				$request_body_properties = array();
				$query_parameters = array();

				$params = $endpoint->getParams();
				foreach ($params as $name => $param) {
					$property = array(
						'name' => $name,
						'description' => $param->getDescription(),
						'required' => !$param->isOptional()
					);

					if ($method === 'GET') {
						$property['in'] = 'query';
						$property['schema'] = array(
							'type' => $param->getType()
						);
						$query_parameters[] = $property;
					} else {
						if ($param->allowsMultipleValues()) {
							$property['type'] = 'array';
							// $param_spec['collectionFormat'] = 'multi';
						} else {
							$property['type'] = $param->getType();
						}
						$request_body_properties[$name] = $property;
					}
				}

				if ($method === 'GET') {
					$operation["parameters"] = $query_parameters;
				} else {
					$operation["requestBody"] = array (
						'content' => array(
							"application/x-www-form-urlencoded" => array(
								'schema' => array(
									"type" => "object",
									"properties" => $request_body_properties
								)
							)
						)
					);
				}

				$openapi_spec['paths']
					["/api.php?call=/$namespace_slug$endpoint_slug"]
						[strtolower($method)] = $operation;
			}
		}
	}

	header('Content-type: application/json');
	echo json_encode($openapi_spec);

	if (json_last_error() !== JSON_ERROR_NONE) {
		header('HTTP/1.1 400 Bad Request');
		header('Content-type: text/plain');
		echo "Error encoding JSON result";
	}

	exit;
}

if (!array_key_exists('call', $_GET)) {
	$template_info = StartupAPI::getTemplateInfo();
	StartupAPI::$template->display('@startupapi/swagger-ui.html.twig', $template_info);
	exit;
}

// parameters come from query string
$query = $_SERVER['QUERY_STRING'];
$params = \StartupAPI\API\Endpoint::parseURLEncoded($query);


$raw_request_body = file_get_contents('php://input');

// ignore encoding header when comparing format
// TODO: might need to test how it behaves with multibyte payload
if (array_key_exists('CONTENT_TYPE', $_SERVER)) {
	$content_type = explode(';', $_SERVER['CONTENT_TYPE'])[0];

	if (!empty($raw_request_body) && strtolower($content_type) == 'application/x-www-form-urlencoded') {
		$params = \StartupAPI\API\Endpoint::parseURLEncoded($raw_request_body, $params);
	}
}

unset($params['call']); // except for the call parameter

header('Content-type: application/json');

try {
	if ($_SERVER['REQUEST_METHOD'] !== "GET") {
		$found_CSRF_header = false;
		foreach (getallheaders() as $header => $value) {
			if (strtolower($header) === 'x-csrf-token') {
				$found_CSRF_header = true;
			}
		}

		if (!$found_CSRF_header) {
			throw new \StartupAPI\API\UnauthenticatedException("Missing X-CSRF-token header");
		}
	}

	$endpoint = \StartupAPI\API\Endpoint::getEndpoint($_SERVER['REQUEST_METHOD'], $_GET['call']);

	// default output format is JSON
	$response = array(
		'meta' => array(
			'success' => true,
			'params' => $params
		),
		'result' => $endpoint->call($params, $raw_request_body)
	);
} catch (\StartupAPI\API\NotFoundException $ex) {
	header('HTTP/1.1 404 Not Found');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (\StartupAPI\API\MethodNotAllowedException $ex) {
	header('HTTP/1.1 405 Method not allowed');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (\StartupAPI\API\UnauthenticatedException $ex) {
	header('HTTP/1.1 401 Authentication Required');
	header('WWW-Authenticate: FormBased');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (\StartupAPI\API\UnauthorizedException $ex) {
	header('HTTP/1.1 403 Forbidden');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (\StartupAPI\API\BadParameterException $ex) {
	header('HTTP/1.1 400 Bad Parameter');
	$response = array(
		'meta' => array(
			'success' => false,
			'error' => $ex->getMessage(),
			'error_code' => $ex->getCode()
		)
	);
} catch (\StartupAPI\API\APIException $ex) {
	header('HTTP/1.1 500 Server Error');
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
