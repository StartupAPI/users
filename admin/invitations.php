<?php
require_once(__DIR__ . '/admin.php');

$user = User::require_login();

if (!$user->isAdmin()) {
	require_once(__DIR__ . '/admin_access_only.php');
	exit;
}

require_once(dirname(__DIR__) . '/classes/Invitation.php');

if (array_key_exists('save', $_POST)) {
	foreach (array_keys($_POST['email']) as $code) {
		$invitation = Invitation::getByCode($code);
		$need_to_save = false;

		if (trim($_POST['email'][$code]) != '') {
			$invitation->setSentToEmail($_POST['email'][$code]);
			$need_to_save = true;
		}

		if (trim($_POST['name'][$code]) != '') {
			$invitation->setSentToName($_POST['name'][$code]);
			$need_to_save = true;
		}

		if (trim($_POST['note'][$code]) != '') {
			$invitation->setNote($_POST['note'][$code]);
			$need_to_save = true;
		}

		if ($need_to_save) {
			$invitation->setIssuer($user);
			$invitation->save();
		}
	}

	header("Location: #message=saved");
	exit;
}

if (array_key_exists('add', $_POST) && is_numeric($_POST['add'])) {
	$howmany = (int) $_POST['add'];

	if ($howmany > 0) {
		Invitation::generateAdminInvites($howmany, true);
	}

	header("Location: #message=added");
	exit;
}

foreach (array_keys($_POST) as $key) {
	if (strstr('cancel_', $key) == 0) {
		$code_to_delete = substr($key, strlen('cancel_'));

		Invitation::cancelByCode($code_to_delete);

		header("Location: #message=cancelled");
		exit;
	}
}

$ADMIN_SECTION = 'invitations';

require_once(__DIR__ . '/header.php');
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

	if (count($invitations) == 0) {
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
	} else {
		?>
		<form class="form-inline" action="" method="POST">
			<table class="table">
				<tr>
					<th>Code</th>
					<th>Sent to</th>
					<?php if (!is_null(UserConfig::$onRenderUserInvitationAction)) { ?><th>Actions</th><?php } ?>
				</tr>

				<?php
				foreach ($invitations as $invitation) {
					$code = $invitation->getCode();
					?>
					<tr>
						<td>
							<span class="badge badge-info"><?php echo UserTools::escape($code) ?></span>
						</td>
						<td>
							<div class="controls controls-row">
								<input type="text" class="span4"
									   name="name[<?php echo UserTools::escape($code) ?>]"
									   id="name_<?php echo UserTools::escape($code) ?>"
									   placeholder="John Smith">
								<input type="email" class="span4"
									   name="email[<?php echo UserTools::escape($code) ?>]"
									   id="email_<?php echo UserTools::escape($code) ?>"
									   placeholder="john.smith@example.com">
								<input type="text" class="span4"
									   name="note[<?php echo UserTools::escape($code) ?>]"
									   id="note_<?php echo UserTools::escape($code) ?>"
									   placeholder="note">
							</div>
						</td>

						<?php
						if (!is_null(UserConfig::$onRenderUserInvitationAction)) {
							?>
							<td>
								<?php
								call_user_func_array(UserConfig::$onRenderUserInvitationAction, array($invitation));
								?>
							</td>
							<?php
						}
						?>
					</tr>
					<?php
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

	if (count($invitations) > 0) {
		?>
		<form action="" method="POST">

			<h2>Sent Invitations</h2>
			<table class="table">
				<tr>
					<th>Code</th>
					<th>Invited By</th>
					<th>Sent to</th>
					<?php if (!is_null(UserConfig::$onRenderUserInvitationFollowUpAction)) { ?>
						<th>Actions</th>
					<?php } ?>
				</tr>
				<?php
				foreach ($invitations as $invitation) {
					$issuer = $invitation->getIssuer();
					$email = trim($invitation->getSentToEmail());
					$note = trim($invitation->getNote());
					?><tr>
						<td><span class="badge badge-important"><?php echo UserTools::escape($invitation->getCode()) ?></span></td>
						<td>
							<?php echo UserTools::escape(is_null($issuer) ? '' : $issuer->getName()) ?>
						</td>
						<td>
							<b><?php echo UserTools::escape($invitation->getSentToName()) ?></b>
							<?php
							if ($email) {
								?>
								&lt;<a target="_blank" href="mailto:<?php echo UserTools::spaceencode($email) ?>"><?php echo UserTools::escape($email) ?></a>&gt;
								<?php
							}

							if ($note) {
								?>
								<p><?php echo UserTools::escape($note) ?></p>
								<?php
							}
							?>
						</td>
						<?php
						if (!is_null(UserConfig::$onRenderUserInvitationFollowUpAction)) {
							?>
							<td>
								<?php
								call_user_func_array(UserConfig::$onRenderUserInvitationFollowUpAction, array($invitation));
								?>
								<button class="btn btn-mini" type="submit" name="cancel_<?php echo $invitation->getCode(); ?>" onclick="return confirm('Are you sure you want to cancel this invitation?')"><i class="icon-remove"></i> Cancel Invitation</button>
							</td>
							<?php
						}
						?>
					</tr>
					<?php
				}
				?>
			</table>
			<?php UserTools::renderCSRFNonce(); ?>
		</form>
		<?php
	}

	$invitations = Invitation::getAccepted(true);

	if (count($invitations) > 0) {
		?>
		<h2>Accepted Invitations</h2>
		<table class="table">
			<tr>
				<th>Code</th>
				<th>Invited By</th>
				<th>Sent to</th>
				<th>Registered</th>
				<th>Registered User</th>
			</tr>
			<?php
			$now = time();
			foreach ($invitations as $invitation) {
				$issuer = $invitation->getIssuer();
				$invited_user = $invitation->getUser();
				$email = trim($invitation->getSentToEmail());
				$note = trim($invitation->getNote());

				$regtime = $invited_user->getRegTime();
				$ago = intval(floor(($now - $regtime) / 86400));

				$tz = date_default_timezone_get();
				?><tr>
					<td><span class="badge badge-success"><?php echo UserTools::escape($invitation->getCode()) ?></span></td>
					<td><?php echo UserTools::escape(is_null($issuer) ? '' : $issuer->getName()) ?></td>
					<td>
						<b><?php echo UserTools::escape($invitation->getSentToName()) ?></b>
						<?php
						if ($email) {
							?>
							&lt;<a target="_blank" href="mailto:<?php echo UserTools::spaceencode($email) ?>"><?php echo UserTools::escape($email) ?></a>&gt;
							<?php
						}

						if ($note) {
							?>
							<p><?php echo UserTools::escape($note) ?></p>
							<?php
						}
						?>
					</td>
					<td align="right">
						<?php echo date('M j Y, h:iA', $regtime) ?><br/>
						<span class="badge<?php if ($ago <= 5) { ?> badge-success<?php } ?>">
							<?php echo $ago ?>
						</span> day<?php echo $ago != 1 ? 's' : '' ?> ago
					</td>
					<td>
						<i class="icon-user"></i> <a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/user.php?id=<?php echo UserTools::escape($invited_user->getID()) ?>"><?php echo UserTools::escape($invited_user->getName()) ?></a>
					</td>
				</tr>
				<?php
			}
		}
		?>
	</table>
</div>


</div>
<?php
require_once(__DIR__ . '/footer.php');

