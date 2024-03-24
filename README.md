# Pi-Star POCSAG Interface

This is a PHP project that allows you to send POCSAG messages with your pi-star MMDVM device.

## Requirements

- An MMDVM device with the RemoteCommand and POCSAG enabled
- Webserver running PHP with the SSH2 module enabled

## Preparing Pi-Star

To use the POCSAG manager you need to configure your pi-star as follows:

In the general configuration, enable POCSAG and configure the frequency you want to run it on.
The fields relating to DAPNET can be left as-is if you don't have/want DAPNET integration.

In the expert section you need to enable the remote command. To reach expert mode, click on "Configuration" in the top menu of the main page,
then click "Expert" in the same menu.
Now click "MMDVMHost" in the same menu again.
Towards the bottom you will find a section named "Remote Control" (Use `CTRL`+`F` to find it easier).
In that section, set the following values:

- Enable: `1`
- Port: `7642`
- Address: `127.0.0.1`

Then click "Apply Changes", and afterwards restart your device.

## Preparing PHP

The SSH2 module is not enabled by default in PHP.

### Linux

Installation and enabling modules differs between Linux variants.
Use the search engine of your choice to find the operations required to install and enable it.
It usually boils down to two commands, one to install it from your package manager and one to enable the module itself.

### Windows

For Windows, you can [download the DLL from PECL](https://pecl.php.net/package/ssh2) and put it into the "ext" folder of your PHP installation.
DLLs are currently no longer built automatically. If you need a newer version because you're using PHP 8.3, you can download it from github:
[jhanley-com/php-ssh2-windows](https://github.com/jhanley-com/php-ssh2-windows/blob/70bd3341186668858e270e20df5b235cfb806fa3/PHP_8.3/vs16-x64-nts/php_ssh2.dll)
(Click on the download icon on the right side of the page).
Enable it by adding `extension=ssh2` to the php.ini file (other lines like this should already be present, it's best to add your line there).

## Installing the Pager Manager

Simply copy all files to your webserver, then access `index.php` to create your credentials.
The configuration will be saved in `settings.php`, and deleting the file will revert the installation back into a blank state.

The minimum configuration you have to do is set the IP address of your pi-star device, and clear the host key field.
The username and password don't need changing unless you changed them on the pi-star.

The host key will be autodetected when you save the settings without explicitly specifying a key yourself.
