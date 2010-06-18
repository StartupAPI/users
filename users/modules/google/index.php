<?php
class GoogleAuthenticationModule implements IAuthenticationModule
{
	private $siteid;
	private $regHeadersLoaded = false;
	private $adminHeaderShown = false;

	public function __construct($siteid)
	{
		$this->siteid = $siteid;
	}

	public function getID()
	{
		return "google";
	}

	public function getTitle()
	{
		return "Other accounts using Google Friend Connect";
	}

	public function getUserCredentials($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT google_id FROM '.UserConfig::$mysql_prefix.'googlefriendconnect WHERE user_id = ?'))
		{
			if (!$stmt->bind_param('i', $userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($google_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$google_ids = array();
			while ($stmt->fetch() === TRUE) {
				$google_ids[] = $google_id;
			}
			$stmt->close();

			if (count($google_ids) > 0)
			{
				$creds = '';

				if (!$this->adminHeaderShown) {
					$creds .= '
                <script src="http://www.google.com/jsapi"></script>
                <script src="http://www.google.com/friendconnect/script/friendconnect.js?key=notsupplied&v=0.8"></script>
                <script>
                google.setOnLoadCallback(function() {
                        google.friendconnect.container.loadOpenSocialApi({
                                site: \''.$this->siteid.'\',
                                onload: function(securityToken) {
                                        if (!window.timesloaded) {
                                                window.timesloaded = 1;
                                        } else {
                                                window.timesloaded++;
                                        }

                                        if (window.timesloaded > 1) {
                                                document.googleloginform.submit();
                                        }
                                }
                        });
                });
                </script>';
					$this->adminHeaderShown = true;
				}

				$first = true;
				foreach ($google_ids as $google_id) {
					if ($first) {
						$first = false;
					} else {
						$creds .= ', ';
					}
					$creds .= "<a href=\"#\" onclick=\"google.friendconnect.showMemberProfile('$google_id'); return false;\">$google_id</a>";
				}

				return $creds;
			}
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return null;
	}


	public function renderLoginForm($action)
	{
		?>
		<script src="http://www.google.com/jsapi"></script>
		<script src="http://www.google.com/friendconnect/script/friendconnect.js?key=notsupplied&v=0.8"></script>
		<script>
		google.setOnLoadCallback(function() {
			google.friendconnect.container.loadOpenSocialApi({
				site: '<?php echo $this->siteid?>',
				onload: function(securityToken) {
					if (!window.timesloaded) {
						window.timesloaded = 1;
					} else {
						window.timesloaded++;
					}

					if (window.timesloaded > 1) {
						document.googleloginform.submit();
					}
				}
			});
		});
		</script>

		<p>Sign in using your <b>OpenID</b> or an existing account with <b>Google</b>, <b>Twitter</b>, <b>Yahoo!</b> and more.</p>
		<a href="#" onclick="google.friendconnect.requestSignIn(function() {document.googleloginform.submit()}); return false;"><span style="background-image: url(<?php echo UserConfig::$USERSROOTURL ?>/modules/google/google-sprite.png); background-position: 0px 0px; width: 152px; height: 21px; display: block; cursor: hand;" title="Log in using existing account via Gogle Friend Connect"></span></a>
		
		<form action="<?php echo $action?>" method="POST" name="googleloginform">
		<input type="hidden" name="login" value="Login &gt;&gt;&gt;"/>
		</form>
		<?php
	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null , $data = null)
	{
		if (is_null($action))
		{
			$action = UserConfig::$USERSROOTURL.'/register.php?module='.$this->getID();
		}

		if (!$this->regHeadersLoaded)
		{
			?>
			<script src="http://www.google.com/jsapi"></script>
			<script src="http://www.google.com/friendconnect/script/friendconnect.js?key=notsupplied&v=0.8"></script>
			<script>
			google.setOnLoadCallback(function() {
				google.friendconnect.container.loadOpenSocialApi({
					site: '<?php echo $this->siteid?>',
					onload: function(securityToken) {
						if (!window.timesloaded) {
							window.timesloaded = 1;
						} else {
							window.timesloaded++;
						}

						if (window.timesloaded > 1) {
							document.googleregform.submit();
						}
					}
				});
			});
			</script>

			<form action="<?php echo $action?>" method="POST" name="googleregform">
			<input type="hidden" name="register" value="Register &gt;&gt;&gt;"/>
			</form>
			<?php
			$this->regHeadersLoaded = true;
		}

		if ($full)
		{
		?>
			<p>Sign un using your <b>OpenID</b> or an existing account with <b>Google</b>, <b>Twitter</b>, <b>Yahoo!</b> and more.</p>
		<?php
		}
		?>
		<a href="#" onclick="google.friendconnect.requestSignIn(); return false;"><span style="background-image: url(<?php echo UserConfig::$USERSROOTURL ?>/modules/google/google-sprite.png); background-position: 0px -42px; width: 200px; height: 21px; display: block; cursor: hand;" title="Quick Sign up using existing account via Google Friend Connect"></span></a>
		<?php
	}

	/*
	 * Renders user editing form
	 *
	 * Parameters:
	 * $action - form action to post back to
	 * $errors - error messages to display
	 * $user - user object for current user that is being edited
	 * $data - data submitted to the form
	 */
	public function renderEditUserForm($action, $errors, $user, $data)
	{
		$associations = $user->getGoogleFriendsConnectAssociations();

		?>
		<form action="<?php echo $action?>" method="POST">
		<input type="hidden" name="save"value ="save"/>
		<?php
		foreach ($associations as $association)
		{
			?><div style="float: left; margin-right: 1em">
			<img src="<?php echo $association['userpic']?>"/><br/>
			<input type="submit" name="remove[<?php echo $association['google_id']?>]" value="remove" style="font-size: xx-small"/>
			</div><?php
		}

		?>
		</form>

		<div style="clear: both"></div>

		<script src="http://www.google.com/jsapi"></script>
		<script src="http://www.google.com/friendconnect/script/friendconnect.js?key=notsupplied&v=0.8"></script>
		<script>
		google.setOnLoadCallback(function() {
			google.friendconnect.container.loadOpenSocialApi({
				site: '<?php echo $this->siteid?>',
				onload: function(securityToken) {
					if (!window.timesloaded) {
						window.timesloaded = 1;
					} else {
						window.timesloaded++;
					}

					if (window.timesloaded > 1) {
						document.googleeditform.submit();
					}
				}
			});
		});
		</script>
		<p>Connect to your <b>OpenID</b> or existing account with <b>Google</b>, <b>Twitter</b>, <b>Yahoo!</b> and more.</p>
		<a href="#" onclick="google.friendconnect.requestSignIn(function() {document.googleeditform.submit()}); return false;"><span style="background-image: url(<?php echo UserConfig::$USERSROOTURL ?>/modules/google/google-sprite.png); background-position: 0px -21px; width: 218px; height: 21px; display: block; cursor: hand;" title="Connect to another account via Google Friend Connect"></span></a>

		<form action="<?php echo $action?>" method="POST" name="googleeditform">
		<input type="hidden" name="save" value="Save &gt;&gt;&gt;"/>
		</form>
		<?php
	}

	public function processLogin($data)
	{
		$fcauth = $_COOKIE['fcauth'.$this->siteid];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/friendconnect/api/people/@viewer/@self?fcauth='.urlencode($fcauth));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$data = json_decode(curl_exec($ch), true);
		curl_close($ch);

		if (!is_null($data) &&
			array_key_exists('entry', $data) &&
			array_key_exists('id', $data['entry']))
		{
			$user = User::getUserByGoogleFriendConnectID($data['entry']['id']);
			if (!is_null($user)) {
				return $user;
			} else {
				return $this->processRegistration($post_data);
			}
		} else {
			return false;
		}
	}

	public function processRegistration($data)
	{
		$fcauth = $_COOKIE['fcauth'.$this->siteid];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/friendconnect/api/people/@viewer/@self?fcauth='.urlencode($fcauth));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$data = json_decode(curl_exec($ch), true);
		curl_close($ch);

		$googleid = null;
		$displayName = null;
		$thumbnailUrl = null;

		if (!is_null($data) &&
			array_key_exists('entry', $data) &&
			array_key_exists('id', $data['entry']))
		{
			$googleid = $data['entry']['id'];
			$displayName = $data['entry']['displayName'];
			$thumbnailUrl = $data['entry']['thumbnailUrl'];
		}


		$errors = array();
		if (is_null($googleid))
		{
			$errors['googleid'][] = 'No Google Friend Connect user id is passed';
			throw new InputValidationException('No Google Friend Connect user id', 0, $errors);
		}

		$existing_user = User::getUserByGoogleFriendConnectID($googleid);
		if (!is_null($existing_user))
		{
			return $existing_user;
		}

		if (is_null($displayName))
		{
			$errors['username'][] = "User doesn't have display name";
		}

		if (count($errors) > 0)
		{
			throw new ExistingUserException('User already exists', 0, $errors);
		}

		// ok, let's create a user
		return User::createNewGoogleFriendConnectUser($displayName, $googleid, $thumbnailUrl);
	}

	/*
	 * Updates user information
	 *
	 * returns true if successful and false if unsuccessful
	 *
	 * throws InputValidationException if there are problems with input data
	 */
	public function processEditUser($user, $data)
	{
		// if remove, then save stores id to remove
		if (array_key_exists('remove', $data))
		{
			$keys = array_keys($data['remove']);

			if (count($keys) > 0) {
				$user->removeGoogleFriendConnectAssociation($keys[0]);
			}
		}
		else
		{
			$fcauth = $_COOKIE['fcauth'.$this->siteid];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/friendconnect/api/people/@viewer/@self?fcauth='.urlencode($fcauth));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			$data = json_decode(curl_exec($ch), true);
			curl_close($ch);

			$googleid = null;
			$displayName = null;
			$thumbnailUrl = null;

			if (!is_null($data) &&
				array_key_exists('entry', $data) &&
				array_key_exists('id', $data['entry']))
			{
				$googleid = $data['entry']['id'];
				$displayName = $data['entry']['displayName'];
				$thumbnailUrl = $data['entry']['thumbnailUrl'];
			}

			if (is_null($googleid))
			{
				$errors['googleid'][] = 'No Google Friend Connect user id is passed';
				throw new InputValidationException('No Google Friend Connect user id', 0, $errors);
			}

			$user->addGoogleFriendConnectAssociation($googleid, $thumbnailUrl);
		}

		return true;
	}
}
