<?php
require_once(dirname(dirname(dirname(__FILE__))).'/global.php');

require_once(dirname(dirname(dirname(__FILE__))).'/User.php');

UsernamePasswordAuthenticationModule::$IGNORE_PASSWORD_RESET = true;

$user = User::get();

$errors = array();

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
		$errors = $ex->getErrors();
	}
}

require_once(UserConfig::$header);

?>
<div id="startupapi-passwordreset">
<h2>Password reset</h2>

<form id="startupapi-passwordreset-form" action="" method="POST">
<fieldset>
<legend>Please enter new password</legend>
<ul>
<li><label>New password</label><input name="pass" type="password" size="25" autocomplete="off"/><?php echo array_key_eXists('pass', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape($errors['pass']).'">*</span>' : ''?></li>
<li><label>Repeat new password</label><input name="repeatpass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('repeatpass', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape($errors['repeatpass']).'">*</span>' : ''?></li>
<li><button id="startupapi-passwordreset-button" type="submit" name="save">Save changes</button></li>
</ul>
</fieldset>
</form>

</div>

<?php
require_once(UserConfig::$footer);
