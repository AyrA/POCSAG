<?php

require_once('config.php');

class API
{
	public static function UseApiKey($key): bool
	{
		if (API::CheckApiKey($key)) {
			$conf = Config::Get();
			$conf->GetKey($key)->UseKey();
			Config::Set($conf);
			return TRUE;
		}
		return FALSE;
	}

	public static function CheckApiKey(string $apikey): bool
	{
		$conf = Config::Get();
		return $conf->HasKey($apikey);
	}

	public static function DeleteKey(string $key): bool
	{
		$conf = Config::Get();
		if ($conf->DelKey($key)) {
			Config::Set($conf);
			return TRUE;
		}
		return FALSE;
	}

	public static function CreateKey(): ApiKey
	{
		$conf = Config::Get();
		$key = $conf->NewKey();
		Config::Set($conf);
		return $key;
	}
}
