<?php
require_once(__DIR__ . '/global.php');

if (!array_key_exists('call', $_GET) && array_key_exists('swagger-spec', $_GET)) {
	$swagger_spec = array(
		'swagger' => '2.0',
		'info' => array(
			'contact' => array(
				'email' => UserConfig::$supportEmailFromEmail
			),
			'version' => UserConfig::$apiSpecVersion
		),
		'basePath' => UserConfig::$USERSROOTURL . '/api.php'
	);

	if (UserConfig::$appName) {
		$swagger_spec['info']['title'] = UserConfig::$appName;
	}

	if (UserConfig::$termsOfServiceFullURL) {
		$swagger_spec['info']['termsOfService'] = UserConfig::$termsOfServiceFullURL;
	}

	// Swagger tags, e.g. API namespaces
	foreach (\StartupAPI\API\Endpoint::getNamespaces() as $namespace) {
		$swagger_spec['tags'][] = array(
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
							'description' => 'success'
						),
						'400' => array(
							'description' => 'invalid input'
						)
					)
				);

				$params = $endpoint->getParams();

				foreach ($params as $name => $param) {
					$param_spec = array(
						'name' => $name,
						'description' => $param->getDescription(),
						'required' => !$param->isOptional()
					);

					if ($method === 'GET') {
						$param_spec['in'] = 'query';
					} else {
						$param_spec['in'] = 'formData';
					}

					if ($param->allowsMultipleValues()) {
						$param_spec['type'] = 'array';
						$param_spec['collectionFormat'] = 'multi';
					}
					$operation['parameters'][] = $param_spec;
				}

				$swagger_spec['paths']["/api.php?call=/$namespace_slug$endpoint_slug"] = array(
					strtolower($method) => $operation
				);
			}
		}
	}

	header('Content-type: application/json');
	echo json_encode($swagger_spec);

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

if (!empty($raw_request_body) && strtolower($_SERVER['CONTENT_TYPE']) == 'application/x-www-form-urlencoded') {
	$params = \StartupAPI\API\Endpoint::parseURLEncoded($raw_request_body, $params);
}

unset($params['call']); // except for the call parameter

header('Content-type: application/json');

try {
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
