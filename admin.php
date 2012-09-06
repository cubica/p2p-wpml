<?php
class P2P_WPML_Admin {
	// WP constants
	const SETTINGS_GROUP = 'p2p_wpml_settings';
	const SYNCHRONIZE_OPTION_NAME = 'p2p_wpml_synchronize';
	const SYNCHRONIZE_METADATA_OPTION_NAME = 'p2p_wpml_synchronize_metadata';
	const FILTER_OPTION_NAME = 'p2p_wpml_filter';
	const DEFAULT_SYNCHRONIZE = '0';
	const DEFAULT_SYNCHRONIZE_METADATA = '0';
	const DEFAULT_FILTER = '1';
	const WEBSITE_URL = 'https://github.com/cubica/p2p-wpml';
	
	public static function init() {
		add_action('admin_menu', array(__CLASS__, 'admin_menu'));
	}
	
	public static function admin_menu() {
		$wpmlMenuPage = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH).'/menu/languages.php');
		add_submenu_page($wpmlMenuPage, 'WPML - Posts 2 Posts', 'Posts 2 Posts', 'manage_options', __FILE__, array(__CLASS__, 'submenu_page'));
		
		// call register settings function
		add_action( 'admin_init', array(__CLASS__, 'register_settings') );
	}
	
	public static function register_settings() {
		register_setting( self::SETTINGS_GROUP, self::SYNCHRONIZE_OPTION_NAME );
		register_setting( self::SETTINGS_GROUP, self::SYNCHRONIZE_METADATA_OPTION_NAME );
		register_setting( self::SETTINGS_GROUP, self::FILTER_OPTION_NAME );

		add_settings_section('p2p-wpml-section', 'P2P WPML Settings', array(__CLASS__, 'get_settings_section_text'), __FILE__);
		add_settings_field(self::SYNCHRONIZE_OPTION_NAME, 'Synchronize connections between translations', array(__CLASS__, 'create_synchronize_setting_field'), __FILE__, 'p2p-wpml-section');
		add_settings_field(self::SYNCHRONIZE_METADATA_OPTION_NAME, 'Synchronize connection metadata between translations', array(__CLASS__, 'create_synchronize_metadata_setting_field'), __FILE__, 'p2p-wpml-section');
		add_settings_field(self::FILTER_OPTION_NAME, 'Filter connectable items by current language', array(__CLASS__, 'create_filter_setting_field'), __FILE__, 'p2p-wpml-section');
	}
	
	public static function get_settings_section_text() {
		echo sprintf(__('Here you can configure the integration options between WPML and Posts 2 Posts. See <a href="%s" target="_blank">here</a> for documentation.', 'p2p_wpml'), self::WEBSITE_URL);
	}
	
	public static function create_synchronize_setting_field() {
		echo self::create_checkbox_setting_field(self::SYNCHRONIZE_OPTION_NAME, self::shouldSynchronize());
	}
	
	public static function create_synchronize_metadata_setting_field() {
		echo self::create_checkbox_setting_field(self::SYNCHRONIZE_METADATA_OPTION_NAME, self::shouldSynchronizeMetadata());
	}
	
	public static function create_filter_setting_field() {
		echo self::create_checkbox_setting_field(self::FILTER_OPTION_NAME, self::shouldFilter());
	}
	
	private static function create_checkbox_setting_field($optionName, $value) {
		$checked = $value?' checked="checked"':'';
		return '<input type="hidden" value="0" name="' . $optionName . '" /><input type="checkbox" name="' . $optionName . '" value="1"' . $checked . ' />';
	}
	
	public static function submenu_page() {
		?>
<div class="wrap">
	<div class="icon32" id="icon-options-general"><br /></div>
	<h2>WPML - Posts 2 Posts</h2>
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
	
	public static function shouldSynchronize() {
		return get_option(self::SYNCHRONIZE_OPTION_NAME, self::DEFAULT_SYNCHRONIZE) === '1';
	}
	
	public static function shouldSynchronizeMetadata() {
		return get_option(self::SYNCHRONIZE_METADATA_OPTION_NAME, self::DEFAULT_SYNCHRONIZE_METADATA) === '1';
	}
	
	public static function shouldFilter() {
		return get_option(self::FILTER_OPTION_NAME, self::DEFAULT_FILTER) === '1';
	}
}