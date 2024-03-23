<?php
//TODO: '192.168.1.54', 22, 'pi-star', 'raspberry', '97E5E348E920DB90656394863ED2F37A7933B10E'

require_once('config.php');
require_once('pocsag.php');

//Running on command line
if (PHP_SAPI === 'cli') {
	if(!Config::Has()){
		throw new \Exception('Application is not yet configured');
	}
	$conf = Config::Get();
	if (!$conf['cli_enabled']) {
		throw new \Exception('CLI has been disabled in settings');
	}
	//Run with command line arguments first
	if (!empty($argv) && count($argv) > 1) {
		if (count($argv) > 2 && !in_array('/?', $argv)) {
			$pistar = new PiStar\POCSAG($conf['pistar_host'], $conf['pistar_port'], $conf['pistar_user'], $conf['pistar_pass'], $conf['pistar_hash']);
			$pistar->SendMessage($argv[1], $argv[2]);
		} else {
			echo basename(__FILE__) . ' <ric> <message>' . PHP_EOL;
		}
	} else { //Run in CLI mode
		$pistar = new PiStar\POCSAG($conf['pistar_host'], $conf['pistar_port'], $conf['pistar_user'], $conf['pistar_pass'], $conf['pistar_hash']);
		$pistar->Cli();
	}
}
