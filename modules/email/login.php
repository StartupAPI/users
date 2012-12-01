<?php
require_once(dirname(dirname(dirname(__FILE__))).'/global.php');

require_once(dirname(dirname(dirname(__FILE__))).'/User.php');

$errors = array();

if (array_key_exists('code', $_GET) && array_key_exists('email', $_GET))
{
	$module = StartupAPIModule::get('email');

	try
	{
		$user = $module->processLoginLink($_GET['email'], $_GET['code']);

		if (!is_null($user))
		{
			$return = User::getReturn();
			User::clearReturn();
			if (!is_null($return))
			{
				header('Location: '.$return);
			}
			else
			{
				header('Location: '.UserConfig::$DEFAULTLOGINRETURN);
			}

			exit;
		} else {
			throw new InputValidationException('Invalid code', 0, array(
				'code' => array('Invalid code')
			));
		}
	}
	catch(InputValidationException $ex)
	{
		$errors = $ex->getErrors();
	}
}

require_once(UserConfig::$header);

?>
<div id="startupapi-loginlink">
<p>Confirmation code was sent to <?php echo array_key_exists('email', $_GET) ? UserTools::escape($_GET['email']) : 'your email address' ?> please enter it below and click login button.</p>
<?php
if (count($errors) > 0)
{
	?><div class="startupapi-errorbox"><ul><?php
	foreach ($errors as $field => $errorset)
	{
		foreach ($errorset as $error)
		{
			?><li><?php echo $error?></li><?php
		}
	}
	?></ul></div><?php
}
?>
<form id="startupapi-loginlink-form" action="" method="GET">
<fieldset>
<legend>Enter confirmation code to login</legend>
<ul>
<li><label>Email</label><input name="email" type="text" size="40"<?php if (array_key_exists('email', $_GET)) {?> value="<?php echo UserTools::escape($_GET['email'])?>"<?php }?>/><?php echo array_key_exists('email', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape($errors['email']).'">*</span>' : ''?></li>
<li><label>Code</label><input name="code" type="text" size="40" autocomplete="off"/><?php echo array_key_exists('code', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape(implode('; ', $errors['code'])).'">*</span>' : ''?></li>
<li><button id="startupapi-loginlink-button" type="submit" name="login">Login</button></li>
</ul>
</fieldset>
</form>

</div>

<?php
require_once(UserConfig::$footer);
