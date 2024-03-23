<?php

require_once('config.php');

class Security
{
	private static string $algo = PASSWORD_BCRYPT;
	private static array $algo_params = ['cost' => 15];

	public static function CheckCredentials(string $username, string $password): bool
	{
		$conf = Config::Get();
		return Security::CheckPassword($password) && $conf->username === strtolower($username);
	}

	public static function CheckPassword(string $password): bool
	{
		$conf = Config::Get();
		if (!$conf->password) {
			throw new \Exception('Password has not been set');
		}
		if (password_verify($password, $conf->password)) {
			//Update password if necessary
			if (password_needs_rehash($conf->password, Security::$algo, Security::$algo_params)) {
				Security::SetCredentials($conf->username, $password);
			}
			return TRUE;
		}
		return FALSE;
	}

	public static function ChangePassword(string $password, string $new1, string $new2)
	{
		if ($new1 !== $new2) {
			throw new \InvalidArgumentException('Passwords do not match');
		}
		if (!Security::CheckPassword($password)) {
			throw new \InvalidArgumentException('Existing password is incorrect');
		}
		$conf = Config::Get();
		Security::SetCredentials($conf->username, $new1);
	}

	public static function SetCredentials(string $username, string $password)
	{
		if (empty($username)) {
			throw new \InvalidArgumentException('Username cannot be empty');
		}
		if (empty($password)) {
			throw new \InvalidArgumentException('Username cannot be empty');
		}

		$hash = password_hash($password, Security::$algo, Security::$algo_params);
		if (!$hash) {
			throw new \Exception('Password hashing failed');
		}
		$conf = Config::Get();
		$conf->username = strtolower($username);
		$conf->password = $hash;
		Config::Set($conf);
	}
}
