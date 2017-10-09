<?php
/*
Plugin Name: Worona
Plugin URI: http://www.worona.org/
Description: Turn your WordPress site into a native iOS, Android and Windows Phone App.
Version: 1.1.7
Author: Worona Labs SL
Author URI: http://www.worona.org/
License: GPL v3
Copyright: Worona Labs SL
*/



if( !class_exists('worona') ):

class worona
{
	// vars
	public $plugin_version = '1.1.7';
	public $rest_api_installed 	= false;
	public $rest_api_active 	= false;
	public $rest_api_working	= false;



	/*
	*  Constructor
	*
	*  This function will construct all the neccessary actions, filters and functions for the Worona plugin to work
	*
	*  @type	function
	*  @date	10/06/14
	*  @since	0.6.0
	*
	*  @param	N/A
	*  @return	N/A
	*/

	function __construct()
	{
		// actions
		add_action('init', array($this, 'init'), 1);
		add_action('admin_menu', array($this, 'worona_admin_actions')); //add the admin page
		add_action('admin_init', array($this,'worona_register_settings')); //register the settings
		add_action('admin_notices',array($this,'worona_admin_notices')); //Display the validation errors and update messages

		add_action('wp_ajax_sync_with_worona',array($this,'sync_with_worona'));
		add_action('wp_ajax_worona_change_siteid',array($this,'change_siteid_ajax'));
		add_action('wp_ajax_worona_change_advanced_settings',array($this,'change_advanced_settings_ajax'));
		add_action('wp_ajax_worona_send_contact_form',array($this,'send_contact_form_ajax'));
		add_action('wp_head', array($this, 'worona_add_js_injector'), 1); // adds the injector

		add_action('plugins_loaded', array($this,'wp_rest_api_plugin_is_installed'));
		add_action('plugins_loaded', array($this,'wp_rest_api_plugin_is_active'));
		add_action('init', array($this,'allow_origin'));

		add_action( 'admin_enqueue_scripts', array( $this, 'register_worona_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_worona_styles' ) );

		add_action( 'rest_api_init', function () {
			register_rest_route( 'worona/v1', '/siteid/', array(
				'methods' => 'GET',
				'callback' => array( $this,'get_worona_site_id'))
			);
			register_rest_route( 'worona/v1', '/discover/', array(
				'methods' => 'GET',
				'callback' => array( $this,'discover_url'))
			);
			register_rest_route( 'worona/v1', '/plugin-version/', array(
				'methods' => 'GET',
				'callback' => array( $this,'get_worona_plugin_version'))
			);
			register_rest_route( 'worona/v1', '/site-info/', array(
				'methods' => 'GET',
				'callback' => array( $this,'get_site_info'))
			);
		});
		// filters
	}

	/*
	*  init
	*
	*  This function is called during the 'init' action and will do things such as:
	*  create custom_post_types, register scripts, add actions / filters
	*
	*  @type	action (init)
	*  @date	10/06/14
	*  @since	0.6.0
	*
	*  @param	N/A
	*  @return	N/A
	*/

	function init()
	{
		// requires
	}

	//settings are being updated via AJAX, this validator is not used now
	function worona_settings_validator($args){

		if(!isset($args['worona_appId']) || strlen($args['worona_appId'])<17){
				$settings = get_option("worona_settings");
				$args['worona_appId'] = $settings['worona_appId'];
				add_settings_error('worona_settings', 'worona_invalid_appId', 'Please enter a valid APP ID!', $type = 'error');
		}
		if(isset($args['synced_with_worona']) && $args['synced_with_worona']=='true'){
			$args['synced_with_worona'] = true;
		}

    //make sure you return the args
    return $args;
	}

	function worona_admin_notices(){
		settings_errors();
	}

	function worona_register_settings() {
		register_setting(
							'worona_settings',
							'worona_settings',
							array($this,'worona_settings_validator')
		);
	}

	/**
 	* Register and enqueue style sheet.
 	*/
	public function register_worona_styles($hook) {

		wp_register_style('font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css', array(), '4.5.0');
		wp_register_style('bulma-css', 'https://cdnjs.cloudflare.com/ajax/libs/bulma/0.0.26/css/bulma.min.css',array('font-awesome'));

	}

	/**
	* Register and enqueue scripts.
	*/
	public function register_worona_scripts($hook) {

		wp_register_script('worona_admin_js',plugin_dir_url(__FILE__) . 'admin/js/worona-admin.js', array( 'jquery' ), true, true);
		wp_register_script('worona_help_js',plugin_dir_url(__FILE__) . 'admin/js/worona-help.js', array( 'jquery' ), true, true);


		wp_enqueue_script('worona_admin_js');
		wp_enqueue_script('worona_help_js');

	}

