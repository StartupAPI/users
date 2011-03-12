<?php
require_once(dirname(dirname(dirname(__FILE__))).'/OAuthModule.php');

class MeetupAuthenticationModule extends OAuthAuthenticationModule
{
	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret, $remember = true)
	{
		parent::__construct(
			'Meetup',
			'http://api.meetup.com',
			$oAuthConsumerKey,
			$oAuthConsumerSecret,
			'https://api.meetup.com/oauth/request/',
			'https://api.meetup.com/oauth/access/',
			'http://www.meetup.com/authorize/',
			array('HMAC-SHA1', 'PLAINTEXT'),
			$remember
		);
	}

	public function getID()
	{
		return "meetup";
	}

	public function getLegendColor()
	{
		return "e51837";
	}

	public function getTitle()
	{
		return "Meetup";
	}

	public function getUserCredentials($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		# TODO get user's meetup credentials

		return null;
	}

	protected function getIdentity($oauth_user_id) {
	// TODO Implement retrieving self info:
	// 'https://api.meetup.com/members.json/?relation=self';

		return 1; // stub
	}

	/*
	 * retrieves recent aggregated registrations numbers
	 */
	public function getRecentRegistrations()
	{
		$db = UserConfig::getDB();

		$regs = 0;

		# TODO Implement getting a number of recent users registered using Meetup

		return $regs;
	}

	/*
	 * retrieves aggregated registrations numbers
	 */
	public function getDailyRegistrations()
	{
		$db = UserConfig::getDB();

		$dailyregs = array();

		# TODO Implement getting a number of users registered using Meetup by day

		return $dailyregs;
	}

	// implemented in parent class
	#public function renderLoginForm($action)
	#public function renderRegistrationForm($full = false, $action = null, $errors = null , $data = null)
	#public function renderEditUserForm($action, $errors, $user, $data)

	public function processLogin($data, &$remember)
	{
		$db = UserConfig::getDB();
		$store = OAuthStore::instance('MySQLi', array(
			'conn' => $db,
			'table_prefix' => UserConfig::$mysql_prefix)
		);

# TODO Implement getting user info from Meetup and logging them in

#		$remember = $this->remember;
#
#		$fcauth = $_COOKIE['fcauth'.$this->siteid];
#
#		$ch = curl_init();
#		curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/friendconnect/api/people/@viewer/@self?fcauth='.urlencode($fcauth));
#		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
#		$data = json_decode(curl_exec($ch), true);
#		curl_close($ch);
#
#		if (!is_null($data) &&
#			array_key_exists('entry', $data) &&
#			array_key_exists('id', $data['entry']))
#		{
#			$user = User::getUserByGoogleFriendConnectID($data['entry']['id']);
#			if (!is_null($user)) {
#				$user->recordActivity(USERBASE_ACTIVITY_LOGIN_GFC);
#				return $user;
#			} else {
#				return $this->processRegistration($data, $remember);
#			}
#		} else {
#			return false;
#		}
	}

	public function processRegistration($data, &$remember)
	{
# TODO Implement getting user info from Meetup and registering them

#		$remember = $this->remember;
#
#		$fcauth = $_COOKIE['fcauth'.$this->siteid];
#
#		$ch = curl_init();
#		curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/friendconnect/api/people/@viewer/@self?fcauth='.urlencode($fcauth));
#		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
#		$data = json_decode(curl_exec($ch), true);
#		curl_close($ch);
#
#		$googleid = null;
#		$displayName = null;
#		$thumbnailUrl = null;
#
#		if (!is_null($data) &&
#			array_key_exists('entry', $data) &&
#			array_key_exists('id', $data['entry']))
#		{
#			$googleid = $data['entry']['id'];
#			$displayName = $data['entry']['displayName'];
#			$thumbnailUrl = $data['entry']['thumbnailUrl'];
#		}
#
#
#		$errors = array();
#		if (is_null($googleid))
#		{
#			$errors['googleid'][] = 'No Google Friend Connect user id is passed';
#			throw new InputValidationException('No Google Friend Connect user id', 0, $errors);
#		}
#
#		$existing_user = User::getUserByGoogleFriendConnectID($googleid);
#		if (!is_null($existing_user))
#		{
#			$existing_user->recordActivity(USERBASE_ACTIVITY_LOGIN_GFC);
#			return $existing_user;
#		}
#
#		if (is_null($displayName))
#		{
#			$errors['username'][] = "User doesn't have display name";
#		}
#
#		if (count($errors) > 0)
#		{
#			throw new ExistingUserException('User already exists', 0, $errors);
#		}
#
#		// ok, let's create a user
#		$user = User::createNewGoogleFriendConnectUser($displayName, $googleid, $thumbnailUrl);
#		$user->recordActivity(USERBASE_ACTIVITY_REGISTER_GFC);
#		return $user;
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
