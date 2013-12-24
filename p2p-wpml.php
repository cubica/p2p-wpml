<?php
/*
Plugin Name: Posts 2 Posts - WPML integration
Description: A plugin for integrating Posts 2 Posts and WPML, solving some inconsistencies
Version: 1.2.5
Author: Lorenzo Carrara
Author URI: http://www.cubica.eu
*/

class P2P_WPML {

    public static function run () {

        // checks that WPML is installed and activated
        if( ! defined('ICL_SITEPRESS_VERSION') ) return;

        // init our plugin on the p2p_init action hook
        if(isset($_REQUEST['icl_ajx_action']) && function_exists('_p2p_init') ){
            add_action('init', array(__CLASS__, 'early_init') );
        }
        else {
            add_action('p2p_init', array(__CLASS__, 'init'), 14 );
        }

        // shows admin notices
        add_action('admin_notices', array(__CLASS__,'admin_notices') );

    }
	
	public static function init() {

		$basePath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
		require_once $basePath . 'ui' . DIRECTORY_SEPARATOR . 'ui.php';
		require_once $basePath . 'synchronizer.php';
		require_once $basePath . 'admin.php';
				
		// initialize classes
		P2P_WPML_UI::init();
		P2P_WPML_Synchronizer::init();
		P2P_WPML_Admin::init();

        do_action('p2p_wmpl_init');
	}

    public static function early_init () {
        _p2p_init();
        self::init();
    }

    public static function admin_notices () {

        // display only on plugins page
        if( $GLOBALS['hook_suffix'] !== 'plugins.php' ) return;

        // could init our plugin, no notice to display
        if( did_action('p2p_wmpl_init') ) return;

        // wpml not installed and activated
        if( ! defined('ICL_SITEPRESS_VERSION') )
            $message = sprintf( __('Posts 2 Posts - WPML integration plugin is enabled but not effective. It requires <a href="%s" target="_blank">WPML</a> in order to work.', 'p2p_wpml'), 'http://wpml.org/' );

        // p2p not installed and activated
        else
            $message = sprintf( __('Posts 2 Posts - WPML integration plugin is enabled but not effective. It requires <a href="%s" target="_blank">Posts 2 Posts</a> in order to work.', 'p2p_wpml'), 'http://wordpress.org/plugins/posts-to-posts/' );
        ?>
        <div class="message error">
            <p><?php echo $message; ?></p>
        </div>
    <?php

    }
}
add_action('plugins_loaded', array('P2P_WPML', 'run'));
