<?php

namespace Detain\MyAdminVestaCP;

use Detain\MyAdminVestaCP\VestaCP;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'VestaCP Webhosting';
	public static $description = 'Allows selling of VestaCP Server and VPS License Types.  More info at https://www.netenberg.com/vestacp.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a vestacp license. Allow 10 minutes for activation.';
	public static $module = 'webhosting';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.reactivate' => [__CLASS__, 'getReactivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getTerminate'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_WEB_VESTA) {
			myadmin_log(self::$module, 'info', 'VestaCP Activation', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$serverdata = get_service_master($serviceClass->getServer(), self::$module);
			$hash = $serverdata[$settings['PREFIX'].'_key'];
			$ip = $serverdata[$settings['PREFIX'].'_ip'];
			$hostname = $serviceClass->getHostname();
			if (trim($hostname) == '')
				$hostname = $serviceClass->getId().'.server.com';
			$password = website_get_password($serviceClass->getId());
			$username = get_new_webhosting_username($serviceClass->getId(), $hostname, $serviceClass->getServer());
			$data = $GLOBALS['tf']->accounts->read($serviceClass->getCustid());
			list($user, $pass) = explode(':', $hash);
			myadmin_log(self::$module, 'info', "Calling vesta = new VestaCP($ip, $user, ****************)", __LINE__, __FILE__);
			$vesta = new VestaCP($ip, $user, $pass);
			$package = 'default';
			myadmin_log(self::$module, 'info', "Calling vesta->createAccount({$username}, ****************, {$event['email']}, {$data['name']}, {$package})", __LINE__, __FILE__);
			if ($vesta->createAccount($username, $password, $event['email'], $data['name'], $package)) {
				request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'vesta', 'createAccount', array('username' => $username, 'password' => $password, 'email' => $event['email'], 'name' => $data['name'], 'package' => $package), $vesta->response);
				myadmin_log(self::$module, 'info', 'Success, Response: '.var_export($vesta->response, TRUE), __LINE__, __FILE__);
				$ip = $serverdata[$settings['PREFIX'].'_ip'];
				$db = get_module_db(self::$module);
				$username = $db->real_escape($username);
				$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_ip='$ip', {$settings['PREFIX']}_username='{$username}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
				function_requirements('website_welcome_email');
				website_welcome_email($serviceClass->getId());
				$event['success'] = TRUE;
			} else {
				request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'vesta', 'createAccount', array('username' => $username, 'password' => $password, 'email' => $event['email'], 'name' => $data['name'], 'package' => $package), $vesta->response);
				add_output('Error Creating Website');
				myadmin_log(self::$module, 'info', 'Failure, Response: '.var_export($vesta->response, TRUE), __LINE__, __FILE__);
				$event['success'] = FALSE;
			}
			$event->stopPropagation();
		}
	}

	public static function getReactivate(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_WEB_VESTA) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$serverdata = get_service_master($serviceClass->getServer(), self::$module);
			$hash = $serverdata[$settings['PREFIX'].'_key'];
			$ip = $serverdata[$settings['PREFIX'].'_ip'];
			list($user, $pass) = explode(':', $hash);
			myadmin_log(self::$module, 'info', 'VestaCP Reactivation', __LINE__, __FILE__);
			$vesta = new VestaCP($ip, $user, $pass);
			myadmin_log(self::$module, 'info', "Calling vesta->unsuspendAccount({$serviceClass->getUsername()})", __LINE__, __FILE__);
			if ($vesta->unsuspendAccount($serviceClass->getUsername())) {
				myadmin_log(self::$module, 'info', 'Success, Response: '.json_encode($vesta->response), __LINE__, __FILE__);
			} else {
				myadmin_log(self::$module, 'info', 'Failure, Response: '.json_encode($vesta->response), __LINE__, __FILE__);
				$event['success'] = FALSE;
			}
			$event->stopPropagation();
		}
	}

	public static function getDeactivate(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_WEB_VESTA) {
			myadmin_log(self::$module, 'info', 'VestaCP Deactivation', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$serviceTypes = run_event('get_service_types', FALSE, self::$module);
			$settings = get_module_settings(self::$module);
			$event->stopPropagation();
		}
	}

	public static function getTerminate(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_WEB_VESTA) {
			myadmin_log(self::$module, 'info', 'VestaCP Termination', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$serviceTypes = run_event('get_service_types', FALSE, self::$module);
			$settings = get_module_settings(self::$module);
			$event->stopPropagation();
		}
	}

	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_WEB_VESTA) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$vestacp = new VestaCP(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', "IP Change - (OLD:".$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $vestacp->editIp($serviceClass->getIp(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'VestaCP editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getIp());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_vestacp', 'icons/database_warning_48.png', 'ReUsable VestaCP Licenses');
			$menu->add_link(self::$module, 'choice=none.vestacp_list', 'icons/database_warning_48.png', 'VestaCP Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.vestacp_licenses_list', 'whm/createacct.gif', 'List all VestaCP Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('crud_vestacp_list', '/../vendor/detain/crud/src/crud/crud_vestacp_list.php');
		$loader->add_requirement('crud_reusable_vestacp', '/../vendor/detain/crud/src/crud/crud_reusable_vestacp.php');
		$loader->add_requirement('get_vestacp_licenses', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp.inc.php');
		$loader->add_requirement('get_vestacp_list', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp.inc.php');
		$loader->add_requirement('vestacp_licenses_list', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp_licenses_list.php');
		$loader->add_requirement('vestacp_list', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp_list.php');
		$loader->add_requirement('get_available_vestacp', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp.inc.php');
		$loader->add_requirement('activate_vestacp', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp.inc.php');
		$loader->add_requirement('get_reusable_vestacp', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp.inc.php');
		$loader->add_requirement('reusable_vestacp', '/../vendor/detain/myadmin-vestacp-webhosting/src/reusable_vestacp.php');
		$loader->add_requirement('class.VestaCP', '/../vendor/detain/vestacp-webhosting/src/VestaCP.php');
		$loader->add_requirement('vps_add_vestacp', '/vps/addons/vps_add_vestacp.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_website_vesta_server', 'Default VestaCP Setup Server', NEW_WEBSITE_VESTA_SERVER, SERVICE_TYPES_WEB_VESTA);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_webhosting_vestacp', 'Out Of Stock VestaCP Webhosting', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_WEBHOSTING_VESTACP'), array('0', '1'), array('No', 'Yes',));
	}

}
