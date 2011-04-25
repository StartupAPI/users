<?php
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

?>
<style>
#userbase-passwordreset {
	font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
	background: white;
	padding: 0 1em;
	margin: 0 auto;
	width: 400px;
}

#userbase-passwordreset h2 {
	font-weight: bold;
	font-size: 2.5em;
}

#userbase-passwordreset h3 {
	font-weight: bold;
	font-size: 1.5em;
}

#userbase-passwordreset-form {
	font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
	padding: 0.4em 1em;
	margin: 0;
	width: 400px;
	border: 4px solid #ccc;
	border-radius: 7px;
	-moz-border-radius: 7px;
	-webkit-border-radius: 7px;
}

#userbase-passwordreset-form li {
	font-size: 1.2em;
	line-height: 1.5;

	clear: both;
	margin: 0 0 .75em;
	padding: 0;
}

#userbase-passwordreset-form fieldset {
	border: 0;
	padding: 0;
	margin: 0;
}

#userbase-passwordreset-form legend {
	border: 0;
	padding: 0;
	margin: 0;
	font-size: 1.8em;
	line-height: 1.8;
	padding-bottom: .6em;
}

#userbase-passwordreset-form ul {
	list-style: none;
	margin: 0;
	padding: 0;
}

#userbase-passwordreset-form label {
	display: block;
	float: left;
	line-height: 1.6;
	margin-right: 10px;
	text-align: right;
	width: 160px;
	padding: 3px 0;
}

#userbase-passwordreset-form label:after {
	content: ':';
}

#userbase-passwordreset-button {
	margin-left: 170px;
	padding: 0.3em 25px;
	cursor: pointer;
}

#userbase-passwordreset-form input {
	background: #f6f6f6;
	border: 2px solid #888;
	border-radius: 2px;
	-moz-border-radius: 2px;
	-webkit-border-radius: 2px;
	padding: 4px;
}

#userbase-passwordreset-form input:focus {
	background: #fff;
}
</style>


<div id="userbase-passwordreset">
<h2>Password reset</h2>

<form id="userbase-passwordreset-form" action="" method="POST">
<fieldset>
<legend>Please enter new password</legend>
<ul>
<li><label>New password</label><input name="pass" type="password" size="25" autocomplete="off"/><?php echo array_key_eXists('pass', $errors) ? ' <span style="color:red" title="'.UserTools::escape($errors['pass']).'">*</span>' : ''?></li>
<li><label>Repeat new password</label><input name="repeatpass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('repeatpass', $errors) ? ' <span style="color:red" title="'.UserTools::escape($errors['repeatpass']).'">*</span>' : ''?></li>
<li><button id="userbase-passwordreset-button" type="submit" name="save">Save changes</button></li>
</ul>
</fieldset>
</form>

</div>

<?php
require_once(UserConfig::$footer);
