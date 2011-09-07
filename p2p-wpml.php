<?php
/*
Plugin Name: Posts 2 Posts - WPML integration
Description: A plugin for integrating Posts 2 Posts and WPML, solving some inconsistencies
Version: 1.0
Author: Lorenzo Carrara <lorenzo.carrara@cubica.eu>
Author URI: http://www.cubica.eu
*/

function p2p_wpml_init() {
	$basePath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
	require_once $basePath . 'ui' . DIRECTORY_SEPARATOR . 'ui.php';
	require_once $basePath . 'synchronizer.php';
	require_once $basePath . 'admin.php';
	
	// initialize classes
	P2P_WPML_UI::init();
	P2P_WPML_Synchronizer::init();
	P2P_WPML_Admin::init();
}

add_action('init', 'p2p_wpml_init');