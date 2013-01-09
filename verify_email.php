<?php
require_once(dirname(__FILE__) . '/global.php');

require_once(dirname(__FILE__) . '/classes/User.php');

UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION = true;

$user = User::get();
$email = is_null($user) ? null : $user->getEmail();

$errors = array();

$verification_complete = false;

if (array_key_exists('code', $_GET)) {
	try {
		if (User::verifyEmailLinkCode($_GET['code'], $user)) {
			$verification_complete = true;
		} else {
			throw new InputValidationException('Invalid code', 0, array(
				'code' => array('Invalid code')
			));
		}
	} catch (InputValidationException $ex) {
		$errors = $ex->getErrors();
	}
}

require_once(UserConfig::$header);
?>
<div class="container-fluid" style="margin-top: 1em">
	<div class="row-fluid">
		<div class="span12">
			<?php
			if ($verification_complete) {
				$return = User::getReturn();
				User::clearReturn();
				if (is_null($return)) {
					$return = UserConfig::$DEFAULT_EMAIL_VERIFIED_RETURN;
				}
				?>
				<div id="startupapi-verifyemail-success">
					<h3>Thank you for verifying your email address</h3>
					<p>You successfully verified your email address.</p>
					<a class="btn btn-primary" href="<?php echo UserTools::escape($return) ?>">Click here to continue.</a>
				</div>
				<?php
			} else {
				?>
				<div>
					<?php
					if (count($errors) > 0) {
						?>
						<div class="alert alert-block alert-error fade-in">
							<h4 style="margin-bottom: 0.5em">Snap!</h4>
							<ul>
								<?php
								foreach ($errors as $field => $errorset) {
									foreach ($errorset as $error) {
										?>
										<li>
											<label  style="cursor: pointer" for="startupapi-code">
												<?php echo $error ?>
											</label>
										</li>
										<?php
									}
									?>
								</ul>
							</div>
							<?php
						}
					}
					?>
					<h3>Please verify your email address</h3>
					<p>Confirmation code was sent to <?php echo!is_null($email) ? '<span class="startupapi-email-to-verify">' . UserTools::escape($email) . '</span>' : 'your email address' ?><br/>Please enter it below and click verify button.</p>

					<form class="form well" action="" method="GET">
						<fieldset>
							<div class="control-group<?php if (array_key_exists('code', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['code'])) ?><?php } ?>">
								<label class="control-label" for="startupapi-code">Confirmation Code</label>
								<div class="controls">
									<input id="startupapi-code" name="code" type="text" autocomplete="off"/>
									<a id="startupapi-verifyemail-resend" href="<?php echo UserConfig::$USERSROOTURL ?>/send_email_verification_code.php">I never got the code, please resend</a>
								</div>
							</div>
							<div class="control-group">
								<div class="controls">
									<button class="btn btn-primary" type="submit">Verify</button>
								</div>
							</div>
						</fieldset>
					</form>

				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>
<?php
require_once(UserConfig::$footer);
