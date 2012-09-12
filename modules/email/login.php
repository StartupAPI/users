<?php
require_once(dirname(dirname(dirname(__FILE__))).'/global.php');

require_once(dirname(dirname(dirname(__FILE__))).'/User.php');

$errors = array();

if (array_key_exists('code', $_GET) && array_key_exists('email', $_GET))
{
	$module = UserBaseModule::get('email');

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
<style>
.userbase-errorbox {
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

.userbase-errorbox ul {
	margin: 0;
	padding-left: 1em;
}
#userbase-loginlink {
	font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
	padding: 0 1em;
	margin: 0 auto;
	width: 450px;
}

#userbase-loginlink h2 {
	font-weight: bold;
	font-size: 2.5em;
}

#userbase-loginlink h3 {
	font-weight: bold;
	font-size: 1.5em;
}

#userbase-loginlink-form {
	font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
	padding: 0.4em 1em;
	margin: 0;
	width: 450px;
	border: 4px solid #ccc;
	border-radius: 7px;
	-moz-border-radius: 7px;
	-webkit-border-radius: 7px;
}

#userbase-loginlink-form li {
	font-size: 1.2em;
	line-height: 1.5;

	clear: both;
	margin: 0 0 .75em;
	padding: 0;
}

#userbase-loginlink-form fieldset {
	border: 0;
	padding: 0;
	margin: 0;
}

#userbase-loginlink-form legend {
	border: 0;
	padding: 0;
	margin: 0;
	font-size: 1.8em;
	line-height: 1.8;
	padding-bottom: .6em;
}

#userbase-loginlink-form ul {
	list-style: none;
	margin: 0;
	padding: 0;
}

#userbase-loginlink-form label {
	display: block;
	float: left;
	line-height: 1.6;
	margin-right: 10px;
	text-align: right;
	width: 60px;
	padding: 3px 0;
}

#userbase-loginlink-form label:after {
	content: ':';
}

#userbase-loginlink-button {
	margin-left: 70px;
	padding: 0.3em 25px;
	cursor: pointer;
}

#userbase-loginlink-form input {
	background: #f6f6f6;
	border: 2px solid #888;
	border-radius: 2px;
	-moz-border-radius: 2px;
	-webkit-border-radius: 2px;
	padding: 4px;
}

#userbase-loginlink-form input:focus {
	background: #fff;
}
</style>


<div id="userbase-loginlink">
<p>Confirmation code was sent to <?php echo array_key_exists('email', $_GET) ? UserTools::escape($_GET['email']) : 'your email address' ?> please enter it below and click login button.</p>
<?php
if (count($errors) > 0)
{
	?><div class="userbase-errorbox"><ul><?php
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
<form id="userbase-loginlink-form" action="" method="GET">
<fieldset>
<legend>Enter confirmation code to login</legend>
<ul>
<li><label>Email</label><input name="email" type="text" size="40"<?php if (array_key_exists('email', $_GET)) {?> value="<?php echo UserTools::escape($_GET['email'])?>"<?php }?>/><?php echo array_key_exists('email', $errors) ? ' <span style="color:red" title="'.UserTools::escape($errors['email']).'">*</span>' : ''?></li>
<li><label>Code</label><input name="code" type="text" size="40" autocomplete="off"/><?php echo array_key_exists('code', $errors) ? ' <span style="color:red" title="'.UserTools::escape(implode('; ', $errors['code'])).'">*</span>' : ''?></li>
<li><button id="userbase-loginlink-button" type="submit" name="login">Login</button></li>
</ul>
</fieldset>
</form>

</div>

<?php
require_once(UserConfig::$footer);
