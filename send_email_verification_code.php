<?php
require_once(dirname(__FILE__) . '/global.php');

require_once(dirname(__FILE__) . '/classes/User.php');

UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION = true;

$user = User::require_login();

$email = $user->getEmail();

if (!is_null($email)) {
	$user->sendEmailVerificationCode();
}

require_once(UserConfig::$header);
?>
<div class="container-fluid" style="margin-top: 1em">
	<div class="row-fluid">
		<div class="span12">
			<?php
			if (is_null($email)) {
				?>
				<div id="startupapi-sendverificationcode-failure">
					<h3>No email address provided</h3>
					<p>You don't have an email set for your profile.</p>
					<a class="btn btn-primary" href="<?php echo UserConfig::$USERSROOTURL ?>/edit.php">Click here to add email address.</a>
				</div>
			<?php } else {
				?>
				<div id="startupapi-sendverificationcode-success">
					<h3>Verification code sent</h3>
					<p>Verification code was sent to <span class="startupapi-email-to-verify"><?php echo UserTools::escape($user->getEmail()) ?></span>.</p>
					<a class="btn" href="<?php echo UserConfig::$USERSROOTURL ?>/verify_email.php">Click here when you receive it</a>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>
<?php
require_once(UserConfig::$footer);
