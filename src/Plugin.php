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
		];
	}

	public static function Activate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log('licenses', 'info', 'Vestacp Activation', __LINE__, __FILE__);
			function_requirements('activate_vestacp');
			activate_vestacp($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$vestacp = new Vestacp(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $vestacp->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Vestacp editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_vestacp', 'icons/database_warning_48.png', 'ReUsable Vestacp Licenses');
			$menu->add_link($module, 'choice=none.vestacp_list', 'icons/database_warning_48.png', 'Vestacp Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.vestacp_licenses_list', 'whm/createacct.gif', 'List all Vestacp Licenses');
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
		$settings->add_text_setting('licenses', 'Vestacp', 'vestacp_username', 'Vestacp Username:', 'Vestacp Username', $settings->get_setting('FANTASTICO_USERNAME'));
		$settings->add_text_setting('licenses', 'Vestacp', 'vestacp_password', 'Vestacp Password:', 'Vestacp Password', $settings->get_setting('FANTASTICO_PASSWORD'));
		$settings->add_dropdown_setting('licenses', 'Vestacp', 'outofstock_licenses_vestacp', 'Out Of Stock Vestacp Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_FANTASTICO'), array('0', '1'), array('No', 'Yes', ));
	}

}
