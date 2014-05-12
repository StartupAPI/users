<?php
require_once(__DIR__ . '/global.php');

$user = User::require_login();

$current_account = null;
$can_invite_to_account = false;

$current_account = $user->getCurrentAccount();
if (!$current_account->isIndividual()
	&& $current_account->getUserRole($user) === Account::ROLE_ADMIN
) {
	$can_invite_to_account = true;
}

$errors = array();

if (array_key_exists('send', $_POST)) {
	$invitation_name = trim($_POST['invitation_name']);
	if (empty($invitation_name)) {
		$errors['name'][] = "Please provide persons name";
	}

	$invitation_email = trim($_POST['invitation_email']);
	if (empty($invitation_email)) {
		$errors['email'][] = "Please provide persons email";
	}

	$invitation_note = trim($_POST['invitation_note']);

	$invite_to_account = null;
	if ($can_invite_to_account && array_key_exists('invite_to_account', $_POST)) {
		// can only invite to user's current account
		$invite_to_account = $current_account;
	}

	if (count($errors) == 0) {
		Invitation::sendUserInvitation($user, $invitation_name, $invitation_email, $invitation_note, $invite_to_account);
		header("Location: #message=sent");
		exit;
	}
}

if (array_key_exists('resend', $_POST)) {
	foreach (array_keys($_POST['resend']) as $code) {
		$invitation = Invitation::getByCode($code);

		if ($invitation->getIssuer()->isTheSameAs($user)) {
			$invitation->send();
			header("Location: #message=resent");
			exit;
		} else {
			header("Location: #message=wrongissuer");
			exit;
		}
	}
}

if (array_key_exists('cancel', $_POST)) {
	foreach (array_keys($_POST['cancel']) as $code) {
		$invitation = Invitation::getByCode($code);

		if ($invitation->getIssuer()->isTheSameAs($user)) {
			$invitation->cancel();
			header("Location: #message=cancelled");
			exit;
		} else {
			header("Location: #message=wrongissuer");
			exit;
		}
	}
}

$SECTION = 'invitations';

require_once(__DIR__ . '/sidebar_header.php');
?>
<script>
	$(document).ready(function() {
		var available_messages = {
			sent: { 'class': 'success', 'text': 'Invitations sent'},
			cancelled: { 'class': 'success', 'text': 'Invitation cancelled'}
		};
	});
</script>

<h1><?php echo UserConfig::$userInvitationSectionTitle ?></h1>
<form class="form-horizontal well" action="" method="POST">
	<div class="control-group">
		<div class="controls">
			Please provide a personal message, name and email of the person you'd like to invite.
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="invitation_name">Name</label>
		<div class="controls">
			<input class="span6" type="text" name="invitation_name" id="invitation_name" placeholder="John Smith">
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="invitation_email">Email</label>
		<div class="controls">
			<input class="span6" type="email" name="invitation_email" id="invitation_email" placeholder="john.smith@example.com">
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="invitation_note">Message</label>
		<div class="controls">
			<?php
			$message = call_user_func_array(UserConfig::$onRenderUserInvitationMessagePlaceholder, array($user));
			?>
			<textarea class="span6" id="invitation_note" name="invitation_note" rows="3"><?php echo $message ?></textarea>
		</div>
	</div>
	<?php
	if ($can_invite_to_account) {
		?>
		<div class="control-group">
			<div class="controls">
				<label class="checkbox">
					<input type="checkbox" class="checkbox" name="invite_to_account" <?php if (array_key_exists('joinaccount', $_GET)) { ?> checked="checked"<?php } ?>/>
					Invite them to join <b><i><?php echo UserTools::escape($current_account->getName()) ?></i></b> account
				</label>
			</div>
		</div>
		<?php
	}
	?>
	<div class="control-group">
		<div class="controls">
			<button class="btn btn-primary" name="send">Send invitation</button>
		</div>
	</div>
	<?php UserTools::renderCSRFNonce(); ?>
</form>
<?php
$invitations = $user->getSentInvitations();

if (count($invitations) > 0) {
	?>
	<form action="" method="POST">
		<h2>Sent Invitations</h2>
		<table class="table">
			<?php
			$now = time();

			foreach ($invitations as $invitation) {
				$issuer = $invitation->getIssuer();
				$email = trim($invitation->getSentToEmail());
				$note = trim($invitation->getNote());

				$created = $invitation->getTimeCreated();
				$ago = intval(floor(($now - $created) / 86400));
				?><tr>
					<td>
						<div>
							Sent <span class="badge<?php if ($ago <= 5) { ?> badge-success<?php } ?>"><?php echo $ago ?></span> day<?php echo $ago != 1 ? 's' : '' ?> ago
						</div>
						<div class="invitation_sent_time">
							<?php echo date('M j, h:iA', $created) ?>
						</div>
					</td>
					<td>
						<b><?php echo UserTools::escape($invitation->getSentToName()) ?></b>
						<?php
						if ($email) {
							?>
							&lt;<a target="_blank" href="mailto:<?php echo UserTools::spaceencode($email) ?>"><?php echo UserTools::escape($email) ?></a>&gt;
							<?php
						}
						?>
					</td>
					<td>
						<button class="btn btn-mini btn-primary" type="submit" name="resend[<?php echo $invitation->getCode(); ?>]"><i class="icon-white icon-envelope"></i> Re-send</button>
						<button class="btn btn-mini" type="submit" name="cancel[<?php echo $invitation->getCode(); ?>]" onclick="return confirm('Are you sure you want to cancel this invitation?')"><i class="icon-remove"></i> Cancel Invitation</button>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php UserTools::renderCSRFNonce(); ?>
	</form>
	<?php
}

$invitations = $user->getAcceptedInvitations();

if (count($invitations) > 0) {
	?>
	<h2>Accepted Invitations</h2>
	<table class="table">
		<tr>
			<th>Registered User</th>
			<th>Sent to</th>
		</tr>
		<?php
		foreach ($invitations as $invitation) {
			$issuer = $invitation->getIssuer();
			$invited_user = $invitation->getUser();
			$email = trim($invitation->getSentToEmail());
			$note = trim($invitation->getNote());
			?>
			<tr>
				<td>
					<i class="icon-user"></i> <?php echo UserTools::escape($invited_user->getName()) ?>
				</td>
				<td>
					<b><?php echo UserTools::escape($invitation->getSentToName()) ?></b>
					<?php
					if ($email) {
						?>
						&lt;<a target="_blank" href="mailto:<?php echo UserTools::spaceencode($email) ?>"><?php echo UserTools::escape($email) ?></a>&gt;
						<?php
					}
					?>
				</td>
			</tr>
			<?php
		}
		?>
	</table>
	<?php
}

require_once(__DIR__ . '/sidebar_footer.php');
