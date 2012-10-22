<?php
require_once(dirname(__FILE__).'/global.php');
require_once(dirname(__FILE__).'/User.php');

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
	 * @var string Invitation comment (reminder to issuer why it was sent)
	 */
	private $usagecomment;

	/**
	 * @var int ID of the User who got invited
	 */
	private $user;

	/**
	 * Creates new invitation object
	 *
	 * @param string $code Invitation code
	 * @param string $time_created Date/time when invitation code was created
	 * @param int $issuedby ID of the user who created an invitation
	 * @param string $usagecomment Invitation comment (reminder to issuer why it was sent)
	 * @param int $user ID of the User who got invited
	 */
	private function __construct($code, $time_created, $issuedby, $usagecomment = null, $user = null)
	{
		$this->code = $code;
		$this->time_created = $time_created;
		$this->issuedby = $issuedby;
		$this->usagecomment = $usagecomment;
		$this->user = $user;
	}

	/**
	 * Returns invitations that were generated, but not sent yet
	 *
	 * @return array Array of Invitation objects
	 *
	 * @throws DBException
	 */
	public static function getUnsent()
	{
		$invitations = array();

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT code, created, issuedby FROM '.UserConfig::$mysql_prefix.'invitation WHERE sentto IS NULL AND user IS NULL'))
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
				$invitations[] = new self($code, $time_created, $issuedby);
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
	 * @return array Array of Invitation objects
	 *
	 * @throws DBException
	 */
	public static function getSent()
	{
		$invitations = array();

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT code, created, issuedby, sentto FROM '.UserConfig::$mysql_prefix.'invitation WHERE sentto IS NOT NULL AND user IS NULL'))
		{
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby, $sentto))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$invitations[] = new self($code, $time_created, $issuedby, $sentto);
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
	 * @return array Array of Invitation objects
	 *
	 * @throws DBException
	 */
	public static function getAccepted()
	{
		$invitations = array();

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT code, created, issuedby, sentto, user FROM '.UserConfig::$mysql_prefix.'invitation WHERE user IS NOT NULL'))
		{
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby, $sentto, $userid))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$invitations[] = new self($code, $time_created, $issuedby, $sentto, $userid);
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

		if ($stmt = $db->prepare('SELECT code, created, issuedby, sentto, user FROM '.UserConfig::$mysql_prefix.'invitation WHERE code = ?'))
		{
			if (!$stmt->bind_param('s', $code))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby, $sentto, $userid))
			{
				throw new DBBindResultException($db, $stmt);
			}

			if ($stmt->fetch() === TRUE)
			{
				$invitation = new self($code, $time_created, $issuedby, $sentto, $userid);
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
	 * Creates new invitation codes to be used for inviting new users
	 *
	 * @param int $howmany How many codes to generate
	 *
	 * @throws DBException
	 */
	public static function generate($howmany)
	{
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'invitation (code) VALUES (?)'))
		{
			for ($i = 0; $i < $howmany; $i++)
			{
				if (!$stmt->bind_param('s', self::generateCode()))
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
	 * Returns invitation comment
	 *
	 * @return string Invitation comments
	 */
	public function getComment()
	{
		return $this->usagecomment;
	}

	/**
	 * Sets invitation comment
	 *
	 * @param string $comment Invitation comment
	 */
	public function setComment($comment)
	{
		$this->usagecomment = $comment;
	}

	/**
	 * Persists invitation object in database
	 *
	 * @throws DBException
	 */
	public function save()
	{
		$db = UserConfig::getDB();

		$comment = mb_convert_encoding($this->usagecomment, 'UTF-8');

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'invitation SET sentto = ?, issuedby = ?, user = ? WHERE code = ?'))
		{
			if (!$stmt->bind_param('siis', $comment, $this->issuedby, $this->user, $this->code))
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
		return User::getUser($this->user);
	}

	/**
	 * Sets user ID of invited user
	 *
	 * @param int $user User ID
	 */
	public function setUser($user)
	{
		$this->user = $user->getID();
	}

	/**
	 * Returns true if invitation is already accepted
	 *
	 * @return boolean True if invitation is already accepted
	 */
	public function getStatus()
	{
		return !is_null($this->user);
	}
}
