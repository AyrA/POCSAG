<?php

require_once('pocsag.php');

class Config
{
	/** 
	 * Pramble used to safely store the config value.
	 * Usually a PHP start tag with a line comment to ensure
	 * someone with HTTP access to the file will not see the contents
	 */
	private const string PREAMBLE = '<?php //';
	/**
	 * File where the configuration is stored.
	 * Must not be set to a file with meaningful contents inside because it will be overwritten
	 */
	private const string FILENAME = __DIR__ . '/settings.php';

	private static ?ConfigValues $settings = NULL;

	/** Checks if a saved config exists */
	public static function Has(): bool
	{
		return is_file(Config::FILENAME);
	}

	/** Gets config values from file */
	public static function Get(): ConfigValues
	{
		if (Config::$settings) {
			return Config::$settings;
		}
		if (!is_file(Config::FILENAME)) {
			return Config::Default();
		}
		$file = file(Config::FILENAME);
		if (is_array($file)) {
			foreach ($file as $line) {
				$pos = stripos($line, Config::PREAMBLE);
				if ($pos !== FALSE) {
					$line = substr($line, $pos + strlen(Config::PREAMBLE));
					try {
						return Config::$settings = unserialize($line);
					} catch (\TypeError $e) {
						return Config::$settings = Config::Default();
					}
				}
			}
		}
		return Config::Default();
	}

	/** Save config values to file */
	public static function Set(ConfigValues $config)
	{
		if (empty($config)) {
			throw new \InvalidArgumentException('Value cannot be null');
		}
		file_put_contents(Config::FILENAME, Config::PREAMBLE . serialize($config));
		Config::$settings = $config;
	}

	/** Deletes all config and resets the application to uninitialized state */
	public static function Delete(): bool
	{
		return unlink(Config::FILENAME);
	}

	/** Get the default config */
	public static function Default(): ConfigValues
	{
		return new ConfigValues();
	}
}

class ConfigValues
{
	public PiStarConfig $pistar;
	public AddressBook $addresses;
	public string $username;
	public string $password;
	private array $apikeys;

	public function __construct()
	{
		$this->pistar = new PiStarConfig();
		$this->apikeys = [];
		$this->addresses = new AddressBook();
	}

	/** Add a new API key. Will not autosave */
	public function NewKey(): ApiKey
	{
		$apiKey = new ApiKey();
		$this->apikeys[$apiKey->key] = $apiKey;
		return $apiKey;
	}

	/** Gets an API key. Will not autosave */
	public function GetKey($key): ApiKey
	{
		Validator::ApiKey($key);
		if (isset($this->apikeys[$key])) {
			return $this->apikeys[$key];
		}
		throw new \InvalidArgumentException('Key not found');
	}

	/** Checks if a given API key exists */
	public function HasKey($key): bool
	{
		Validator::ApiKey($key);
		if (isset($this->apikeys[$key])) {
			return TRUE;
		}
		return FALSE;
	}

	/** Gets all API keys */
	public function GetKeys(): array
	{
		return array_keys($this->apikeys);
	}

	/** Delete an API key. Will not autosave */
	public function DelKey(string $key): bool
	{
		Validator::ApiKey($key);
		if (isset($this->apikeys[$key])) {
			unset($this->apikeys[$key]);
			return TRUE;
		}
		return FALSE;
	}

	public function __wakeup()
	{
		if (empty($this->pistar)) {
			$this->pistar = new PiStarConfig();
		}
		if (empty($this->addresses)) {
			$this->addresses = new AddressBook();
		}
	}
}

class AddressBook
{
	private array $addresses;

	public function __construct()
	{
		$this->addresses = [];
	}

	public function __wakeup(): void
	{
		if (empty($this->addresses)) {
			$this->addresses = [];
		}
	}

	public function Add(int $ric, string $name): Address
	{
		Validator::Ric($ric);
		Validator::Name($name);
		foreach ($this->addresses as $addr) {
			if ($addr->GetRic() === $ric) {
				throw new InvalidArgumentException('Duplicate RIC. Address book already contains an entry named ' . $addr->GetName() . ' with the same RIC');
			}
		}
		$addr = new Address($ric, $name);
		$this->addresses[] = $addr;
		return $addr;
	}

	public function Edit($ric, $name): bool
	{
		foreach ($this->addresses as $addr) {
			if ($addr->GetRic() === $ric) {
				$addr->SetName($name);
				return TRUE;
			}
		}
		return FALSE;
	}

	public function Delete($ric)
	{
		$index = -1;
		foreach ($this->addresses as $k => $addr) {
			if ($addr->GetRic() === $ric) {
				$index = $k;
				break;
			}
		}
		if ($index >= 0) {
			array_splice($this->addresses, $index, 1, []);
		}
		return $index >= 0;
	}

	public function GetAll(): array
	{
		return array_merge($this->addresses, []);
	}
}

class Address
{
	private int $ric;
	private string $name;

	public function __construct(int $ric, string $name)
	{
		Validator::Ric($ric);
		Validator::Name($name);
		$this->ric = $ric;
		$this->name = $name;
	}

	public function GetRic(): int
	{
		return $this->ric;
	}

	public function GetName(): string
	{
		return $this->name;
	}

	public function SetName($name)
	{
		Validator::Name($name);
		$this->name = $name;
	}
}

class ApiKey
{
	public string $key;
	public int $created;
	public ?int $lastused;

	public function __construct()
	{
		$this->key = ApiKey::Guid();
		$this->created = time();
		$this->lastused = NULL;
	}

	public static function Guid(): string
	{
		$data = random_bytes(16);

		$data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // set version to 0100
		$data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // set bits 6-7 to 10

		return strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
	}

	public function __wakeup()
	{
		if (empty($this->lastused)) {
			$this->lastused = NULL;
		}
	}

	public function UseKey()
	{
		$this->lastused = time();
	}
}

class PiStarConfig
{
	public string $host;
	public int $port;
	public string $user;
	public string $pass;
	public string $hostkey;

	public function __construct()
	{
		$this->host = '';
		$this->port = 22;
		$this->user = 'pi-star';
		$this->pass = 'raspberry';
		$this->hostkey = '0123456789ABCDEF0123456789ABCDEF01234567';
	}

	public function DetectHostKey(): void
	{
		$pager = new PiStar\POCSAG($this->host, $this->port, 'ignored', 'ignored', NULL);
		$this->hostkey = $pager->GetHostKey();
	}

	public function GetPocsagInstance(): PiStar\POCSAG
	{
		return new PiStar\POCSAG($this->host, $this->port, $this->user, $this->pass, $this->hostkey);
	}
}
