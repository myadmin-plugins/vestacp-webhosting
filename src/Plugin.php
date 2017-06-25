<?php

namespace Detain\MyAdminVestacp;

use Detain\Vestacp\Vestacp;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Vestacp Webhosting';
	public static $description = 'Allows selling of Vestacp Server and VPS License Types.  More info at https://www.netenberg.com/vestacp.php';
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
		];
	}

	public static function getActivate(GenericEvent $event) {
		$service = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_WEB_VESTA) {
			myadmin_log(self::$module, 'info', 'VestaCP Activation', __LINE__, __FILE__);
			$event->stopPropagation();
		}
	}

	public static function getReactivate(GenericEvent $event) {
		$service = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_WEB_VESTA) {
			$serviceInfo = $service->getServiceInfo();
			$settings = get_module_settings(self::$module);
			$serverdata = get_service_master($serviceInfo[$settings['PREFIX'].'_server'], self::$module);
			$hash = $serverdata[$settings['PREFIX'].'_key'];
			$ip = $serverdata[$settings['PREFIX'].'_ip'];
			$success = TRUE;
			list($user, $pass) = explode(':', $hash);
			myadmin_log(self::$module, 'info', 'VestaCP Reactivation', __LINE__, __FILE__);
			require_once(INCLUDE_ROOT . '/webhosting/VestaCP.php');
			$vesta = new \VestaCP($ip, $user, $pass);
			myadmin_log(self::$module, 'info', "Calling vesta->unsuspend_account({$serviceInfo[$settings['PREFIX'] . '_username']})", __LINE__, __FILE__);
			if ($vesta->unsuspend_account($serviceInfo[$settings['PREFIX'] . '_username'])) {
				myadmin_log(self::$module, 'info', 'Success, Response: ' . json_encode($vesta->response), __LINE__, __FILE__);
			} else {
				myadmin_log(self::$module, 'info', 'Failure, Response: ' . json_encode($vesta->response), __LINE__, __FILE__);
				$success = FALSE;
			}
			$event->stopPropagation();
		}
	}

	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_WEB_VESTA) {
			$service = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$vestacp = new Vestacp(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', "IP Change - (OLD:".$service->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $vestacp->editIp($service->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'VestaCP editIp('.$service->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $service->get_ip());
				$service->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_vestacp', 'icons/database_warning_48.png', 'ReUsable Vestacp Licenses');
			$menu->add_link(self::$module, 'choice=none.vestacp_list', 'icons/database_warning_48.png', 'Vestacp Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.vestacp_licenses_list', 'whm/createacct.gif', 'List all Vestacp Licenses');
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
		$loader->add_requirement('class.Vestacp', '/../vendor/detain/vestacp-webhosting/src/Vestacp.php');
		$loader->add_requirement('vps_add_vestacp', '/vps/addons/vps_add_vestacp.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_website_vesta_server', 'Default VestaCP Setup Server', NEW_WEBSITE_VESTA_SERVER, SERVICE_TYPES_WEB_VESTA);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_webhosting_vestacp', 'Out Of Stock VestaCP Webhosting', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_WEBHOSTING_VESTACP'), array('0', '1'), array('No', 'Yes',));
	}

}
