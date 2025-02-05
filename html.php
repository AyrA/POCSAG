<?php

require_once('csrf.php');

class HTML
{
	private static bool $inSection = FALSE;

	private static function FormElement(string $name, string $type, string|null $mode, string|null $value = NULL, array $attr = [])
	{
		if ($value === NULL) {
			$value = (!empty($mode) && $mode === HTML::GetPostMode()) ? ($_POST[$name] ?? '') : '';
		}
		$attr['name'] = $name;
		$attr['type'] = $type;
		$attr['value'] = $value;
		$attrs = [];
		foreach ($attr as $attrname => $attrvalue) {
			$attrs[] = HTML::HE($attrname) . '="' . HTML::HE($attrvalue) . '"';
		}
		return '<input ' . implode(' ', $attrs) . ' />';
	}

	private static function ModeField(string $mode): string
	{
		return HTML::FormElement('mode', 'hidden', NULL, $mode);
	}

	public static function Section(string $title, bool|string|array $open = FALSE): string
	{
		if (is_string($open)) {
			$open = [$open];
		}
		if (is_array($open)) {
			$isOpen = FALSE;
			$mode = Session::GetMode();
			foreach ($open as $str) {
				$isOpen = $isOpen || $str === $mode;
			}
			echo "<!-- $mode -->";
			echo "<!-- " . implode(' ', $open) . " -->";
			$open = $isOpen;
		}
		if (!is_bool($open)) {
			throw new InvalidArgumentException('$open argument must be string, array of strings, or a boolean');
		}
		$ret = HTML::EndSection();
		$ret .= '<details' . ($open ? ' open' : '') . '>';
		$ret .= '<summary>' . HTML::HE($title) . '</summary>';

		HTML::$inSection = TRUE;
		return $ret;
	}

	public static function EndSection(): string
	{
		if (HTML::$inSection) {
			HTML::$inSection = FALSE;
			return '</details>';
		}
		return '';
	}

	public static function Head(): string
	{
		$time = filemtime('style.css');
		return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8" /><title>Pager Manager</title><link rel="stylesheet" href="style.css?' . $time . '" /></head><body>';
	}

	public static function Error(string $e): string
	{
		if (empty($e)) {
			return '';
		}
		return '<div class="error">' . HTML::HE($e) . '</div>';
	}

	public static function HE(string $data): string
	{
		return htmlspecialchars($data, ENT_HTML5 | ENT_SUBSTITUTE | ENT_QUOTES);
	}

	public static function H(int $level, string $content): string
	{
		$content = HTML::HE($content);
		return "<h$level>$content</h$level>";
	}

	public static function P(string $content, $raw = FALSE): string
	{
		return '<p>' . ($raw ? $content : HTML::HE($content)) . '</p>';
	}

	public static function Footer(): string
	{
		return HTML::EndSection() . '<p class="footer">Copyright &copy; 2024 - HB9HZK, Kevin Gut</p></body></html>';
	}

	public static function TimeZoneField(): string
	{
		$zones = [];
		foreach (timezone_identifiers_list() as $tz) {
			$parts = explode('/', $tz, 2);
			if (count($parts) < 2) {
				continue;
			}
			$zones[$parts[0]][] = $parts[1];
		}
		$ret = '<select name="timezone"><option value="">No (Use server default: ' . HTML::HE(date_default_timezone_get()) . ')</option>';
		foreach ($zones as $zone => $values) {
			$ret .= '<optgroup label="' . HTML::HE($zone) . '">';
			foreach ($values as $entry) {
				$ret .= '<option value="' . HTML::HE($zone) . '/' . HTML::HE($entry) . '">' . HTML::HE($entry) . '</option>';
			}
			$ret .= '</optgroup>';
		}
		return "$ret</select>";
	}

	public static function LoginForm(): string
	{
		return '<form method="post">' . CSRF::GetFormElement() .
			HTML::ModeField('login') .
			'<table><tr><td>Username</td><td>' . HTML::FormElement('username', 'text', 'login') . '</td></tr>' .
			'<tr><td>Password</td><td>' . HTML::FormElement('password', 'password', 'login', '') . '</td></tr></table>' .
			'<input type="submit" value="Login" /></form>';
	}

	public static function LogoutForm(): string
	{
		return '<form method="post">' . CSRF::GetFormElement() . HTML::ModeField('logout') .
			'<input type="submit" value="Logout" /></form>';
	}

	public static function AddApiForm(): string
	{
		return '<form method="post">' . CSRF::GetFormElement() . HTML::ModeField('addapi') .
			'<input type="submit" value="Add API Token" /></form>';
	}

	public static function EditApiForm(): string
	{
		$conf = Config::Get();
		$ret = '';
		foreach ($conf->GetKeys() as $key) {
			$value = $conf->GetKey($key);
			$ret .= '<form method="post">' . CSRF::GetFormElement() . HTML::ModeField('delapi') . HTML::FormElement('key', 'hidden', NULL, $key) .
				'<code>' . HTML::HE($key) . '</code><br />' .
				'<span>Created: ' . HTML::HE(date('Y-m-d H:i:s', $value->created)) . '</span><br />' .
				'<span>Last use: ' . HTML::HE($value->lastused ? date('Y-m-d H:i:s', $value->lastused) : '<never>') . '</span><br />' .
				'<input type="submit" value="Delete" /></form>';
		}
		return $ret;
	}

	public static function InitForm(): string
	{
		return '<form method="post">' . CSRF::GetFormElement() . HTML::ModeField('init') .
			'<table><tr><td>Username</td><td>' . HTML::FormElement('username', 'text', 'init') . '</td></tr>' .
			'<tr><td>New password</td><td>' . HTML::FormElement('password1', 'password', '', '') . '</td></tr>' .
			'<tr><td>New password</td><td>' . HTML::FormElement('password2', 'password', '', '') . '</td></tr></table>' .
			'<input type="submit" value="Initialize" /></form>';
	}

