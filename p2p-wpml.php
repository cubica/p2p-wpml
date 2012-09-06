<?php
/*
Plugin Name: Posts 2 Posts - WPML integration
Description: A plugin for integrating Posts 2 Posts and WPML, solving some inconsistencies
Version: 1.2
Author: Lorenzo Carrara, Twinpictures, Baden03
Author URI: http://www.cubica.eu
*/

class P2P_WPML {
	private static $REQUIRED_PLUGINS = array(
		'sitepress-multilingual-cms/sitepress.php',
		'posts-to-posts/posts-to-posts.php'
	);
	
	public static function init() {
		if(self::checkRequiredPlugins()) {
			$basePath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
			require_once $basePath . 'ui' . DIRECTORY_SEPARATOR . 'ui.php';
			require_once $basePath . 'synchronizer.php';
			require_once $basePath . 'admin.php';
				
			// initialize classes
			P2P_WPML_UI::init();
			P2P_WPML_Synchronizer::init();
			P2P_WPML_Admin::init();
		}
		else {
			add_action('admin_init', array(__CLASS__, 'add_missing_plugins_warning'));
		}
	}
	
	public static function add_missing_plugins_warning() {
		echo '<div class="error"><p><strong>P2P-WPML: in order to use this plugin, both WPML and Posts to Posts must be installed and activated</p></div>';
	}
	
	private static function checkRequiredPlugins() {
		if(!function_exists('is_plugin_active')) include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		foreach(self::$REQUIRED_PLUGINS as $plugin) {
			if(!is_plugin_active($plugin)) return false;
		} 
		
		return true;
	}
}

add_action('init', array('P2P_WPML', 'init'));