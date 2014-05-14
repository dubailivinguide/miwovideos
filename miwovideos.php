<?php
/*
Plugin Name: MiwoVideos
Plugin URI: http://miwisoft.com
Description: MiwoVideos allows you to turn your site into a professional looking video-sharing website with features similar to YouTube.
Author: Miwisoft LLC
Version: 1.0.2
Author URI: http://miwisoft.com
Plugin URI: http://miwisoft.com/wordpress-plugins/miwovideos-share-your-videos
*/

defined('ABSPATH') or die('MIWI');

if (!class_exists('MWordpress')) {
    require_once(dirname(__FILE__) . '/wordpress.php');
}

final class MVideos extends MWordpress {

    public function __construct() {
	    if (!defined('MURL_MIWOVIDEOS')) {
		    define('MURL_MIWOVIDEOS', plugins_url('', __FILE__));
	    }

        parent::__construct('miwovideos', '33.0002');
    }

	public function initialise() {
		$miwi = MPATH_WP_CNT.'/miwi/initialise.php';

		if (!file_exists($miwi)) {
			return false;
		}

		require_once($miwi);

		$this->app = MFactory::getApplication();

		$this->app->initialise();

		require_once(MPATH_WP_PLG.'/miwovideos/admin/library/miwovideos.php');
		$user_plg = MiwoVideos::getPlugin('miwovideos', 'user');

		add_action('profile_update', array($user_plg, 'OnUserAfterSave'), 10, 2);
		add_action('user_register', array($user_plg, 'OnUserAfterSave'));
		add_action('deleted_user', array($user_plg, 'OnUserAfterDelete'), 10, 2);
	}
}

$mvideos = new MVideos();

register_activation_hook(__FILE__, array($mvideos, 'activate'));
register_deactivation_hook(__FILE__, array($mvideos, 'deactivate'));