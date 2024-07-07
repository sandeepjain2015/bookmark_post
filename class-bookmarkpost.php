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
 * @package BookmarkPost
 */

namespace bookmarkpost;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BookmarkPost
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
		// Define plugin paths.
		$this->define_constants();
		// Register activation hook.
		register_activation_hook( __FILE__, array( $this, 'create_bookmark_table' ) );

		// Include necessary files.
		$this->load_bookmark_post_basic_files();
		
	}
/**
	 * Define plugin constants.
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		define( 'BOOKMARKPOST_PATH', plugin_dir_url( __FILE__ ) );
		define( 'BOOKMARKPOST_DIR', plugin_dir_path( __FILE__ ) );
		define( 'BOOKMARKPOST_VERSION', '1.8' );
	}
	/**
	 * Create custom table for bookmarks on plugin activation
	 */
	public function create_bookmark_table() {
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
	 * Include necessary files for bookmark post functioanlity.
	 */
	private function load_bookmark_post_basic_files() {
		// Directory path to include folder.
		$include_path = BOOKMARKPOST_DIR . 'includes/';
		$files = glob( $include_path . '*.php' );
		foreach ( $files as $file ) {
			require_once $file;
		}
	}
	
}

// Initialize the class.
new BookmarkPost();
