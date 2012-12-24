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
			$invitation->setIssuer($user);
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

foreach (array_keys($_POST) as $key) {
	if (strstr('cancel_', $key) == 0) {
		$code_to_delete = substr($key, strlen('cancel_'));

		Invitation::cancel($code_to_delete);

		header("Location: #message=cancelled");
		exit;
	}
}

$ADMIN_SECTION = 'invitations';

require_once(dirname(__FILE__) . '/header.php');
?>
<script>
$(document).ready(function() {
	var available_messages = {
			added: { 'class': 'success', 'text': 'Invitations added'},
			saved: { 'class': 'success', 'text': 'Invitation comments saved'}
	};
});
</script>

<div class="span9">
	<h2>Unsent Invitations</h2>
<?php
$invitations = Invitation::getUnsent();

if (count($invitations) == 0)
{
	?><div style="border: 1px dotted silver; text-align: center; padding: 2em">
<form class="form-horizontal" action="" method="POST">
	Generate more codes
	<div class="input-append">
	  <input type="text" name="add" value="5"><input type="submit" class="btn" value="Add"/>
	</div>
	<?php UserTools::renderCSRFNonce(); ?>
</form>
</div>
<?php
}
else
{
?>
<form action="" method="POST">
<table class="table">
<tr>
	<th>Code</th>
	<th>Sent To</th>
	<?php if (!is_null(UserConfig::$onRenderUserInvitationAction)) {?><th>Actions</th><?php }?>
</tr>
<?php
	foreach ($invitations as $invitation)
	{
		$code = $invitation->getCode();
		?><tr>
		<td><span class="badge badge-info"><?php echo UserTools::escape($code)?></span></td>
		<td><input name="code_<?php echo UserTools::escape($invitation->getCode())?>" value="" style="width: 100%"></td><?php

		if (!is_null(UserConfig::$onRenderUserInvitationAction))
		{
			?><td><?php
			call_user_func_array(UserConfig::$onRenderUserInvitationAction, array($code));
			?>
			</td><?php
		}

		?></tr><?php
	}
?>
<tr>
	<td></td>
	<td><input class="btn" type="submit" name="save" value="Save" style="float: right"></td>
	<td></td>
</tr>
</table>
	<?php UserTools::renderCSRFNonce(); ?>
</form>
<?php
}

$invitations = Invitation::getSent(true);

if (count($invitations) > 0)
{
?>
<form action="" method="POST">

<h2>Sent Invitations</h2>
<table class="table">
<tr><th>Code</th><th>Invited By</th><th>Sent To</th><?php if (!is_null(UserConfig::$onRenderUserInvitationFollowUpAction)) {?><th>Actions</th><?php }?></tr>
<?php
	foreach ($invitations as $invitation)
	{
		$code = $invitation->getCode();
		$issuer = $invitation->getIssuer();
		$comment = $invitation->getComment();

		?><tr>
		<td><span class="badge badge-important"><?php echo UserTools::escape($code)?></span></td>
		<td><?php echo UserTools::escape(is_null($issuer) ? '' : $issuer->getName()) ?></td>
		<td><?php echo UserTools::escape($comment)?></td><?php

		if (!is_null(UserConfig::$onRenderUserInvitationFollowUpAction))
		{
			?><td><?php
			call_user_func_array(UserConfig::$onRenderUserInvitationFollowUpAction,
				array($code, $comment)
			);
			?>
			<input class="btn btn-danger" type="submit" name="cancel_<?php echo $invitation->getCode(); ?>" onclick="return confirm('Are you sure you want to cancel invitation for <?php echo UserTools::escape($comment)?>?')" value="Cancel Invitation">
			</td><?php
		}

		?></tr><?php
	}
?>
</table>
	<?php UserTools::renderCSRFNonce(); ?>
</form>
<?php
}

$invitations = Invitation::getAccepted(true);

if (count($invitations) > 0)
{
?>
<h2>Accepted Invitations</h2>
<table class="table">
<tr><th>Code</th><th>Invited By</th><th>Sent To</th><th>User</th></tr>
<?php
	foreach ($invitations as $invitation)
	{
		$issuer = $invitation->getIssuer();
		$invited_user = $invitation->getUser();

		?><tr>
		<td><span class="badge badge-success"><?php echo UserTools::escape($invitation->getCode())?></span></td>
		<td><?php echo UserTools::escape(is_null($issuer) ? '' : $issuer->getName()) ?></td>
		<td><?php echo UserTools::escape($invitation->getComment())?></td>
		<td>
			<i class="icon-user"></i> <a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/user.php?id=<?php echo UserTools::escape($invited_user->getID())?>"><?php echo UserTools::escape($invited_user->getName())?></a>
		</td>
		</tr><?php
	}
}
?>
</table>

</div>


</div>
<?php
require_once(dirname(__FILE__) . '/footer.php');
