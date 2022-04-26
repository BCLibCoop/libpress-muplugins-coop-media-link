<?php

/**
 * Coop Media Link
 *
 * A plugin to that adds a simple widget that takes text and a URL and displays
 * it. Used to display a media link in the searchform. Supports multi-language.
 *
 * PHP Version 7
 *
 * @package           BCLibCoop\CoopMediaLink
 * @author            Erik Stainsby <eric.stainsby@roaringsky.ca>
 * @author            Ben Holt <ben.holt@bc.libraries.coop>
 * @author            Jonathan Schatz <jonathan.schatz@bc.libraries.coop>
 * @author            Sam Edwards <sam.edwards@bc.libraries.coop>
 * @copyright         2013-2021 BC Libraries Cooperative
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Coop Media Link
 * Description:       Options to allow libraries to customize their digital media link in searchform
 * Version:           1.0.3
 * Network:           true
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            BC Libraries Cooperative
 * Author URI:        https://bc.libraries.coop
 * Text Domain:       coop-media-link
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace BCLibCoop\CoopMediaLink;

use function pll_languages_list;

class CoopMediaLink
{
    private static $instance;
    public $slug = 'coop-media-links';
    public $languages;

    public function __construct()
    {
        if (isset(self::$instance)) {
            return;
        }

        self::$instance = $this;

        // Dummy langauges option for when Polylang is not in use
        $this->languages = [
            (object) [
                'locale' => '',
                'name' => '',
            ],
        ];

        add_action('init', array(&$this, 'init'));
    }

    public function init()
    {
        // Check if polylang is available and if so use its list of languages
        if (function_exists('pll_languages_list')) {
            $this->languages = pll_languages_list('fields');
        }

        if (is_admin()) {
            add_action('admin_menu', array(&$this, 'addMediaLinkMenu'));
            add_action('admin_post_coop_media_link_submit', array($this, 'coopMediaLinkSaveChangeCallback'));
        }

        // Set default options if not already set
        foreach ($this->languages as $curlang) {
            /**
             * When langyages are present, this does generate an option in the format:
             * coop-media-linksen_CA-label-text, which is maybe not the nicest, but is
             * kept for backwards compatibility
             */
            add_option($this->slug . $curlang->locale . '-label-text', 'Download Digital Media');
            add_option($this->slug . $curlang->locale . '-uri', '/research/download-digital-media');
        }

        add_shortcode('coop-media-link', [&$this, 'coopMediaLinkShortcode']);
    }

    public function addMediaLinkMenu()
    {
        add_submenu_page(
            'site-manager',
            'Co-op Media Link',
            'Media Link',
            'manage_local_site',
            $this->slug,
            [&$this, 'adminMediaLinkSettingsPage']
        );
    }

    public function coopMediaLinkSaveChangeCallback()
    {
        // Check the nonce field, if it doesn't verify report error and stop
        if (
            ! isset($_POST['coop_media_link_nonce'])
            || ! wp_verify_nonce($_POST['coop_media_link_nonce'], 'coop_media_link_submit')
        ) {
            wp_die('Sorry, there was an error handling your form submission.');
        }

        foreach ($this->languages as $curlang) {
            $link_text = sanitize_text_field($_POST[$this->slug . $curlang->locale . '-label-text']);
            $link_uri = sanitize_text_field($_POST[$this->slug . $curlang->locale . '-uri']);

            update_option($this->slug . $curlang->locale . '-label-text', $link_text);
            update_option($this->slug . $curlang->locale . '-uri', $link_uri);
        }

        wp_redirect(admin_url('admin.php?page=coop-media-links'));
        exit;
    }

    /**
     * Store option to map URL to unqiue address
     * per co-op client library
     **/
    public function adminMediaLinkSettingsPage()
    {
        if (!current_user_can('manage_local_site')) {
            wp_die('You do not have required permissions to view this page');
        }

        $out = [];
        $out[] = '<div class="wrap">';

        $out[] = '<h1 class="wp-heading-inline">Media Link</h1>';
        $out[] = '<hr class="wp-header-end">';

        $out[] = '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
        $out[] = '<table class="form-table">';

        foreach ($this->languages as $curlang) {
            $link_text = stripslashes(get_option($this->slug . $curlang->locale . '-label-text'));
            $link_uri = get_option($this->slug . $curlang->locale . '-uri');
            $prefix = $this->slug . $curlang->locale;

            $out[] = '<tr valign="top">';
            $out[] = '<th scope="row">';
            $out[] = '<label for="' . $prefix . '-uri">' . $curlang->name . ' Media Link URI:</label>';
            $out[] = '</th>';
            $out[] = '<td>';
            $out[] = '<input type="text" id="' . $prefix . '-uri" name="' . $prefix . '-uri"  value="'
                     . $link_uri . '">';
            $out[] = '</td>';
            $out[] = '</tr>';

            $out[] = '<tr valign="top">';
            $out[] = '<th scope="row">';
            $out[] = '<label for="' . $prefix . '-label-text">' . $curlang->name . ' Media Link Label:</label>';
            $out[] = '</th>';
            $out[] = '<td>';
            $out[] = '<input type="text" id="' . $prefix . '-label-text" name="' . $prefix . '-label-text"  value="'
                     . $link_text . '">';
            $out[] = '</td>';
            $out[] = '</tr>';
        }

        $out[] = '</table>';
        $out[] = '<p class="submit">';
        $out[] = '<input type="hidden" name="action" value="coop_media_link_submit">';
        $out[] = wp_nonce_field('coop_media_link_submit', 'coop_media_link_nonce');
        $out[] = '<input type="submit" value="Save Changes" class="button button-primary" id="'
                 . $this->slug . '-submit" name="submit">';
        $out[] = '</p>';
        $out[] = '</form>';

        echo implode("\n", $out);
    }

    /**
     * Front-side shortcode callback
     **/
    public function coopMediaLinkShortcode()
    {
        // Check if polylang is available and if so get correct info for configured language
        if (function_exists('pll_languages_list')) {
            $link_text = stripslashes(get_option($this->slug . get_locale() . '-label-text'));
            $link_uri = get_option($this->slug . get_locale() . '-uri');
        } else {
            $link_text = stripslashes(get_option($this->slug . '-label-text'));
            $link_uri = get_option($this->slug . '-uri');
        }

        $link = '<a class="coop-media-link overdrive-link" href="' . $link_uri . '">' . $link_text . '</a>';

        return $link;
    }
}

// No direct access
defined('ABSPATH') || die(-1);

$coopmedialink = new CoopMediaLink();
