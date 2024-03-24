<?php

class Session
{
	public static function Start()
	{
		if (!session_start()) {
			throw new \Exception('Unable to start session. Session settings in PHP.INI likely invalid');
		}
	}

	public static function SignIn(string $username)
	{
		Session::Ensure();
		$_SESSION['username'] = strtolower($username);
		$_SESSION['login'] = TRUE;
	}

	public static function GetName(): string
	{
		$un = $_SESSION['username'] ?? NULL;
		if (!$un) {
			throw new \Exception('User not signed in');
		}
		return $un;
	}

	public static function IsSignedIn()
	{
		return Session::Has() && ($_SESSION['login'] ?? FALSE);
	}

	public static function EnsureSignedIn()
	{
		if (!Session::IsSignedIn()) {
			throw new \Exception('User not signed in');
		}
	}

	public static function Has(): bool
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}

	public static function Ensure()
	{
		if (!Session::Has()) {
			throw new \Exception('Session has not been started');
		}
	}

	public static function Save()
	{
		Session::Ensure();
		if (!session_write_close()) {
			throw new \Exception('Unable to save session');
		}
	}

	public static function SignOut(): bool
	{
		if (Session::Has()) {
			unset($_SESSION['login']);
			unset($_SESSION['username']);
			return TRUE;
		}
		return FALSE;
	}

	public static function GetMode(): ?string
	{
		return $_SESSION['html_mode'] ?? NULL;
	}

	public static function SetMode(?string $mode)
	{
		Session::Ensure();
		if ($mode === NULL) {
			unset($_SESSION['html_mode']);
		} else {
			$_SESSION['html_mode'] = $mode;
		}
	}
}
