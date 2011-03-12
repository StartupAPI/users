<?php
include_once(dirname(__FILE__).'/oauth-php/library/OAuthStore.php');
include_once(dirname(__FILE__).'/oauth-php/library/OAuthRequester.php');

abstract class OAuthAuthenticationModule implements IAuthenticationModule
{
	protected $serviceName;

	// oauth parameters
	protected $oAuthAPIRootURL;
	protected $oAuthConsumerKey;
	protected $oAuthConsumerSecret;
	protected $oAuthRequestTokenURL;
	protected $oAuthAccessTokenURL;
	protected $oAuthAuthorizeURL;
	protected $oSignatureMethods = array();

	// support for immediate mode (when server redirects back if user is already connected)
	protected $supportsImmediate = false;

	protected $remember;

	public function __construct($serviceName,
		$oAuthAPIRootURL,
		$oAuthConsumerKey, $oAuthConsumerSecret,
		$oAuthRequestTokenURL, $oAuthAccessTokenURL, $oAuthAuthorizeURL,
		$oSignatureMethods,
		$remember = true)
	{
		$this->serviceName = $serviceName;
		$this->oAuthAPIRootURL = $oAuthAPIRootURL;
		$this->oAuthConsumerKey = $oAuthConsumerKey;
		$this->oAuthConsumerSecret= $oAuthConsumerSecret;
		$this->oAuthRequestTokenURL = $oAuthRequestTokenURL;
		$this->oAuthAccessTokenURL = $oAuthAccessTokenURL;
		$this->oAuthAuthorizeURL = $oAuthAuthorizeURL;
		$this->oSignatureMethods = $oSignatureMethods;

		$this->remember = $remember;
	}

###########################################################################################
# Methods related to OAuth handling
###########################################################################################

	/**
	 * Each module is supposed to implement this service to retrieve identity info from the server.
	 *
	 * Relying on the key only is not enough as some servers might not return same access_token
	 * for the same user. Also, access_tokens can expire or be revoked eventually, but this doesn't
	 * mean that the system user identity has changed.
	 *
	 * @param array $oauth_user_id OAuth user id to get identity for
	 * @return string $identity Identity string or integer id unique for the service
	 */
	abstract protected function getIdentity($oauth_user_id);

###########################################################################################
# Methods related to UserBase mechanics
###########################################################################################

	/*
	 * retrieves recent aggregated registrations numbers
	 */
	public function getRecentRegistrations()
	{
# TODO Implement getting recent registrations

#		$db = UserConfig::getDB();
#
#		$regs = 0;
#
#		if ($stmt = $db->prepare('SELECT count(*) AS reqs FROM (SELECT u.id FROM '.UserConfig::$mysql_prefix.'users u LEFT JOIN '.UserConfig::$mysql_prefix.'googlefriendconnect g ON u.id = g.user_id WHERE regtime > DATE_SUB(NOW(), INTERVAL 30 DAY) AND g.google_id IS NOT NULL GROUP BY id) AS agg'))
#		{
#			if (!$stmt->execute())
#			{
#				throw new Exception("Can't execute statement: ".$stmt->error);
#			}
#			if (!$stmt->bind_result($regs))
#			{
#				throw new Exception("Can't bind result: ".$stmt->error);
#			}
#
#			$stmt->fetch();
#			$stmt->close();
#		}
#		else
#		{
#			throw new Exception("Can't prepare statement: ".$db->error);
#		}
#
#		return $regs;
	}

	/*
	 * retrieves aggregated registrations numbers
	 */
	public function getDailyRegistrations()
	{
# TODO Implement getting registrations

#		$db = UserConfig::getDB();
#
#		$dailyregs = array();
#
#		if ($stmt = $db->prepare('SELECT regdate, count(*) AS reqs FROM (SELECT CAST(regtime AS DATE) AS regdate, id AS regs FROM '.UserConfig::$mysql_prefix.'users u LEFT JOIN '.UserConfig::$mysql_prefix.'googlefriendconnect g ON u.id = g.user_id WHERE g.google_id IS NOT NULL GROUP BY id) agg group by agg.regdate'))
#		{
#			if (!$stmt->execute())
#			{
#				throw new Exception("Can't execute statement: ".$stmt->error);
#			}
#			if (!$stmt->bind_result($regdate, $regs))
#			{
#				throw new Exception("Can't bind result: ".$stmt->error);
#			}
#
#			while($stmt->fetch() === TRUE)
#			{
#				$dailyregs[] = array('regdate' => $regdate, 'regs' => $regs);
#			}
#
#			$stmt->close();
#		}
#		else
#		{
#			throw new Exception("Can't prepare statement: ".$db->error);
#		}
#
#		return $dailyregs;
	}

	public function renderLoginForm($action)
	{
		?>
		<p>Sign in using your existing account with <b><?php echo UserTools::escape($this->serviceName)?></b>.</p>
		<form action="<?php echo $action?>" method="POST">
		<input type="submit" name="login" value="Log in using <?php echo UserTools::escape($this->serviceName)?> &gt;&gt;&gt;"/>
		</form>
		<?php
	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null , $data = null)
	{
		if (is_null($action))
		{
			$action = UserConfig::$USERSROOTURL.'/register.php?module='.$this->getID();
		}

		if ($full)
		{
		?>
			<p>Sign in using your existing account with <b><?php echo UserTools::escape($this->serviceName)?></b>.</p>
		<?php
		}
		?>
		<form action="<?php echo $action?>" method="POST">
		<input type="submit" name="register" value="Register using <?php echo UserTools::escape($this->serviceName)?>&gt;&gt;&gt;"/>
		</form>
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
		?>EDIT FORM GOES HERE<?php
	}

	public function processLogin($data, &$remember)
	{
		return false;
	}

	public function processRegistration($data, &$remember)
	{
		return false;
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
		return true;
	}
}
