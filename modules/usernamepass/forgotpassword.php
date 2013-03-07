<?php
require_once(dirname(dirname(__DIR__)) . '/global.php');

require_once(dirname(dirname(__DIR__)) . '/classes/User.php');

if (array_key_exists('recover', $_POST)) {
	$users = User::getUsersByEmailOrUsername(mb_convert_encoding($_POST['emailorusername'], 'UTF-8'));

	$subject = UserConfig::$passwordRecoveryEmailSubject;

	$headers = 'From: ' . UserConfig::$supportEmailFrom . "\r\n" .
			'Reply-To: ' . UserConfig::$supportEmailReplyTo . "\r\n" .
			'X-Mailer: ' . UserConfig::$supportEmailXMailer;

	if (!is_null(UserConfig::$onRenderTemporaryPasswordEmail)) {
		$baseurl = UserConfig::$USERSROOTFULLURL . '/login.php';

		foreach ($users as $user) {
			$temppass = $user->generateTemporaryPassword();
			$tempass_enc = urlencode($temppass);

			$username = $user->getUsername();
			$name_enc = urlencode($username);

			$email = $user->getEmail();

			$message = call_user_func_array(UserConfig::$onRenderTemporaryPasswordEmail, array($baseurl, $username, $temppass));

			mail($email, $subject, $message, $headers);
		}

		// We always report "sent" to avoid information disclosure
		// e.g. letting hackers know which usernames and emails are available
		header('Location: ' . UserConfig::$USERSROOTURL . '/modules/usernamepass/forgotpassword.php?status=sent');

		exit;
	} else {
		throw new StartupAPIException('Can\'t render temporary password email, check if UserConfig::$onRenderTemporaryPasswordEmail is set');
	}
}

require_once(UserConfig::$header);
?>
<div class="container-fluid" style="margin-top: 1em">
	<div class="row-fluid">
		<?php
		if (array_key_exists('status', $_GET) && $_GET['status'] == 'sent') {
			?>
			<div id="startupapi-passwordsent">
				<fieldset>
					<legend>Temporary password is sent</legend>
					<p>We generated temporary password and sent it to email address on file with your account.</p>
					<p>This password is only valid for one day, so please check your mail and come back to <a href="<?php echo UserConfig::$USERSROOTURL . '/login.php' ?>" style="font-weight: bold; text-decoration: underline">log in</a>.</p>
					<p>Once you're logged in, you will be asked to reset your password.</p>
				</fieldset>
			</div>
			<?php
		} else {
			?><h2>Forgot password?</h2>
			<form id="startupapi-forgotpassword-form" class="form-horizontal" action="" method="POST">
				<fieldset>
					<legend>Please enter your email or username</legend>
					<input type="text" name="emailorusername" value="" size="40"/>
					<button class="btn btn-primary" type="submit" name="recover">Send</button>
					<p>Email with temporary password will be sent to email address associated with your user account.</p>
				</fieldset>
			</form>
			<?php
		}
		?>
	</div>
</div>

<?php
require_once(UserConfig::$footer);
