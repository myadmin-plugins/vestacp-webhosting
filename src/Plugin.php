<?php

namespace Detain\MyAdminVestaCP;

use Detain\MyAdminVestaCP\VestaCP;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminVestaCP
 */
class Plugin
{
    public static $name = 'VestaCP Webhosting';
    public static $description = 'Simple & Clever Hosting Control Panel.  More info at https://vestacp.com/';
    public static $help = '';
    public static $module = 'webhosting';
    public static $type = 'service';

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public static function getHooks()
    {
        return [
            self::$module.'.settings' => [__CLASS__, 'getSettings'],
            self::$module.'.activate' => [__CLASS__, 'getActivate'],
            self::$module.'.reactivate' => [__CLASS__, 'getReactivate'],
            self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
            self::$module.'.terminate' => [__CLASS__, 'getTerminate']
        ];
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @throws \Exception
     * @throws \SmartyException
     */
    public static function getActivate(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('WEB_VESTA')) {
            $serviceClass = $event->getSubject();
            myadmin_log(self::$module, 'info', 'VestaCP Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $settings = get_module_settings(self::$module);
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            $hash = $serverdata[$settings['PREFIX'].'_key'];
            $ip = $serverdata[$settings['PREFIX'].'_ip'];
            $hostname = $serviceClass->getHostname();
            if (trim($hostname) == '') {
                $hostname = $serviceClass->getId().'.server.com';
            }
            $password = website_get_password($serviceClass->getId());
            $username = get_new_webhosting_username($serviceClass->getId(), $hostname, $serviceClass->getServer());
            $data = $GLOBALS['tf']->accounts->read($serviceClass->getCustid());
            [$user, $pass] = explode(':', $hash);
            myadmin_log(self::$module, 'info', "Calling vesta = new VestaCP($ip, $user, ****************)", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $vesta = new VestaCP($ip, $user, $pass);
            $package = 'default';
            myadmin_log(self::$module, 'info', "Calling vesta->createAccount({$username}, ****************, {$event['email']}, {$data['name']}, {$package})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            if ($vesta->createAccount($username, $password, $event['email'], $data['name'], $package)) {
                request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'vesta', 'createAccount', ['username' => $username, 'password' => $password, 'email' => $event['email'], 'name' => $data['name'], 'package' => $package], $vesta->response, $serviceClass->getId());
                myadmin_log(self::$module, 'info', 'Success, Response: '.var_export($vesta->response, true), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $ip = $serverdata[$settings['PREFIX'].'_ip'];
                $db = get_module_db(self::$module);
                $username = $db->real_escape($username);
                $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_ip='{$ip}', {$settings['PREFIX']}_username='{$username}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
                function_requirements('website_welcome_email');
                website_welcome_email($serviceClass->getId());
                $event['success'] = true;
            } else {
                request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'vesta', 'createAccount', ['username' => $username, 'password' => $password, 'email' => $event['email'], 'name' => $data['name'], 'package' => $package], $vesta->response, $serviceClass->getId());
                add_output('Error Creating Website');
                myadmin_log(self::$module, 'info', 'Failure, Response: '.var_export($vesta->response, true), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['success'] = false;
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getReactivate(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('WEB_VESTA')) {
            $serviceClass = $event->getSubject();
            $settings = get_module_settings(self::$module);
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            $hash = $serverdata[$settings['PREFIX'].'_key'];
            $ip = $serverdata[$settings['PREFIX'].'_ip'];
            [$user, $pass] = explode(':', $hash);
            myadmin_log(self::$module, 'info', 'VestaCP Reactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $vesta = new VestaCP($ip, $user, $pass);
            myadmin_log(self::$module, 'info', "Calling vesta->unsuspendAccount({$serviceClass->getUsername()})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            if ($vesta->unsuspendAccount($serviceClass->getUsername())) {
                myadmin_log(self::$module, 'info', 'Success, Response: '.json_encode($vesta->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            } else {
                myadmin_log(self::$module, 'info', 'Failure, Response: '.json_encode($vesta->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['success'] = false;
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getDeactivate(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('WEB_VESTA')) {
            $serviceClass = $event->getSubject();
            myadmin_log(self::$module, 'info', 'VestaCP Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $settings = get_module_settings(self::$module);
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            $hash = $serverdata[$settings['PREFIX'].'_key'];
            $ip = $serverdata[$settings['PREFIX'].'_ip'];
            [$user, $pass] = explode(':', $hash);
            $vesta = new \Detain\MyAdminVestaCP\VestaCP($ip, $user, $pass);
            myadmin_log(self::$module, 'info', "Calling vesta->suspendAccount({$serviceClass->getUsername()})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            if ($vesta->suspendAccount($serviceClass->getUsername())) {
                $event['success'] = true;
                myadmin_log(self::$module, 'info', 'Success, Response: '.json_encode($vesta->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            } else {
                $event['success'] = false;
                myadmin_log(self::$module, 'info', 'Failure, Response: '.json_encode($vesta->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @return bool
     */
    public static function getTerminate(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('WEB_VESTA')) {
            $serviceClass = $event->getSubject();
            myadmin_log(self::$module, 'info', 'VestaCP Termination', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $settings = get_module_settings(self::$module);
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            $hash = $serverdata[$settings['PREFIX'].'_key'];
            $ip = $serverdata[$settings['PREFIX'].'_ip'];
            if (trim($serviceClass->getUsername()) == '') {
                return true;
            }
            [$user, $pass] = explode(':', $hash);
            $vesta = new \Detain\MyAdminVestaCP\VestaCP($ip, $user, $pass);
            myadmin_log(self::$module, 'info', "Calling vesta->suspendAccount({$serviceClass->getUsername()})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            if ($vesta->deleteAccount($serviceClass->getUsername())) {
                myadmin_log(self::$module, 'info', 'Success, Response: '.json_encode($vesta->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                return true;
            } else {
                myadmin_log(self::$module, 'info', 'Failure, Response: '.json_encode($vesta->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                return false;
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getChangeIp(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('WEB_VESTA')) {
            $serviceClass = $event->getSubject();
            $settings = get_module_settings(self::$module);
            $vestacp = new VestaCP(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
            myadmin_log(self::$module, 'info', 'IP Change - (OLD:' .$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $result = $vestacp->editIp($serviceClass->getIp(), $event['newip']);
            if (isset($result['faultcode'])) {
                myadmin_log(self::$module, 'error', 'VestaCP editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['status'] = 'error';
                $event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
            } else {
                $GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getId(), $serviceClass->getCustid());
                $serviceClass->set_ip($event['newip'])->save();
                $event['status'] = 'ok';
                $event['status_text'] = 'The IP Address has been changed.';
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getMenu(GenericEvent $event)
    {
        $menu = $event->getSubject();
        if ($GLOBALS['tf']->ima == 'admin') {
            $menu->add_link(self::$module, 'choice=none.reusable_vestacp', '/images/myadmin/to-do.png', _('ReUsable VestaCP Licenses'));
            $menu->add_link(self::$module, 'choice=none.vestacp_list', '/images/myadmin/to-do.png', _('VestaCP Licenses Breakdown'));
            $menu->add_link(self::$module.'api', 'choice=none.vestacp_licenses_list', '/images/whm/createacct.gif', _('List all VestaCP Licenses'));
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getRequirements(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Plugins\Loader $this->loader
         */
        $loader = $event->getSubject();
        $loader->add_page_requirement('crud_vestacp_list', '/../vendor/detain/crud/src/crud/crud_vestacp_list.php');
        $loader->add_page_requirement('crud_reusable_vestacp', '/../vendor/detain/crud/src/crud/crud_reusable_vestacp.php');
        $loader->add_requirement('get_vestacp_licenses', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp.inc.php');
        $loader->add_requirement('get_vestacp_list', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp.inc.php');
        $loader->add_page_requirement('vestacp_licenses_list', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp_licenses_list.php');
        $loader->add_page_requirement('vestacp_list', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp_list.php');
        $loader->add_requirement('get_available_vestacp', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp.inc.php');
        $loader->add_requirement('activate_vestacp', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp.inc.php');
        $loader->add_requirement('get_reusable_vestacp', '/../vendor/detain/myadmin-vestacp-webhosting/src/vestacp.inc.php');
        $loader->add_page_requirement('reusable_vestacp', '/../vendor/detain/myadmin-vestacp-webhosting/src/reusable_vestacp.php');
//        $loader->add_requirement('class.VestaCP', '/../vendor/detain/myadmin-vestacp-webhosting/src/VestaCP.php', '\\Detain\\MyAdminVestaCP\\');
        $loader->add_page_requirement('vps_add_vestacp', '/vps/addons/vps_add_vestacp.php');
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
        $settings->setTarget('module');
        $settings->add_select_master(_(self::$module), _('Default Servers'), self::$module, 'new_website_vesta_server', _('Default VestaCP Setup Server'), defined('NEW_WEBSITE_VESTA_SERVER') ? NEW_WEBSITE_VESTA_SERVER : '', get_service_define('WEB_VESTA'));
        $settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'outofstock_webhosting_vestacp', _('Out Of Stock VestaCP Webhosting'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_WEBHOSTING_VESTACP'), ['0', '1'], ['No', 'Yes']);
        $settings->setTarget('global');
    }
}