	/*
	*  worona_admin_actions
	*
	*  This function is called during the 'admin_menu' action and will do things such as:
	*  add a worona menu page to the Main Menu
	*
	*  @type	action (admin_menu)
	*  @date	18/07/14
	*  @since	0.6.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	function worona_admin_actions() {

		$icon_url	= trailingslashit(plugin_dir_url( __FILE__ )) . "assets/worona20x20.png";
		$position	= 64.999989; //Right before the "Plugins"

		add_menu_page(
			'Admin - WORONA',
			'Worona',
			1,
			'worona-admin',
			array($this, 'render_worona_admin'),
			$icon_url,
			$position
		);
		add_submenu_page(
			'worona-admin',// the slug name for the parent menu
			'Admin | Worona',// the title of the page when the browser visits it
			'Admin',// the name of the option in the menu
			'manage_options',// gives the plugin the ability to save settings
			'worona-admin',// this submenu's slug
			array($this, 'render_worona_admin')// the function that will render the admin page
		);
		add_submenu_page(
			'worona-admin', // the slug name for the parent menu
			'Help | Worona', // the title of the page when the browser visits it
			'Contact & Help', // the name of the option in the menu
			'manage_options', // gives the plugin the ability to save settings
			'worona-help', // this submenu's slug
			array( $this, 'render_worona_help' ) // the function that will render the admin page
		);
	}

	/*
	*  render_worona_admin
	*
	*  This function is called by the 'worona_admin_actions' function and will do things such as:
	*  add a worona page to render the admin content
	*
	*  @type	fucntion called by 'worona_admin_actions'
	*  @date	18/07/14
	*  @since	0.6.0
	*
	*  @param	N/A
	*  @return	N/A
	*/

	function render_worona_admin() {
		wp_enqueue_style('bulma-css');
	  include( 'admin/worona_admin_page.php');
	}

	/*
	*  render_worona_help
	*
	*  This function is called by the 'worona_admin_actions' function and will do things such as:
	*  add a worona page to render the help content
	*
	*  @type	fucntion called by 'worona_admin_actions'
	*  @date	18/07/14
	*  @since	0.6.0
	*
	*  @param	N/A
	*  @return	N/A
	*/

	function render_worona_help() {
		wp_enqueue_style('bulma-css');
	  include('admin/worona_help_page.php');
	}

	/*
  *  worona_add_js_detector
  *
  *  This function is called during the wp_head() and will do things such as:
  *  include a js script to detect if it's mobile
  *
  *  @type  method
  *  @date  30/07/15
  *  @since  1.0.0
  *
  *  @param  N/A
  *  @return  N/A
  */

  function worona_add_js_injector() {
		// require('injector/worona-injector.php');
		// Commented because we are using require(WP_PLUGIN_DIR . '/worona/injector/worona-injector.php');
		// on header.php, just after <head>.
  }

	/*
	*  add_worona_content_to_api
	*
	*  This function is called during the 'json_prepare_post' filter and will do things such as:
	*  prepare everything to output a new filed (worona_content) with the content the app needs from a post
	*
	*  @type	filter (json_prepare_post)
	*  @date	10/06/14
	*  @since	0.6.0
	*
	*  @param	N/A
	*  @return	N/A
	*/

	function add_worona_content_to_api( $_post, $post, $context ) {

	    // get all the fields of this post from Advanced Custom Fields
	    if( function_exists( "get_fields" ) )
	    {
	    	$fields = get_fields( $post['ID'] );
	    	$_post['worona_content']['acf'] = $fields;
	    }

	    // add the html content of the post to worona_content
	    $html = str_replace( PHP_EOL, '', wpautop( strip_tags( do_shortcode( $post['post_content'] ), '<h1><h2><h3><h4><h5><h6><img><p><ul><li><a><strong>'), false ) );
	    $_post['worona_content']['html'] = apply_filters( "worona_prepare_html", $html );


	    return $_post;
	}

	function get_worona_site_id() {

		$settings = get_option('worona_settings');

		if (isset($settings['worona_siteid'])) {
			$worona_site_id = $settings["worona_siteid"];
		} else {
			$worona_site_id = NULL;
		}

		return array('siteId'=> $worona_site_id);
	}

	function get_worona_plugin_version() {
		return array('plugin_version' => $this->plugin_version);
	}

