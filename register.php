<?php
require_once(dirname(__FILE__).'/global.php');

require_once(dirname(__FILE__).'/User.php');
require_once(dirname(__FILE__).'/Invitation.php');

$errors = array();

$user_exists = false;

if (UserConfig::$enableRegistration && array_key_exists('register', $_POST))
{
	$module = AuthenticationModule::get($_GET['module']);

	if (is_null($module))
	{
		throw new StartupAPIException('Wrong module specified');
	}

	$invitation = null;

	if (UserConfig::$enableInvitations)
	{
		if (!array_key_exists('invite', $_GET))
		{
			throw new StartupAPIException('Invitation code is not submitted');
		}

		$invitation = Invitation::getByCode($_GET['invite']);

		if (is_null($invitation) || $invitation->getStatus())
		{
			throw new StartupAPIException('Invitation code is invalid');
		}
	}

	try
	{
		$remember = false;
		$user = $module->processRegistration($_POST, $remember);

		if (is_null($user))
		{
			header('Location: '.UserConfig::$USERSROOTURL.'/register.php?module='.$_GET['module'].'&error=failed');
			exit;
		}

		if (!is_null($invitation))
		{
			$invitation->setUser($user);
			$invitation->save();
		}

		$user->setSession($remember);

		$return = User::getReturn();
		User::clearReturn();
		if (!is_null($return))
		{
			header('Location: '.$return);
		}
		else
		{
			header('Location: '.UserConfig::$DEFAULTREGISTERRETURN);
		}

		exit;
	}
	catch(InputValidationException $ex)
	{
		$errors[$module->getID()] = $ex->getErrors();
	}
	catch(ExistingUserException $ex)
	{
		$user_exists = true;
		$errors[$module->getID()] = $ex->getErrors();
	}
}

require_once(UserConfig::$header);

?>
<div id="startupapi-authlist">
<h2>Sign up</h2>
<?php

if (UserConfig::$enableRegistration)
{
	$show_registration_form = true;
	$invitation_used = null;

	if (UserConfig::$enableInvitations)
	{
		$message = UserConfig::$invitationRequiredMessage;

		$show_registration_form = false;

		if (array_key_exists('invite', $_GET))
		{
			$invitation = Invitation::getByCode($_GET['invite']);

			if (is_null($invitation) || $invitation->getStatus())
			{
				$message = 'Invitation code you entered is not valid';
			}
			else
			{
				$invitation_used = $invitation;

				$show_registration_form = true;
			}
		}

		if (!$show_registration_form) {
			?>
			<form id="startupapi-invitation-form" action="" method="GET">
			<fieldset>
			<legend><?php echo $message?></legend>
			<p>
			<input id="startupapi-invite-code" name="invite" size="30" value="<?php echo UserTools::escape(array_key_exists('invite', $_GET) ? $_GET['invite'] : '')?>"/>
			<button id="startupapi-invitation-button" type="submit">&gt;&gt;</button>
			</p>
			</fieldset>
			</form>
			<?php
		}
	}
	
	if ($show_registration_form)
	{
		foreach (UserConfig::$authentication_modules as $module)
		{
			$id = $module->getID();

			?>
			<div style="margin-bottom: 2em">
			<h3 name="<?php echo $id?>"><?php echo $module->getTitle()?></h3>
		<?php
			if (array_key_exists($id, $errors) && is_array($errors[$id]) && count($errors[$id]) > 0)
			{
				?><div class="startupapi-errorbox"><ul><?php
				foreach ($errors[$id] as $field => $errorset)
				{
					foreach ($errorset as $error)
					{
						?><li><?php echo $error?></li><?php
					}
				}
				?></ul></div><?php
			}

			$module->renderRegistrationForm(true, "?module=$id&invite=".(is_null($invitation_used) ? '' : $invitation_used->getCode()), array_key_exists($id, $errors) ? $errors[$id] : array(), $_POST);
			?></div>
<?php
		}
	}
}
else
{
?>
	<p><?php echo UserConfig::$registrationDisabledMessage?></p>

	<p>If you already have an account, you can <a href="<?php echo UserConfig::$USERSROOTURL?>/login.php">log in here</a>.</p>
<?php
}
?></div><?php
require_once(UserConfig::$footer);
