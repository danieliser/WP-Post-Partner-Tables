<?php
/**
 * Copyright: 2015 Daniel Iser
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Location_Partner_Table extends Partner_Table {
	public $post_types = array( 'page', 'post' );
	public $name = 'location';
	public $version = '1.0.0';
	public $primary_key = 'id';
	public $foreign_key = 'post_id';
	public $auto_join = true;

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function get_columns() {
		return array(
			'id'      => '%d',
			'post_id' => '%d',
			'city'    => '%s',
			'state'   => '%s',
			'zipcode' => '%s',
			'lat'     => '%f',
			'long'    => '%f',
		);
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function get_column_defaults() {
		return array(
			'post_id' => 0,
			'city'    => '',
			'state'   => '',
			'zipcode' => '',
			'lat'     => '',
			'long'    => '',
		);
	}

	/**
	 * Install/update the db table schema.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function install() {

		$sql = "CREATE TABLE " . $this->table_name . " (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `post_id` bigint(20) NOT NULL,
		  `city` varchar(255) NOT NULL,
		  `state` varchar(255) NOT NULL,
		  `zipcode` varchar(255) NOT NULL,
		  `lat` varchar(255) NOT NULL,
		  `long` varchar(255) NOT NULL,
		  PRIMARY KEY (id)
		) CHARACTER
		SET utf8 COLLATE utf8_general_ci";

		dbDelta( $sql );

	}

	/**
	 * Example function to search for posts by partner table data.
	 * Search for posts by zipcode.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function get_posts_by_zipcode( $zipcode ) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $this->table_name WHERE `zipcode` = %s", $zipcode ),
			ARRAY_A
		);

		$posts = array();
		foreach ( $rows as $row ) {
			$posts[] = $this->join_post_with_data( $row['post_id'], $row );
		}

		return $posts;
	}
}

/**
 * Instantiate Location_Partner_Table.
 *
 * @access  public
 * @since   1.0.0
 */
global $Location_Partner_Table;
$Location_Partner_Table = new Location_Partner_Table();