	function get_site_info() {
		$homepage_title = get_bloginfo( 'name' );
		$homepage_metadesc = get_bloginfo( 'description' );

		$site_info = array(
			'homepage_title' => $homepage_title,
			'homepage_metadesc' => $homepage_metadesc
		);

		if(has_filter('worona_get_site_info')) {
			$site_info = apply_filters('worona_get_site_info', $site_info);
		}

		return $site_info;
	}

	/*
	*	@param \WP_REST_Request $request Full details about the request
	*/
	function discover_url( $request ) {
		$first_folder = $request['first_folder'];
		$last_folder = $request['last_folder'];

		if (is_null($last_folder)) {
			return array('Error' => 'last_folder is missing');
		}

		// ----------------
		// Post
		// ----------------
		$args = array(
  		'name'        => $last_folder,
  		'numberposts' => 1,
		);
		$post = get_posts($args);
		if ( sizeof($post) > 0 ) {
			return $post[0];
		}

		// ----------------
		// Page
		// ----------------
		$args = array(
  		'name'        => $last_folder,
  		'numberposts' => 1,
			'post_type'		=> 'page',
		);
		$page = get_posts($args);
		if ( sizeof($page) > 0 ) {
			return $page[0];
		}

		// ----------------
		// Author
		// ----------------
		if($first_folder === 'author') {
			$args = array(
				'author_name'		=> $last_folder,
			);
			$author = get_posts($args);
			if ( sizeof($author) > 0 ) {
				return $author[0];
			} else {
				return( new stdClass() ); //empty object instead of null
			}
		}

		// ----------------
		// Category
		// ----------------
		$category = get_term_by('slug',$last_folder,'category');
		if( $category ) {
			return $category;
		}

		// ----------------
		// Tag
		// ----------------
		$tag = get_term_by('slug',$last_folder,'tag');
		if( $tag ) {
			return $tag;
		}

		// ----------------
		// Custom Post type
		// ----------------

		$post_types = get_post_types('','object');
		$post_type = '';

		foreach ($post_types as $p) {
			if( $p->rewrite['slug'] == $first_folder ) {
				$post_type = $p->name;
			}
		}

		if ( $post_type !== '' ) {
			$args = array(
				'name'        => $last_folder,
				'numberposts' => 1,
				'post_type'		=> $post_type,
			);
			$custom_post = get_posts($args);

			if ( sizeof($custom_post) > 0 ) {
				return $custom_post[0];
			}
		}

		// ----------------
		// Custom Taxonomy
		// ----------------
		$taxonomies = get_taxonomies('','object');
		$taxonomy = '';

		foreach ($taxonomies as $t) {
			if( $t->rewrite['slug'] === $first_folder ) {
				$taxonomy = $t->name;
			}
		}

		if ( $taxonomy === '' ) {
			return array('Error' => $first_folder . ' not supported');
		}

		$custom_taxonomy = get_term_by('slug',$last_folder,$taxonomy);

		if( $custom_taxonomy ) {
			return $custom_taxonomy;
		} else {
				return array('Error' => $first_folder . 'not supported');
		}

		// ----------------
		// first_folder not found
		// ----------------
		return array('Error' => $last_folder .' not found');
	}

	function sync_with_worona() {
		flush_rewrite_rules();

		$settings = get_option('worona_settings');
		$settings['synced_with_worona'] = true;

		update_option('worona_settings', $settings);

		$siteId = $settings['worona_siteid'];

		wp_send_json( array(
			'status' => 'ok',
			'siteId' => $siteId
		));
	}

	function change_siteid_ajax() {
		flush_rewrite_rules();

		$siteId = $_POST['siteid'];

		if(strlen($siteId)<17) {
			wp_send_json(array(
				'status' => 'error',
				'reason' => 'Site ID is not valid.'
			));
		} else {
			$settings = get_option('worona_settings');
			$settings['worona_siteid'] = $siteId;
			$settings["synced_with_worona"] = true;

			update_option('worona_settings', $settings);

			wp_send_json( array(
				'status' => 'ok',
			));
		}
	}

	function change_advanced_settings_ajax() {

		$worona_ssr = $_POST['worona_ssr'];
		$worona_cdn = $_POST['worona_cdn'];

		$settings = get_option('worona_settings');
		$settings['worona_ssr'] = $worona_ssr;
		$settings['worona_cdn'] = $worona_cdn;

		update_option('worona_settings', $settings);

		wp_send_json( array(
			'status' => 'ok',
		));
	}

