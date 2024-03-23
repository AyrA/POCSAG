<?php

error_reporting(E_ALL);

require_once('pocsag.php');
require_once('config.php');
require_once('api.php');
require_once('security.php');
require_once('session.php');
require_once('html.php');
require_once('actions.php');

function getUrl()
{
	return $_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? './';
}

function reload()
{
	$url = getUrl();
	header("Location: $url");
	die(0);
}

//Handle API without a session
try {
	if (Actions::HandleAPI()) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['success' => TRUE]);
		die(0);
	}
} catch (\Exception $ex) {
	header('HTTP/1.1 400 Bad Request');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => FALSE, 'error' => $ex->getMessage()]);
	die(0);
}

Session::Start();

//Handle form events
$error = NULL;
try {
	switch (HTML::GetPostMode()) {
		case 'init':
			if (Actions::InitForm()) {
				reload();
			}
			break;
		case 'login':
			if (Actions::LoginForm()) {
				reload();
			};
			break;
		case 'logout':
			if (Actions::LogoutForm()) {
				reload();
			};
			break;
		case 'addapi':
			if (Actions::AddApiForm()) {
				reload();
			};
			break;
		case 'delapi':
			if (Actions::DelApiForm()) {
				reload();
			};
			break;
		case 'pistar':
			if (Actions::PistarForm()) {
				reload();
			};
			break;
		case 'pocsag':
			if (Actions::PocsagForm()) {
				reload();
			};
			break;
		case 'addr':
			if (Actions::AddrForm()) {
				reload();
			};
			break;
		case 'changepass':
			if (Actions::ChangePasswordForm()) {
				reload();
			};
			break;
		case 'reinit':
			if (Actions::ReinitForm()) {
				Session::SignOut();
				reload();
			};
			break;
	}
} catch (\Exception $ex) { //Custom exceptions
	http_response_code(400);
	$error = 'Your input could not be processed due to the following error: ' . $ex->getMessage();
} catch (\Error $e) { //PHP internal errors
	http_response_code(500);
	$error = 'Internal error: ' . $ex->getMessage();
}

echo HTML::Head();

if (Session::IsSignedIn()) {
	echo HTML::LogoutForm();
	echo '<hr />';
	echo HTML::Error($error ?? '');

	echo HTML::H(1, 'Send Message');
	echo HTML::P('Sends a message to the given RIC');
	echo HTML::PocsagForm();

	echo HTML::H(2, 'Program time');
	echo HTML::P('Sends the current date and time to all supported pagers in range.
		The server time must be correct and the time zone set to your local time for this to work,
		because pagers use local time instead of UTC.
		If your timezone is wrong, you can either fix it in php.ini, or override it using the control below.<br />
		Current server date and time: <code>' . HTML::HE(date('Y-m-d H:i')) . '</code><br />Timezone: <code>' . HTML::HE(date_default_timezone_get()) . '</code>', TRUE);
	echo HTML::TimeForm();

	echo HTML::H(2, 'Address book');
	echo HTML::P('Manage addressbook entries. Typing the name into the RIC field of the message sender will show the suggested RIC value (you must select it to apply it)');
	echo HTML::AddrForm();

	echo HTML::H(1, 'Pi-Star');
	echo HTML::P('Configure the Pi-Star parameters here. Don\'t forget to enable the remote command feature on Pi-Star or it will not work.');
	echo HTML::P('Hostkey, username, and password are validated when saving. Make sure pi-star is running and connected to your network.');
	echo HTML::PistarForm();

	echo HTML::H(1, 'API');
	echo HTML::P('Create and delete API keys.');
	echo '<p>API keys can be used to send messages via GET API. Example URL: <code>' . HTML::HE(getUrl()) .
		'?mode=send&key={apikey}?ric={ric}&message={message}</code><br />' .
		'The API response is a JSON object with a boolean "success" property and a string "error" property (if success is false only)</p>';
	echo HTML::AddApiForm();
	echo HTML::EditApiForm();

	echo HTML::H(1, 'Account');
	echo HTML::P('Manage your account details');

	echo HTML::H(2, 'Change password');
	echo HTML::ChangePasswordForm();

	echo HTML::H(2, 'Reinitialize');
	echo HTML::P('This will reset the application to the initial, unconfigured state. This destroys all configuration, ' .
		'API tokens and address book entries');
	echo HTML::ReinitForm();

	echo '<hr />';
	echo HTML::LogoutForm();
} elseif (Config::Has()) {
	echo HTML::Error($error ?? '');
	echo HTML::H(1, 'Login');
	echo HTML::LoginForm();
} else {
	echo HTML::Error($error ?? '');
	echo HTML::H(1, 'Create login');
	echo HTML::P('You\'re using this application for the first time. Please specify a user name and a password to create the initial config. The password can be changed later.');
	echo HTML::InitForm();
}
echo HTML::Footer();
