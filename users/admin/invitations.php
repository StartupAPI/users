<?
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

require_once(dirname(dirname(__FILE__)).'/config.php');
require_once(dirname(dirname(__FILE__)).'/User.php');
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

<h1><a href="./">Users</a> | Invitations</h1><div style="background: white; padding: 1em">
<h2>Unsent Invitations</h2>
<?
$invitations = Invitation::getUnsent();

if (count($invitations) == 0)
{
	?><div style="border: 1px dotted silver; text-align: center; padding: 2em">
<form action="" method="POST">
Generate <input name="add" size="4" value="5"> more <input type="submit" value="&gt;&gt;">
</form>
</div>
<?
}
else
{
?>
<form action="" method="POST">
<table cellpadding="5" cellspacing="0" border="1" width="100%">
<tr><th>Code</th><th>By</th><th>Sent To</th><? if (!is_null(UserConfig::$onRenderUserInvitationAction)) {?><th>Actions</th><?}?></tr>
<?
	foreach ($invitations as $invitation)
	{
		$code = $invitation->getCode();
		?><tr><td><?=htmlentities($code)?></td><td><?=htmlentities(User::getUser($invitation->getIssuer())->getUserName())?></td><td><input name="code_<?=htmlentities($invitation->getCode())?>" value="" style="width: 100%"></td><?

		if (!is_null(UserConfig::$onRenderUserInvitationAction))
		{
			?><td><?
			error_log(UserConfig::$onRenderUserInvitationAction.'($code);');
			eval(UserConfig::$onRenderUserInvitationAction.'($code);');
			?></td><?
		}

		?></tr><?
	}
?>
<tr><td colspan="2"></td>
<td><input type="submit" name="save" value="save &gt;&gt;" style="float: right"></td>
<td></td></tr>
</table>
</form>
<?
}

$invitations = Invitation::getSent();

if (count($invitations) > 0)
{
?>
<h2>Sent Invitations</h2>
<table cellpadding="5" cellspacing="0" border="1" width="100%">
<tr><th>Code</th><th>By</th><th>Sent To</th><? if (!is_null(UserConfig::$onRenderUserInvitationFollowUpAction)) {?><th>Actions</th><?}?></tr>
<?
	foreach ($invitations as $invitation)
	{
		$code = $invitation->getCode();
		$comment = $invitation->getComment();

		?><tr><td><?=htmlentities($code)?></td><td><?=htmlentities(User::getUser($invitation->getIssuer())->getUserName())?></td><td><?=htmlentities($comment)?></td><?

		if (!is_null(UserConfig::$onRenderUserInvitationFollowUpAction))
		{
			?><td><?
			eval(UserConfig::$onRenderUserInvitationFollowUpAction.'($code, $comment);');
			?></td><?
		}

		?></tr><?
	}
}
?>
</table>

<?
$invitations = Invitation::getAccepted();

if (count($invitations) > 0)
{
?>
<h2>Accepted Invitations</h2>
<table cellpadding="5" cellspacing="0" border="1" width="100%">
<tr><th>Code</th><th>By</th><th>Sent To</th><th>User</th></tr>
<?
	foreach ($invitations as $invitation)
	{
		?><tr><td><?=htmlentities($invitation->getCode())?></td><td><?=htmlentities(User::getUser($invitation->getIssuer())->getUserName())?></td><td><?=htmlentities($invitation->getComment())?></td><td><?=htmlentities($invitation->getUser()->getUserName())?></td></tr><?
	}
}
?>
</table>

</div><?
require_once(UserConfig::$footer);
