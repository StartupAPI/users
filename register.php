<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/tools.php');

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

?><h2>Sign up</h2>
<style>
.errorbox {
	background: #f7dfb9;
	font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
	padding: 0.4em 1em;
	margin: 1em 0;
	width: 445px;
	border: 4px solid #f77;
	border-radius: 7px;
	-moz-border-radius: 7px;
	-webkit-border-radius: 7px;
	font-size: 1.2em;
	color: #500;
	font-weight: bold;
}

.errorbox ul {
	margin: 0;
	padding-left: 1em;
}
</style>

<div style="background: white; padding: 0 1em"><?php

if (UserConfig::$enableRegistration)
{
	$show_registration_form = true;
	$invitation_used = null;

	if (UserConfig::$enableInvitations)
	{
		if (array_key_exists('invite', $_GET))
		{
			$invitation = Invitation::getByCode($_GET['invite']);

			if (is_null($invitation) || $invitation->getStatus())
			{
				?><p>Invitation code you entered is not valid.</p>
				<form action="" method="GET">
				<input name="invite" size="10" value="<?php echo UserTools::escape($_GET['invite'])?>"/><input type="submit" value="&gt;&gt;"/>
				</form>
				<?php
				$show_registration_form = false;
			}
			else
			{
				$invitation_used = $invitation;
			}
		}
		else
		{
			?><p><?php echo UserConfig::$invitationRequiredMessage?></p>
			<form action="" method="GET">
			<input name="invite" size="10"/><input type="submit" value="&gt;&gt;"/>
			</form>
			<?php
			$show_registration_form = false;
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
				?><div class="errorbox"><ul><?php
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