	public function send_contact_form_ajax() {
		$from = $_POST['email'];
		$name = $_POST['name'];
		$subject = $_POST['subject'];
		$message = $_POST['message'];

		if(!filter_var($from, FILTER_VALIDATE_EMAIL) || empty($from)) {
			wp_send_json( array(
				'status' => 'error',
				'message' => 'Invalid email'
			) );
		} else if (empty($message)) {
			wp_send_json( array(
				'status' => 'error',
				'message' => 'Empty message'
			) );
		} else {
			if(empty($name)){
				$name = $email;
			}
			$headers = "From: $name <$email>\r\n";
			$date = date('d/m/y H:i:s');
			$return = wp_mail( "support@worona.org", "[".$subject."] from ".get_site_url()." (". $date .")", stripslashes( trim( $message ) ), $headers );

			wp_send_json( array(
				'status' => 'ok',
				'return' => $return
			) );
		}
	}

	//Checks if the rest-api plugin is installed
	public function wp_rest_api_plugin_is_installed() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();

		$this->rest_api_installed = isset($plugins['rest-api/plugin.php']);
	}

	//Checks if the rest-api plugin is active
	public function wp_rest_api_plugin_is_active() {
		$this->rest_api_active = class_exists( 'WP_REST_Controller' );
	}

	//Generates the url to 'auto-activate' the rest-api plugin
	public function get_activate_wp_rest_api_plugin_url() {
		$plugin = 'rest-api/plugin.php';
		$plugin_escaped = str_replace('/', '%2F', $plugin);

		$activateUrl = sprintf(admin_url('plugins.php?action=activate&plugin=%s&plugin_status=all&paged=1&s'), $plugin_escaped);

  	// change the plugin request to the plugin to pass the nonce check
  	$_REQUEST['plugin'] = $plugin;
  	$activateUrl = wp_nonce_url($activateUrl, 'activate-plugin_' . $plugin);

  	return $activateUrl;
	}

	//Adds Cross origin * to the header
	function allow_origin() {
    header("Access-Control-Allow-Origin: *");
	}

	//Checks if the json posts endpoint is responding correctly
	function wp_rest_api_endpoint_works() {
		$rest_api_url = get_site_url() . '/wp-json/wp/v2/posts';
		$args = array('timeout' => 10, 'httpversion' => '1.1' );

		$response = wp_remote_get( $rest_api_url, $args );

		if( is_array($response) ) {
			$body = $response['body'];
			$code = $reponse['reponse']['code'];
			$message = $reponse['reponse']['message'];
			$json_reponse = json_decode($body);

			//CHECKS
			// $code != 200
			// json valid
			// json without error message { code: "rest_no_route" }


		} else {
			return false;
		}
	}
}

/*
*  worona
*
*  The main function responsible for returning the one true worona Instance to functions everywhere.
*  Use this function like you would a global variable, except without needing to declare the global.
*
*  Example: <?php $worona = worona(); ?>
*
*  @type	function
*  @date	11/06/14
*  @since	0.6.0
*
*  @param	N/A
*  @return	(object)
*/

function worona()
{
	global $worona;

	if( !isset($worona) )
	{
		$worona = new worona();
	}

	return $worona;
}

// initialize
worona();

function worona_activation() {

	//generates a random Site Id
	function generate_siteId() {
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
		$chars_length = (strlen($chars) - 1);// Length of character list
		$string = $chars{rand(0, $chars_length)};// Start our string

		for ($i = 1; $i < 17; $i++) {// Generate random string
				$r = $chars{rand(0, $chars_length)};// Grab a random character from our list
				$string .= $r;// Make sure the same two characters don't appear next to each other
		}
		return $string;
	}

	$current_user = wp_get_current_user();
	$email = $current_user->user_email;

	$settings = get_option('worona_settings');

	if (isset($settings["synced_with_worona"])) {
		$synced_with_worona = $settings["synced_with_worona"];
	} else {
		$synced_with_worona = false;
	}

	if (isset($settings['worona_siteid'])) {
		$siteId = $settings['worona_siteid'];
	} else {
		$siteId = generate_siteId();
	}

	if (isset($settings['worona_ssr'])) {
		$worona_ssr = $settings['worona_ssr'];
	} else {
		$worona_ssr = 'https://pwa.worona.io';
	}

	if (isset($settings['worona_static'])) {
		$worona_static = $settings['worona_static'];
	} else {
		$worona_static = 'https://pwa-static.worona.io';
	}

	$defaults = array("synced_with_worona" => $synced_with_worona,
										"worona_siteid" => $siteId,
										"worona_ssr" => $worona_ssr,
										"worona_cdn" => $worona_cdn);

	if($settings === false){
		add_option('worona_settings',$defaults , '','yes');
	} else {
		update_option('worona_settings',$defaults);
	}
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'worona_activation');

endif; // class_exists check
