<?php
require_once(dirname(dirname(__DIR__)).'/global.php');

require_once(dirname(dirname(__DIR__)).'/classes/User.php');

UsernamePasswordAuthenticationModule::$IGNORE_PASSWORD_RESET = true;

$user = User::get();
$template_info = StartupAPI::getTemplateInfo();

if (array_key_exists('save', $_POST))
{
	$module = new UsernamePasswordAuthenticationModule();

	try
	{
		if ($module->processUpdatePassword($user, $_POST))
		{
			$return = User::getReturn();
			User::clearReturn();
			if (!is_null($return))
			{
				header('Location: '.$return);
			}
			else
			{
				header('Location: '.UserConfig::$DEFAULTUPDATEPASSWORDRETURN);
			}

			exit;
		}
	}
	catch(InputValidationException $ex)
	{
		$template_info['errors']['usernamepass'] = $ex->getErrors();
	}
}

$template_info['slug'] = 'usernamepass';

StartupAPI::$template->display('@startupapi/modules/usernamepass/passwordreset.html.twig', $template_info);