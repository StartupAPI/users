<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/global.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/User.php');

$errors = array();

if (array_key_exists('code', $_GET) && array_key_exists('email', $_GET)) {
	$module = StartupAPIModule::get('email');

	try {
		$user = $module->processLoginLink($_GET['email'], $_GET['code']);

		if (!is_null($user)) {
			$return = User::getReturn();
			User::clearReturn();
			if (!is_null($return)) {
				header('Location: ' . $return);
			} else {
				header('Location: ' . UserConfig::$DEFAULTLOGINRETURN);
			}

			exit;
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

		<h2>Log in</h2>

		<p>Confirmation code was sent to <?php echo array_key_exists('email', $_GET) ? UserTools::escape($_GET['email']) : 'your email address' ?> please enter it below and click login button.</p>
		<?php
		$slug = 'email';

		if (is_array($errors) && count($errors) > 0) {
			?>
			<div class="alert alert-block alert-error fade-in">
				<h4 style="margin-bottom: 0.5em">Snap!</h4>

				<ul>
					<?php
					foreach ($errors as $field => $errorset) {
						foreach ($errorset as $error) {
							?>
							<li><label  style="cursor: pointer" for="startupapi-<?php echo $slug ?>-login-<?php echo $field ?>"><?php echo $error ?></label></li>
							<?php
						}
					}
					?>
				</ul>
			</div>
			<?php
		}
		?>
		<form class="form-horizontal" action="" method="GET">
			<fieldset>
				<legend>Enter confirmation code to login</legend>

				<div class="control-group<?php if (array_key_exists('email', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['email'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-<?php echo $slug ?>-login-email">Email</label>
					<div class="controls">
						<input class="input-xlarge" id="startupapi-<?php echo $slug ?>-login-email" name="email" type="email" placeholder="john@example.com"<?php if (array_key_exists('email', $_GET)) { ?> value="<?php echo UserTools::escape($_GET['email']) ?>"<?php } ?>/>
					</div>
				</div>

				<div class="control-group<?php if (array_key_exists('code', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['code'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-<?php echo $slug ?>-login-code">Code</label>
					<div class="controls">
						<input class="input-xlarge" id="startupapi-<?php echo $slug ?>-login-code" name="code" type="text"/>
					</div>
				</div>

				<div class="control-group">
					<div class="controls">
						<button class="btn btn-primary" type="submit" name="login">Login</button>
					</div>
				</div>
			</fieldset>
		</form>

	</div>
</div>

<?php
require_once(UserConfig::$footer);