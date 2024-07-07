<?php
/**
 * Plugin Name: Bookmark post
 * Description: Adds bookmark functionality for posts.
 * Version: 1.0
 * Author: Sandeep Jain
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bookmark-post
 * Domain Path: /languages
 *
 * @package BookmarkPost
 */

namespace BookmarkPost;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BookmarkPost.
 *
 * Handles the functionality for bookmarking posts.
 *
 * @package BookmarkPost
 */
class BookmarkPost {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->define_constants();

		// Hooks for plugin activation.
		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );

		// Include necessary files.
		$this->load_bookmark_post_basic_files();
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		define( 'BOOKMARKPOST_PATH', plugin_dir_url( __FILE__ ) );
		define( 'BOOKMARKPOST_DIR', plugin_dir_path( __FILE__ ) );
		define( 'BOOKMARKPOST_VERSION', '1.0' );
	}

	/**
	 * Plugin activation tasks.
	 */
	public function plugin_activation() {
		$this->create_bookmark_table();
		$this->create_bookmark_page();
	}

	/**
	 * Create custom table for bookmarks on plugin activation.
	 */
	private function create_bookmark_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'bookmarks';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			post_id bigint(20) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create a page with the slug 'bookmarks-list' and the content '[bookmarks_list]' if it doesn't exist.
	 */
	private function create_bookmark_page() {
		$page_slug    = 'bookmarks-list';
		$page_title   = 'Bookmarks List';
		$page_content = '[bookmarks_list]';
		$page         = get_page_by_path( $page_slug );

		if ( ! $page ) {
			$page_data = array(
				'post_title'   => $page_title,
				'post_name'    => $page_slug,
				'post_content' => $page_content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			);

			wp_insert_post( $page_data );
		}
	}

	/**
	 * Include necessary files for bookmark post functionality.
	 */
	private function load_bookmark_post_basic_files() {
		$include_path = BOOKMARKPOST_DIR . 'includes/';
		$files        = glob( $include_path . '*.php' );
		foreach ( $files as $file ) {
			require_once $file;
		}
	}
}

// Initialize the class.
new BookmarkPost();
