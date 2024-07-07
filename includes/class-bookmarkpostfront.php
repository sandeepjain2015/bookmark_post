<?php
/**
 * Frontend functionality for Bookmark Post plugin.
 *
 * @package BookmarkPost
 */

namespace BookmarkPost\Frontend;

class BookmarkPostFront {

	/**
	 * Add important action and filters for front end bookmark functionality.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'the_content', array( $this, 'add_bookmark_button' ) );
		add_action( 'wp_ajax_toggle_bookmark', array( $this, 'toggle_bookmark' ) );
		add_action( 'wp_ajax_nopriv_toggle_bookmark', array( $this, 'toggle_bookmark' ) );
		add_action( 'wp_ajax_refresh_bookmarks_list', array( $this, 'refresh_bookmarks_list' ) );
		add_action( 'wp_ajax_nopriv_refresh_bookmarks_list', array( $this, 'refresh_bookmarks_list' ) );
		add_shortcode( 'bookmarks_list', array( $this, 'bookmarks_list_shortcode' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( is_single() || is_page() ) {
			wp_enqueue_script( 'bookmark-js', BOOKMARKPOST_PATH . 'js/bookmark.js', array( 'jquery' ), BOOKMARKPOST_VERSION, true );
			wp_enqueue_style( 'bookmark-css', BOOKMARKPOST_PATH . 'css/bookmark.css', array(), BOOKMARKPOST_VERSION );
			wp_localize_script(
				'bookmark-js',
				'bp_ajax',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'bp_ajax_nonce' ),
				)
			);
		}
	}

	/**
	 * Add bookmark button to single post pages.
	 *
	 * @param string $content The content of the post.
	 * @return string The content with the bookmark button added.
	 */
	public function add_bookmark_button( $content ) {
		if ( ( is_single() || is_page() ) && ! has_shortcode( $content, 'bookmarks_list' ) ) {
			global $post;
			$post_id      = $post->ID;
			$user_id      = get_current_user_id();
			$bookmarked   = $this->is_bookmarked( $user_id, $post_id ) ? 'bookmarked' : '';
			$button_text  = $bookmarked ? __( 'Unbookmark', 'bookmark-post' ) : __( 'Bookmark', 'bookmark-post' );
			$button_html  = '<button class="bookmark-post-button ' . esc_attr( $bookmarked ) . '" data-post_id="' . esc_attr( $post_id ) . '">' . esc_html( $button_text ) . '</button>';
			$button_html .= '<div id="bookmark-post-loader" style="display: none;">';
			$button_html .= sprintf( '<img src="%s" alt="Loading...">', BOOKMARKPOST_PATH . 'images/loader.gif' );
			$button_html .= '</div>';
			return $content . $button_html;
		}
		return $content;
	}

