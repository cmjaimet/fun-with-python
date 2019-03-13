<?php
/**
 * Plugin Name: Postmedia Layouts
 * Short Name: postmedia_layouts
 * Description: Manage all post lists on all index pages and in sidebar
 * Author: Charles Jaimet (cjaimet@postmedia.com, charles@calmseamedia.com)
 * Version: 4.0.6
 * Requires at least: 3.0
 * Tested up to: 4.7
 *
 * Plugin Dependencies: WP Large Options (required!), pn_playlist_shortcode() for video list content widget
 * Plugin Support: Postmedia Network News Dashboard (supports pn_pointer post type)
 * Contributors: Vasu Kuppam, Ivan Plotnikov
 * Author URI: https://cmjaimet.github.io/
 *
 *
 * Copyright (C) 2013	PostMedia Inc
 *
 * This program is free software - you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.	If not, see <http://www.gnu.org/licenses/>.

CAPABILITIES:
manage_layouts - allows editorial control over Layouts
manage_advertising - allows advertising control over Layouts

Change since 2.3.0
- allow sidebar-less site display
- embed widgets, ads, and video in outfits
*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PM_LAYOUT_PATH', plugin_dir_path( __FILE__ ) );
define( 'PM_LAYOUT_URI', plugins_url( '', __FILE__ ) . '/' );
define( 'PM_LAYOUT_VERSION', '4.0' );

// Initialize Postmedia Library
if ( is_dir( WP_CONTENT_DIR . '/themes/vip' ) ) {
	require_once( WP_CONTENT_DIR . '/themes/vip/postmedia-plugins/postmedia-library/init.php' );
} else {
	require_once( WP_CONTENT_DIR . '/themes/postmedia-plugins/postmedia-library/init.php' );
}

// include the main plugin class file
require_once( PM_LAYOUT_PATH . 'classes/PostmediaLayouts.php' );

// instantiate the main class as an object that can be used throughout this plugin, in the theme, and other plugins as needed
global $postmedia_layouts;
$postmedia_layouts = new PostmediaLayouts();

// include other class files
require_once( PM_LAYOUT_PATH . 'classes/PostmediaLayoutsConfiguration.php' );
require_once( PM_LAYOUT_PATH . 'classes/PostmediaLayoutsAdWidget.php' );
require_once( PM_LAYOUT_PATH . 'classes/PostmediaLayoutsListWidget.php' );
require_once( PM_LAYOUT_PATH . 'classes/PostmediaLayoutsTermMeta.php' );
require_once( PM_LAYOUT_PATH . 'classes/PostmediaLayoutsVideoCenter.php' );

if ( is_admin() ) {
	// include the admin class file
	require_once( PM_LAYOUT_PATH . 'classes/PostmediaLayoutsAdmin.php' );
}

// determine the location of the theme folder (child or parent)
$postmedia_layouts->get_templates_folder();

// include the theme settings file
$postmedia_settings_path = $postmedia_layouts->template_path . 'PostmediaLayoutsTheme.php';
if ( file_exists( $postmedia_settings_path ) ) {
	require_once( $postmedia_settings_path );
}
