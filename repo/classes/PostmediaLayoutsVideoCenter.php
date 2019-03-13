<?php

// Postmedia Library Content
use Postmedia\Web\Content;
use Postmedia\Web\Utilities;

class PostmediaLayoutsVideoCenter {

	const KEY_NAME = 'video_center';
	const FIELD_NAME = 'pmlay_video_center';

	private static $layouts = null;
	private static $from_cache = false;
	private static $videos_on_page = null;
	public static $first_video = null;

	/**
	 * Setup and hooks.
	 *
	 * @return null
	 */
	function init() {
		global $postmedia_layouts;
		self::$layouts = $postmedia_layouts;

		// Enable per site in Settings > Layouts
		$site_enabled = ( isset( self::$layouts->pmlay_count['show_vc'] ) ) ? intval( self::$layouts->pmlay_count['show_vc'] ) : 0;
		if ( ! $site_enabled ) {
			return;
		}

		add_action( 'pn_layouts_admin_display_form', array( __CLASS__, 'admin_output_form_field' ) );
		add_filter( 'pn_layouts_save_wlo_options', array( __CLASS__, 'admin_save_form_field' ), 10, 2 );
		add_filter( 'pn_add_additional_pmlay_settings', array( __CLASS__, 'add_vc_pmlay_setting' ) );
		add_filter( 'pn_choose_template_path', array( __CLASS__, 'use_vc_template_path' ), 10, 3 );

		// Enable per page layout if VC selected at top.
		if ( self::vc_enabled() ) {
			$cached_videos = get_transient( 'videos_on_page' );
			if ( false !== $cached_videos ) {
				self::$from_cache = true;
				self::$videos_on_page = json_decode( $cached_videos );
			}

			add_action( 'jetpack_open_graph_tags', array( __CLASS__, 'jetpack_open_graph_tags' ), 11, 1 );
			add_filter( 'pn_layouts_single_list_generated', array( __CLASS__, 'all_videos_on_page' ) );
			add_action( 'pn_layouts_display_video_center', array( __CLASS__, 'display_video_center' ) );
			add_action( 'pn_layouts_save_cache', array( __CLASS__, 'save_videos_to_cache' ) );
			add_action( 'pn_layouts_expire_all_caches', array( __CLASS__, 'expire_videos_cache' ) );
			add_action( 'pn_layouts_add_outfits', array( __CLASS__, 'add_vc_templates' ) );

			// Youtube List filters
			add_filter( 'pn_layouts_add_custom_type', array( __CLASS__, 'pn_layouts_add_custom_type' ), 10, 1 );
			add_filter( 'pn_layouts_display_custom_selected_value', array( __CLASS__, 'pn_layouts_display_custom_selected_value' ), 10, 3 );
			add_filter( 'pn_layouts_check_if_custom_type_exists', array( __CLASS__, 'pn_layouts_check_if_custom_type_exists' ), 10, 2 );
			add_filter( 'pn_layouts_get_custom_list', array( __CLASS__, 'pn_layouts_get_custom_list' ), 10, 2 );

			if ( ! is_admin() ) {
				add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
			}
		}
	}

	/**
	 * Alter OG tags for sharing individual videos
	 *
	 * @param array $tags Current meta tags.
	 *
	 * @return array
	 */
	public static function jetpack_open_graph_tags( $tags ) {
		$video = get_query_var( 'video_id' );
		if ( $video ) {
			$video_id = htmlentities( $video );

			$video = self::get_video_metadata( $video_id );
			if ( $video ) {
				$tags['og:title'] = $video->post_title;
				$tags['og:image'] = $video->thumbnail;
				$tags['og:description'] = $video->post_excerpt;
				$tags['og:video'] = $video->ID;
			}
		}

		return $tags;
	}

