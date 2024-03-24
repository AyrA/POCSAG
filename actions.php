<?php

require_once('validator.php');
require_once('api.php');
require_once('config.php');
require_once('session.php');
require_once('security.php');

class Actions
{
	public static function InitForm(): bool
	{
		$un = Validator::Username($_POST['username'] ?? '');
		$pw1 = Validator::Password($_POST['password1'] ?? '');
		$pw2 = $_POST['password2'] ?? '';

		if ($pw1 !== $pw2) {
			throw new InvalidArgumentException('Passwords do not match');
		}

		if (Config::Has()) {
			return FALSE;
		}
		Security::SetCredentials($un, $pw1);
		return TRUE;
	}

	public static function LoginForm(): bool
	{
		$un = $_POST['username'] ?? NULL;
		$pw = $_POST['password'] ?? NULL;

		if (empty($un) || empty($pw)) {
			return FALSE;
		}
		if (Security::CheckCredentials($un, $pw)) {
			Session::SignIn($un);
			return TRUE;
		}
		throw new InvalidArgumentException('Invalid username and/or password');
	}

	public static function LogoutForm(): bool
	{
		return Session::SignOut();
	}

	public static function AddApiForm(): bool
	{
		Session::EnsureSignedIn();
		return !!API::CreateKey();
	}

	public static function DelApiForm(): bool
	{
		Session::EnsureSignedIn();
		$id = $_POST['key'] ?? FALSE;
		if (!$id) {
			return FALSE;
		}
		return API::DeleteKey($id);
	}

	public static function PistarForm(): bool
	{
		Session::EnsureSignedIn();
		$conf = Config::Get();
		$conf->pistar->host = Validator::Host($_POST['host'] ?? '');
		$conf->pistar->port = Validator::Port(intval($_POST['port']));
		$hostkey = $_POST['hostkey'] ?? '';
		if (strlen($hostkey) === 0) {
			$conf->pistar->DetectHostKey();
		} else {
			$conf->pistar->hostkey = Validator::HostKey($hostkey);
		}
		$conf->pistar->user = Validator::PistarUser($_POST['username'] ?? '');
		//Update password only if it has changed
		if (($_POST['password'] ?? '') !== ($_POST['pwtemp'] ?? '')) {
			$conf->pistar->pass = Validator::PistarPassword($_POST['password'] ?? '');
		}
		$instance = $conf->pistar->GetPocsagInstance();
		if (!$instance->CheckCredentials()) {
			throw new InvalidArgumentException('Username and/or password are invalid');
		}
		Config::Set($conf);
		return TRUE;
	}

	public static function TimeForm(): bool
	{
		Session::EnsureSignedIn();

		$tz = $_POST['timezone'] ?? '';

		if (!empty($tz)) {
			$tz = Validator::TimeZone($tz);
		}
		$conf = Config::Get();
		$pocsag = $conf->pistar->GetPocsagInstance();
		$old = date_default_timezone_get();
		date_default_timezone_set($tz);
		try {
			$pocsag->SendTimeSignal();
		} finally {
			date_default_timezone_set($old);
		}
		return TRUE;
	}

	public static function PocsagForm(): bool
	{
		Session::EnsureSignedIn();
		$conf = Config::Get();
		$pocsag = $conf->pistar->GetPocsagInstance();
		$pocsag->SendMessage($_POST['ric'] ?? '', $_POST['message'] ?? '');
		return TRUE;
	}

	public static function AddrForm(): bool
	{
		Session::EnsureSignedIn();
		$conf = Config::Get();
		$ric = Validator::Ric($_POST['ric'] ?? '');
		$name = $_POST['name'] ?? '';
		$mode = strtolower($_POST['action'] ?? '');
		if ($mode !== 'delete') {
			$name = Validator::Name($name);
		}
		switch ($mode) {
			case 'add':
				$conf->addresses->Add($ric, $name);
				Config::Set($conf);
				return TRUE;
			case 'edit':
				$ret = $conf->addresses->Edit($ric, $name);
				Config::Set($conf);
				return $ret;
			case 'delete':
				$ret = $conf->addresses->Delete($ric);
				Config::Set($conf);
				return $ret;
		}
		return FALSE;
	}

	public static function ChangePasswordForm(): bool
	{
		Session::Ensure();
		$pw = $_POST['password'] ?? '';
		$pw1 = Validator::Password($_POST['password1'] ?? '');
		$pw2 = $_POST['password2'] ?? '';

		Security::ChangePassword($pw, $pw1, $pw2);
		return FALSE;
	}

	public static function ReinitForm(): bool
	{
		Session::Ensure();
		$pw = $_POST['password'] ?? '';
		if (!Security::CheckPassword($pw)) {
			throw new InvalidArgumentException('Password is invalid');
		}
		return Config::Delete();
	}

	public static function HandleAPI(): bool
	{
		if (empty($_GET['mode'])) {
			return FALSE;
		}

		$key = Validator::ApiKey($_GET['key'] ?? '');
		$mode = $_GET['mode'] ?? '';
		$conf = Config::Get();
		if ($conf->HasKey($key)) {
			$conf->GetKey($key)->UseKey();
			Config::Set($conf);
		} else {
			throw new InvalidArgumentException('Invalid API key');
		}

		switch (strtolower($mode)) {
			case 'send':
				$pocsag = $conf->pistar->GetPocsagInstance();
				$ric = Validator::Ric($_GET['ric'] ?? '');
				$msg = Validator::Message($_GET['message'] ?? '');
				$pocsag->SendMessage($ric, $msg);
				return TRUE;
			case '':
				return FALSE;
			default:
				throw new InvalidArgumentException('Invalid API mode');
		}
	}
}
