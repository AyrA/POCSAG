<?php

require_once('session.php');

class CSRF
{
	/** Name of the form field as well as the session field */
	private const string NAME = '__csrf';

	public static function GetFormElement(): string
	{
		return '<input type="hidden" name="' . htmlspecialchars(CSRF::NAME) . '" value="' . htmlspecialchars(CSRF::GetToken()) . '" />';
	}

	public static function ValidateToken(): bool
	{
		Session::Ensure();
		if (empty($_SESSION[CSRF::NAME])) {
			return FALSE;
		}
		$token = $_POST[CSRF::NAME] ?? NULL;
		return $token === $_SESSION[CSRF::NAME];
	}

	public static function GetToken(): string
	{
		Session::Ensure();
		if (empty($_SESSION[CSRF::NAME])) {
			$_SESSION[CSRF::NAME] = base64_encode(random_bytes(16));
		}
		return $_SESSION[CSRF::NAME];
	}

	public static function Delete(): void
	{
		Session::Ensure();
		unset($_SESSION[CSRF::NAME]);
	}
}
