<?php
require_once(dirname(__DIR__).'/global.php');
require_once(dirname(__DIR__).'/classes/User.php');

/**
 * Invitation class
 *
 * This class supports admin-initiated invitations only at the moment
 *
 * @todo Add ability for users to invite each other
 * @todo Get rid of integers for user IDs and use user objects instead
 *
 * @package StartupAPI
 */
class Invitation
{
	/**
	 * @var string Invitation code
	 */
	private $code;

	/**
	 * @var string Date/time when invitation code was created
	 */
	private $time_created;

	/**
	 * @var int User ID of the user who sent the invitation
	 */
	private $issuedby;

	/**
	 * @var boolean Whatever this invitation was generated using admin UI or not
	 */
	private $is_admin_invite;

	/**
	 * @var string Email address invitation was sent to
	 */
	private $sent_to_email;

	/**
	 * @var string Name of invited user
	 */
	private $sent_to_name;

	/**
	 * @var string Invitation comment (reminder to issuer why it was sent)
	 */
	private $sent_to_note;

	/**
	 * @var int ID of the User who got invited, null if invitation is not accepted yet
	 */
	private $user_id;

	/**
	 * Creates new invitation object
	 *
	 * @param string $code Invitation code
	 * @param string $time_created Date/time when invitation code was created
	 * @param int $issuedby ID of the user who created an invitation
	 * @param boolean $is_admin_invite Is this an invitation from administrator
	 * @param string $sent_to_note Invitation comment (reminder to issuer why it was sent)
	 * @param int $user_id ID of the User who got invited
	 */
	private function __construct($code, $time_created, $issuedby, $is_admin_invite = true,
			$sent_to_email = null, $sent_to_name = null, $sent_to_note = null, $user_id = null)
	{
		$this->code = $code;
		$this->time_created = $time_created;
		$this->issuedby = $issuedby;
		$this->is_admin_invite = $is_admin_invite;
		$this->sent_to_email = $sent_to_email;
		$this->sent_to_name = $sent_to_name;
		$this->sent_to_note = $sent_to_note;
		$this->user_id = $user_id;
	}

