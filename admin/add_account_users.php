<?php
require_once(__DIR__ . '/admin.php');

/**
 * Checking if we work with real account
 */
$account = null;
if (array_key_exists('account_id', $_GET)) {
	$account_id = $_GET['account_id'];

	$account = Account::getByID($account_id);
}

if (is_null($account)) {
	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/accounts.php');
	exit;
}

if (array_key_exists('user_id', $_POST)) {
	$user = User::getUser($_POST['user_id']);

	if (!is_null($user)) {
		/**
		 * Making first user an admin automatically
		 */
		$current_account_users = $account->getUsers();
		$user_role = Account::ROLE_USER;
		if (count($current_account_users) == 0) {
			$user_role = Account::ROLE_ADMIN;
		}

		$account->addUser($user, $user_role);

		// @todo make them admin if they are the only user

		header('Location: ' . UserConfig::$USERSROOTURL . '/admin/account.php?id=' . UserTools::escape($account_id));
		exit;
	}
}

$ADMIN_SECTION = 'registrations';
require_once(__DIR__ . '/header.php');

$total = 0;

$perpage = 20;
$pagenumber = 0;

if (array_key_exists('page', $_GET)) {
	$pagenumber = $_GET['page'];
}

$search = null;
if (array_key_exists('q', $_GET)) {
	$search = trim($_GET['q']);
	if ($search == '') {
		$search = null;
	}
}

if (array_key_exists('sort', $_GET) && $_GET['sort'] == 'activity') {
	$sortby = 'activity';
} else {
	$sortby = 'registration';
}

if (is_null($search)) {
	if (array_key_exists('date_from', $_GET) && array_key_exists('date_from', $_GET)) {
		$users = User::getUsers($pagenumber, $perpage, $sortby, $_GET['date_from'], $_GET['date_to']);
	} else if (array_key_exists('date_from', $_GET)) {
		$users = User::getUsers($pagenumber, $perpage, $sortby, $_GET['date_from']);
	} else if (array_key_exists('date_to', $_GET)) {
		$users = User::getUsers($pagenumber, $perpage, $sortby, null, $_GET['date_to']);
	} else {
		$users = User::getUsers($pagenumber, $perpage, $sortby);
	}
} else {
	UserTools::debug($search);

	$users = User::searchUsers($search, $pagenumber, $perpage, $sortby);

	UserTools::debug(count($users));
}
?>
<div class="span9">
	<table class="table table-bordered table-striped" width="100%">
		<thead>
			<tr>
				<th></th>
				<th>Name</th>
				<th>Credentials</th>
				<th>Email</th>
			</tr>
			<tr>
				<td colspan="6">
					<div cZlass="control-group">
						<form action="" id="search" name="search" class="form-horizontal" style="margin: 0">
							<input type="hidden" name="account_id" value="<?php echo UserTools::escape($account_id) ?>"/>

							<input type="search" class="search-query input-medium" placeholder="User's name or email" id="q" name="q"<?php echo is_null($search) ? '' : ' value="' . UserTools::escape($search) . '"' ?>/>
							<input type="submit" class="btn btn-medium" value="search"/>
							<input type="button" class="btn btn-medium" value="clear" onclick="document.getElementById('q').value=''; document.search.submit()"/>

							<label class="pull-right">Sort by:
								<select name="sort" style="margin-left: 0.5em" onchange="document.search.submit();">
									<option value="registration"<?php echo $sortby == 'registration' ? ' selected="yes"' : '' ?>>Registration date</option>
									<option value="activity"<?php echo $sortby == 'activity' ? ' selected="yes"' : '' ?>>User activity</option>
								</select>
							</label>
						</form>
					</div>
				</td>
			</tr>
		</thead>

		<form action="" method="POST" id="search" name="search" class="form-horizontal" style="margin: 0">
			<input type="hidden" name="account_id" value="<?php echo UserTools::escape($account_id) ?>"/>

			<?php
			$now = time();

			foreach ($users as $user) {
				$regtime = $user->getRegTime();
				$ago = intval(floor(($now - $regtime) / 86400));

				$tz = date_default_timezone_get();

				$userid = $user->getID();
				?>
				<tr valign="top">
					<td><button name="user_id" value="<?php echo $userid ?>" class="btn btn-mini btn-success"><i class="icon-plus icon-white"></i> add</button></td>
					<td><i class="icon-user"></i>
						<a href="user.php?id=<?php echo $userid ?>"<?php if ($user->isDisabled()) { ?> style="color: silver; text-decoration: line-through"<?php } ?>>
							<?php echo UserTools::escape($user->getName()) ?>
						</a>
						<span class="pull-right">(ID: <?php echo $userid; ?>)</span>
					</td>
					<td>
						<?php
						foreach (UserConfig::$authentication_modules as $module) {
							$creds = $module->getUserCredentials($user);

							if (!is_null($creds)) {
								?>
								<div><b><?php echo $module->getID() ?>: </b><?php echo $creds->getHTML() ?></div>
								<?php
							}
						}
						?>
					</td>
					<td><?php echo UserTools::escape($user->getEmail()) ?></td>
				</tr>
				<?php
			}

			UserTools::renderCSRFNonce();
			?>
		</form>
	</table>

	<ul class="pager">
		<?php
		if ($pagenumber > 0) {
			?><li class="previous"><a href="?account_id=<?php echo UserTools::escape($account_id) ?>&page=<?php
		echo $pagenumber - 1;
		echo is_null($search) ? '' : '&q=' . urlencode($search);
		echo $sortby == 'activity' ? '&sort=activity' : '';
			?>">&larr; prev</a></li><?php
							} else {
			?><li class="previous disabled"><a href="#">&larr; prev</a></li><?php
		}
		?>
		<li>Page <?php echo $pagenumber + 1 ?></li>
		<?php
		if (count($users) >= $perpage) {
			?>
			<li class="next"><a href="?account_id=<?php echo UserTools::escape($account_id) ?>&page=<?php
		echo $pagenumber + 1;
		echo is_null($search) ? '' : '&q=' . urlencode($search);
		echo $sortby == 'activity' ? '&sort=activity' : '';
			?>">next &rarr;</a></li>
				<?php
			} else {
				?>
			<li class="next disabled"><a href="#">next &rarr;</a></li>
			<?php
		}
		?>
	</ul>

</div>
<?php
require_once(__DIR__ . '/footer.php');