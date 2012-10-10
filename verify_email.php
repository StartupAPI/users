<?php
require_once(dirname(__FILE__).'/global.php');

require_once(dirname(__FILE__).'/User.php');

UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION = true;

$user = User::get();
$email = is_null($user) ? null : $user->getEmail();

$errors = array();

$verification_complete = false;

if (array_key_exists('code', $_GET))
{
	try
	{
		if (User::verifyEmailLinkCode($_GET['code'], $user))
		{
			$verification_complete = true;
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

if ($verification_complete) {
	$return = User::getReturn();
	User::clearReturn();
	if (is_null($return))
	{
		$return = UserConfig::$DEFAULT_EMAIL_VERIFIED_RETURN;
	}
	?>
<div id="startupapi-verifyemail-success">
	<h3>Thank you for verifying your email address</h3>
	<p>You successfully verified your email address.</p>
	<a href="<?php echo UserTools::escape($return)?>">Click here to continue.</a>
</div>
<?php
} else {
?>
<div id="startupapi-verifyemail">
<h3>Please verify your email address</h3>
<p>Confirmation code was sent to <?php echo !is_null($email) ? '<span class="startupapi-email-to-verify">'.UserTools::escape($email).'</span>' : 'your email address' ?><br/>Please enter it below and click verify button.</p>
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
<form id="startupapi-verifyemail-form" action="" method="GET">
<fieldset>
<legend>Enter confirmation code to verify your email address</legend>
<label>Code</label><input name="code" type="text" size="40" autocomplete="off"/><?php echo array_key_exists('code', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape(implode('; ', $errors['code'])).'">*</span>' : ''?>
<button id="startupapi-verifyemail-button" type="submit">Verify</button>
<a id="startupapi-verifyemail-resend" href="<?php echo UserConfig::$USERSROOTURL ?>/send_email_verification_code.php">I never got the code, please resend</a>
</fieldset>
</form>

</div>
<?php
}

require_once(UserConfig::$footer);
