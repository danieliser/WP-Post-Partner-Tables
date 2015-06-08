<?php
/*
Plugin Name: WP Post Partner Tables
Plugin URI: https://github.com/danieliser/WP-Post-Partner-Tables
Description: Example plugin that shows how the Partner_Table class works.
Version: 1.0.0
Author: Daniel Iser
Author URI: http://danieliser.com
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Copyright: 2015 Daniel Iser
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once 'includes/class.partner-table.php';
require_once 'includes/class.location-partner-table.php';

// Example helper functions when $auto_join is set to true.
function get_the_city() {
	global $post;
	return ! empty( $post->city ) ? $post->city : '';
}

function the_city() {
	echo get_the_city();
}
