<?php

namespace PiStar;

use Validator;

require_once('exceptions.php');
require_once('validator.php');

/** Provides POCSAG functionality on a pi-star device */
class POCSAG
{
	private readonly string $ssh_host;
	private readonly int $ssh_port;
	private readonly string $ssh_username;
	private readonly string $ssh_password;
	private readonly string|null $ssh_hostkey;

	/** Values to force modern cryptography in SSH */
	private const array SSH_OPT = array(
		'hostkey' => 'ssh-ed25519,ssh-rsa,ssh-dss',
		'kex'     => 'diffie-hellman-group-exchange-sha256',
		'client_to_server' => array(
			'crypt' => 'aes256-ctr,aes192-ctr,aes256-cbc,aes192-cbc',
			'comp'  => 'none',
			'mac'   => 'hmac-sha2-512,hmac-sha2-256'
		),
		'server_to_client' => array(
			'crypt' => 'aes256-ctr,aes192-ctr,aes256-cbc,aes192-cbc',
			'comp'  => 'none',
			'mac'   => 'hmac-sha2-512,hmac-sha2-256'
		)
	);

	/** Constructor
	 * @param string $host    SSH host name or IP address
	 * @param int    $port    SSH port number
	 * @param string $user    SSH username
	 * @param string $pass    SSH passwords
	 * @param string $hostkey SSH SHA1 host key hash. Skips key check if NULL
	 */
	public function __construct(string $host, int $port, string $user, string $pass, string|null $hostkey)
	{
		if (empty($host)) {
			throw new \InvalidArgumentException('SSH host value cannot be empty');
		}
		if ($port < 1 || $port > 0xFFFF) {
			throw new \InvalidArgumentException('SSH port value is out of range');
		}
		if (empty($user)) {
			throw new \InvalidArgumentException('SSH username cannot be empty');
		}
		if (empty($pass)) {
			throw new \InvalidArgumentException('SSH password cannot be empty');
		}
		if (isset($hostkey) && !preg_match('#^[\dA-F]{40}$#i', $hostkey)) {
			throw new \InvalidArgumentException('If not null, the SSH host key must be 40 hexadecimal characters');
		}
		$this->ssh_host = $host;
		$this->ssh_port = $port;
		$this->ssh_username = $user;
		$this->ssh_password = $pass;
		$this->ssh_hostkey = $hostkey;
	}

	private static function p(string|array $line): void
	{
		//Remove the code below to make this library silent
		if (is_array($line)) {
			$line = implode(PHP_EOL, $line);
		}
		echo "$line" . PHP_EOL;
	}

	private function Connect()
	{
		$originalConnectionTimeout = ini_get('default_socket_timeout');
		ini_set('default_socket_timeout', 5);
		$conn = @ssh2_connect($this->ssh_host, $this->ssh_port, POCSAG::SSH_OPT);
		ini_set('default_socket_timeout', $originalConnectionTimeout);
		if (!$conn) {
			throw new ConnectException($this->ssh_host, $this->ssh_port);
		}
		return $conn;
	}

	private function CheckKey($conn)
	{
		$key = ssh2_fingerprint($conn, SSH2_FINGERPRINT_SHA1 | SSH2_FINGERPRINT_HEX);
		if (strtoupper($key) !== strtoupper($this->ssh_hostkey)) {
			ssh2_disconnect($conn);
			throw new KeyException($this->ssh_hostkey, $key);
		}
	}

	/** Connect to SSH host and get host key hash of this instance. No authentication is performed */
	public function GetHostKey(): string
	{
		$hex = NULL;
		$conn = $this->Connect();
		$hex = strtoupper(ssh2_fingerprint($conn, SSH2_FINGERPRINT_SHA1 | SSH2_FINGERPRINT_HEX));
		ssh2_disconnect($conn);
		return $hex;
	}

	/** Checks if the given credentials are valid. Also checks host key */
	public function CheckCredentials()
	{
		$conn = $this->Connect();
		$this->CheckKey($conn);
		$auth = @ssh2_auth_password($conn, $this->ssh_username, $this->ssh_password);
		ssh2_disconnect($conn);
		return $auth;
	}

	/** Send a message to the given RIC */
	public function SendMessage(int $ric, string $message): void
	{
		//Ensure RIC is valid
		Validator::Ric($ric);
		Validator::Message($message);

		$message = escapeshellarg($message);
		$conn = $this->Connect();
		$this->CheckKey($conn);
		$auth = ssh2_auth_password($conn, $this->ssh_username, $this->ssh_password);
		if (!$auth) {
			ssh2_disconnect($conn);
			throw new AuthenticationException();
		}
		$cmd = ssh2_exec($conn, "RemoteCommand 7642 page $ric $message");
		POCSAG::p(stream_get_contents($cmd));
		fclose($cmd);
		ssh2_disconnect($conn);
	}

	/** Sends a standard time message to RIC 224 */
	public function SendTimeSignal($time = NULL): void
	{
		if ($time === NULL) {
			$time = time();
		} else if (!is_float($time) && !is_int($time)) {
			throw new \Exception('Invalid time argument. If specified, must be number, but is ' . gettype($time));
		}
		$dt = date('ymdHis', $time);
		//Using "YYYY" but supplying the year with two digits is correct
		$this->SendMessage('224', "YYYYMMDDHHMMSS$dt");
	}

	/** Runs in CLI mode */
	public function Cli(): void
	{
		$inCli = TRUE;
		while ($inCli) {
			POCSAG::p([
				'[1] Send time signal',
				'[2] Send message',
				'[Q] Quit'
			]);
			$menu = readline('[1/2/Q]: ');
			//End of I/O stream. Caused by CTRL+Z on Windows or CTRL+D on Linux
			if ($menu === FALSE) {
				return;
			}
			switch (strtoupper($menu)) {
				case '1':
					try {
						POCSAG::p($this->SendTimeSignal() ? 'OK' : 'ERR');
					} catch (\Exception $ex) {
						POCSAG::p('Unable to send time message. Error: ' . $ex->getMessage());
					}
					break;
				case '2':
					$ric = readline('RIC: ');
					$msg = readline('MSG: ');
					if ($ric === FALSE || $msg === FALSE) {
						$inCli = FALSE;
					} else {
						try {
							POCSAG::p($this->SendMessage($ric, $msg) ? 'OK' : 'ERR');
						} catch (\Exception $ex) {
							POCSAG::p('Unable to send message. Error: ' . $ex->getMessage());
						}
					}
					break;
				case 'Q':
					$inCli = FALSE;
					break;
				default:
					POCSAG::p('Invalid option');
					break;
			}
		}
	}
}
