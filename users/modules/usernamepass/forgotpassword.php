<?
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_once(dirname(dirname(dirname(__FILE__))).'/User.php');

if (array_key_exists('recover', $_POST))
{
	$users = User::getUsersByEmailOrUsername($_POST['emailorusername']);

	$subject = UserConfig::$passwordRecoveryEmailSubject;

	$headers = 'From: '.UserConfig::$supportEmailFrom."\r\n".
		'Reply-To: '.UserConfig::$supportEmailReplyTo."\r\n".
		'X-Mailer: '.UserConfig::$supportEmailXMailer;

	if (!is_null(UserConfig::$onRenderTemporaryPasswordEmail))
	{
		$baseurl = UserConfig::$USERSROOTFULLURL.'/login.php';

		foreach($users as $user)
		{
			$temppass = $user->generateTemporaryPassword();
			$tempass_enc = urlencode($temppass);

			$username = $user->getUsername();
			$name_enc = urlencode($username);

			$email = $user->getEmail();

			$message = '';
			eval('$message='.UserConfig::$onRenderTemporaryPasswordEmail.'($baseurl, $username, $temppass);');

			mail($email, $subject, $message, $headers);
		}

		// We always report "sent" to avoid information disclosure
		// e.g. letting hackers know which usernames and emails are available
		header('Location: '.UserConfig::$USERSROOTURL.'/modules/usernamepass/forgotpassword.php?status=sent');

		exit;
	}
	else
	{
		throw Exception('Can\'t render temporary password email, check if UserConfig::$onRenderTemporaryPasswordEmail is set');
	}
}

require_once(UserConfig::$header);

if (array_key_exists('status', $_GET) && $_GET['status'] == 'sent')
{
?>
	<h1>Temporary password is sent</h1>
	<p>We generated temporary password and sent it to email address on file with your account.</p>
	<p>This password is only valid for one day, so please check your mail and come back to <a href="<?=UserConfig::$USERSROOTURL.'/login.php'?>" style="font-weight: bold; text-decoration: underline">log in</a>.</p>
	<p>Once you're logged in, you will be asked to reset your password.</p>
<?
}
else
{
	?><h1>Forgot password?</h1>
	<div style="background: white; padding: 0 1em 0 1em">
	<form action="" method="POST">
	<p>Please enter your email or username:</p>
	<input type="text" name="emailorusername" value="" size="40"/>
	<input type="submit" name="recover" value="send"/>

	<p>Email with temporary password will be sent to email address associated with your user account.</p>
	</form>
	</div><?
}
require_once(UserConfig::$footer);