	public static function PistarForm(): string
	{
		$conf = Config::Get();
		$inmode = HTML::GetPostMode() === 'pistar';
		$pwtemp = strtoupper(hash_hmac('sha1', $conf->pistar->pass, CSRF::GetToken()));
		return '<form method="post">' . CSRF::GetFormElement() . HTML::ModeField('pistar') .
			HTML::FormElement('pwtemp', 'hidden', '', $pwtemp) .
			'<table>' .
			'<tr><td>Host IP or DNS name</td><td>' . HTML::FormElement('host', 'text', 'pistar', $inmode ? NULL : $conf->pistar->host) . '</td></tr>' .
			'<tr><td>Port</td><td>' . HTML::FormElement('port', 'number', 'pistar', $inmode ? NULL : $conf->pistar->port, ['min' => 1, 'max' => 0xFFFF]) . '</td></tr>' .
			'<tr><td>Host Key SHA1</td><td>' . HTML::FormElement('hostkey', 'text', 'pistar', $inmode ? NULL : $conf->pistar->hostkey) . ' (leave empty to autodetect)</td></tr>' .
			'<tr><td>Username</td><td>' . HTML::FormElement('username', 'text', 'pistar', $inmode ? NULL : $conf->pistar->user) . ' (unless changed, this should be \'pi-star\')</td></tr>' .
			'<tr><td>Password</td><td>' . HTML::FormElement('password', 'password', 'pistar', $inmode ? NULL : $pwtemp) . ' (unless changed, this should be (\'raspberry\')</td></tr>' .
			'</table><input type="submit" value="Save" /></form>';
	}

	public static function PocsagForm(): string
	{
		$conf = Config::Get();
		$list = '<datalist id="riclist">';
		foreach ($conf->addresses->GetAll() as $addr) {
			$ric = HTML::HE($addr->GetRic());
			$name = HTML::HE($addr->GetName());
			$list .= "<option value=\"$ric\">$name ($ric)</option>";
		}
		$list .= '</datalist>';
		return '<form method="post">' . CSRF::GetFormElement() . HTML::ModeField('pocsag') .
			'<table>' .
			'<tr><td>RIC</td><td>' . HTML::FormElement('ric', 'text', 'pocsag', NULL, ['list' => 'riclist']) . '</td></tr>' .
			'<tr><td>Message</td><td>' . HTML::FormElement('message', 'text', 'pocsag', NULL, ['maxlength' => 80]) . '</td></tr></table>' .
			'<input type="submit" value="Send" /></form>' . $list;
	}

	public static function TimeForm(): string
	{
		return '<form method="post">' . CSRF::GetFormElement() . HTML::ModeField('sendtime') .
			'Override Time Zone: ' . HTML::TimeZoneField() . '<br />' .
			'<input type="submit" value="Send Current Time" /></form>';
	}

	public static function AddrForm(): string
	{
		$conf = Config::Get();
		$list = '<table><thead><tr><th>RIC</th><th>Name</th><th>&nbsp;</th></tr></thead><tbody>';
		//Add address form
		$list .=
			'<tr><form method="post"><td>' . CSRF::GetFormElement() . HTML::ModeField('addr') . HTML::FormElement('ric', 'number', 'addr', NULL, ['min' => Validator::MinRic, 'max' => Validator::MaxRic]) . '</td>' .
			'<td>' . HTML::FormElement('name', 'text', 'addr') . '</td>' .
			'<td>' . HTML::FormElement('action', 'submit', 'addr', 'Add') . '</td></form></tr>';

		//Edit address form
		foreach ($conf->addresses->GetAll() as $addr) {
			$ric = HTML::HE($addr->GetRic());
			$name = HTML::HE($addr->GetName());
			$list .=
				'<tr><form method="post"><td>' . CSRF::GetFormElement() . HTML::ModeField('addr') . HTML::FormElement('ric', 'number', 'addr', $ric, ['min' => Validator::MinRic, 'max' => Validator::MaxRic]) . '</td>' .
				'<td>' . HTML::FormElement('name', 'text', 'addr', $name) . '</td>' .
				'<td>' . HTML::FormElement('action', 'submit', 'addr', 'Edit') . HTML::FormElement('action', 'submit', 'addr', 'Delete') . '</td></form></tr>';
		}
		return "$list</table>";
	}

	public static function ChangePasswordForm(): string
	{
		return '<form method="post">' . CSRF::GetFormElement() . HTML::ModeField('changepass') .
			'<table><tr><td>Existing password</td><td>' . HTML::FormElement('password', 'password', 'changepass', '') . '</td></tr>' .
			'<tr><td>New password</td><td>' . HTML::FormElement('password1', 'password', 'changepass', '') . '</td></tr>' .
			'<tr><td>New password</td><td>' . HTML::FormElement('password2', 'password', 'changepass', '') . '</td></tr></table>' .
			'<input type="submit" value="Change Password" /></form>';
	}

	public static function ReinitForm(): string
	{
		return '<form method="post">' . CSRF::GetFormElement() . HTML::ModeField('reinit') .
			'<table><tr><td>Existing password</td><td>' . HTML::FormElement('password', 'password', 'reinit', '') . '</td></tr></table>' .
			'<input type="submit" value="Reset" /></form>';
	}

	/** Gets the form mode field value if the CSRF token validation passes. */
	public static function GetPostMode(): ?string
	{
		if (!CSRF::ValidateToken()) {
			return NULL;
		}
		return $_POST['mode'] ?? NULL;
	}
}
