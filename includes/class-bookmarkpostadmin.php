<?php
/**
 * Admin functionality for Bookmark Post plugin.
 *
 * @package BookmarkPost
 */

namespace BookmarkPost\Admin;

class BookmarkPostAdmin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'wp_ajax_refresh_bookmark_count', array( $this, 'refresh_bookmark_count' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add custom meta box to post and page edit screen.
	 */
	public function add_meta_boxes() {
		global $post;
		if ( has_shortcode( $post->post_content, 'bookmarks_list' ) ) {
			return;
		}
		add_meta_box( 'bp_bookmark_count', esc_html__( 'Bookmark Count', 'bookmark-post' ), array( $this, 'bookmark_count_meta_box' ), array( 'post', 'page' ), 'side', 'high' );
	}

	/**
	 * Meta box display callback.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function bookmark_count_meta_box( $post ) {
		$count = $this->get_bookmark_count( $post->ID );
		echo sprintf(
			'<p>%s <span id="bookmark-count">%s</span></p>',
			esc_html__( 'Number of bookmarks:', 'bookmark-post' ),
			esc_html( $count )
		);
		echo sprintf(
			'<button id="refresh-bookmark-count" class="button">%s</button>',
			esc_html__( 'Refresh Count', 'bookmark-post' )
		);
	}

	/**
	 * Get the number of bookmarks for a post.
	 *
	 * @param int $post_id The ID of the post.
	 * @return int The number of bookmarks.
	 */
	private function get_bookmark_count( $post_id ) {
		// Try to get the bookmark count from the cache.
		$cache_key      = 'bookmark_count_' . $post_id;
		$bookmark_count = wp_cache_get( $cache_key, 'bookmark_post' );

		// If the bookmark count is not found in the cache, query the database.
		if ( false === $bookmark_count ) {
			global $wpdb;
			$table_name     = $wpdb->prefix . 'bookmarks';
			$query          = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
				$post_id
			);
			$bookmark_count = (int) $wpdb->get_var( $query );

			// Store the bookmark count in the cache.
			wp_cache_set( $cache_key, $bookmark_count, 'bookmark_post', MINUTE_IN_SECONDS );
		}

		return $bookmark_count;
	}

	/**
	 * Handle AJAX request to refresh bookmark count
	 */
	public function refresh_bookmark_count() {
		check_ajax_referer( 'bp_ajax_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$count   = $this->get_bookmark_count( $post_id );
		echo esc_html( $count );
		wp_die();
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $post;
		if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && in_array( $post->post_type, array( 'post', 'page' ), true ) && ! has_shortcode( $post->post_content, 'bookmarks_list' ) ) {
			wp_enqueue_script( 'bookmark-admin-js', BOOKMARKPOST_PATH . 'js/bookmark-admin.js', array( 'jquery' ), BOOKMARKPOST_VERSION, true );
			wp_localize_script(
				'bookmark-admin-js',
				'bp_admin_ajax',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'bp_ajax_nonce' ),
				)
			);
		}
	}

}

// Initialize the class.
new BookmarkPostAdmin();
