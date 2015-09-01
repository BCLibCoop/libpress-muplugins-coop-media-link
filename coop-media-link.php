<?php defined('ABSPATH') || die(-1);

/**
 * Plugin Name: Co-op Media Link
 * Description: Options to allow libraries to customize their digital media link in searchform. Installed as MUST USE.
 * Author: Jonathan Schatz, BC Libraries Cooperative
 * Author URI: https://bc.libraries.coop
 * Version: 0.1.0
 **/

if ( ! class_exists( 'CoopMediaLink' )) :

	class CoopMediaLink {

		var $slug = 'coop-media-links';

		public function __construct() {
	
			add_action( 'init', array( &$this, '_init' ));
		}

		public function _init() {
				
			if( is_admin()) {
				add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles_scripts' ));
				add_action( 'admin_menu', array( &$this,'add_media_link_menu' ));
				add_action( 'wp_ajax_'.$this->slug.'-save-change', array( &$this, 'coop_media_link_save_change_callback'));
			}
			else {
				add_action( 'enqueue_scripts', array( &$this, 'frontside_enqueue_styles_scripts' ));
			}

			//default options if not already set
			add_option ($this->slug . '-label-text', 'Download Digital Media', '', 'yes');
			add_option ($this->slug . '-uri', '/research/download-digital-media', '', 'yes');
		
		}
	
		public function admin_enqueue_styles_scripts($hook) {
	
			if( 'site-manager_page_'.$this->slug !== $hook ) {
				return;
			}

			wp_register_script( $this->slug.'-admin-js', plugins_url( '/js/'.$this->slug.'-admin.js',__FILE__), array('jquery'));
			wp_enqueue_script( $this->slug.'-admin-js' );

		}

		public function add_media_link_menu() {
			add_submenu_page( 'site-manager', 'Co-op Media Link', 'Media Link', 'manage_local_site', $this->slug, array(&$this,'admin_media_link_settings_page'));
		}

		public function coop_media_link_save_change_callback() {
		
			$link_text = sanitize_text_field($_POST[$this->slug.'-label-text']);
			$link_uri = sanitize_text_field($_POST[$this->slug.'-uri']);	
			
			error_log( $link_text .', '. $link_uri );
			
			update_option($this->slug.'-label-text',$link_text);
			update_option($this->slug.'-uri',$link_uri);
			
			echo '{"result":"success","feedback":"Saved"}';
			die();
		}

		/**
		*	Store option to map URL to unqiue address 
		*	per co-op client library
		*	
		**/
		public function admin_media_link_settings_page() {
		
			if( ! current_user_can('manage_local_site') ) die('You do not have required permissions to view this page');
			
			$link_text = get_option($this->slug.'-label-text');
			$link_uri = get_option($this->slug.'-uri');	
		
			$out = array();
			$out[] = '<div class="wrap">';
			
			$out[] = '<div id="icon-options-general" class="icon32">';
			$out[] = '<br>';
			$out[] = '</div>';
			
			$out[] = '<h2>Media Link</h2>';
			
			$out[] = '<table class="form-table">';
			
			$out[] = '<tr valign="top">';
			$out[] = '<th scope="row">';
			$out[] = '<label for="'.$this->slug.'-uri">Media Link URI:</label>';
			$out[] = '</th>';
			$out[] = '<td>';
			$out[] = '<input type="text" id="'.$this->slug.'-uri" name="'.$this->slug.'-uri"  value="'.$link_uri.'">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			
			$out[] = '<tr valign="top">';
			$out[] = '<th scope="row">';
			$out[] = '<label for="'.$this->slug.'-label-text">Media Link Label:</label>';
			$out[] = '</th>';
			$out[] = '<td>';
			$out[] = '<input type="text" id="'.$this->slug.'-label-text" name="'.$this->slug.'-label-text"  value="'.$link_text.'">';
			$out[] = '</td>';	
			$out[] = '</tr>';

		
			$out[] = '</table>';
			
			$out[] = '<p class="submit">';
			$out[] = '<input type="submit" value="Save Changes" class="button button-primary" id="'.$this->slug.'-submit" name="submit">';
			$out[] = '</p>';
			
			echo implode("\n",$out);
		}

			/**
		*	Front-side widget callback
		*
		**/
		public function coop_media_link_widget( /*$args*/ ) {
			
			//extract($args);

			$link_text = get_option($this->slug.'-label-text');
			$link_uri = get_option($this->slug.'-uri');

			$link = '<a class="coop-media-link overdrive-link" href="'.$link_uri.'">'.$link_text.'</a>';

			return $link;
		}

	} //class

if ( ! isset( $coopmedialink ) ) {
	global $coopmedialink;
	$coopmedialink = new CoopMediaLink();
}
endif; /* ! class_exists */
