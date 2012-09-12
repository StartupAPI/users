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
		throw new Exception('Wrong module specified');
	}

	$invitation = null;

	if (UserConfig::$enableInvitations)
	{
		if (!array_key_exists('invite', $_GET))
		{
			throw new Exception('Invitation code is not submitted');
		}

		$invitation = Invitation::getByCode($_GET['invite']);

		if (is_null($invitation) || $invitation->getStatus())
		{
			throw new Exception('Invitation code is invalid');
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
<style>
.userbase-errorbox {
	background: #f7dfb9;
	font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
	padding: 0.4em 1em;
	margin: 1em 0;
	width: 465px;
	border: 4px solid #f77;
	border-radius: 7px;
	-moz-border-radius: 7px;
	-webkit-border-radius: 7px;
	font-size: 1.2em;
	color: #500;
	font-weight: bold;
}

.userbase-errorbox ul {
	margin: 0;
	padding-left: 1em;
}

#userbase-authlist {
	font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
	padding: 0 1em;
	margin: 0 auto;
	width: 480px;
}

#userbase-authlist h2 {
	font-weight: bold;
	font-size: 2.5em;
}
#userbase-authlist h3 {
	font-weight: bold;
	font-size: 1.5em;
}
</style>

<div id="userbase-authlist">
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
			<style>
			#userbase-invitation-form {
				font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
				padding: 0.4em 1em;
				margin: 0;
				width: 382px;
				border: 4px solid #ccc;
				border-radius: 7px;
				-moz-border-radius: 7px;
				-webkit-border-radius: 7px;
			}

			#userbase-invitation-form p {
				font-size: 1.2em;
				line-height: 1.5;

				clear: both;
				margin: 0 0 .75em;
				padding: 0;
			}

			#userbase-invitation-form fieldset {
				border: 0;
				padding: 0;
				margin: 0;
			}

			#userbase-invitation-form legend {
				border: 0;
				padding: 0;
				margin: 0;
				font-size: 1.8em;
				line-height: 1.8;
				padding-bottom: .6em;
			}

			#userbase-invitation-button {
				padding: 0.3em 25px;
				cursor: pointer;
			}

			#userbase-invitation-form input {
				background: #f6f6f6;
				border: 2px solid #888;
				border-radius: 2px;
				-moz-border-radius: 2px;
				-webkit-border-radius: 2px;
				padding: 4px;
			}

			#userbase-invitation-form input:focus {
				background: #fff;
			}

			#userbase-invitation-form abbr {
				cursor: help;
				font-style: normal;
				border: 0;
				color: red;
				font-size: 1.2em;
				font-weight: bold;
			}

			#userbase-invite-code {
				width: 290px;
			}
			</style>

			<form id="userbase-invitation-form" action="" method="GET">
			<fieldset>
			<legend><?php echo $message?></legend>
			<p>
			<input id="userbase-invite-code" name="invite" size="30" value="<?php echo UserTools::escape(array_key_exists('invite', $_GET) ? $_GET['invite'] : '')?>"/>
			<button id="userbase-invitation-button" type="submit">&gt;&gt;</button>
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
				?><div class="userbase-errorbox"><ul><?php
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
