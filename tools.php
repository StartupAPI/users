<?php
/*
 * Various tools used within UserBase
 */
class UserTools
{
	/*
	 * Escapes strings making it safe to include user data in HTML output
	 */
	public static function escape($string)
	{
		return htmlentities($string, ENT_COMPAT, 'UTF-8');
	}
}