	/**
	 * Returns admin invitations that were generated, but not sent yet
	 *
	 * @return array Array of Invitation objects
	 *
	 * @throws DBException
	 */
	public static function getUnsent()
	{
		$invitations = array();

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT code, created, issuedby
			FROM '.UserConfig::$mysql_prefix.'invitation
			WHERE is_admin_invite = 1 AND sent_to_note IS NULL AND user_id IS NULL'))
		{
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$invitations[] = new self($code, $time_created, $issuedby, true);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $invitations;
	}

	/**
	 * Returns invitations that were sent, but not used for registration yet
	 *
	 * @param boolean $admin Pass true if you want only admin invitations
	 *
	 * @return array Array of Invitation objects
	 *
	 * @throws DBException
	 */
	public static function getSent($admin = null)
	{
		$invitations = array();

		$db = UserConfig::getDB();

		$query = 'SELECT code, created, issuedby, is_admin_invite,
				sent_to_email, sent_to_name, sent_to_note
			FROM '.UserConfig::$mysql_prefix.'invitation
			WHERE sent_to_note IS NOT NULL AND user_id IS NULL';

		if (!is_null($admin)) {
			$query .= ' AND is_admin_invite = ' . ($admin ? 1 : 0);
		}

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby, $is_admin_invite,
					$sent_to_email, $sent_to_name, $sent_to_note))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$invitations[] = new self($code, $time_created, $issuedby, $is_admin_invite ? true : false,
						$sent_to_email, $sent_to_name, $sent_to_note);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $invitations;
	}

	/**
	 * Cancels invitation code
	 *
	 * @param string $code Invitation code to cancel
	 *
	 * @throws DBException
	 */
	public static function cancel($code) {
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.'invitation WHERE code = ?'))
		{
			if (!$stmt->bind_param('s', $code))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Returns invitations that were accepted
	 *
	 * @param boolean $admin Pass true if you want only admin invitations
	 *
	 * @return array Array of Invitation objects
	 *
	 * @throws DBException
	 */
	public static function getAccepted($admin = null)
	{
		$invitations = array();

		$db = UserConfig::getDB();

		$query = 'SELECT code, created, issuedby, is_admin_invite,
				sent_to_email, sent_to_name, sent_to_note, user_id
			FROM '.UserConfig::$mysql_prefix.'invitation
			WHERE user_id IS NOT NULL';

		if (!is_null($admin)) {
			$query .= ' AND is_admin_invite = ' . ($admin ? 1 : 0);
		}

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby, $is_admin_invite,
					$sent_to_email, $sent_to_name, $sent_to_note, $user_id))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$invitations[] = new self($code, $time_created, $issuedby, $is_admin_invite ? true : false,
						$sent_to_email, $sent_to_name, $sent_to_note, $user_id);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $invitations;
	}

	/**
	 * Returns invitation by invitation code
	 *
	 * @param string $code Invitation code
	 *
	 * @return Invitation
	 *
	 * @throws DBException
	 */
	public static function getByCode($code)
	{
		$invitation = null;

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT code, created, issuedby, is_admin_invite,
				sent_to_email, sent_to_name, sent_to_note, user_id
			FROM '.UserConfig::$mysql_prefix.'invitation
			WHERE code = ?'))
		{
			if (!$stmt->bind_param('s', $code))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby, $is_admin_invite,
					$sent_to_email, $sent_to_name, $sent_to_note, $user_id))
			{
				throw new DBBindResultException($db, $stmt);
			}

			if ($stmt->fetch() === TRUE)
			{
				$invitation = new self($code, $time_created, $issuedby, $is_admin_invite ? true : false,
						$sent_to_email, $sent_to_name, $sent_to_note, $user_id);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $invitation;
	}

	/**
	 * Creates new invitation codes to be used for inviting new users in admin UI
	 *
	 * @param int $howmany How many codes to generate
	 *
	 * @throws DBException
	 */
	public static function generateAdminInvites($howmany = 1)
	{
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'invitation (code, is_admin_invite) VALUES (?, 1)'))
		{
			for ($i = 0; $i < $howmany; $i++)
			{
				$code = self::generateCode();

				if (!$stmt->bind_param('s', $code))
				{
					throw new DBBindParamException($db, $stmt);
				}
				if (!$stmt->execute())
				{
					throw new DBExecuteStmtException($db, $stmt);
				}
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Creates new invitation code string
	 *
	 * @return string Invitation code
	 */
	private static function generateCode()
	{
		// Length of invitation strings
		$length = 10;

		// characters to use in invitation strings
		$chars = 'abcdefghijklmnopqrstuvwxyz1234567890';

		// Length of character list
		$chars_length = (strlen($chars) - 1);

		// Start our string
		$string = $chars{rand(0, $chars_length)};

		// Generate random string
		for ($i = 1; $i < $length; $i = strlen($string))
		{
			// Grab a random character from our list
			$r = $chars{rand(0, $chars_length)};

			// Make sure the same two characters don't appear next to each other
			if ($r != $string{$i - 1}) $string .=  $r;
		}

		// Return the string
		return $string;
	}

	/**
	 * Returns invitation code
	 *
	 * @return string Invitation code
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * Returns date/time code was generated
	 *
	 * @return string Date/time when code was generated
	 */
	public function getTimeCreated()
	{
		return $this->time_created;
	}

	/**
	 * Returns User object for who issued invitation
	 *
	 * @return User User who issued invitation
	 */
	public function getIssuer()
	{
		return User::getUser($this->issuedby);
	}

	/**
	 * Sets invitation issuer
	 *
	 * @param User $user Issuer's User object
	 */
	public function setIssuer($user) {
		$this->issuedby = $user->getID();
	}

	/**
	 * Returns email address this invitation was sent to
	 *
	 * @return string Email address
	 */
	public function getSentToEmail() {
		return $this->sent_to_email;
	}

	/**
	 * Sets email address this invitation was sent to
	 *
	 * @param string $sent_to_email Email address
	 */
	public function setSentToEmail($sent_to_email) {
		$this->sent_to_email = $sent_to_email;
	}

	/**
	 * Returns name of the person invitation was sent to
	 *
	 * @return string Person's name
	 */
	public function getSentToName() {
		return $this->sent_to_name;
	}

	/**
	 * Sets name of the person invitation was sent to
	 *
	 * @param string sent_to_name Person's name
	 */
	public function setSentToName($sent_to_name) {
		$this->sent_to_name = $sent_to_name;
	}

	/**
	 * Returns invitation comment
	 *
	 * @return string Invitation comments
	 */
	public function getComment()
	{
		return $this->sent_to_note;
	}

	/**
	 * Sets invitation comment
	 *
	 * @param string $comment Invitation comment
	 */
	public function setComment($comment)
	{
		$this->sent_to_note = $comment;
	}

	/**
	 * Persists invitation object in database
	 *
	 * @throws DBException
	 */
	public function save()
	{
		$db = UserConfig::getDB();

		$email = mb_convert_encoding($this->sent_to_email, 'UTF-8');
		$name = mb_convert_encoding($this->sent_to_name, 'UTF-8');
		$comment = mb_convert_encoding($this->sent_to_note, 'UTF-8');

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'invitation
			SET sent_to_email = ?,
				sent_to_name = ?,
				sent_to_note = ?,
				issuedby = ?,
				is_admin_invite = ?,
				user_id = ?
			WHERE code = ?'))
		{
			if (!$stmt->bind_param('sssiiis', $email, $name, $comment, $this->issuedby, $this->is_admin_invite, $this->user_id, $this->code))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Returns ID of invited user
	 *
	 * @return int User ID
	 */
	public function getUser()
	{
		return User::getUser($this->user_id);
	}

	/**
	 * Sets invited user
	 *
	 * @param User $user User
	 */
	public function setUser($user)
	{
		$this->user_id = $user->getID();
	}

	/**
	 * Returns true if invitation is already accepted
	 *
	 * @return boolean True if invitation is already accepted
	 */
	public function getStatus()
	{
		return !is_null($this->user_id);
	}
}
