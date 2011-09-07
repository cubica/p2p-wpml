<?php
class P2P_WPML_Admin {
	// WP constants
	const SETTINGS_GROUP = 'p2p_wpml_settings';
	const SYNCHRONIZE_OPTION_NAME = 'p2p_wpml_synchronize';
	const FILTER_OPTION_NAME = 'p2p_wpml_filter';
	const DEFAULT_SYNCHRONIZE = '0';
	const DEFAULT_FILTER = '1';
	
	public static function init() {
		add_action('admin_menu', array(__CLASS__, 'admin_menu'));
	}
	
	public static function admin_menu() {
		$wpmlMenuPage = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH).'/menu/languages.php');
		add_submenu_page($wpmlMenuPage, 'Posts 2 Posts', 'WPML - Posts 2 Posts', 'manage_options', __FILE__, array(__CLASS__, 'submenu_page'));
		
		// call register settings function
		add_action( 'admin_init', array(__CLASS__, 'register_settings') );
	}
	
	public static function register_settings() {
		register_setting( self::SETTINGS_GROUP, self::SYNCHRONIZE_OPTION_NAME );
		register_setting( self::SETTINGS_GROUP, self::FILTER_OPTION_NAME );

		add_settings_section('p2p-wpml-section', 'P2P WPML Settings', array(__CLASS__, 'get_settings_section_text'), __FILE__);
		add_settings_field(self::SYNCHRONIZE_OPTION_NAME, 'Synchronize connections between translations', array(__CLASS__, 'create_synchronize_setting_field'), __FILE__, 'p2p-wpml-section');
		add_settings_field(self::FILTER_OPTION_NAME, 'Filter connectable items by current language', array(__CLASS__, 'create_filter_setting_field'), __FILE__, 'p2p-wpml-section');
	}
	
	public static function get_settings_section_text() {
		echo 'Insert the configurations parameters required in order to access the Yoox API';
	}
	
	public static function create_synchronize_setting_field() {
		echo self::create_checkbox_setting_field(self::SYNCHRONIZE_OPTION_NAME, self::DEFAULT_SYNCHRONIZE);
	}
	
	public static function create_filter_setting_field() {
		echo self::create_checkbox_setting_field(self::FILTER_OPTION_NAME, self::DEFAULT_FILTER);
	}
	
	private static function create_checkbox_setting_field($optionName, $defaultValue) {
		$value = get_option($optionName, $defaultValue);
		$checked = ($value == '1')?' checked="checked"':'';
		return '<input type="checkbox" name="' . $optionName . '"' . $checked . ' />';
	}
	
	public static function submenu_page() {
		?>
<div class="wrap">
	<div class="icon32" id="icon-options-general"><br /></div>
	<h2>Yoox API</h2>
	<form method="post" action="options.php">
	    <?php settings_fields( self::SETTINGS_GROUP ); ?>
	    <?php do_settings_sections( __FILE__ ); ?>
	    <p class="submit">
	    	<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
	    </p>
	</form>
</div>
		<?php
	}
}