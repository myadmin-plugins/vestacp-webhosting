<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_vestacp define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Vestacp Webhosting',
	'description' => 'Allows selling of Vestacp Server and VPS License Types.  More info at https://www.netenberg.com/vestacp.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a vestacp license. Allow 10 minutes for activation.',
	'module' => 'webhosting',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-vestacp-webhosting',
	'repo' => 'https://github.com/detain/myadmin-vestacp-webhosting',
	'version' => '1.0.0',
	'type' => 'service',
	'hooks' => [
		/*'function.requirements' => ['Detain\MyAdminVestacp\Plugin', 'Requirements'],
		'webhosting.settings' => ['Detain\MyAdminVestacp\Plugin', 'Settings'],
		'webhosting.activate' => ['Detain\MyAdminVestacp\Plugin', 'Activate'],
		'webhosting.change_ip' => ['Detain\MyAdminVestacp\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminVestacp\Plugin', 'Menu'] */
	],
];
