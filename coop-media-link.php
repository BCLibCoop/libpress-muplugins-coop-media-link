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
				add_action( 'admin_menu', array( &$this,'add_media_link_menu' ));
				add_action( 'admin_post_coop_media_link_submit', array( $this, 'coop_media_link_save_change_callback' ));
			}

			//Set default options if not already set
			if (function_exists('pll_languages_list')) {

				// Polylang is enabled so loop through each configured language
				$languages = pll_languages_list('fields');
				foreach($languages as $curlang) {
					if ($curlang->locale == 'en_CA') {
						add_option ($this->slug . $curlang->locale . '-label-text', 'Download Digital Media', '', 'yes');
						add_option ($this->slug . $curlang->locale . '-uri', '/research/download-digital-media', '', 'yes');
					}
				}
			}
			else {
				add_option ($this->slug . '-label-text', 'Download Digital Media', '', 'yes');
				add_option ($this->slug . '-uri', '/research/download-digital-media', '', 'yes');
			}
		
		}
	
		public function add_media_link_menu() {
			add_submenu_page( 'site-manager', 'Co-op Media Link', 'Media Link', 'manage_local_site', $this->slug, array(&$this,'admin_media_link_settings_page'));
		}

		public function coop_media_link_save_change_callback() {
			// Check the nonce field, if it doesn't verify report error and stop
			if (! isset( $_POST['coop_media_link_nonce']) || ! wp_verify_nonce( $_POST['coop_media_link_nonce'], 'coop_media_link_submit')) {
				die('Sorry, there was an error handling your form submission.');
			}

			if (function_exists('pll_languages_list')) {

				// Polylang is enabled so loop through each configured language
				$languages = pll_languages_list('fields');
				foreach($languages as $curlang) {
					$link_text = sanitize_text_field($_POST[$this->slug . $curlang->locale . '-label-text']);
					$link_uri = sanitize_text_field($_POST[$this->slug . $curlang->locale . '-uri']);
					error_log($link_text . ',' . $link_uri);
					update_option($this->slug . $curlang->locale . '-label-text', $link_text);
					update_option($this->slug . $curlang->locale . '-uri', $link_uri);
				}
			}
			else {

				$link_text = sanitize_text_field($_POST[$this->slug.'-label-text']);
				$link_uri = sanitize_text_field($_POST[$this->slug.'-uri']);

				error_log( $link_text .', '. $link_uri );

				update_option($this->slug.'-label-text',$link_text);
				update_option($this->slug.'-uri',$link_uri);
			}

			wp_redirect(admin_url('admin.php?page=coop-media-links'));


		}

		/**
		*	Store option to map URL to unqiue address 
		*	per co-op client library
		*	
		**/
		public function admin_media_link_settings_page() {

			if( ! current_user_can('manage_local_site') ) die('You do not have required permissions to view this page');

			if (!function_exists('pll_languages_list')) {
				$link_text = get_option($this->slug.'-label-text');
				$link_uri = get_option($this->slug.'-uri');
			}

			$out = array();
			$out[] = '<div class="wrap">';

			$out[] = '<div id="icon-options-general" class="icon32">';
			$out[] = '<br>';
			$out[] = '</div>';
			$out[] = '<h2>Media Link</h2>';
			$out[] = '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';			
			$out[] = '<table class="form-table">';

			// Check if polylang is available and if so output a form field for each configured language
			if (function_exists('pll_languages_list')) {
				$languages = pll_languages_list('fields');
				foreach($languages as $curlang) {
					$link_text = stripslashes(get_option($this->slug . $curlang->locale . '-label-text'));
					$link_uri = get_option($this->slug . $curlang->locale . '-uri');



					$out[] = '<tr valign="top">';
					$out[] = '<th scope="row">';
					$out[] = '<label for="' . $this->slug . $curlang->locale . '-uri">' . $curlang->name . ' Media Link URI:</label>';
					$out[] = '</th>';
					$out[] = '<td>';
					$out[] = '<input type="text" id="' . $this->slug . $curlang->locale . '-uri" name="' . $this->slug . $curlang->locale . '-uri"  value="' . $link_uri . '">';
					$out[] = '</td>';
					$out[] = '</tr>';

					$out[] = '<tr valign="top">';
					$out[] = '<th scope="row">';
					$out[] = '<label for="' . $this->slug . $curlang->locale . '-label-text">' . $curlang->name . ' Media Link Label:</label>';
					$out[] = '</th>';
					$out[] = '<td>';
					$out[] = '<input type="text" id="' . $this->slug . $curlang->locale . '-label-text" name="' . $this->slug . $curlang->locale . '-label-text"  value="' . $link_text . '">';
					$out[] = '</td>';
					$out[] = '</tr>';
				}
			}
			else {

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
			}

			$out[] = '</table>';
			$out[] = '<p class="submit">';
			$out[] = '<input type="hidden" name="action" value="coop_media_link_submit">';
			$out[] = wp_nonce_field( 'coop_media_link_submit', 'coop_media_link_nonce' );
			$out[] = '<input type="submit" value="Save Changes" class="button button-primary" id="'.$this->slug.'-submit" name="submit">';
			$out[] = '</p>';
			$out[] = '</form>';

			echo implode("\n",$out);
		}

		/**
		*Front-side widget callback
		*
		**/
		public function coop_media_link_widget() {

			// Check if polylang is available and if so get correct info for configured language
			if (function_exists('pll_languages_list')) {
				$link_text = stripslashes(get_option($this->slug . get_locale() . '-label-text'));
				$link_uri = get_option($this->slug . get_locale() . '-uri');
			}
			else {
				$link_text = stripslashes(get_option($this->slug.'-label-text'));
				$link_uri = get_option($this->slug.'-uri');
			}

			$link = '<a class="coop-media-link overdrive-link" href="' . $link_uri . '">' .$link_text .'</a>';
			return $link;
		}

	} //class

if ( ! isset( $coopmedialink ) ) {
	global $coopmedialink;
	$coopmedialink = new CoopMediaLink();
}
endif; /* ! class_exists */
