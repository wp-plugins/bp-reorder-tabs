<?php 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'BuddyPress_Reorder_Tabs_Plugin' ) ):
/**
 *
 * BuddyBoss Reorder Tabs Plugin Main Controller
 * **************************************
 *
 *
 */
class BuddyPress_Reorder_Tabs_Plugin {
	/**
	 * Default options for the plugin, the strings are
	 * run through localization functions during instantiation,
	 * and after the user saves options the first time they
	 * are loaded from the DB.
	 *
	 * @var array
	 */
	private $default_options = array(
		'profile'	=> array(
			'default'		=> '',
			'config_data'	=> '',
		),
		'groups'	=> array(
			'default'		=> '',
			'config_data'	=> '',
		)
	);
	
	/**
	 * This options array is setup during class instantiation, holds
	 * default and saved options for the plugin.
	 *
	 * @var array
	 */
	public $options = array();
	
	/**
	 * Just a random string.
	 * This is appended to a single groups url while querying to save group nav info into db.
	 * The operation is only performed if this key is detected in url.
	 * 
	 * @var string 
	 */
	private $secret = 'yuYmn_erin2356UY';
	
	/**
	 * Main BP Reorder Tabs Instance.
	 *
	 * Insures that only one instance of this class exists in memory at any
	 * one time. Also prevents needing to define globals all over the place.
	 *
	 * @since BP Reorder Tabs (1.0.0)
	 *
	 * @static object $instance
	 * @uses BuddyBoss_Edit_Activity::setup_actions() Setup the hooks and actions.
	 * @uses BuddyBoss_Edit_Activity::setup_textdomain() Setup the plugin's language file.
	 * @see buddyboss_edit_activity()
	 *
	 * @return object BuddyBoss_Edit_Activity
	 */
	public static function instance(){
		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been run previously
		if ( null === $instance )
		{
			$instance = new BuddyPress_Reorder_Tabs_Plugin();
			$instance->setup_globals();
			$instance->setup_actions();
			$instance->setup_textdomain();
		}

		// Always return the instance
		return $instance;
	}
	
