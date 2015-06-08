<?php
/**
 * Copyright: 2015 Daniel Iser
 * URI: https://github.com/danieliser/WP-Post-Partner-Tables
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Partner_Table {

	/**
	 * The post types this table will attach to
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_types = array();

	/**
	 * The suffix name of our database table
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public $name;


	/**
	 * The full name of our database table including prefixes.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public $table_name;

	/**
	 * The version of our database table
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The name of the primary column
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public $primary_key;

	/**
	 * The name of the foreign key column
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public $foreign_key;

	/**
	 * Whether to auto join partner table columns to the $post via the_posts
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public $auto_join = false;


	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'pt_' . $this->name;

		$current_version = get_option( $this->table_name . '_db_version' );
		if ( ! $current_version || version_compare( $current_version, $this->version, '<' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$this->install();
			update_option( $this->table_name . '_db_version', $this->version );
		}

		add_filter( 'get_post_metadata', array( $this, 'get_metadata' ), 0, 3 );
		add_filter( 'add_post_metadata', array( $this, 'update_metadata' ), 0, 4 );
		add_filter( 'update_post_metadata', array( $this, 'update_metadata' ), 0, 4 );
		add_filter( 'delete_post_metadata', array( $this, 'delete_metadata' ), 0, 3 );


		if ( $this->auto_join ) {
			add_filter( 'the_posts', array( $this, 'the_posts' ), 10, 2 );
			//add_filter( 'posts_where', array( $this, 'posts_where'), 10, 2 );
		}
	}

	/**
	 * Filters the_posts after WP_Query runs, joins partner table columns if post_type is supported by each post.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function the_posts( $posts ) {
		global $wpdb;
		$ids = array();
		foreach ( $posts as $post ) {
			if ( in_array( $post->post_type, $this->post_types ) ) {
				$ids[] = $post->ID;
			}
		}

		if ( empty ( $ids ) ) {
			return $posts;
		}

		$ids = implode( ',', $ids );

		$rows = $wpdb->get_results( "SELECT * FROM $this->table_name WHERE $this->foreign_key IN ($ids);" );

		if ( empty ( $rows ) ) {
			return $posts;
		}

		$post_rows = array();
		foreach ( $rows as $row ) {
			$post_rows[ $row->{$this->foreign_key} ] = $row;
		}
		unset( $rows );

		foreach ( $posts as &$post ) {
			if ( array_key_exists( $post->ID, $post_rows ) ) {
				$post = $this->join_post_with_data( $post, $post_rows[ $post->ID ] );
			}
		}

		return $posts;

	}

	/**
	 * Checks WP_Query args to see if querying supported post_types. (NOT USED CURRENTLY)
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function posts_where( $where, $query ) {
		if ( $query->is_main_query() || in_array( $query->query_vars['post_type'], $this->post_types ) ) {
			add_filter( 'posts_fields', array( $this, 'posts_fields'), 10, 2 );
			add_filter( 'posts_join', array( $this, 'posts_join'), 10, 2 );
		}
		return $where;
	}

	/**
	 * Adds joined fields to WP_Query args. (NOT USED CURRENTLY)
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function posts_fields( $fields, $query ) {
		$join_fields = '';
		foreach ( $this->get_columns() as $field => $type ) {
			if ( $field != $this->primary_key && $field != $this->foreign_key ) {
				$join_fields .= ", $this->table_name.$field";
			}
		}
		$fields .= $join_fields;
		return $fields;
	}

	/**
	 * Adds join statement to WP_Query args. (NOT USED CURRENTLY)
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function posts_join( $join, $query ) {
		global $wpdb;
		$join_table = "LEFT JOIN $this->table_name ON $wpdb->posts.ID = $this->table_name.$this->foreign_key ";
		$join .= $join_table;
		return $join;
	}

	/**
	 * Hooks the get_metadata calls and overrides it for partner tables.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function get_metadata( $check, $object_id, $meta_key ) {
		if ( $meta_key == 'pt_' . $this->name ) {
			return (array) $this->get_by( $this->foreign_key, $object_id );
		}
		return $check;
	}

	/**
	 * Hooks the add_metadata/update_metadata calls and overrides it for partner tables.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function update_metadata( $check, $object_id, $meta_key, $meta_value ) {
		if ( $meta_key == 'pt_' . $this->name ) {
			if ( $this->get_by( $this->foreign_key,  $object_id ) ) {
				$this->update( $object_id, $meta_value, $this->foreign_key );
			}
			else {
				$meta_value[ $this->foreign_key ] = $object_id;
				$this->insert( $meta_value );
			}
			return false;
		}
		return $check;
	}

	/**
	 * Hooks the delete_metadata calls and overrides it for partner tables.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function delete_metadata( $check, $object_id, $meta_key ) {
		if ( $meta_key == 'pt_' . $this->name ) {
			return $this->delete_by( $this->foreign_key, $object_id );
		}
		return $check;
	}

	/**
	 * Whitelist of columns
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function get_columns() {
		return array();
	}

	/**
	 * Default column values
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function get_column_defaults() {
		return array();
	}

	/**
	 * Retrieve a row by the primary key
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  object
	 */
	public function get( $row_id ) {
		global $wpdb;
		return $wpdb->get_row( "SELECT * FROM $this->table_name WHERE $this->primary_key = $row_id LIMIT 1;" );
	}

	/**
	 * Retrieve a row by a specific column / value
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  object
	 */
	public function get_by( $column, $row_id ) {
		global $wpdb;
		return $wpdb->get_row( "SELECT * FROM $this->table_name WHERE $column = '$row_id' LIMIT 1;" );
	}

	/**
	 * Retrieve a specific column's value by the primary key
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_column( $column, $row_id ) {
		global $wpdb;
		return $wpdb->get_var( "SELECT $column FROM $this->table_name WHERE $this->primary_key = $row_id LIMIT 1;" );
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;
		return $wpdb->get_var( "SELECT $column FROM $this->table_name WHERE $column_where = '$column_value' LIMIT 1;" );
	}

	/**
	 * Insert a new row
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  int
	 */
	public function insert( $data ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( 'partner_table_pre_insert_' . $this->name, $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );

		do_action( 'partner_table_post_insert_' . $this->name, $wpdb->insert_id, $data );

		return $wpdb->insert_id;
	}

	/**
	 * Update a row
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  bool
	 */
	public function update( $row_id, $data = array(), $where = '' ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if( empty( $row_id ) ) {
			return false;
		}

		if( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === $wpdb->update( $this->table_name, $data, array( $where => $row_id ), $column_formats ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  bool
	 */
	public function delete( $row_id = 0 ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if( empty( $row_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  bool
	 */
	public function delete_by( $column, $row_id ) {
		global $wpdb;
		if( empty( $row_id ) ) {
			return false;
		}
		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $column = '%s'", $row_id ) ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Prepare the query if using Paging (NOT CURRENTLY USED)
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function prepare_query( $query, $args = array() ) {

		if( $args['orderby'] ) {
			$query .= " ORDER BY {$args['orderby']} {$args['order']}";
		}

		$query .= " LIMIT {$args['limit']}";

		if( $args['offset'] ) {
			$query .= " OFFSET {$args['offset']}";
		}

		$query .= ';';

		return $query;

	}

	/**
	 * Joins post with partner table row. This is used when you lookup posts by searching partner tables.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function join_post_with_data( $post, $data ) {
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post );
		}
		foreach ( $data as $key => $value ) {
			$post->$key = $value;
		}
		return $post;
	}

	/**
	 * Placeholder function for child classes. Replace with db_delta and CREATE TABLE query.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function install() {}

	/**
	 * Eventual home of a default search function for partner tables.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function search( $args = array() ) {
		if ( empty( $args ) ) {
			return;
		}
	}
}
