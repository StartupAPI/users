<?php
require_once(dirname(__FILE__).'/admin.php');

$user = User::require_login();

if (!$user->isAdmin()) {
	require_once(dirname(__FILE__).'/admin_access_only.php');
	exit;
}

require_once(dirname(dirname(__FILE__)).'/Invitation.php');

if (array_key_exists('save', $_POST))
{
	foreach ($_POST as $key => $value)
	{
		if (strpos($key, 'code_') === 0 && trim($value) != '')
		{
			$invitation = Invitation::getByCode(substr($key, 5));
			$invitation->setComment($value);
			$invitation->save();
		}
	}

	header("Location: #message=saved");
	exit;
}

if (array_key_exists('add', $_POST) && is_numeric($_POST['add']))
{
	$howmany = (int)$_POST['add'];

	if ($howmany > 0)
	{
		Invitation::generate($howmany);
	}

	header("Location: #message=added");
	exit;
}

$_styles = array (
	'http://yui.yahooapis.com/2.7.0/build/button/assets/skins/sam/button.css',
	'http://yui.yahooapis.com/2.7.0/build/container/assets/skins/sam/container.css'
);
$_scripts = array ( 
	'http://yui.yahooapis.com/2.7.0/build/yahoo-dom-event/yahoo-dom-event.js',
	'http://yui.yahooapis.com/2.7.0/build/animation/animation-min.js',
	'http://yui.yahooapis.com/2.7.0/build/connection/connection-min.js',
	'http://yui.yahooapis.com/2.7.0/build/element/element-min.js',
	'http://yui.yahooapis.com/2.7.0/build/button/button-min.js',
	'http://yui.yahooapis.com/2.7.0/build/dragdrop/dragdrop-min.js',
	'http://yui.yahooapis.com/2.7.0/build/container/container-min.js'
);

require_once(UserConfig::$header);
?>
<script>
YAHOO.util.Event.onDOMReady(function() {
	showMessages({
			added: { 'class': 'success', 'text': 'Invitations added'},
			saved: { 'class': 'success', 'text': 'Invitation comments saved'},
	});
});
</script>

<h2><a href="./">Users</a> | Invitations</h2><div style="background: white; padding: 1em">
<h2>Unsent Invitations</h2>
<?php
$invitations = Invitation::getUnsent();

if (count($invitations) == 0)
{
	?><div style="border: 1px dotted silver; text-align: center; padding: 2em">
<form action="" method="POST">
Generate <input name="add" size="4" value="5"> more <input type="submit" value="&gt;&gt;">
	<?php UserTools::renderCSRFNonce(); ?>
</form>
</div>
<?php
}
else
{
?>
<form action="" method="POST">
<table cellpadding="5" cellspacing="0" border="1" width="100%">
<tr><th>Code</th><th>By</th><th>Sent To</th><?php if (!is_null(UserConfig::$onRenderUserInvitationAction)) {?><th>Actions</th><?php }?></tr>
<?php
	foreach ($invitations as $invitation)
	{
		$code = $invitation->getCode();
		?><tr>
		<td><?php echo UserTools::escape($code)?></td>
		<td><?php // echo UserTools::escape(User::getUser($invitation->getIssuer())->getUserName())?></td>
		<td><input name="code_<?php echo UserTools::escape($invitation->getCode())?>" value="" style="width: 100%"></td><?php

		if (!is_null(UserConfig::$onRenderUserInvitationAction))
		{
			?><td><?php
			error_log(UserConfig::$onRenderUserInvitationAction.'($code);');
			eval(UserConfig::$onRenderUserInvitationAction.'($code);');
			?></td><?php
		}

		?></tr><?php
	}
?>
<tr><td colspan="2"></td>
<td><input type="submit" name="save" value="save &gt;&gt;" style="float: right"></td>
<td></td></tr>
</table>
	<?php UserTools::renderCSRFNonce(); ?>
</form>
<?php
}

$invitations = Invitation::getSent();

if (count($invitations) > 0)
{
?>
<h2>Sent Invitations</h2>
<table cellpadding="5" cellspacing="0" border="1" width="100%">
<tr><th>Code</th><th>By</th><th>Sent To</th><?php if (!is_null(UserConfig::$onRenderUserInvitationFollowUpAction)) {?><th>Actions</th><?php }?></tr>
<?php
	foreach ($invitations as $invitation)
	{
		$code = $invitation->getCode();
		$comment = $invitation->getComment();

		?><tr>
		<td><?php echo UserTools::escape($code)?></td>
		<td><?php // echo UserTools::escape(User::getUser($invitation->getIssuer())->getUserName())?></td>
		<td><?php echo UserTools::escape($comment)?></td><?php

		if (!is_null(UserConfig::$onRenderUserInvitationFollowUpAction))
		{
			?><td><?php
			eval(UserConfig::$onRenderUserInvitationFollowUpAction.'($code, $comment);');
			?></td><?php
		}

		?></tr><?php
	}
}
?>
</table>

<?php
$invitations = Invitation::getAccepted();

if (count($invitations) > 0)
{
?>
<h2>Accepted Invitations</h2>
<table cellpadding="5" cellspacing="0" border="1" width="100%">
<tr><th>Code</th><th>By</th><th>Sent To</th><th>User</th></tr>
<?php
	foreach ($invitations as $invitation)
	{
		?><tr>
		<td><?php echo UserTools::escape($invitation->getCode())?></td>
		<td><?php // echo UserTools::escape(User::getUser($invitation->getIssuer())->getUserName())?></td>
		<td><?php echo UserTools::escape($invitation->getComment())?></td>
		<td><?php echo UserTools::escape($invitation->getUser()->getUserName())?></td>
		</tr><?php
	}
}
?>
</table>

</div><?php
require_once(UserConfig::$footer);
