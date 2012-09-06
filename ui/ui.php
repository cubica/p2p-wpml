<?php

class P2P_WPML_UI {
	public static function init() {
		add_action('admin_init', array(__CLASS__, 'admin_init'));
	}
	
	public static function admin_init() {
		if(P2P_WPML_Admin::shouldFilter()) add_action( 'add_meta_boxes', array(__CLASS__, 'register_js') );
	}
	
	public static function register_js() {
		wp_enqueue_script( 'p2p-wpml-admin', plugins_url( 'ui.js', __FILE__ ), array( 'p2p-box' ), '0.8', true );
	}
}