	/**
	 * Check if post is bookmarked by user.
	 *
	 * @param int $user_id The user ID.
	 * @param int $post_id The post ID.
	 * @return bool True if the post is bookmarked by the user, false otherwise.
	 */
	private function is_bookmarked( $user_id, $post_id ) {
		if ( $user_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'bookmarks';
			// Generate a unique cache key based on user ID and post ID.
			$cache_key = 'is_bookmarked_' . $user_id . '_' . $post_id;

			// Try to get the result from cache.
			$is_bookmarked = wp_cache_get( $cache_key, 'bookmark_post' );

			if ( false === $is_bookmarked ) {
				$result        = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM %i WHERE user_id = %d AND post_id = %d',
						$table_name,
						$user_id,
						$post_id
					)
				);
				$is_bookmarked = $result > 0;
				wp_cache_set( $cache_key, $is_bookmarked, 'bookmark_post', MINUTE_IN_SECONDS );
			}
			return $is_bookmarked;
		} else {
			if ( isset( $_COOKIE['bookmarks'] ) ) {
				$bookmarks = json_decode( wp_unslash( $_COOKIE['bookmarks'] ), true );
				return in_array( $post_id, $bookmarks, true );
			}
			return false;
		}
	}


	/**
	 * Handle AJAX request to bookmark/unbookmark post.
	 */
	public function toggle_bookmark() {
		check_ajax_referer( 'bp_ajax_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$user_id = get_current_user_id();
		global $wpdb;
		$table_name = $wpdb->prefix . 'bookmarks';

		if ( $user_id ) {
			if ( $this->is_bookmarked( $user_id, $post_id ) ) {
				// Remove bookmark.
				$wpdb->delete(
					$table_name,
					array(
						'user_id' => $user_id,
						'post_id' => $post_id,
					)
				);
				echo 'removed';
			} else {
				// Add bookmark.
				$wpdb->insert(
					$table_name,
					array(
						'user_id' => $user_id,
						'post_id' => $post_id,
					)
				);
				echo 'added';
			}
		} else {
			// Manage bookmarks for visitors using cookies.
			if ( isset( $_COOKIE['bookmarks'] ) ) {
				$bookmarks = json_decode( stripslashes( $_COOKIE['bookmarks'] ), true );
			} else {
				$bookmarks = array();
			}

			if ( in_array( $post_id, $bookmarks, true ) ) {
				// Remove bookmark.
				$bookmarks = array_diff( $bookmarks, array( $post_id ) );
				echo 'removed';
			} else {
				// Add bookmark.
				$bookmarks[] = $post_id;
				echo 'added';
			}

			// Update cookie.
			setcookie( 'bookmarks', wp_json_encode( $bookmarks ), time() + 3600 * 24 * 30, COOKIEPATH, COOKIE_DOMAIN, false, true );
		}
		wp_die();
	}

	/**
	 * Shortcode to list bookmarks and include refresh button.
	 *
	 * @return string The bookmarks list HTML.
	 */
	public function bookmarks_list_shortcode() {
		ob_start();
		?>
		<div id="bookmark-post-list-container">
			<?php
			$this->display_bookmarks_list();
			if ( ! empty( $this->get_bookmarks() ) ) {
				?>
				<button id="refresh-bookmark-post-button"><?php echo esc_html__( 'Refresh Bookmarks', 'bookmark-post' ); ?></button>
				<?php
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Retrieve bookmarks based on user ID or cookies.
	 *
	 * @return array List of bookmarked post IDs.
	 */
	private function get_bookmarks() {
		$user_id   = get_current_user_id();
		$bookmarks = array();

		if ( $user_id ) {
			$cache_key = 'bookmarks_' . $user_id;
			$bookmarks = wp_cache_get( $cache_key, 'bookmark_post' );
			if ( false === $bookmarks ) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'bookmarks';
				$bookmarks  = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT post_id FROM %i WHERE user_id = %d',
						$table_name,
						$user_id
					),
					ARRAY_A
				);
				$bookmarks  = wp_list_pluck( $bookmarks, 'post_id' );
				wp_cache_set( $cache_key, $bookmarks, 'bookmark_post', MINUTE_IN_SECONDS );

			}
		} elseif ( isset( $_COOKIE['bookmarks'] ) ) {
			$bookmarks = json_decode( stripslashes( $_COOKIE['bookmarks'] ), true );
		}

		return $bookmarks;
	}

	/**
	 * Display the bookmarks list.
	 */
	private function display_bookmarks_list() {
		$bookmarks = $this->get_bookmarks();

		if ( ! empty( $bookmarks ) ) {
			echo '<ul class="bookmark-post-list">';
			foreach ( $bookmarks as $bookmark ) {
				$post = get_post( $bookmark );
				if ( $post ) {
					printf(
						'<li><a href="%s">%s</a></li>',
						esc_url( get_permalink( $post ) ),
						esc_html( $post->post_title )
					);
				}
			}
			echo '</ul>';
		} else {
			echo sprintf(
				'<p class="no-bookmark-post-message">%s</p>',
				esc_html__( 'You have no bookmarks.', 'bookmark-post' )
			);
		}
	}

	/**
	 * Handle AJAX request to refresh the bookmarks list.
	 */
	public function refresh_bookmarks_list() {
		ob_start();
		$this->display_bookmarks_list();
		$content = ob_get_clean();
		echo $content;
		wp_die();
	}
}

// Initialize the class.
new BookmarkPostFront();
?>
