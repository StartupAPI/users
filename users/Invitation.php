<?
/*
 * Invitation class
*/
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/User.php');

class Invitation 
{
	private $code;
	private $time_created;
	private $issuedby;
	private $usagecomment;
	private $user;

	public static function getUnsent()
	{
		$invitations = array();

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT code, created, issuedby FROM '.UserConfig::$mysql_prefix.'invitation WHERE sentto IS NULL AND user IS NULL'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$invitations[] = new self($code, $time_created, $issuedby);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $invitations;
	}

	public static function getSent()
	{
		$invitations = array();

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT code, created, issuedby, sentto FROM '.UserConfig::$mysql_prefix.'invitation WHERE sentto IS NOT NULL AND user IS NULL'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby, $sentto))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$invitations[] = new self($code, $time_created, $issuedby, $sentto);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $invitations;
	}

	public static function getAccepted()
	{
		$invitations = array();

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT code, created, issuedby, sentto, user FROM '.UserConfig::$mysql_prefix.'invitation WHERE user IS NOT NULL'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby, $sentto, $userid))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$invitations[] = new self($code, $time_created, $issuedby, $sentto, $userid);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $invitations;
	}

	public static function getByCode($code)
	{
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT code, created, issuedby, sentto, user FROM '.UserConfig::$mysql_prefix.'invitation WHERE code = ?'))
		{
			if (!$stmt->bind_param('s', $code))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($code, $time_created, $issuedby, $sentto, $userid))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				$invitation = new self($code, $time_created, $issuedby, $sentto, $userid);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $invitation;
	}

	public function __construct($code, $time_created, $issuedby, $usagecomment = null, $user = null)
	{
		$this->code = $code;	
		$this->time_created = $time_created;
		$this->issuedby = $issuedby;
		$this->usagecomment = $usagecomment;
		$this->user = $user;
	}

	public static function generate($howmany)
	{
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'invitation (code) VALUES (?)'))
		{
			for ($i = 0; $i < $howmany; $i++)
			{
				if (!$stmt->bind_param('s', self::generateCode()))
				{
					 throw new Exception("Can't bind parameter".$stmt->error);
				}
				if (!$stmt->execute())
				{
					throw new Exception("Can't execute statement: ".$stmt->error);
				}
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}

	public static function generateCode()
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

	public function getCode()
	{
		return $this->code;
	}

	public function getTimeCreated()
	{
		return $this->time_created;
	}

	public function getIssuer()
	{
		return $this->issuedby;
	}

	public function getComment()
	{
		return $this->usagecomment;
	}
	public function setComment($comment)
	{
		$this->comment = $comment;
	}

	public function save()
	{
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'invitation SET sentto = ?, user = ? WHERE code = ?'))
		{
			if (!$stmt->bind_param('sis', $this->comment, $this->user, $this->code))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return;
	}

	public function getUser()
	{
		return User::getUser($this->user);
	}
	public function setUser($user)
	{
		$this->user = $user->getID();
	}

	public function getStatus()
	{
		return !is_null($this->user);
	}
}