	/**
	 * Get video data from cache
	 *
	 * @param string $id YouTube video ID.
	 *
	 * @return PostObject|null
	 */
	public static function get_video_metadata( $id ) {

		if ( ! empty( self::$videos_on_page ) ) {
			$cached_videos = self::$videos_on_page;
		} else {
			$cached_videos = get_transient( 'videos_on_page' );
		}

		if ( ! empty( $cached_videos ) ) {
			$key = array_search( 'https://www.youtube.com/watch?v=' . $id, array_column( $cached_videos, 'url' ), true );
			if ( false !== $key ) {
				return $cached_videos[ $key ];
			}
		}

		// There are no videos cached and they won't be fetched until further on in the request
		// Get the video metadata from the api instead
		return self::get_youtube_video_data( $id );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return null
	 */
	public static function enqueue_scripts() {
		if ( self::vc_enabled() ) {
			wp_enqueue_style( 'pn_layouts_video_center_css', PM_LAYOUT_URI . 'css/video-center.css', false, false, 'all' );
			wp_enqueue_script( 'pn_layouts_video_center_js', PM_LAYOUT_URI . 'js/video-center.js', false, false, true );
		}

		wp_enqueue_style( 'pn_layouts_horizontal_list_css', PM_LAYOUT_URI . 'css/list-hl.css', false, false, 'all' );
		wp_enqueue_script( 'pn_layouts_horizontal_list_js', PM_LAYOUT_URI . 'js/list-hl.js', false, false, 'all' );
	}

	/**
	 * Display the option to use video center functionality for admin term pages.
	 *
	 * @param string $taxonomy String representation of current taxonomy.
	 *
	 * @return null
	 */
	public static function admin_output_form_field( $taxonomy = '' ) {
		if ( false === in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
			return;
		}

		$vc = self::vc_enabled() ? 'on' : false;

		echo '<tr class="form-field">';
		echo '<th valign="top" scope="row">';
		echo '<label for="' . esc_attr( self::FIELD_NAME ) . '">Video Center</label>';
		echo '</th>';
		echo '<td>';
		echo '<input type="checkbox" style="width: auto;" id="' . esc_attr( self::FIELD_NAME ) . '" name="' . esc_attr( self::FIELD_NAME ) . '" ' . checked( $vc, 'on', false ) . ' />';
		echo '<p class="description">If selected, this page will function as a video center.</p>';
	}

	/**
	 * Add any additional settings we want to save to the main WLO post.
	 *
	 * @param array $array     Array of data eventually saved to WLO post for layout settings.
	 * @param array $post_data Form data submitted via $_POST.
	 *
	 * @return array
	 */
	public static function admin_save_form_field( $array, $post_data ) {
		if ( isset( $post_data[ self::FIELD_NAME ] ) ) {
			$array[ self::KEY_NAME ] = $post_data[ self::FIELD_NAME ];
		}

		return $array;
	}

	/**
	 * Check if current post being added to list has featured video.
	 * If true, keep our own reference to be used later in JS.
	 *
	 * @param array $posts Current posts for list.
	 *
	 * @return array
	 */
	public static function all_videos_on_page( $posts ) {
		global $postmedia_layouts;

		foreach ( $posts as $post ) {
			// RSS/external feed items come in with url property.
			if ( isset( $post->url ) ) {
				$video_id = self::get_youtube_id_from_url( $post->url );
				if ( $video_id ) {
					$post->ID = $video_id;
					$post->post_type = 'youtube_video';
					self::$videos_on_page[] = $post;
					continue;
				}
			}

			// allow for posts coming from image api service
			if ( 'youtube_video' === $post->post_type ) {
				$video_id = $post->ID;
			}

			// WP posts will have to refer to meta.
			$media = $postmedia_layouts->get_post_media( $post );
			if ( ! empty( $media->video->id ) ) {
				$post->post_type = 'youtube_video';
				$video_id = $media->video->id;
			}

			$content = pn_post_to_content( $post );
			$rl = $content->get_related_links();

			if ( is_array( $rl ) && count( $rl ) > 0 ) {
				// String used for variable JS output.
				$rl_string = '';

				foreach ( $rl as $i => $link ) {
					if ( isset( $link->url ) && isset( $link->text ) ) {
						$rl_string .= '<a href="' . $link->url . '">' . $link->text . '</a>';

						if ( isset( $rl[ $i + 1 ] ) ) {
							$rl_string .= ', ';
						}
					}
				}

				$post->related_links = $rl;
				$post->related_links_string = $rl_string;
			}

			if ( $video_id ) {
				$post->link_out = true;
				$post->ID = $video_id;
				$post->url = 'https://www.youtube.com/watch?v=' . $video_id;
				$post->post_date = strtotime( $post->post_date );
				$post->post_author = get_the_author_meta( 'display_name', $post->post_author );
				self::$videos_on_page[] = $post;
			}
		}

		return $posts;
	}

	public static function get_youtube_id_from_url( $url ) {
		preg_match( '/(?<=watch\?v=)(.*)/', $url, $matches );
		if ( isset( $matches[0] ) ) {
			return $matches[0];
		}
		return false;
	}

	/**
	 * Save videos in cache at same time as layouts save posts.
	 *
	 * @return null
	 */
	public static function save_videos_to_cache() {
		delete_transient( 'videos_on_page' ); // Clear exisiting.
		set_transient( 'videos_on_page', wp_json_encode( self::$videos_on_page ), 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Admin resaved backend; expire the videos cache.
	 *
	 * @return null
	 */
	public static function expire_videos_cache() {
		delete_transient( 'videos_on_page' );
	}

	/**
	* Featured video player output at the top of term pages.
	*
	* @return null
	*/
	public static function display_video_center() {
		if ( ! self::vc_enabled() ) {
			return;
		}

		$first_video = null;
		$get_video = get_query_var( 'video_id' );

		if ( is_array( self::$videos_on_page ) && isset( self::$videos_on_page[0] ) ) {
			$post_count = count( self::$videos_on_page );

			for ( $i = 0; $i < $post_count; $i++ ) {
				$video = self::$videos_on_page[ $i ];

				// Customize array before we send to JS.
				$video->post_title = html_entity_decode( $video->post_title );
				$video->post_excerpt = html_entity_decode( $video->post_excerpt );
				$video->formatted_date = date( 'F j, Y', $video->post_date );

				$post_author = isset( $video->post_author ) ? $video->post_author : '';
				if ( empty( $post_author ) && isset( $video->author->name ) ) {
					$video->post_author = $video->author->name;
				}

				// Set initial video to first post or post
				// matching youtube id provided by query string
				$match = self::get_youtube_id_from_url( $video->url );
				if ( $match ) {
					$video->video_id = $match;
					if ( ! $first_video || $match === $get_video ) {
						$first_video = $video;
					}
				}
			}

			$js_vars = array(
				'videosOnPage' => self::$videos_on_page,
			);

			// Use filter in theme to hardcode where video player sticks to.
			// Must override both, not just 1. Integer values only!
			$px_from_top = array(
				'desktop' => null,
				'mobile' => null,
			);
			$js_vars['pxFromTop'] = apply_filters( 'video_center_sticky_position', $px_from_top );

			wp_localize_script( 'pn_layouts_video_center_js', 'VideoCenter', $js_vars );
			if ( ! $first_video ) {
				$first_video = self::$videos_on_page[0];
			}
		}

		if ( ! $first_video ) {
			return;
		}

		self::$first_video = $first_video;
		self::$layouts->choose_template( 'video-center', '', true );
	}

	/**
	 * If video center is enabled on this page for both back and frontend.
	 *
	 * @return bool
	 */
	public static function vc_enabled() {
		if ( function_exists( 'wlo_get_option' ) ) {
			if ( is_admin() ) {
				$id = isset( $_GET['tag_ID'] ) ? sanitize_text_field( wp_unslash( $_GET['tag_ID'] ) ) : 0; // Input var ok, CSRF ok.
			} else {
				$id = get_queried_object_id();
			}

			$key = 'pmlayouts_lists_' . $id;
			$pmlay_settings = json_decode( wlo_get_option( $key ) );
		}

		if ( isset( $pmlay_settings->video_center ) && 'on' === $pmlay_settings->video_center ) {
			return true;
		}
		return false;
	}

	/**
	 * Add our default VC templates for a horizontal list outfit.
	 *
	 * @param object $layouts PostmediaLayouts object.
	 *
	 * @return PostmediaLayouts object
	 */
	public static function add_vc_templates( $layouts ) {
		if ( isset( $layouts->outfit_settings ) && ! isset( $layouts->outfit_settings['hl'] ) ) {
			$default_vc = array(
				'Horizontal Lists',
				'hl',
				true, // Supports sponsored output at top.
				0,
				0,
			);
			$layouts->outfit_settings['hl'] = $default_vc;
		}

		return $layouts;
	}

	/**
	 * Override template in theme path when we want to use horizontal list outfit.
	 *
	 * @param string $path Current template path to use.
	 * @param string $type Current type of template.
	 * @param string $id.  Current ID of template.
	 *
	 * @return string
	 */
	public static function use_vc_template_path( $path, $type, $id ) {
		// Reset default location.
		self::$layouts->template_path = '';
		self::$layouts->get_templates_folder();
		$path = '';

		// Look in theme folder for existing files.
		if ( 'hl' === $id || 'video-center' === $type ) {
			$template_path = self::$layouts->template_path . $type . '/';
			$template_file = self::$layouts->create_template_path( $template_path, $type . '-' . $id );

			if ( ! file_exists( $template_file ) ) {
				// No file within theme folder so use the version inside plugin.
				$path = PM_LAYOUT_PATH . 'pm_layouts/';
			}
		}

		return $path;
	}

	/**
	 * Add boolean to main $pmlay_settings used across all files for plugin.
	 *
	 * @param array $pmlay_settings Layout settings.
	 *
	 * @return array
	 */
	public static function add_vc_pmlay_setting( $pmlay_settings ) {
		$pmlay_settings['is_video_center'] = self::vc_enabled();
		return $pmlay_settings;
	}

	/**
	 * Match specific URLs to query variable when video should play first on page.
	 *
	 * @return null
	 */
	public static function use_vc_rewrite_rules() {
		add_rewrite_rule( 'category/(.+)/video/?(.+)?/?$', 'index.php?category_name=$matches[1]&video_id=$matches[2]', 'top' );
		add_rewrite_rule( 'tag/(.+)/video/?(.+)?/?$', 'index.php?tag=$matches[1]&video_id=$matches[2]', 'top' );
		add_rewrite_tag( '%video_id%', '(.+)' );
	}

	/**
	 * Hook to add Youtube list type to list layout list options
	 *
	 * @param array $list Custom list types.
	 *
	 * @return array
	 */
	public static function pn_layouts_add_custom_type( $list ) {
		$list['you'] = 'YouTube';
		return $list;
	}

	/**
	 * Hook to map Youtube list items to their ids
	 * In this case the id is the item
	 */
	public static function pn_layouts_display_custom_selected_value( $value, $type, $id ) {
		if ( 'you' === $type ) {
			return $id;
		} else {
			return $value;
		}
	}

	/**
	 * Hook to flag Youtube list type as having custom content
	 */
	public static function pn_layouts_check_if_custom_type_exists( $value, $type ) {
		if ( 'you' === $type ) {
			return true;
		} else {
			return $value;
		}
	}

	/**
	 * Hook to generate custom content fot the Youtube list type
	 */
	public static function pn_layouts_get_custom_list( $type, $list_id ) {
		if ( 'you' === $type ) {
			return self::display_module_get_you( $list_id );
		} else {
			return array();
		}
	}

	/**
	* Populates the list data with data pulled from image api youtube end points
	*
	* @param $_list_id string Youtube list id
	*
	* @return (null)
	*/
	public static function display_module_get_you( $list_id ) {
		global $postmedia_layouts;
		if ( $postmedia_layouts ) {
			// Get the playlist data from the playlists endpoint
			$_list_data = self::get_youtube_playlist_data( $list_id );
			if ( $_list_data ) {
				// Set layouts flag to indicate that this list uses WCM
				$postmedia_layouts->list_uses_wcm = true;
				// Format and return posts
				return $postmedia_layouts->wcm_to_post_list( $_list_data->items );
			}
		}
		return null;
	}

	public static function get_youtube_video_data( $video_id ) {
		global $postmedia_layouts;
		$response = self::image_api_request( '/media/videos/' . $video_id . '/json?target=false' );
		if ( ! empty( $response->items ) ) {
			$posts = $postmedia_layouts->wcm_to_post_list( $response->items );
			if ( ! empty( $posts ) ) {
				return $posts[0];
			}
		}
		return null;
	}

	public static function get_youtube_playlist_data( $list_id ) {
		return self::image_api_request( '/media/videos/playlists/' . $list_id . '/json?target=false' );
	}

	public static function image_api_request( $uri ) {
		$_response = false;

		$image_api_url = get_option( 'wcm_api_url_images' );
		$_api_url = ! empty( $image_api_url ) ? $image_api_url : get_option( 'wcm_api_url' );

		if ( ! empty( $_api_url ) ) {
			$_api_url = $_api_url . $uri;

			// Try to get the request from the cache.
			$transient_key = 'postmedia_layouts_image_api_request' . md5( $_api_url );
			$transient = get_transient( $transient_key );
			if ( false !== $transient ) {
				$_response = $transient;
			} else {
				$_args = array(
					'method' => 'GET',
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(
						'x-api-key' => get_option( 'wcm_read_key', '' ),
					),
					'body' => '',
				);
				$_response = vip_safe_wp_remote_get( $_api_url, null, 3, 3, 20, $_args );

				if ( ( is_wp_error( $_response ) ) || ( ( is_array( $_response ) ) && ( isset( $_response['response']['code'] ) ) && ( 200 !== $_response['response']['code'] ) ) ) {
					// Save as empty array otherwise get_transient() will return false if we save value as '';
					$_response = array();
				}

				// Save the response to the cache.
				set_transient( $transient_key, $_response, 5 * MINUTE_IN_SECONDS );
			}
		}

		if ( ( is_array( $_response ) ) && ( isset( $_response['body'] ) ) ) {
			$_response = json_decode( $_response['body'] );
		}

		return $_response;
	}
}

add_action( 'init', array( 'PostmediaLayoutsVideoCenter', 'use_vc_rewrite_rules' ) );