	/* Magic Methods
	 * ===================================================================
	 */
	private function __construct() { /* Do nothing here */ }

	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'bp-reorder-tabs' ), '1.0.0' ); }

	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'bp-reorder-tabs' ), '1.0.0' ); }

	public function __isset( $key ) { return isset( $this->data[$key] ); }

	public function __get( $key ) { return isset( $this->data[$key] ) ? $this->data[$key] : null; }

	public function __set( $key, $value ) {
		
		if ( !empty ($this->data[$key]) ) {
			$this->data[$key] = $value;
		}
		
	}

	public function __unset( $key ) { if ( isset( $this->data[$key] ) ) unset( $this->data[$key] ); }

	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }
	
	/**
	 * Setup BP Reorder Tabs plugin global variables.
	 *
	 * @since BP Reorder Tabs (1.0.0)
	 * @access private
	 */
	private function setup_globals(){
		// DEFAULT CONFIGURATION OPTIONS
		$default_options = $this->default_options;

		$saved_options = get_option( 'b_r_t_plugin_options' );
		$saved_options = maybe_unserialize( $saved_options );

		$this->options = wp_parse_args( $saved_options, $default_options );
		
		$group_settings = $this->option( 'groups' );
		if( isset( $group_settings['default'] ) && !empty( $group_settings['default'] ) ){
			if( !defined( 'BP_GROUPS_DEFAULT_EXTENSION' ) ){
				define( 'BP_GROUPS_DEFAULT_EXTENSION', $group_settings['default'] );
			}
		}
		
		$profile_settings = $this->option( 'profile' );
		if( isset( $profile_settings['default'] ) && !empty( $profile_settings['default'] ) ){
			if( !defined( 'BP_DEFAULT_COMPONENT' ) ){
				define( 'BP_DEFAULT_COMPONENT', $profile_settings['default'] );
			}
		}
	}
	
	private function setup_actions(){
		if ( ( is_admin() || is_network_admin() ) && current_user_can( 'manage_options' ) ){
			$this->load_admin();
		}
		
		// Hook into BuddyPress init
		add_action( 'bp_loaded', array( $this, 'bp_loaded' ) );
		add_action( 'wp_before_admin_bar_render', array($this, 'reorder_wp_menus'), 9999 );
		
	}
	
	/**
	 * Include required admin files.
	 *
	 * @since BP Reorder Tabs (1.0.0)
	 * @access private
	 */
	private function load_admin(){
		require_once( BUDDYBOSS_REORDER_TABS_PLUGIN_DIR . 'includes/admin.php' );

		$this->admin = BuddyPress_Reorder_Tabs_Admin::instance();
	}
	
	/**
	 * Load plugin text domain
	 *
	 * @since BP Reorder Tabs (1.0.0)
	 *
	 * @uses sprintf() Format .mo file
	 * @uses get_locale() Get language
	 * @uses file_exists() Check for language file
	 * @uses load_textdomain() Load language file
	 */
	public function setup_textdomain(){
		$domain = 'bp-reorder-tabs';
		$locale = apply_filters('plugin_locale', get_locale(), $domain);
		
		//first try to load from wp-contents/languages/plugins/ directory
		load_textdomain($domain, WP_LANG_DIR.'/plugins/'.$domain.'-'.$locale.'.mo');
		
		//if not found, then load from bp-reorder-tabs/languages/ directory
		load_plugin_textdomain( $domain, false, 'bp-reorder-tabs/languages' );
	}
	
	/**
	 * We require BuddyPress to run the main components, so we attach
	 * to the 'bp_loaded' action which BuddyPress calls after it's started
	 * up. This ensures any BuddyPress related code is only loaded
	 * when BuddyPress is active.
	 *
	 * @since BP Reorder Tabs (1.0.0)
	 *
	 * @return void
	 */
	public function bp_loaded(){
		add_action( 'bp_setup_nav',				array( $this, 'change_profile_tab_order' ), 999 );
		/**
		 * Actions bp_setup_nav or groups_setup_nav don't work for group navs.
		 * Let's hook late.
		 */
		add_action( 'template_redirect',		array( $this, 'change_groups_tab_order' ) );
		add_action( 'bp_setup_nav',				array( $this, 'change_subnavs' ), 999 );
		
		add_action( 'bp_group_options_nav',		array( $this, 'save_group_navs_info' ) );
		
		add_filter( 'bp_r_t_my_group_url',		array( $this, 'url_add_secret' ) );
	}
	
	public function change_profile_tab_order(){
		$bp = buddypress();
		
		$profile_settings = $this->option( 'profile' );
		
		// those, whose position has been specified.
		$navs_defined = array();
		$last_nav_position = 0;
		
		if( isset( $profile_settings['config_data'] ) && !empty( $profile_settings['config_data'] ) ){
			$config = (array)$profile_settings['config_data'];
			foreach( $config as $nav => $nav_settings ){
				$nav_settings = (array)$nav_settings;
				/**
				 * set position starting from 10.
				 * so first nav has position 10, second has 11 and so on
				 */
				if( isset( $bp->bp_nav[$nav] ) ){
					$bp->bp_nav[$nav]['position'] = 10 + (int)$nav_settings['position'];
					$last_nav_position = (int)$nav_settings['position'];
					$navs_defined[] = $nav;
				}
			}
		}
		
		$last_nav_position += 10;
		//now put all the remaining at the end
		foreach( $bp->bp_nav as $bp_nav=>$bp_nav_props ){
			if( !in_array( $bp_nav, $navs_defined ) ){
				$last_nav_position++;
				$bp->bp_nav[$bp_nav]['position'] = $last_nav_position;
			}
		}
		
		//change subnav order
		if( bp_displayed_user_id() ){
			if( isset( $bp->bp_options_nav[bp_current_action()] ) ){
				
			}
		}
	}
	
	public function change_groups_tab_order(){
		$bp = buddypress();
		
		if( 'groups' !=  bp_current_component() || ! bp_is_single_item() )
			return;
		
		if( bp_displayed_user_id() )
			return;
		
		$the_index = bp_current_item();
		if( !$the_index )
			return;
		
		$profile_settings = $this->option( 'groups' );
		
		// those, whose position has been specified.
		$navs_defined = array();
		$last_nav_position = 0;
		
		if( isset( $bp->bp_options_nav[$the_index] ) && isset( $profile_settings['config_data'] ) && !empty( $profile_settings['config_data'] ) ){
			$group_navs = (array)$profile_settings['config_data'];
			$reordered_group_navs = array();
			
			foreach( $group_navs as $nav=>$nav_props ){
				if( '' == $nav )
					continue;

				$nav_props = (array) $nav_props;
				$reordered_group_navs[$nav_props['position']] = $nav;
			}
			
			if( !empty( $reordered_group_navs ) ){
				ksort( $reordered_group_navs );
				foreach( $reordered_group_navs as $nav ){
					$nav_settings = (array) $group_navs[$nav];
					/**
					 * set position starting from 10.
					 * so first nav has position 10, second has 11 and so on
					*/
					$bp->bp_options_nav[$the_index][$nav]['position'] = 10 + (int)$nav_settings['position'];
					$last_nav_position = (int)$nav_settings['position'];
					$navs_defined[] = $nav;
				}
			}
		}
		
		$last_nav_position += 10;
		//now put all the remaining at the end
		foreach( $bp->bp_options_nav[$the_index] as $bp_nav=>$bp_nav_props ){
			if( !in_array( $bp_nav, $navs_defined ) ){
				$last_nav_position++;
				$bp->bp_options_nav[$the_index][$bp_nav]['position'] = $last_nav_position;
			}
		}
	}
	
	public function change_subnavs(){
		$bp = buddypress();
		
		$profile_settings = $this->option( 'profile' );
		
		if( isset( $profile_settings['config_data'] ) && !empty( $profile_settings['config_data'] ) ){
			$topnavs = (array)$profile_settings['config_data'];
			foreach( $topnavs as $topnav=>$topnav_settings ){
				
				if( isset( $topnav_settings->subnavs ) && !empty( $topnav_settings->subnavs ) ){
					$subnavs = (array) $topnav_settings->subnavs;
				
					foreach( $subnavs as $subnav=>$subnav_props ){
						
						if( isset( $bp->bp_options_nav[$topnav] ) ){
							foreach( $bp->bp_options_nav[$topnav] as $index=>$opt_nav ){
								if( $opt_nav['slug']==$subnav ){
									$bp->bp_options_nav[$topnav][$index]['position'] = 10 + $subnav_props->position;
								}
							}
						}
					}
				}
			}
		}
	}
	
	function url_add_secret( $url ){
		if( $url ){
			$url = esc_url(add_query_arg( 'secret', $this->secret, $url ));
		}
		return $url;
	}
	
	/**
	 * Apparantely, there is no direct way of determining what all nav items will be displayed on a group page.
	 * 
	 * So we'll hook into this action and save the nav items in db for later use.
	 * 
	 * @since 1.0.0
	 */
	public function save_group_navs_info(){
		if( !isset( $_GET['secret'] ) || $_GET['secret'] != $this->secret )
			return;
		
		$bp = buddypress();
		
		if ( ! bp_is_single_item() ) 
			return;
		/**
		 * get all nav items for a single group
		 */
		$group_navs = array();
		if ( !isset( $bp->bp_options_nav[bp_current_item()] ) || count( $bp->bp_options_nav[bp_current_item()] ) < 1 ) {
			return false;
		} else {
			$the_index = bp_current_item();
		}

		// Loop through each navigation item
		foreach ( (array) $bp->bp_options_nav[$the_index] as $subnav_item ) {
			$item = array(
				'name'		=> $subnav_item['name'],
				'position'	=> $subnav_item['position'],
			);
			
			$group_navs[$subnav_item['slug']] = $item;
		}
		
		//override positions with settings saved previously
		$saved_options = $this->options;
		$config_data = $saved_options['groups']['config_data'];
		if( !empty( $config_data ) ){
			foreach( $config_data as $s_nav=>$s_nav_props ){
				$s_nav_props = (array)$s_nav_props;
				if( isset( $s_nav_props['position'] ) ){
					if( isset( $group_navs[$s_nav] ) )
						$group_navs[$s_nav]['position'] = $s_nav_props['position'];
				}
			}
		}
		
		$saved_options['groups']['config_data'] = $group_navs;
		
		update_option( 'b_r_t_plugin_options', $saved_options );
	}
	
	/**
	 * Convenience function to access plugin options, returns false by default
	 */
	public function option( $key ){
		$key    = strtolower( $key );
		$option = isset( $this->options[$key] )
		        ? $this->options[$key]
		        : null;

		// Apply filters on options as they're called for maximum
		// flexibility. Options are are also run through a filter on
		// class instatiation/load.
		// ------------------------

		// This filter is run for every option
		$option = apply_filters( 'b_r_t_plugin_option', $option );

		// Option specific filter name is converted to lowercase
		$filter_name = sprintf( 'b_r_t_plugin_option_%s', strtolower( $key  ) );
		$option = apply_filters( $filter_name,  $option );

		return $option;
	}
	
	/**
	 * REORDER WORDPRESS ADMIN-BAR ITEMS
	 *
	 * @since BuddyBoss Reorder tabs 1.0.0
	 */
	public function reorder_wp_menus() {
		
		global $wp_admin_bar;
		
		$profile_settings = $this->option( 'profile' );
		$nav_data_arr = array();
		
		if ( isset( $profile_settings[ 'config_data' ] ) && ! empty( $profile_settings[ 'config_data' ] ) ) {
			$config = ( array ) $profile_settings[ 'config_data' ];
			
			//Loop for removing all the nodes
			foreach ( $config as $nav => $nav_settings ) {

				if ( 'profile' == $nav ) {
					
					$nav_data_arr[ $nav ] = $wp_admin_bar->get_node( 'my-account-x' . $nav );
					$wp_admin_bar->remove_node( 'my-account-x' . $nav );
					
				} else {
				
					$nav_data_arr[ $nav ] = $wp_admin_bar->get_node( 'my-account-' . $nav );
					$wp_admin_bar->remove_node( 'my-account-' . $nav );
				}
			}
			//Loop for adding the reordered node
			foreach ( $config as $nav => $nav_settings ) {
				
				$wp_admin_bar->add_menu( array(
					'parent' => $nav_data_arr[$nav]->parent,
					'id' => $nav_data_arr[$nav]->id,
					'title' => $nav_data_arr[$nav]->title,
					'href' => $nav_data_arr[$nav]->href
				) );
				
			}
			
		}	
		
	}
	
}

endif;