<?php

namespace PiStar;

/** Exception that is thrown when SSH authentication fails */
class AuthenticationException extends \Exception
{
	public function __construct()
	{
		parent::__construct("SSH Authentication failed");
	}
}

/** Exception that is thrown when a connection to the given SSH host cannot be made */
class ConnectException extends \Exception
{
	public function __construct(string $host, int $port)
	{
		parent::__construct("Connection to $host:$port could not be established");
	}
}

/** Exception that is thrown if the SSH host key hash mismatches the expected value */
class KeyException extends \Exception
{
	/** Actual key hash decoded from SSH session */
	public readonly string $ActualKeyHex;
	/** Expected SSH key hash, hex encoded */
	public readonly string $ExpectedKeyHex;

	public function __construct(string $expectedKeyHex, string $actualKeyHex)
	{
		$this->ActualKeyHex = strtoupper($actualKeyHex);
		$this->ExpectedKeyHex = strtoupper($expectedKeyHex);
		parent::__construct("Server key hash $actualKeyHex does not matches $expectedKeyHex");
	}
}
