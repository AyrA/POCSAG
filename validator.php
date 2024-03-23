<?php

require_once('exceptions.php');

class Validator
{
	public const int MinRic = 1;
	public const int MaxRic = 0xFFFFFF;
	public const int MaxMessageLength = 80;
	public const int MaxNameLength = 50;
	public const int MinPasswordLength = 8;
	public const int MinPasswordComplexityScore = 3;
	public const int MinPortNumber = 1;
	public const int MaxPortNumber = 0xFFFF;
	public const int HostKeyHashHexLength = 40;

	public static function PistarUser($user): string
	{
		if (!preg_match('#^[a-z][-\w]*\$?$#i', $user)) {
			throw new InvalidArgumentException('Username is not a valid linux user name. It must be alphanumeric, must start with a letter a-z, and may also contain \'_\'');
		}
		return trim($user);
	}

	public static function PistarPassword($pass): string
	{
		if (empty($pass)) {
			throw new InvalidArgumentException('Password cannot be empty');
		}
		return $pass;
	}

	public static function HostKey($hostkey): string
	{
		if (!preg_match('#^[\da-f]{' . Validator::HostKeyHashHexLength . '}$#i', $hostkey)) {
			throw new InvalidArgumentException('Host key hash is invalid. Must be exactly ' . Validator::HostKeyHashHexLength . ' hexadecimal characters');
		}
		return trim($hostkey);
	}

	public static function Host($host): string
	{
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return trim($host);
		}
		if (filter_var($host, FILTER_VALIDATE_DOMAIN)) {
			return trim($host);
		}
		throw new InvalidArgumentException('Host is neither a valid IP address nor domain name');
	}

	public static function Port($port): int
	{
		if (!is_numeric($port)) {
			throw new InvalidArgumentException('Port must be a number');
		}
		$port = intval($port);
		if ($port < Validator::MinPortNumber || $port > Validator::MaxPortNumber) {
			throw new InvalidArgumentException('Port number must be in range ' . Validator::MinPortNumber . ' to ' . Validator::MaxPortNumber . ' inclusive');
		}
		return $port;
	}

	public static function Username($un): string
	{
		if (empty($un) || !preg_match('#^\w+$#', $un)) {
			throw new InvalidArgumentException('Username must be alphanumeric');
		}
		return trim($un);
	}

	public static function Password($pw): string
	{
		if (!is_string($pw)) {
			throw new InvalidArgumentException('Password must be a string');
		}
		if (mb_strlen($pw) < Validator::MinPasswordLength) {
			throw new InvalidArgumentException('Password must be at least ' . Validator::MinPasswordLength . ' characters long');
		}
		$count = 0;
		$count += preg_match('#[a-z]#', $pw) ? 1 : 0;
		$count += preg_match('#[A-Z]#', $pw) ? 1 : 0;
		$count += preg_match('#\d#', $pw) ? 1 : 0;
		$count += preg_match('#[^a-zA-Z\d]#', $pw) ? 1 : 0;
		if ($count < Validator::MinPasswordComplexityScore) {
			throw new InvalidArgumentException('Password must contain ' . Validator::MinPasswordComplexityScore . ' out of the 4 categories: Uppercase A-Z, lowercase a-z, digit 0-9, other symbol');
		}
		return $pw;
	}

	public static function ApiKey($key): string
	{
		if (!is_string($key)) {
			throw new InvalidArgumentException('API key must be a string');
		}
		if (!preg_match('#^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}$#i', $key)) {
			throw new InvalidArgumentException('API key is not in the correct format');
		}
		return trim($key);
	}

	public static function Ric($ric): int
	{
		if (!is_numeric($ric) || $ric < Validator::MinRic || $ric > Validator::MaxRic) {
			throw new InvalidArgumentException('Pager RIC must be from ' . Validator::MinRic . ' to ' . Validator::MaxRic . ' inclusive');
		}
		return intval($ric);
	}

	public static function Name($name): string
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		if (empty($name)) {
			throw new InvalidArgumentException('Name cannot be empty');
		}
		if (mb_strlen($name) > Validator::MaxNameLength) {
			throw new InvalidArgumentException('Name cannot be longer than ' . Validator::MaxNameLength . ' characters');
		}
		return $name;
	}

	public static function Message($message): string
	{
		if (!is_string($message)) {
			throw new InvalidArgumentException('Message must be a string');
		}
		//Remove trailing spaces as they're useless.
		$message=rtrim($message);
		if (empty($message)) {
			throw new InvalidArgumentException('Message cannot be empty');
		}
		if (!mb_check_encoding($message, 'ASCII')) {
			throw new InvalidArgumentException("Message must consist of only US-ASCII encodable characters");
		}
		if (strlen($message) > Validator::MaxMessageLength) {
			throw new InvalidArgumentException('Message cannot be longer than ' . Validator::MaxMessageLength . ' characters');
		}
		return $message;
	}
}
