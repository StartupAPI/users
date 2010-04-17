<?
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_once(dirname(dirname(dirname(__FILE__))).'/User.php');

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

?><h1>Please update your password</h1>

<form action="" method="POST">
<table>
<tr><td>New password</td><td><input name="pass" type="password" size="25" autocomplete="off"/><?=array_key_eXists('pass', $errors) ? ' <span style="color:red" title="'.htmlentities($errors['pass']).'">*</span>' : ''?></td></tr>
<tr><td>Repeat new password</td><td><input name="repeatpass" type="password" size="25" autocomplete="off"/><?=array_key_exists('repeatpass', $errors) ? ' <span style="color:red" title="'.htmlentities($errors['repeatpass']).'">*</span>' : ''?></td></tr>
<tr><td></td><td><input type="submit" name="save" value="Save &gt;&gt;&gt;"/></td></tr>
</table>
</form>
<?

require_once(UserConfig::$footer);
