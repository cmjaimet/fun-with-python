<?php
/**
* Don't load this file directly
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Postmedia Library Content
use Postmedia\Web\Content;
use Postmedia\Web\Utilities;
use Postmedia\Web\Data\DataHelper;

/** DEFINE THE CLASS THAT HANDLES THE BACK END **/
class PostmediaLayouts {
	const FIRST_WIDGET_MARKER = '<div style="display: none">&&&|||&&&</div>';

	/** configuration properties **/
	// cache the content of the first outfit on the home page, where editors tend to place breaking news
	public $cache_breaking_news = false;
	// on mobile, the minimum number of words separating slots where widgets can be inserted between paragraphs
	public $words_per_slot = 120;
	// the maximum number of content widgets (tickers) that can be inserted on an index page
	public $max_ticker = 5;
	// number of seconds that cached outfit HTML will be used before it is refreshed - make this a settable option in UI?
	private $cache_duration = 300;
	// the list of possible advertorial types
	public $adv_type = array(
		'advertisement',
		'sponsored_by',
		'promoted_by',
		'presented_by',
	);
	// the list of possible story list sources
	public $list_type = array(
		'cat' => 'Category (+ subs)',
		'cax' => 'Category (only)',
		'tag' => 'Tag',
		'zon' => 'Zoninator',
		'shar' => 'WCM Shared',
		'ug' => 'Usergroup',
		'ugc' => 'Usergroup+Cat',
		'ugs' => 'Usergroup+Cat+Subs',
		'auth' => 'Author',
		'rss' => 'External Feed',
		'chrt' => 'Chartbeat',
		'cust' => 'Custom',
	);
	// number of pages to assume each category has before forcing 404
	public $rss_pagination = 5;

	/** general property declaration - leave these values alone **/
	private $offset = array( 'gather' => false, 'count' => 0, 'post_id' => 0 );
	public $sidebar_slotting = null; // if true then cut up the sidebar into an array of widgets and slot the widgets into the main content - for use on mobile on post and index pages
	public $sidebar_widgets = array();
	public $temp_widgets = array();
	public $sidebar_positions = array();
	public $sidebar_current_position = -1;
	public $sidebar_slug = '';
	public $sidebar_adwords = 0;
	private $posts_displayed = 0; // count of all posts displayed incremented as they are displayed
	public $list_args = array();
	public $pmlay_settings = array();
	public $pmlay_count = array();
	public $sidebar_id = 's';
	public $current_news_ticker_attribs = '';
	public $current_news_ticker_parameters = array();
	public $outfit_settings = array();
	public $widget_settings = array();
	public $is_mobile = null;
	public $is_index = false;
	private $outfit_content = array();
	public $outfits_after_content = ''; // HTML of widgets to be displayed after all content on post pages - needs to stored and displayed separately since the main widget processing happens as a filter on the_content and this needs to appear after Facebook commenting, well below content
	public $layout_area = ''; // waterfall, baselist, sidebar
	private $outfit_cache = array();
	private $use_outfit_cache = false;
	private $update_outfit_cache = false;
	private $transient_names = array(
		'expire' => '',
		'outfits' => '',
		'posts' => '',
		'exclude' => '',
	);

	public $ad_rules = array();
	public $ad_rules_displayed_count = 0;
	public $ad_rules_position = 'top';
	private $count_ads = 0;
	private $dfpad_size = 'tall';
	public $widget_slot_count = 0; // total number of widget slots available on the page that can accomodate das - used by rules engine to determine the size of the page as measured in slots
	public $current_widget_slot = 0; // starts at zero - the number of the current widget slot. This will increment as slots are rendered on the page
	public $device_page_type = '';

	public $template_path = '';
	public $template_url = '';
	public $template_location = -1;
	public $count_emotion_bubbles = 0; // allow the theme files to count the number of bubbles displayed to control use
	public $count_pull_quotes = 0; // allow the theme files to count the number of pull quotes displayed to control use
	private $use_default_outfits = false;
	public $override_ad_rendering = true;
	private $widget_slugs = array(); // all widget slugs grouped by sidebar
	public $widget_placeholder_text = 'POSTMEDIA_LAYOUTS_DFP_AD_HTML';
	public $main_class = '';
	public $pagination_data = array();
	private $page_num = 0;
	public $widgets_sorted = array();
	public $theme_enqueues = array(
		'scripts' => array(),
		'styles' => array(),
	);
	public $enable_pullquotes = null;
	public $enable_emotions = null;
	private $feed_post_count = 40;
	public $list_uses_wcm = false;
	public $wcm_client_id = '';
	private $wcm_api_keys = array();
	private $wcm_api_url = '';
	private $post_types = array( 'post', 'pn_pointer', 'gallery', 'feature' ); // remove feature, gallery, pn_pointer once these are in their own plugins
	private $post_types_excluded_from_aut = array( 'pn_pointer' ); //post types to be excluded from local searches by author ( author and usergroup lists )
	private $post_widgets_slotting_done = false; // indicates that post widgets have/not been inserted into the text
	private $widget_list = array();
	private $widget_keys = array();

	/**
	* Instantiate the object
	*
	* @return (null)
	*/
	function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp', array( 'PostmediaLayoutsVideoCenter', 'init' ) );
	}

	/**
	* Fires after WordPress has finished loading but before any headers are sent.
	* https://codex.wordpress.org/Plugin_API/Action_Reference/init
	*
	* @return (null)
	*/
	function init() {
		if ( '' === $this->template_path ) {
			$this->get_templates_folder();
		}
		$this->get_plugin_settings();
		$this->get_wcm_settings();
		add_action( 'wp', array( $this, 'get_plugin_settings_web' ) );
		add_action( 'wp_head', array( $this, 'show_alt_title' ) );
		add_filter( 'pn_video_channel_player_id', array( $this, 'get_video_id' ), 10, 3 );
		$this->pmlay_count['sections_home'] = intval( get_option( 'postmedia_layouts_sections_home' ) );
		$this->pmlay_count['sections_category'] = intval( get_option( 'postmedia_layouts_sections_category' ) );
		$this->pmlay_count['sections_tag'] = intval( get_option( 'postmedia_layouts_sections_tag' ) );
		$this->pmlay_count['choose_color'] = intval( get_option( 'postmedia_layouts_choose_color' ) );
		$this->pmlay_count['show_sidebar'] = ( 1 === intval( get_option( 'postmedia_layouts_show_sidebar', 1 ) ) ) ? true : false;
		$this->pmlay_count['show_baselist'] = ( 1 === intval( get_option( 'postmedia_layouts_show_baselist', 1 ) ) ) ? true : false;
		$this->pmlay_count['show_wcm'] = ( 1 === intval( get_option( 'postmedia_layouts_show_wcm', 0 ) ) ) ? true : false;
		$this->pmlay_count['show_vc'] = ( 1 === intval( get_option( 'postmedia_layouts_show_vc', 0 ) ) ) ? true : false;
		$this->pmlay_count['widget_source'] = trim( get_option( 'postmedia_layouts_widget_source' ) );
		$this->pmlay_count['secondary_ad_height'] = intval( get_option( 'postmedia_layouts_secondary_ad_height' ) );
		$this->pmlay_count['wcm_origin_client'] = trim( get_option( 'postmedia_layouts_wcm_origin_client' ) );
		$this->pmlay_count['wcm_origin_client_domain'] = trim( get_option( 'postmedia_layouts_wcm_origin_client_domain' ) );
		$outfit_order_small = trim( get_option( 'postmedia_layouts_outfit_order_small' ) );
		$outfit_order_medium = trim( get_option( 'postmedia_layouts_outfit_order_medium' ) );
		if ( ! empty( $outfit_order_small ) ) {
			$this->pmlay_count['outfit_order']['small'] = explode( ',', $outfit_order_small );
		}
		if ( ! empty( $outfit_order_medium ) ) {
			$this->pmlay_count['outfit_order']['medium'] = explode( ',', $outfit_order_medium );
		}
		$this->pmlay_count['custom_main_video'] = trim( get_option( 'postmedia_layouts_custom_main_video' ) );
		$this->post_types = apply_filters( 'pn_layouts_post_types', $this->post_types ); // allow external code to add or remove post types (e.g. gallery, feature, storyline, pn_pointer)
		$this->post_types_excluded_from_aut = apply_filters( 'pn_layouts_exclude_post_types_author_list', $this->post_types_excluded_from_aut ); //allow external code to add or remove post types that should be excluded from author searches

		remove_all_filters( 'widget_display_callback' );
		add_filter( 'widget_display_callback', array( $this, 'widget_display_callback' ), 99, 3 );
		add_filter( 'the_posts', array( $this, 'the_posts' ), 10, 2 );
		// on a mobile post page, insert placeholders for widgets - WCM solution
		add_filter( 'pre_content_elements_to_html', array( $this, 'set_wcm_content_elements' ), 10, 3 );
		// on a mobile post page, insert placeholders for widgets - Legacy/WP solution
		add_filter( 'the_content', array( $this, 'set_post_widget_slots' ), 998, 3 );
		// remove filters that affect widgets globally because these can cause widgets to reappear in the sidebar despite filters set below - any change to the display of ALL widgets should be added here
		add_filter( 'dynamic_sidebar_params', array( $this, 'count_widgets' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 50, 1 );
		add_action( 'pm_layouts_after_main_article', array( $this, 'widget_after_first_article' ), 9, 1 );
		// filter posts that don't include featured videos
		add_filter( 'pn_layouts_single_list_generated', array( $this, 'post_featured_video_filter' ), 20, 2 );
	}

	function post_featured_video_filter( $posts, $_list_data ) {
		if ( isset( $_list_data['video_posts_only'] ) && 1 === $_list_data['video_posts_only'] ) {
			$filtered_posts = array();
			foreach ( $posts as $post ) {
				$media = $this->get_post_media( $post );
				if ( ! empty( $media->video->id ) ) {
					$filtered_posts[] = $post;
				}
			}
			return $filtered_posts;
		}
		return $posts;
	}

	function the_posts( $posts, $query ) {
		global $postmedia_layouts;
		// only on frontend, on the main category query - based on code from David Binovic
		// because Layouts uses feeds as post sources for categories, and because page 1 could contain fewer than 10 posts from the
		if ( true === is_admin() ) {
			return $posts;
		}
		if ( ( ! $query->is_main_query() ) || ( false === is_category() && false === is_tag() ) || ( is_feed() ) ) {
			return $posts;
		}
		if ( 1 == intval( is_category() ) ) {
			// category page
			$term_id = get_queried_object_id();
			$term_type = 'category';
		} elseif ( 1 == intval( is_tag() ) ) {
			// tag page
			$term_id = get_queried_object_id();
			$term_type = 'tag';
		} else {
			// home page
			$term_id = 0;
			$term_type = 'home';
		}
		$_settings = $postmedia_layouts->get_termdata( $term_id, $term_type );
		// do if there is an override or not show baselist
		// skip if no override and do show baselist
		$_override = isset( $_settings['override'] ) ? trim( $_settings['override'] ) : '';
		$_show_baselist = isset( $postmedia_layouts->pmlay_count['show_baselist'] ) ? $postmedia_layouts->pmlay_count['show_baselist'] : false;
		$_show_baselist = ( true === $_show_baselist ) ? true : false;
		if ( ( '' === $_override ) && ( true === $_show_baselist ) ) {
			return $posts;
		}
		// when Layouts uses an external feed as the baselist, change the pagination counts to reflect posts from this source
		$term = wpcom_vip_get_term_by( 'slug', $query->get( 'category_name' ), 'category' );
		if ( ( true === empty( $term ) ) || ( true === is_wp_error( $term ) ) ) {
			return $posts;
		}
		if ( '' !== $_override ) {
			$_pagination = intval( $postmedia_layouts->rss_pagination );
		} else {
			$_pagination = 10;
		}
		// only if posts are empty and the requested page is < $_pagination (will 404 on ($_pagination+1)th page)
		if ( true === empty( $posts ) && $_pagination >= $query->get( 'paged', 0 ) ) {
			if ( '' !== $_override ) {
				// there is no post list because this page gets its stories from an external feed
				$args = array(
					'posts_per_page' => 1,
					'post_type' => $query->get( 'post_type' ),
					'post_status' => $query->get( 'post_status' ),
					'suppress_filters' => false,
				);
			} else {
				$args = array();
			}
			// Let's attempt to return the first page of category as odds are there is at least one post in the category
			if ( true === $query->is_paged() ) {
				$posts = get_posts( array_merge( $args, array( 'category_name' => $query->get( 'category_name' ) ) ) );
			}
			// if there is no post in the category at all, let's return a "random" latest post of said post type
			if ( true === empty( $posts ) && isset( $args ) ) {
				$posts = get_posts( $args );
			}
		}
		if ( '' !== $_override ) {
			// arbitrary set the number of found posts to 10*$_pagination and number of pages to $_pagination for displaying pagination
			// all categories will have 10*$_pagination posts and $_pagination pages if this is not being dynamically populated
			$query->found_posts = 10 * $_pagination;
			$query->max_num_pages = $_pagination;
		}
		return $posts;
	}

	/**
	* The proper hook to use when enqueuing items that are meant to appear on the front end. Despite the name, it is used for enqueuing both scripts and styles.
	* https://codex.wordpress.org/Plugin_API/Action_Reference/wp_enqueue_scripts
	*
	* @return (null)
	*/
	function wp_enqueue_scripts() {
		// use this to enqueue scripts and styles - get an array from settings.php in theme
		$_count = 0;
		$_enqtypes = array( 'scripts', 'styles' );
		if ( false === isset( $this->pmlay_settings['news_ticker'] ) ) {
			return;
		}
		foreach ( $_enqtypes as $_enq ) {
			if ( ( false === isset( $this->theme_enqueues[ $_enq ] ) ) || ( false === is_array( $this->theme_enqueues[ $_enq ] ) ) ) {
				continue;
			}
			foreach ( $this->theme_enqueues[ $_enq ] as $_slug => $_file ) {
				foreach ( $this->pmlay_settings['news_ticker'] as $_ticker ) {
					if ( ( false === isset( $_ticker['type'] ) ) || ( $_ticker['type'] !== $_slug ) ) {
						continue;
					}
					if ( is_array( $_file ) ) {
						foreach ( $_file as $_enqfile ) {
							$this->enqueue_file( $_enqfile, 'pn_layouts_theme_' . $_count, $_enq );
							$_count ++;
						}
					} else {
						$this->enqueue_file( $_file, 'pn_layouts_theme_' . $_count, $_enq );
						$_count ++;
					}
				}
			}
		}
	}

	/**
	* Enqueue a single script or style
	*
	* @parameter $_file (string) The URI of a script or style file
	* @parameter $_slug (string) The unique slug to identify this resource within the DOM
	* @parameter $_type (string) The type of file (scripts|styles)
	* @parameter $_footer (bool) Delay enqueue until footer
	*
	* @return (null)
	*/
	function enqueue_file( $_file, $_slug, $_type = 'scripts', $_footer = false ) {
		if ( 'scripts' === $_type ) {
			wp_enqueue_script( $_slug . '_js', $_file, false, false, $_footer );
		} else {
			wp_enqueue_style( $_slug . '_css', $_file, false, false, 'all' );
		}
	}

	/**
	* Set up key properties
	*
	* @return (null)
	*/
	function get_plugin_settings() {
		$this->pmlay_settings = array(
			'posts_shown' => '',
			'posts_cached' => '',
			'urls_shown' => array(),
			'baselist_postids' => array(),
			'page1_offset' => 0,
			'phone1_offset' => 0,
			'posts_avail' => 0,
			'posts_total' => 0,
			'sidebar' => true,
			'override' => '',
			'override_count' => 0,
			'override_max' => 0,
			'target' => '_self',
			'list_class' => '',
			'template_type' => '',
			'outfit_type' => '',
			'advertorial_type' => '',
			'retrieved' => false,
			);
		$this->pmlay_count = array(
			'section' => 0,
			'display_id' => 0,
			'module' => 0,
			'header' => 0,
			'widget' => 0,
			'widget_count' => -1,
			'templates_count' => 6,
			'module_max' => 12,
			'header_max' => 12,
			'widget_max' => 6,
			'video_max' => 6,
			'page_type' => 'home',
			'sections_max' => 10,
			'sections_home' => 6,
			'sections_category' => 2,
			'sections_tag' => 2,
			'choose_color' => 1,
		);
	}

	/**
	* Load front end settings
	*
	* @return (null)
	*/
	function get_plugin_settings_web() {
		global $paged;
		$this->is_mobile = $this->is_mobile();
		$this->get_device_page_type();
		$this->set_sidebar_on_off();
		$this->use_sidebar_slotting();
		$this->is_index = $this->is_index();
		$this->page_num = $paged;
		$_term_data = $this->get_page_term_data();
		$this->pmlay_settings = $this->get_termdata( $_term_data->id, $_term_data->type );
	}

	/**
	* Get the WP term data for the current page
	*
	* @return $_output (object) Term ID and type as integer and string properties respectively
	*/
	private function get_page_term_data() {
		$_output = new stdClass();
		$_output->id = -1;
		$_output->type = 'none';
		if ( true === $this->is_index ) {
			if ( 1 == intval( is_category() ) ) {
				// category page
				$_output->id = get_queried_object_id();
				$_output->type = 'category';
			} elseif ( 1 == intval( is_tag() ) ) {
				// tag page
				$_output->id = get_queried_object_id();
				$_output->type = 'tag';
			} else {
				// home page
				$_output->id = 0;
				$_output->type = 'home';
			}
		}
		return $_output;
	}

	/**
	* This method loads the entire index page and calls all other building methods
	*
	* @return (null)
	*/
	public function layout_lists() {
		$this->layout_area = 'waterfall'; // waterfall, baselist, sidebar
		$this->page_num = intval( get_query_var( 'paged' ) );
		$this->page_num = ( 0 == $this->page_num ) ? 1 : $this->page_num;
		$_term_id = intval( $this->pmlay_settings['term_id'] );
		$_term_type = trim( $this->pmlay_settings['term_type'] );
		if ( true === $this->is_mobile ) {
			$this->get_transient_names( 'phone', $_term_id );
		} else {
			$this->get_transient_names( 'desktop', $_term_id );
		}
		// get max number of outfits allowed by term type: cat, tag, home
		$_sect_max = intval( $this->pmlay_count[ 'sections_' . $_term_type ] );
		// get data for this term
		// CMJ already done in get_plugin_settings_web $this->pmlay_settings = $this->get_termdata( $_term_id, $_term_type );
		// if there are no outfits configured for this term use the default outfits
		$this->should_use_default_outfits( $_sect_max );
		// if the term page has no outfits selected use the default ones
		if ( true === $this->use_default_outfits ) {
			$this->get_default_outfits( $_term_id, $_term_type, $_sect_max );
		}
		$this->pmlay_settings['override_count'] = 0;
		$_section_start = 0;
		$_base_list_count = 10;
		$_override = isset( $this->pmlay_settings['override'] ) ? $this->pmlay_settings['override'] : '';
		$_target = isset( $this->pmlay_settings['target'] ) ? $this->pmlay_settings['target'] : '';
		$this->get_outfit_count( $_term_type ); // count the number of outfits that will be displayed - for use in choosing ad rule to use
		// show the content widget in top position if exists (page 1 only)
		if ( 2 > $this->page_num ) {
			$this->display_news_ticker( -1, true );
		}
		// Start displaying HTML for outfits and content widgets
		// ADAPTIVE FOR MOBILE : if this is mobile then preload the sidebar into memory and slot it in among the lists
		do_action( 'pm_layouts_before_layouts' );
		// SHOW THE OUTFITS using $this->get_postlist() then the base list
		$video_center_enabled = (bool) $this->pmlay_settings['is_video_center'];
		$video_center_class = ( $video_center_enabled && is_tag() ) ? ' vc-container' : '';
		echo '<div class="l-main track_event ' . esc_attr( $this->main_class ) . esc_attr( $video_center_class ) . '" id="main" data-event-tracking="outfit">';
		if ( ( 1 < intval( $this->page_num ) ) && ( false === $this->pmlay_count['show_sidebar'] ) ) {
			echo '<div class="fluid-holder">';
		}
		if ( ( false === $this->pmlay_count['show_sidebar'] ) || ( true === $this->sidebar_slotting ) ) {
			// only executes if is_mobile or sidebar turned off - create an array and fill with widgets that are allowed on mobile - blank array elements just display nothing
			$this->sidebar_widgets = $this->get_sidebar_widgets();
		}
		// get the outfits on page 1
		if ( 2 > $this->page_num ) {
			// get the base list post ids first (if not using a feed for the base list) so we can accurately count off the ones that have been displayed on page 1 into $this->pmlay_settings['baselist_postids'];
			if ( '' === $_override ) {
				// need this for pagination even if the baselist is not displayed
				$this->get_baselist_postids( $_term_id );
			}
			// retrieve posts and build all outfits
			$this->get_postlist( $_term_id, $_term_type, $_section_start, 0, -1 );
		}
		$this->get_pagination_data();
		if ( ( true === $this->update_outfit_cache ) && ( 2 > $this->page_num ) ) {
			// if this is page one and the layouts are being refreshed then save $this->offset['count']
			$_settings = $this->pmlay_settings;
			// change offest to be the value of the post ID furthest into the base list
			if ( ! empty( $this->pmlay_settings['baselist_postids'] ) ) {
				$this->offset['count'] = array_search( $this->offset['post_id'], $this->pmlay_settings['baselist_postids'], true ) + 1; // find the last post shown on page 1 and start from there
			} else {
				$this->offset['count'] = 1;
			}
			if ( true === $this->is_mobile ) {
				$_settings['phone1_offset'] = intval( $this->offset['count'] );
			} else {
				$_settings['page1_offset'] = intval( $this->offset['count'] );
			}
			$_offset_array = array(
				'page1_offset' => intval( $_settings['page1_offset'] ),
				'phone1_offset' => intval( $_settings['phone1_offset'] ),
			);
		}

		// Display Video Center player at top before regular templates for 1st page.
		if ( 1 === $this->page_num ) {
			do_action( 'pn_layouts_display_video_center' );
		}

		// display the outfits - HTML for each has been generated already
		$this->display_outfits( $_sect_max );
		if ( 0 < $_term_id ) {
			/** BASELIST **/
			$this->display_baselist( $_term_id, $_term_type, $_override );
			/** PAGINATION **/
			$this->display_pagination();
			/** FINAL WIDGET SLOT ON MOBILE **/
			if ( $this->is_mobile ) {
				// on mobile show all remaining widgets that did not get slotted between outfits
				$this->safe_echo( $this->show_widget_slot( 'all' ) ); // Early-escaped
			}
			echo '</div>';
			do_action( 'pm_layouts_after_base_list' );
		} else {
			// on home page on mobile just show remaining widget slots
			if ( $this->is_mobile ) {
				// on mobile show all remaining widgets that did not get slotted between outfits
				$this->safe_echo( $this->show_widget_slot( 'all' ) ); // Early-escaped
			}
		}
		if ( ( 1 < intval( $this->page_num ) ) && ( false === $this->pmlay_count['show_sidebar'] ) ) {
			echo '</div>';
		}
		echo '</div>';
		$this->set_page_cache();
		$this->layout_area = 'sidebar'; // waterfall, baselist, sidebar
		if ( true === $this->pmlay_count['show_sidebar'] && ! $video_center_enabled ) {
			// display sidebar on non-smartphone devices (tablets, desktop) and sites that do not merge the sidebar into the main well
			$this->layout_sidebar();
		}
		if ( ( true === $this->update_outfit_cache ) && ( 2 > $this->page_num ) ) {
			$this->set_layouts_page1_offset( $_term_id, $_offset_array ); // temporary solution to avoid overwriting layouts config - move to transient and record only when refreshing cache (every fivish minutes ) in new release
		}
	}

	/**
	* Echo baselist
	*
	* @parameter $_term_id (integer) Term ID for the page
	* @parameter $_term_type (string) Term type for the page
	* @parameter $_override (integer) Override value for page (feed URL|blank|wcm) from $this->pmlay_settings['override']
	*
	* @return (null)
	*/
	private function display_baselist( $_term_id = 0, $_term_type = '', $_override = '' ) {
		if ( ( 1 < $this->page_num ) || ( true === $this->pmlay_count['show_baselist'] ) ) {
			// display the base list - tag and category pages only - except on page one where the settings indicate not to use the baselist
			$this->pmlay_settings['outfit_type'] = 'baselist';
			do_action( 'pm_layouts_before_base_list' );
			// on category and tag pages show a base list
			echo '<div id="pm_base_list">' . "\n";
			// CMJ: use display_list
			if ( ( 1 >= $this->page_num ) && ( true === $this->use_outfit_cache ) ) {
				// this is page 1 and it is using cached outfits
				$this->safe_echo( $this->outfit_cache[0] ); // Early-escaped
			} else {
				if ( '' === $_override ) {
					// CMJ confirm WCM is working here
					$_list_type = ( 'category' === $_term_type ) ? 'cat' : 'tag';
					$_args = array( 'list_id' => $_term_id, 'template' => '0', 'list_type' => $_list_type, 'is_baselist' => true, 'echo' => false );
					$_baselist_html = $this->display_list( $_args );
				} else {
					// use a feed as the base list
					$_args = array( 'list_id' => '', 'template' => '0', 'is_baselist' => true, 'echo' => false );
					// option to use WCM or external feed for base list
					if ( 'wcm' === $this->pmlay_settings['override'] ) {
						$_args['list_type'] = 'shar';
					} else {
						$_args['list_type'] = 'rss';
					}
					$_baselist_html = $this->display_list( $_args );
				}
				$this->safe_echo( $_baselist_html ); // Early-escaped
				$this->outfit_content[0] = $_baselist_html;
			}
		}
	}

	/**
	* Echo outfits
	*
	* @parameter $_sect_max (integer) Maximum number of outfits that can be displayed
	*
	* @return (null)
	*/
	private function display_outfits( $_sect_max ) {
		if ( 2 > $this->page_num ) {
			// show widgets that are slotted before content
			$this->safe_echo( $this->show_widget_slot( '' ) ); // Early-escaped
			// get the HTML for the first outfit - need to insert widgets after first post in first outfit
			$_outfit1 = $this->outfit_content[1];
			// split the first outfit after the first post in it
			if ( false !== strpos( $_outfit1, self::FIRST_WIDGET_MARKER ) ) {
				$_outfit1_split = explode( self::FIRST_WIDGET_MARKER, $_outfit1 );
			} else {
				$_outfit1_split = array( $_outfit1, '' );
			}
			// SHOW FIRST OUTFIT: insert widgets into first outfit after first post on mobile
			do_action( 'pn_layouts_before_outfit', $_outfit1, 1 );
			foreach ( $_outfit1_split as $_outfit1_block ) {
				$this->safe_echo( $_outfit1_block ); // Early-escaped
				$this->safe_echo( $this->show_widget_slot( '' ) ); // Early-escaped
			}
			do_action( 'pn_layouts_after_outfit', $_outfit1, 1 );
			// SHOW ALL SUBSEQUENT OUTFITS: show widgets between outfits on mobile
			for ( $x = 2; $x <= $_sect_max; $x ++ ) {
				$_outfit_html = trim( $this->outfit_content[ intval( $x ) ] );
				do_action( 'pn_layouts_before_outfit', $_outfit1_block, $x );
				$this->safe_echo( $_outfit_html ); // Early-escaped
				// show widgets between outfits on mobile
				if ( '' !== $_outfit_html ) {
					$this->safe_echo( $this->show_widget_slot( '' ) ); // Early-escaped
				}
				do_action( 'pn_layouts_after_outfit', $_outfit1_block, $x );
			}
			// on mobile we are considering lazy loading later outfits... if ( $this->is_mobile ) {$this->get_postlist( $term_id, $term_type, 0, 2, -1 ); // this shows just the first two outfits ... $this->get_postlist( $term_id, $term_type, 2, 3, -1 ); // this shows just the third outfit
		}
	}

	/**
	* Set the transients required to rebuild this page without queries
	*
	* @return (null)
	*/
	private function set_page_cache() {
		if ( true === $this->update_outfit_cache ) {
			// if the outfits have been regenerated from the database and templates then save to transient
			// contains a JSON-encoded array of outfit HTML blocks
			$this->pn_update_option( $this->transient_names['outfits'], $this->outfit_content );
			// contains a comma-delimited list of cached posts displayed on first page to exclude them from sidebar lists
			set_transient( $this->transient_names['posts'], wp_json_encode( $this->pmlay_settings['posts_cached'] ), 0 );
			// also save the list of posts that have been displayed to exclude them from page 2 and beyond
			// contains a comma-delimited list of all posts displayed on first page to exclude them from page 2 and beyond
			set_transient( $this->transient_names['exclude'], wp_json_encode( $this->pmlay_settings['posts_shown'] ), 0 );
			do_action( 'pn_layouts_save_cache' );
		}
	}

	/**
	* Set the outfits to be used to the defaults ones in $this->pmlay_settings['outfits']
	*
	* @parameter $_sect_max (integer) Maximum number of outfits that can be displayed
	*
	* @return (null)
	*/
	private function get_default_outfits( $_term_id, $_term_type, $_sect_max ) {
		$_default_outfits = get_option( 'postmedia_layouts_default_outfits' );
		if ( is_string( $_default_outfits ) ) {
			$_default_outfits = json_decode( $_default_outfits, true );
		}
		// get default outfits from tag selected in settings
		$this->pmlay_settings = $this->get_termdata( $_term_id, $_term_type );
		for ( $_section_num = 1; $_section_num <= $_sect_max; $_section_num ++ ) {
			// go through all possible sections
			// get default outfit from settings
			if ( ( isset( $_default_outfits[ $_section_num ] ) ) && ( '' !== trim( $_default_outfits[ $_section_num ] ) ) ) {
				$_outfit_id = intval( $_default_outfits[ $_section_num ] );
			} else {
				$_outfit_id = -1;
			}
			$_outfit_position = $_section_num - 1;
			if ( isset( $this->pmlay_settings['outfits'][ $_outfit_position ] ) && is_array( $this->pmlay_settings['outfits'][ $_outfit_position ] ) ) {
				$this->pmlay_settings['outfits'][ $_outfit_position ]['template'] = $_outfit_id;
			} else {
				$this->pmlay_settings['outfits'][ $_outfit_position ] = array(
					'template' => $_outfit_id,
				);
			}
		}
	}

	/**
	* Determine whether this page needs to use the defaut outfits or not - based on configuration of outfits
	*
	* @parameter $_sect_max (integer) Maximum number of outfits that can be displayed
	*
	* @return (null)
	*/
	private function should_use_default_outfits( $_sect_max ) {
		$this->use_default_outfits = true; // start by assuming we will use the default outfits
		for ( $x = 0; $x < $_sect_max; $x ++ ) {
			// go through all possible sections looking for configured outfits - if find open then do not use default outfits
			if ( ( isset( $this->pmlay_settings['outfits'][ $x ] ) ) && ( is_array( $this->pmlay_settings['outfits'][ $x ] ) ) ) {
				// is an outfit configured for this section?
				if ( -1 < intval( $this->pmlay_settings['outfits'][ $x ]['template'] ) ) {
					// is a template selected for this outfit?
					$this->use_default_outfits = false;
					break;
				}
			}
		}
	}

	/**
	* Get the list of posts that will appear in the baselist of a category or tag page and put them in $this->pmlay_settings['baselist_postids']
	*
	* @parameter $_list_id (integer) term ID for the list
	*
	* @return (null)
	*/
	function get_baselist_postids( $_list_id ) {
		$_list_id = intval( $_list_id );
		// except on page one where the settings indicate not to use the baselist
		if ( 0 < $_list_id ) {
			$_postids = array();
			$_args = array(
				'posts_per_page' => 50,
				'paged' => 1,
				'post_type' => $this->post_types,
				'category__in' => array( $_list_id ),
				'orderby' => 'date',
				'order' => 'DESC',
				'fields' => 'ids',
				'suppress_filters' => false,
			);
			$cache_key = 'get_baselist_postids' . $_list_id;
			$_postlist = wp_cache_get( $cache_key );
			if ( false === $_postlist ) {
				$_postlist = get_posts( $_args );
				wp_cache_set( $cache_key, $_postlist, '', 300 ); //VIP: cache for 5 minutes
			}
			$this->pmlay_settings['baselist_postids'] = $_postlist;
		}
	}

	/**
	* Get a list of posts for a list slot in an outfit
	* CMJ: Needs refactoring in next release (v.4.0.1)
	*
	* @parameter $term_id (integer) term ID for the list
	* @parameter $term_type (string) term type for the list (home|category|tag)
	* @parameter $_start (integer) position in the list for the first post
	* @parameter $_limit (integer) maximum number of posts to return
	* @parameter $_news_ticker_position (integer) position of the content widget / news ticker
	*
	* @return (null)
	*/
	function get_postlist( $term_id, $term_type, $_start = 0, $_limit = 0, $_news_ticker_position = -1 ) {
		// FIND AND INCLUDE EACH OUTFIT ITERATIVELY
		// on mobile slot in the widgets from (array) $this->sidebar_widgets where indicated in the correct order
		// save both desktop and mobile outfits - separate timestamps
		if ( 1 < $this->page_num ) {
			return; // exit if this is page 2+
		}
		$_cache_timestamp = intval( get_transient( $this->transient_names['expire'] ) );
		$_now_timestamp = current_time( 'timestamp' );
		// Variable $_cache_timestamp contains a timestamp indicating when to replace the cached outfit content
		$_xur = ( isset( $_GET['xur'] ) ) ? sanitize_text_field( wp_unslash( $_GET['xur'] ) ) : '';
		if ( 'strange' === $_xur ) {
			// force cache expiry
			$this->use_outfit_cache = false;
			$this->update_outfit_cache = true;
		} elseif ( 0 >= $_cache_timestamp ) {
			// the timestamp cache does not exist so neither does the outfits cache probably so recreate both
			$this->use_outfit_cache = false;
			$this->update_outfit_cache = true;
		} elseif ( $_now_timestamp < $_cache_timestamp ) {
			// the cache has not yet expired so use it
			$this->use_outfit_cache = true;
			$this->update_outfit_cache = false;
		} else {
			// the cache has expired so reset it and the timestamp
			$this->use_outfit_cache = false;
			$this->update_outfit_cache = true;
		}
		if ( true === $this->use_outfit_cache ) {
			$_trans_outfits = $this->pn_get_option( $this->transient_names['outfits'], 0 );
			$_trans_posts = get_transient( $this->transient_names['posts'] );
			$this->outfit_cache = $_trans_outfits;
			if ( empty( $_trans_outfits ) ) { //VIP: Do not change this to ternary operator, it'll break because the first element in the array may be an empty string.
				$this->outfit_cache = '';
			}
			$this->pmlay_settings['posts_cached'] = ( false === $_trans_posts ) ? '' : json_decode( $_trans_posts ); // get the post list for cached outfits
			$this->pmlay_settings['posts_shown'] = $this->pmlay_settings['posts_cached']; // set the cached post list as the beginning of the post exclusion list for outfits and widgets
		}
		if ( true === empty( $this->outfit_cache ) ) {
			// it is possible that the cache has not been forced to refresh but that the transient has expired despite being set to never expire - in this case force page to rebuild cache
			$this->use_outfit_cache = false;
			$this->update_outfit_cache = true;
		}
		if ( true === $this->update_outfit_cache ) {
			// set time to refresh HTML cache to current timestamp plus $this->cache_duration seconds to set duration for which this cached content will be used
			$_cache_timestamp = $_now_timestamp + $this->cache_duration;
			// add/subtract a random number of seconds between -30 and 30
			set_transient( $this->transient_names['expire'], $_cache_timestamp, 0 ); // never expire, always reset
			$this->pmlay_settings['posts_cached'] = ''; // reset the post list for cached outfits
			$this->pmlay_settings['posts_shown'] = ''; // reset the cached post list as the beginning of the post exclusion list for outfits and widgets
		}
		$this->display_news_ticker( 99, true );
		do_action( 'pm_layouts_before_section_1' );
		$_count = 0;
		$_sect_max = intval( $this->pmlay_count[ 'sections_' . $term_type ] ); // CMJ MAX: section_home, section_category, section_tag
		$_limit = ( 0 == $_limit ) ? $_sect_max : $_limit;
		// calculate the number of ads on index page #1 for the term on sites that embed widgets in outfits
		$this->count_ads = 0;
		$this->widget_keys = array_keys( $this->sidebar_widgets );
		if ( $this->is_mobile ) {
			// count count_ads and enqueue scripts for mobile
			foreach ( $this->sidebar_widgets as $_skey => $_sary ) {
				foreach ( $_sary as $_wkey => $_sb_widget ) {
					$_widget_type = isset( $_sb_widget['type'] ) ? trim( $_sb_widget['type'] ) : '';
					if ( 'postmedia_layouts_ad' === $_widget_type ) {
						$this->count_ads ++;
					}
				}
			}
		} else {
			// count count_ads and enqueue scripts for desktop
			$this->prepare_outfit_widgets( $_limit );
		}
		$filter_outfits = false;
		if ( 0 === $term_id ) {
			//on the home page, see if we need to filter the outfits rendered by market size
			$market_size = get_option( 'pn_theme_market_size', false );
			if ( is_string( $market_size ) && isset( $this->pmlay_count['outfit_order'] ) && array_key_exists( $market_size, $this->pmlay_count['outfit_order'] ) ) {
				$filter_outfits = true;
				$outfit_order = $this->pmlay_count['outfit_order'][ $market_size ];
			}
		}

		for ( $this->pmlay_count['section'] = 0; $this->pmlay_count['section'] < $_limit; $this->pmlay_count['section'] ++ ) {
			$this->pmlay_count['module'] = 0;
			$this->pmlay_count['header'] = 0;
			$this->pmlay_count['display_id'] = $this->pmlay_count['section'] + 1;
			$_outfit_html = '';

			if ( isset( $this->pmlay_settings['outfits'][ $this->pmlay_count['section'] ]['template'] ) ) {
				//determine if we should render the outfit ( filter_outs is false OR the outfit number is in the list of outfits to display )
				if ( ! $filter_outfits || ( is_array( $outfit_order ) && in_array( (string) ( $this->pmlay_count['section'] + 1 ), $outfit_order, true ) ) ) {
					$_template = $this->pmlay_settings['outfits'][ $this->pmlay_count['section'] ]['template'];
					$_outfit_html = $this->display_single_outfit( $_template );
					if ( '' !== $_outfit_html ) {
						$_count ++;
					}
				}
			}
			$this->outfit_content[0] = '';
			if ( $_start <= $this->pmlay_count['section'] ) {
				$_sect_next = intval( $this->pmlay_count['section'] ) + 1;
				if ( false === $this->use_outfit_cache ) {
					$_outfit_html .= $this->display_news_ticker( $_sect_next, false );
				}
				$this->outfit_content[ $_sect_next ] = $_outfit_html; // use section plus one so we can save the baselist in index zero
			}
		}
	}

	private function display_single_outfit( $_template = -1 ) {
		if ( -1 === $_template || '-1' === $_template ) {
			return;
		}
		$_outfit_html = '';
		ob_start();
		do_action( 'pm_layouts_before_section_' . $this->pmlay_count['section'] );
		do_action( 'pm_layouts_before_section', $this->pmlay_count['section'] );
		$this->choose_template( 'outfit', $_template, true, true, false, false ); // adaptive for mobile
		do_action( 'pm_layouts_after_section', $this->pmlay_count['section'] );
		do_action( 'pm_layouts_after_section_' . $this->pmlay_count['section'] );
		$_outfit_html = ob_get_contents();
		ob_end_clean();
		$this->count_emotion_bubbles = 0;
		$this->count_pull_quotes = 0;
		return $_outfit_html;
	}

	private function prepare_outfit_widgets( $_limit = 0 ) {
		if ( isset( $this->sidebar_widgets[0] ) ) {
			$this->temp_widgets = $this->sidebar_widgets[0];
		}
		$_count = 0;
		for ( $_sect = 0; $_sect < $_limit; $_sect ++ ) {
			if ( isset( $this->pmlay_settings['outfits'][ $_sect ] ) ) {
				$_outfit_template = intval( $this->pmlay_settings['outfits'][ $_sect ]['template'] );
				if ( isset( $this->outfit_settings[ $_outfit_template ] ) ) {
					$_widget_slots = 0;
					if ( isset( $this->outfit_settings[ $_outfit_template ][3] ) ) {
						$_widget_slots = intval( $this->outfit_settings[ $_outfit_template ][3] );
					}
					if ( 0 < $_widget_slots ) {
						for ( $_slot_num = 0; $_slot_num < $_widget_slots; $_slot_num ++ ) {
							$this->prepare_outfit_single_slot( $_sect, $_slot_num );
						}
					}
				}
			}
		}
	}

	private function prepare_outfit_single_slot( $_sect, $_slot_num ) {
		if ( isset( $this->pmlay_settings['outfits'][ $_sect ]['widgets'][ $_slot_num ] ) ) {
			switch ( $this->pmlay_settings['outfits'][ $_sect ]['widgets'][ $_slot_num ] ) {
				case 'dfpad':
					// there is an ad in this outfit in this slot
					$this->count_ads ++;
					break;
				case 'auto':
					// pull widget from sidebar in this outfit in this slot
					if ( 0 < count( $this->temp_widgets ) ) {
						$_sb_widget = array_shift( $this->temp_widgets );
						$_widget_type = isset( $_sb_widget['type'] ) ? trim( $_sb_widget['type'] ) : '';
						if ( 'postmedia_layouts_ad' === $_widget_type ) {
							$this->count_ads ++;
						}
					}
					break;
				default:
					if ( 'pn_kaltura_playlist' === substr( $this->pmlay_settings['outfits'][ $_sect ]['widgets'][ $_slot_num ], 0, 19 ) ) {
						// Kaltura playlists enqueue their JS but that happens outside the HTML cache so need to re-enqueue
						$this->enqueue_files_video();
					} elseif ( 'pn_galleries-widget' === substr( $this->pmlay_settings['outfits'][ $_sect ]['widgets'][ $_slot_num ], 0, 19 ) ) {
						// if pulling from cache
						if ( $this->use_outfit_cache ) {
							$this->enqueue_files_gallery();
						}
					}
					break;
			}
		}
	}

	function enqueue_files_video() {
		$_widget_option = get_option( 'widget_pn_kaltura_playlist' );
		if ( ! isset( $_widget_option ) ) {
			return;
		}
		if ( ! isset( $this->pmlay_settings['outfits'][ $_sect ]['widgets'][ $_slot_num ] ) ) {
			return;
		}
		$_widget_num = intval( substr( $this->pmlay_settings['outfits'][ $_sect ]['widgets'][ $_slot_num ], 20, 1000 ) );
		if ( ( ! isset( $_widget_option[ $_widget_num ] ) )
			|| ( ! isset( $_widget_option[ $_widget_num ]['pn_kaltura_playlist'] ) )
			|| ( '' === trim( $_widget_option[ $_widget_num ]['pn_kaltura_playlist'] ) ) ) {
				return;
		}
		$_widget_code = trim( $_widget_option[ $_widget_num ]['pn_kaltura_playlist'] );
		$_widget_url = 'http://www.canada.com/pmvideo/playlist/js/pmvids-playlist.min.js?playlistid=' . $_widget_code . '&title=&overridecss=false&responsive=true&glyphIt=true&shareUrl=';
		wp_enqueue_script( 'pn_playlist_' . $_widget_code, esc_url( $_widget_url ), null, '', true );
	}

	function enqueue_files_gallery() {
		$_widget_option = get_option( 'widget_pn_galleries-widget' );
		if ( ( ! isset( $_widget_option ) ) || ( '' === trim( $_widget_option ) ) ) {
			return;
		}
		$_widget_num = intval( substr( $this->pmlay_settings['outfits'][ $_sect ]['widgets'][ $_slot_num ], 20, 1000 ) );
		if ( ( ! isset( $_widget_option[ $_widget_num ] ) )
			|| ( ! isset( $_widget_option[ $_widget_num ]['gallery_id'] ) )
			|| ( '' === trim( $_widget_option[ $_widget_num ]['gallery_id'] ) ) ) {
				return;
		}
		$_widget_code = trim( $_widget_option[ $_widget_num ]['gallery_id'] );
		// run a method on SnapGalleries to enqueue scripts - pass $this->pmlay_settings['outfits'][ $_sect ]['widgets'][ $_slot_num ] );
		if ( class_exists( 'Postmedia\Web\Plugins\SnapGallery' ) ) {
			ob_start();
			Postmedia\Web\Plugins\SnapGallery::render_widget( $_widget_code );
			$_widget_html = ob_get_contents(); // throw HTML away - just need enqueued scripts and localized JS
			ob_end_clean();
			// CMJ unnecessary I believe echo 'nocache';
		}
	}

	/**
	* Calculate the approximate height of the current post in pixels
	*
	* @return integer Pixel height of post in current context
	*/
	function get_post_height() {
		global $post, $paged;
		// an array of fixed heights for different post/page types
		$_heights = array(
			'term' => 2000,
			'page' => 1500,
			'post' => 0,
			'gallery' => 2000,
			'feature' => 2000,
			'featured_image' => 750,
			'default_content' => 300,
			'caption' => 600,
			'pn_versus' => 150,
			'post_footer' => 1000,
		);
		// allow different sites to implement different heights and add post types and shortcodes
		$_heights = apply_filters( 'pmlay_get_post_height', $_heights );

		//make sure we have a default content height
		if ( ! isset( $_heights['default_content'] ) ) {
			$_heights['default_content'] = 200;
		}

		$_post_height = 0;
		if ( ( ( 1 < $paged ) && ( is_tag() || is_category() ) ) || is_search() || is_archive() ) {
			$_post_height = isset( $_heights['term'] ) ? intval( $_heights['term'] ) : 2000;
		} elseif ( is_page() ) {
			$_post_height = isset( $_heights['page'] ) ? intval( $_heights['page'] ) : 1500;
		} elseif ( $post->ID ) {
			$_type = isset( $post->post_type ) ? trim( $post->post_type ) : '';
			if ( '' !== $_type ) {
				if ( isset( $_heights[ $_type ] ) ) {
					$_post_height = intval( $_heights[ $_type ] );
				}
			}
			// if no fixed height is set for this post type then try to calculate the actual height of the content block
			if ( 0 >= $_post_height ) {
				$_num_cols = 3;
				$_measures = array(
					'title' => array(
						'char_per_line' => 35,
						'px_per_line' => 60,
					),
					'story' => array(
						'char_per_line' => ( 3 === $_num_cols ? 60 : 85 ),
						'px_per_line' => 30,
					),
				);
				$_pixels_per_image = 630; // avg height of a featured image incl caption and spacing
				$_story_chars = strlen( $post->post_content );
				$_story_lines = intval( $_story_chars / $_measures['story']['char_per_line'] ) + 1;
				$_content = $post->post_content;
				$_post_height = 0;
				$_shortcode_count = 0;
				$_shortcode_pattern = get_shortcode_regex();
				if ( preg_match_all( '/' . $_shortcode_pattern . '/s', $_content, $matches ) ) {
					$_shortcode_count = count( $matches[0] );
				}
				foreach ( $matches[2] as $shortcode_name ) {
					$_post_height += $this->get_content_height( $shortcode_name, $_heights, $_heights['default_content'] );
				}
				if ( 0 < $_shortcode_count ) {
					$_content = strip_shortcodes( $_content );
				}
				$_paras = explode( "\n", $_content );
				$_story_paras = 0;
				foreach ( $_paras as $_para ) {
					if ( '' !== trim( $_para ) ) {
						$_story_paras += 1; // regular text paragraph
					}
				}
				$_story_lines += $_story_paras;
				$_title_chars = strlen( $post->post_title );
				$_title_lines = intval( $_title_chars / $_measures['title']['char_per_line'] ) + 1;
				$_post_height += $_title_lines * $_measures['title']['px_per_line']; // title
				$_post_height += $_story_lines * $_measures['story']['px_per_line']; // story
				if ( has_post_thumbnail( $post ) ) {
					$_post_height += $this->get_content_height( 'featured_inamge', $_heights ); // featured image
				}
				$_post_height += $this->get_content_height( 'post_footer', $_heights );
			}
		}
		return intval( $_post_height ); // story height in pixels
	}

	function get_content_height( $content_name, $heights, $default = 0 ) {
		if ( isset( $heights[ $content_name ] ) && 0 < $heights[ $content_name ] ) {
			return intval( $heights[ $content_name ] );
		}
		return intval( $default );
	}

	function post_count_increment() {
		// count each post displayed on a page
		if ( $this->is_mobile ) {
			if ( 0 === $this->posts_displayed ) {
				// show sidebar widgets on mobile after the first post
				$this->safe_echo( $this->show_widget_slot() ); // Early-escaped
			}
		}
		$this->posts_displayed ++; // posts displayed so far on this page
	}

	function override_loop() {
		$_ret = 10;
		if ( true === is_category() ) {
			// category page
			$term_id = get_queried_object_id();
			$this->pmlay_settings = $this->get_termdata( $term_id );
			$_override = isset( $this->pmlay_settings['override'] ) ? $this->pmlay_settings['override'] : '';
			if ( '' !== $_override ) {
				// uses a feed
				$_ret = 1;
			}
		}
		return $_ret;
	}

	/**
	* This method gets the list of outfits from $this->get_layouts_data(), then modifies it for use on the front end
	*
	* @return array $_layouts All data entered for the Layouts on the category/tag/home page edit screen
	*/
	function get_termdata( $_term_id, $_term_type = '', $_use_backup = false ) {
		if ( true === is_feed() ) {
			return null;
		}
		// sets public $pmlay_settings
		$_layouts = $this->get_layouts_data( $_term_id, $_use_backup );
		$_keep = $this->pmlay_settings;
		$_layouts['term_id'] = $_term_id;
		$_layouts['term_type'] = $_term_type;
		$_layouts['override_max'] = isset( $_keep['override_max'] ) ? intval( $_keep['override_max'] ) : 0;
		$_layouts['posts_shown'] = isset( $_keep['posts_shown'] ) ? $_keep['posts_shown'] : '';
		$_layouts['posts_cached'] = isset( $_keep['posts_cached'] ) ? $_keep['posts_cached'] : '';
		$_layouts['urls_shown'] = isset( $_keep['urls_shown'] ) ? $_keep['urls_shown'] : array();
		$_layouts['sidebar'] = true; // force true instead of using ( bool ) $_keep['sidebar']
		$_layouts['posts_avail'] = isset( $_keep['posts_avail'] ) ? intval( $_keep['posts_avail'] ) : 0;
		$_layouts['posts_override_countshown'] = isset( $_keep['posts_override_countshown'] ) ? intval( $_keep['posts_override_countshown'] ) : 0;
		$_layouts['list_class'] = '';
		if ( '' === $_term_type ) {
			$_term_type = $this->pmlay_settings['term_type'];
		}
		$_layouts['advertorial_type'] = '';
		$_cat_meta = get_option( $_term_type . '_' . $_term_id . '_meta' );
		if ( is_array( $_cat_meta ) ) {
			if ( isset( $_cat_meta['sponsored_editorial'] ) ) {
				if ( '' !== trim( $_cat_meta['sponsored_editorial'] ) ) {
					$_layouts['advertorial_type'] = trim( $_cat_meta['logo_label'] );
				}
			}
		}
		$_layouts['baselist_postids'] = array();
		$_layouts['gather'] = false;
		$_layouts['retrieved'] = true; // term data retrieval has run so could skip on future calls
		$_layouts = apply_filters( 'pn_add_additional_pmlay_settings', $_layouts );
		return $_layouts;
	}

	/**
	* This method gets the list of outfits and all index page configuration
	*
	* @return array $_layouts All data entered for the Layouts on the category/tag/home page edit screen
	*/
	function get_layouts_data( $_term_id, $_use_backup = false ) {
		$_layouts = array();
		if ( true === $_use_backup ) {
			$_key = 'pmlayouts_lists_bak_' . $_term_id;
		} else {
			$_key = 'pmlayouts_lists_' . $_term_id;
		}
		$_list_json = $this->pn_get_option( $_key, 0 );
		if ( '' !== $_list_json ) {
			$_lists = json_decode( $_list_json, true );
		}
		if ( is_array( $_lists ) ) {
			$_layouts = $_lists;
			$_key = 'pmlayouts_p1_offset_' . $_term_id;
			$_offset_json = $this->pn_get_option( $_key, 0 );
			$_offset = json_decode( $_offset_json, true );
			if ( is_array( $_offset ) ) {
				$_layouts['page1_offset'] = $_offset['page1_offset'];
				$_layouts['phone1_offset'] = $_offset['phone1_offset'];
			}
		}
		return $_layouts;
	}

	function set_layouts_data( $_term_id, $_data, $_use_backup = false ) {
		if ( true === $_use_backup ) {
			$_key = 'pmlayouts_lists_bak_' . $_term_id;
		} else {
			$_key = 'pmlayouts_lists_' . $_term_id;
		}
		$this->pn_update_option( $_key, wp_json_encode( $_data ) );
	}

	function set_layouts_page1_offset( $_term_id, $_data ) {
		$_key = 'pmlayouts_p1_offset_' . $_term_id;
		$this->pn_update_option( $_key, wp_json_encode( $_data ) );
	}

	/**
	* List posts in one outfit list
	* CMJ refactor
	*
	* @param array $_args	Parameters defining the list source and output requirements
	*
	* @return array $_layouts All data entered for the Layouts on the category/tag/home page edit screen
	*/
	function display_list( $_args ) {
		// v.3.1: CMJ - TEST FOR VALID TRANSIENT AND USE THAT IF SO, ELSE REFRESH TRANSIENT
		// called from within outfit templates and for baselist
		// This is the core method that governs the display of every list in every outfit
		$this->list_uses_wcm = false;
		if ( 'widget' === $this->pmlay_settings['outfit_type'] ) {
			$_args['increment'] = false;
		}
		$_defaults = array(
			'template' => 0,
			'posts_max' => 10,
			'posts_per_row' => 0,
			'fields' => array(),
			'image_settings' => array(),
			'title_settings' => array(),
			'class' => '',
			'list_type' => '',
			'list_id' => '',
			'template_type' => '',
			'is_baselist' => false,
			'echo' => true,
			'increment' => true,
		);
		foreach ( $_defaults as $_key => $_val ) {
			if ( ! isset( $_args[ $_key ] ) ) {
				$_args[ $_key ] = $_val;
			}
		}
		$_ignore_cache = false;
		if ( true === $_args['is_baselist'] ) {
			$_ignore_cache = true;
		}
		$this->list_args = array(
			'posts_per_page' => 50,
			'paged' => '1',
			'suppress_filters' => false,
			'post_type' => $this->post_types,
		); // reset args array for new query
		if ( ( 'baselist' === $this->pmlay_settings['outfit_type'] ) && ( 2 <= $this->page_num ) ) {
			$_p1off = intval( ( true === $this->is_mobile ) ? $this->pmlay_settings['phone1_offset'] : $this->pmlay_settings['page1_offset'] );
			// calculate the number of posts to skip based on the ones shown on page 1 from the baselist
			if ( true === $this->pmlay_count['show_baselist'] ) {
				$_baselist_pages = 1;
			} else {
				$_baselist_pages = 2;
			}
			$_baselist_offset = ( $this->page_num - $_baselist_pages ) * intval( $_args['posts_max'] );
			$_offset = $_baselist_offset + $_p1off;
			$this->pmlay_settings['override_count'] += $_p1off;
			$this->list_args['offset'] = $_offset;
		}
		if ( ! is_admin() ) {
			/*** FIND AND RETURN A LIST OF STORIES FROM VARIOUS SOURCES TO THE MODULE TEMPLATE *** list sources: tag, cat, author, chartbeat, zoninator (manually curated) ***/
			$_section_num = intval( $this->pmlay_count['section'] );
			$_module_num = intval( $this->pmlay_count['module'] );
			$_list_data = ( isset( $this->pmlay_settings['outfits'][ $_section_num ]['lists'][ $_module_num ] ) ) ? $this->pmlay_settings['outfits'][ $_section_num ]['lists'][ $_module_num ] : array();
			// when inserting a list widget into an outfit from the outfit drop menu, it has not yet been rendered and the system counts it as a list module - lists that are part of the page sidebar get rendered before the outfits so they're fine
			$this->pmlay_settings['image_settings'] = $_args['image_settings'];
			$this->pmlay_settings['title_settings'] = $_args['title_settings'];
			$this->pmlay_settings['posts_per_row'] = intval( $_args['posts_per_row'] );
			$this->pmlay_settings['responsive_class'] = $_args['class'];
			$this->pmlay_settings['fields'] = $_args['fields'];

			// Load more button customization per list.
			if ( isset( $_list_data['button_label'] ) ) {
				$this->pmlay_settings['button_label'] = $_list_data['button_label'];
			}
			if ( isset( $_list_data['button_link'] ) ) {
				$this->pmlay_settings['button_link'] = $_list_data['button_link'];
			}

			if ( isset( $_list_data['target'] ) ) {
				// use the target for this list
				$_target = $_list_data['target'];
			} else {
				// use the page default target
				$_target = isset( $this->pmlay_settings['target'] ) ? $this->pmlay_settings['target'] : '';
			}
			$this->pmlay_settings['target'] = $_target;
			if ( 'sidebar' === $_args['list_type'] ) {
				// widget so already have the list and have run through this method once already so skip most of this
				$this->choose_template( 'list', $_args['template'], true, true, false, $_ignore_cache );
				return;
			}
			$this->pmlay_settings['max_posts'] = $_args['posts_max'];
			$this->pmlay_settings['list'] = null;
			$_post_count = 0;
			$_show_module = false;
			// force get_posts to return only posts containing images if set -- if no image then posts will not display
			// better to parse these after they're returned and only display the ones with thumbs
			// do this in $this->remove_displayed_posts() instead of by setting $this->list_args['meta_key'] = '_thumbnail_id' where ( in_array( 'thumb', $_args['fields'], true ) )
			$_override = isset( $this->pmlay_settings['override'] ) ? trim( $this->pmlay_settings['override'] ) : '';
			$_list_id = '';
			$_list_name = '';
			if ( '' !== $_args['list_type'] ) {
				$_show_module = true;
				$_typ = $_args['list_type'];
				$_list_id = ( '' !== $_args['list_id'] ) ? $_args['list_id'] : $_override;
				if ( 'rss' === $_typ && $this->is_guid( $_list_id ) ) {
					$_typ = 'shar';
				}
				$_list_id2 = isset( $_args['list_id2'] ) ? trim( $_args['list_id2'] ) : '';
				$_list_name = isset( $_args['list_name'] ) ? trim( $_args['list_name'] ) : '';
				$_module_count = 99;
			} elseif ( isset( $_list_data['type'] ) ) {
				$_show_module = true;
				$_typ = $_list_data['type'];	// get post list from $this->pmlay_count['module'] and pass that as global var to template_part
				$_list_id = $_list_data['id'];
				$_list_id2 = isset( $_list_data['id2'] ) ? trim( $_list_data['id2'] ) : '';
				$_list_name = isset( $_list_data['name'] ) ? trim( $_list_data['name'] ) : '';
				$_module_count = $_module_num;
			} elseif ( true === $this->use_default_outfits ) {
				$_show_module = true;
			}
			// When community category has override setting, show RSS
			if ( '' === trim( $_list_id ) || ( Utilities::is_community() && '' !== $_override ) ) {
				if ( is_category() || is_tag() ) {
					if ( 'wcm' === $_override ) {
						$_typ = 'shar';
						$_list_id = $_override;
					} elseif ( '' !== $_override ) {
						$_typ = 'rss';
						$_list_id = $_override;
						if ( $this->is_guid( $_list_id ) ) {
							$_typ = 'shar';
						}
						$_list_name = '';
					} elseif ( is_category() ) {
						$_typ = 'cax';
						$_list_id = $this->pmlay_settings['term_id'];
					} elseif ( is_tag() ) {
						$_typ = 'tag';
						$_list_id = $this->pmlay_settings['term_id'];
					}
				}
			}
			if ( '' !== trim( $_list_id ) ) {
				$_show_module = true;
			}
			if ( true == $_show_module ) {
				if ( false === $_args['echo'] ) {
					ob_start();
				}
				$this->pmlay_settings['list_type'] = $_typ;
				$this->pmlay_settings['list_id'] = $_list_id;
				if ( '' !== $_list_id || '' !== $_list_name ) {
					switch ( $_typ ) {
						case 'tag':
							$this->display_module_get_tag( $_list_id, $_list_name );
							break;
						case 'cat':
							$this->display_module_get_cat( $_list_id, $_list_name );
							break;
						case 'cax':
							$this->display_module_get_cax( $_list_id, $_list_name );
							break;
						case 'ug':
						case 'ugc':
						case 'ugs':
							$this->display_module_get_ug( $_list_id, $_list_id2, $_typ );
							break;
						case 'auth':
							$this->display_module_get_aut( $_list_id );
							break;
						case 'zon':
							$this->display_module_get_zon( $_list_id, $_list_name );
							break;
						case 'shar':
							if ( isset( $_list_data['thumbs'] ) ) {
								$this->pmlay_settings['allow_posts_without_thumbs'] = ( 0 === $_list_data['thumbs'] ) ? false : true;
							} else {
								$this->pmlay_settings['allow_posts_without_thumbs'] = false;
							}
							$this->list_wcm( 'shar', $_list_id, $_list_id2, $_list_name, $this->list_args['posts_per_page'], $this->list_args['offset'] );
							// handle possibility that WCM is inaccessible - fall back to WP
							if ( null === $this->pmlay_settings['list'] ) {
								$_list_id = intval( $this->pmlay_settings['term_id'] );
								$this->pmlay_settings['list_id'] = $_list_id;
								$_typ = 'cat';
								$this->display_module_get_cat( $_list_id, $_list_name );
							}
							break;
						case 'rss':
							// RSS feed
							if ( isset( $_list_data['thumbs'] ) ) {
								$this->pmlay_settings['allow_posts_without_thumbs'] = ( 0 === $_list_data['thumbs'] ) ? false : true;
							} else {
								$this->pmlay_settings['allow_posts_without_thumbs'] = false;
							}
							$_paged = (get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
							if ( 2 > $_paged ) {
								// page 1
								$_offset = 0;
							} else {
								$_p1off = intval( ( true === $this->is_mobile ) ? $this->pmlay_settings['phone1_offset'] : $this->pmlay_settings['page1_offset'] );
								$_offset = ( $_paged - 1 ) * 10 + $_p1off;
							}
							$this->list_rss( $_list_id, $_post_count, $this->list_args['posts_per_page'], $_offset, 'External Feed', $_args['is_baselist'] );
							break;
						case 'chrt':
							// Chartbeat post list
							$this->list_chartbeat( $_list_id, $_post_count, $this->list_args['posts_per_page'] );
							break;
						default:
							$_return = apply_filters( 'pn_layouts_check_if_custom_type_exists', false, $_typ );
							if ( $_return ) {
								$this->list_custom( $_typ, $_list_id );
							}
							break; // see below where list length == 0
					}
				}

				// Modify any post data in list before proceeding.
				$this->pmlay_settings['list'] = apply_filters( 'pn_layouts_single_list_generated', $this->pmlay_settings['list'], $_list_data );

				$_cnt_posts = isset( $this->pmlay_settings['list'] ) ? count( $this->pmlay_settings['list'] ) : 0;
				$this->offset['gather'] = false;
				if ( ( 2 > $this->page_num ) && ( false === $_args['is_baselist'] ) ) {
					// if this is the first page and this is not the base list...
					if ( ( '' === $_override ) && ( '' !== $_list_id ) && ( 0 !== $_list_id ) && ( in_array( $_typ, array( 'cat', 'cax' ), true ) ) ) {
						$category_parents = get_category_parents( intval( $_list_id ), false, ',' );
						if ( false === is_wp_error( $category_parents ) && false === empty( $category_parents ) ) {
							$_cat_list = ',' . $category_parents;
						}
						$_cat = get_category( $_list_id );
						$_cat_name = $_cat->name;
						if ( false !== strpos( $_cat_list, $_cat->name ) ) {
							$this->offset['gather'] = true;
						}
					} elseif ( 'zon' === $_typ ) {
						// if this is a zone then only some of the posts may be included in the offset
						$this->offset['gather'] = true; // this needs to be more subtle to get an accurate count
					} elseif ( ( '' === $_list_id ) || ( $_override === $_list_id ) || ( $this->pmlay_settings['term_id'] == $_list_id && '' === $_override ) ) {
						// if this module uses the base list then count the posts it displays to add to the offset for subsequent pages and base list here
						$this->offset['gather'] = true;
					}
				}
				if ( 'widget' === $this->pmlay_settings['outfit_type'] ) {
					// do not count posts that are in widgets
					$this->offset['gather'] = false;
				}
				if ( 0 < $_cnt_posts ) {
					$this->pmlay_settings['count_posts'] = 0;			// reset post count
					$this->remove_displayed_posts( $_section_num, $_args['fields'] );
					$_template_type = ( '' == $_args['template_type'] ) ? 'list' : $_args['template_type']; // template type to use: module or widget
					$this->choose_template( $_template_type, $_args['template'], true, true, false, $_ignore_cache );
					unset( $this->pmlay_settings['allow_posts_without_thumbs'] ); // not needed after this point
				}
				if ( false === $_args['echo'] ) {
					$_ret = ob_get_contents();
					ob_end_clean();
					// increment the list source here since you won't get to it below
					if ( true === $_args['increment'] ) {
						$this->pmlay_count['module'] ++;
					}
					return $_ret;
				}
			}
			if ( true === $_args['increment'] ) {
				$this->pmlay_count['module'] ++;
			}
		} else {
			// admin back end
			$this->choose_template( 'list', $_args['template'], true, true, false, false );
		}
	}

	function get_layout_posts() {
		// originally filtered sponsored posts here by adding a meta query but this is costly so filtering after post query
		// then run core WP get_posts and return the post list object
		$cache_key = 'get_layout_posts_' . md5( serialize( $this->list_args ) );
		$_postlist = wp_cache_get( $cache_key );
		if ( false === $_postlist ) {
			$_postlist = get_posts( $this->list_args );
			wp_cache_set( $cache_key, $_postlist, '', 300 ); // VIP: adding 5 minute cache
		}
		return $_postlist;
	}

	/**
	* Set the arguments required to get a list of posts by author
	* In MVP post lists from authors will be pulled from local WP data
	*
	* @param $_list_id integer term ID
	*
	* @return (null)
	*/
	function display_module_get_aut( $_list_id ) {
		// Author post list
		// get data from Wordpress
		$_list = get_user_by( 'id', $_list_id );
		if ( false !== $_list ) {
			$this->pmlay_settings['list_name'] = $_list->display_name;
			$this->pmlay_settings['list_link'] = '<a href="' . esc_url( get_author_posts_url( $_list_id ) ) . '">' . esc_html( $_list->display_name ) . '</a>'; // doesn't exist get_author_link
			$this->list_args['author'] = $_list_id;
			$this->list_args['orderby'] = 'modified';
			$this->list_args['order'] = 'DESC';
			foreach ( $this->post_types_excluded_from_aut as $value ) {
				$key = array_search( $value, $this->list_args['post_type'], true );
				if ( false !== $key ) {
					unset( $this->list_args['post_type'][ $key ] );
				}
			}
			$this->pmlay_settings['list'] = $this->get_layout_posts();
		}
	}

	/**
	* Set the arguments required to get a list of posts by Zoninator zone
	*
	* @param $_list_id integer term ID
	*
	* @return (null)
	*/
	function display_module_get_zon( $_list_id, $_list_name ) {
		// Zoninator post list
		if ( true === $this->site_uses_wcm() ) {
			// get data from WCM
			$_wcm_id = $this->get_wcm_zone_id( $_list_id );
			$this->list_wcm( 'zon', $_wcm_id, '', $_list_name );
			$this->pmlay_settings['list_name'] = $_list_name;
		}
		// if the WCM query failed to reach the API it will have changed $this->pmlay_count['show_wcm'] to false
		if ( false === $this->site_uses_wcm() ) {
			// get data from Wordpress
			$_list = get_term( $_list_id, 'zoninator_zones' );
			if ( false !== $_list ) {
				$_req_thumbnail = ( ( isset( $this->list_args['meta_key'] ) ) && ( '_thumbnail_id' === $this->list_args['meta_key'] ) ) ? true : false; // an image is required for each post?
				$this->pmlay_settings['list_name'] = $_list->name;
				$this->pmlay_settings['list_link'] = $_list->name;
				$this->list_args['order'] = 'ASC';
				$this->list_args['orderby'] = 'meta_value_num';
				$this->list_args['meta_key'] = '_zoninator_order_' . $_list_id;
				$_postlist = $this->get_layout_posts();
				if ( true === $_req_thumbnail ) {
					// remove any posts that do not contain images if they are required in this list
					$_postlist_new = array();
					$_count = 0;
					foreach ( $_postlist as $_key => $_post ) {
						if ( true === has_post_thumbnail( $_post->ID ) ) {
							$_postlist_new[ $_count ] = $_post;
							$_count ++;
						}
					}
					$this->pmlay_settings['list'] = $_postlist_new;
				} else {
					$this->pmlay_settings['list'] = $_postlist;
				}
			}
		}
	}

	/**
	* Set the arguments required to get a list of posts by tag
	*
	* @param $_list_id integer term ID
	*
	* @return (null)
	*/
	function display_module_get_tag( $_list_id, $_list_name ) {
		// Tag post list
		if ( true === $this->site_uses_wcm() ) {
			// get data from WCM
			$_offset = isset( $this->list_args['offset'] ) ? intval( $this->list_args['offset'] ) : 0;
			$this->list_wcm( 'tag', $_list_id, '', $_list_name, $this->list_args['posts_per_page'], $_offset );
			$this->pmlay_settings['list_name'] = $_list_name;
		}
		// if the WCM query failed to reach the API it will have changed $this->pmlay_count['show_wcm'] to false
		if ( false === $this->site_uses_wcm() ) {
			// get data from Wordpress
			$_list = get_tag( $_list_id );
			if ( false !== $_list ) {
				$this->pmlay_settings['list_name'] = $_list->name;
				$this->pmlay_settings['list_link'] = '<a href="' . esc_url( $this->get_term_link( $_list_id, 'post_tag' ) ) . '">' . esc_html( $_list->name ) . '</a>';
				$this->list_args['tag_id'] = $_list_id;
				$this->list_args['orderby'] = 'date';
				$this->list_args['order'] = 'DESC';
				$this->pmlay_settings['list'] = $this->get_layout_posts();
				$this->pmlay_settings['posts_total'] = $_list->category_count;
			}
		}
	}

	/**
	* Set the arguments required to get a list of posts by category and all subcategories
	*
	* @param $_list_id integer term ID
	*
	* @return (null)
	*/
	function display_module_get_cat( $_list_id, $_list_name ) {
		// Category post list
		if ( true === $this->site_uses_wcm() ) {
			// get data from WCM
			$_offset = isset( $this->list_args['offset'] ) ? intval( $this->list_args['offset'] ) : 0;
			$this->list_wcm( 'cat', $_list_id, '', $_list_name, $this->list_args['posts_per_page'], $_offset );
			$this->pmlay_settings['list_name'] = $_list_name;
		}
		// if the WCM query failed to reach the API it will have changed $this->pmlay_count['show_wcm'] to false
		if ( false === $this->site_uses_wcm() ) {
			// get data from Wordpress
			if ( 0 < $_list_id ) {
				$_list = get_category( $_list_id );
				if ( false !== $_list ) {
					$this->pmlay_settings['list_name'] = $_list->name;
					$this->pmlay_settings['list_link'] = '<a href="' . esc_url( $this->get_term_link( $_list_id, 'category' ) ) . '">' . esc_html( $_list->name ) . '</a>';
					$this->list_args['cat'] = $_list_id;
					$this->list_args['orderby'] = 'date';
					$this->list_args['order'] = 'DESC';
					$this->pmlay_settings['list'] = $this->get_layout_posts();
					$this->pmlay_settings['posts_total'] = $_list->category_count;
				}
			} else {
				// this is the fallback to no category
				$this->pmlay_settings['list_name'] = 'All';
				$this->pmlay_settings['list_link'] = '<a href="/">Home</a>';
				$this->list_args['orderby'] = 'date';
				$this->list_args['order'] = 'DESC';
				$this->pmlay_settings['list'] = $this->get_layout_posts();
				$this->pmlay_settings['posts_total'] = 100; // arbitrary number - irrelevant as a fallback
			}
		}
	}

	/**
	* Set the arguments required to get a list of posts by category only
	*
	* @param $_list_id integer term ID
	*
	* @return (null)
	*/
	function display_module_get_cax( $_list_id, $_list_name ) {
		if ( true === $this->site_uses_wcm() ) {
			// get data from WCM
			$_offset = isset( $this->list_args['offset'] ) ? intval( $this->list_args['offset'] ) : 0;
			$this->list_wcm( 'cax', $_list_id, '', $_list_name, $this->list_args['posts_per_page'], $_offset );
			$this->pmlay_settings['list_name'] = $_list_name;
		}
		// if the WCM query failed to reach the API it will have changed $this->pmlay_count['show_wcm'] to false
		if ( false === $this->site_uses_wcm() ) {
			// get data from Wordpress
			$_list = get_category( $_list_id );
			if ( false !== $_list ) {
				$this->pmlay_settings['list_name'] = $_list->name;
				$this->pmlay_settings['list_link'] = '<a href="' . esc_url( $this->get_term_link( $_list_id, 'category' ) ) . '">' . esc_html( $_list->name ) . '</a>';
				$this->list_args['category__in'] = $_list_id;
				$this->list_args['orderby'] = 'date';
				$this->list_args['order'] = 'DESC';
				$this->pmlay_settings['list'] = $this->get_layout_posts();
			}
		}
	}

	/**
	* Set the arguments required to get a list of posts by Editflow usergroup
	* In MVP post lists from user groups will be pulled from local WP data
	* Option to further filter by category or category + subcats
	*
	* @param $_list_id integer usergroup ID
	* @param $_list_id2 integer term ID
	* @param $_typ string ug|ugc|ugs
	*
	* @return (null)
	*/
	function display_module_get_ug( $_list_id, $_list_id2, $_typ ) {
		// Post list by columnists with option of selection by category
		// get data from Wordpress
		global $edit_flow;
		if ( isset( $edit_flow ) ) {
			// requires Editflow plugin
			$usergroup_id = 'columnists';
			$usergroup = $edit_flow->user_groups->get_usergroup_by( 'id', $_list_id );
			if ( is_array( $usergroup->user_ids ) ) {
				if ( ( '' !== $_list_id2 ) && ( 3 === strlen( $_typ ) ) ) {
					$this->pmlay_settings['list_name'] = $_list->name;
					$this->pmlay_settings['list_link'] = '<a href="' . esc_url( $this->get_term_link( $_list_id2, 'category' ) ) . '">' . esc_html( $_list->name ) . '</a>';
					if ( 'ugc' === $_typ ) {
						// usergroup + category
						$this->list_args['category__in'] = $_list_id2;
					} else {
						// usergroup + category + subcategories
						$this->list_args['cat'] = $_list_id2;
					}
				} else {
					$this->pmlay_settings['list_name'] = 'Columnists';
					$this->pmlay_settings['list_link'] = 'Columnists';
					$this->list_args['cat'] = '';
				}
				$this->list_args['orderby'] = 'date'; //post_date
				$this->list_args['order'] = 'DESC';
				$this->list_args['author__in'] = $usergroup->user_ids;
				foreach ( $this->post_types_excluded_from_aut as $value ) {
					$key = array_search( $value, $this->list_args['post_type'], true );
					if ( false !== $key ) {
						unset( $this->list_args['post_type'][ $key ] );
					}
				}
				$this->pmlay_settings['list'] = $this->get_layout_posts();
			}
		}
	}

	function list_categories( $_ary, $_num, $_classes = '', $_styles = '', $_post_id = 0 ) {
		$_ret = '';
		$_id = 0;
		$_num = intval( $_num );
		if ( true === $this->list_uses_wcm ) {
			$_maincat = wpcom_vip_get_term_by( 'name', $_ary->name, 'category' );
			if ( false !== $_maincat ) {
				$_ret = $this->get_cat_link( $_maincat->term_id );
			} else {
				$_ret = $_ary->name;
			}
		} else {
			if ( ! empty( $_ary ) ) {
				if ( 1 === $_num ) {
					// get main category
					$_id = intval( get_post_meta( intval( $_post_id ), '_pn_main_category', true ) );
					if ( 0 < $_id ) {
						$_ret = $this->get_cat_link( $_id );
					}
				}
				if ( 0 === $_id ) {
					if ( is_array( $_ary ) ) {
						if ( 0 < count( $_ary ) ) {
							$x = 0;
							foreach ( $_ary as $_id ) {
								$_id = intval( $_id );
								if ( 0 < $_id ) {
									$_ret .= $this->get_cat_link( $_id );
									$x ++;
									if ( ( 0 < $_num ) && ( $x >= $_num ) ) {
										break;
									}
								}
							}
						}
					}
				}
			}
		}
		return $_ret;
	}

	function list_chartbeat( $_key, $_post_count, $_post_max, $_label = 'Most Popular' ) {
		$this->pmlay_settings['list_name'] = $_label;
		$this->pmlay_settings['list_link'] = $_label;
		$_section = str_replace( ' ', '+', strtolower( $_key ) );
		$this->pmlay_settings['list_name'] = 'Chartbeat Trending';
		$_host = get_option( 'postmedia_layouts_chartbeat_host' );
		$_apikey = get_option( 'postmedia_layouts_chartbeat_apikey' );
		$_apiurl = 'http://api.chartbeat.com/live/toppages/v3/?apikey=' . $_apikey . '&host=' . $_host . '&section=' . $_section . '&limit=30';
		$_xml = wpcom_vip_file_get_contents( esc_url_raw( $_apiurl ), 3, 480 );
		if ( false !== $_xml ) {
			$_json = json_decode( $_xml );
			$_shown = array(); // prevent duplicate entries
			if ( 0 < count( $_json->pages ) ) {
				foreach ( $_json->pages as $_page ) {
					$_pagepath = $_page->path;
					$_pagepath_home = explode( '/', $_pagepath ); // Do not include home page.

					if ( 0 === intval( strpos( $_pagepath, '/category/' ) ) && ! empty( $_pagepath_home[1] ) ) {
						$_new_post = ( object ) array(
							'ID' => 0,
							'post_author' => '',
							'post_excerpt' => '',
							'post_content' => '',
							'post_category' => '',
							'post_title' => '',
							'post_date' => '',
							'thumbnail' => '',
							'url' => '',
							'target' => '',
						);
						preg_match( '/([a-z0-9]+\.[a-z]{2,3})\//i', $_pagepath, $_domains );
						$_domain = ( 2 <= count( $_domains ) ) ? $_domains[1] : '';
						$_is_sp_story = ( false === strpos( $_pagepath, 'story.html' ) ? false : true );
						if ( false !== $_is_sp_story ) {
							$_pagepath = str_replace( $_domain, 'www.' . $_domain, $_pagepath );
						}
						if ( false === strpos( $_pagepath, 'http:' ) ) {
							$_pagepath = 'http://' . $_pagepath;
						}
						$_post_author = ( isset( $_page->authors ) ) ? $_page->authors : '';
						if ( ( $_is_sp_story ) || ( '' !== $_post_author ) ) {
							$_target = $this->get_target( $_pagepath );
							$_new_post->post_title = $_page->title;
							$_new_post->url = $_pagepath;
							$_new_post->target = $_target;
							$this->pmlay_settings['list'][ $_post_count ] = $_new_post;
							$_post_count ++;
							if ( $_post_count >= $_post_max ) {
								break;
							}
						}
					}
				}
			}
		}
	}

	function set_feed_cache_time( $seconds ) {
		// change the default feed cache recreation period to 12 minutes - used in fetch_feed() in list_rss()
		return 720;
	}

	/**
	* Get a list of posts from RSS
	* CMJ refactor
	*
	*/
	function list_rss( $_uri, $_post_count, $_post_max, $_offset, $_label = 'External Feed', $_is_baselist = false ) {
		// RSS/Atom feed
		$_list_id = intval( $_uri );
		if ( 0 < $_list_id ) {
			// this is one of our feeds from SouthPARC - use the app server not the LEGO site cause it's way faster - also lets editors enter the list ID rather than the URI which sometimes confuses them
			$_site = get_option( 'postmedia_layouts_domain' );
			$_uri = 'http://app.canada.com/SouthPARC/service.svc/Content?callingSite=' . $_site . '&contentId=' . $_list_id . '&format=atom&AllLinks=false&maxdocs=' . $this->feed_post_count;
		}
		// validate $_uri - check for transfer protocol
		if ( 0 == preg_match( '/^[a-z]{3,5}\:\/\//i', $_uri ) ) {
			// non-type comparison (0 == ...) covers errors as well as missing subject
			$_uri = 'http://' . $_uri;
		}
		// validate $_uri as a URI
		if ( false === filter_var( $_uri, FILTER_VALIDATE_URL ) ) {
			return; // fall back to base list if $_uri is no good
		}
		$_xml_ok = false;
		if ( true === function_exists( 'fetch_feed' ) ) {
			add_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'set_feed_cache_time' ) ); // set the cache time for this feed to five minutes rather than the usual 12 hours
			$_xml = fetch_feed( esc_url_raw( $_uri ) ); // uses SimplePie : http://codex.wordpress.org/Function_Reference/fetch_feed
			remove_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'set_feed_cache_time' ) );
			if ( ! is_wp_error( $_xml ) ) {
				$_xml_ok = true;
			}
		}
		if ( true === $_xml_ok ) {
			$_excerpt_length = 200;
			$this->pmlay_settings['list_name'] = $_xml->get_title();
			$this->pmlay_settings['list_link'] = $_label;
			$this->pmlay_settings['list_class'] = '';
			$_override = isset( $this->pmlay_settings['override'] ) ? $this->pmlay_settings['override'] : '';
			$_xml->enable_order_by_date( false ); // remove date sorting so Zones come in the right order
			$rss_items = $_xml->get_items( 0, $_post_max );
			if ( true === $_is_baselist ) {
				$this->pmlay_settings['override_max'] = count( $rss_items );
			}
			$_count = 0;
			foreach ( $rss_items as $item ) {
				if ( $_offset <= $_count ) {
					$_new_post = (object) array(
						'ID' => 0,
						'post_author' => '',
						'post_excerpt' => '',
						'post_content' => '',
						'post_category' => $item->get_author(),
						'post_title' => $item->get_title(),
						'post_date' => '',
						'post_modified' => '',
						'thumbnail' => '',
						'url' => $item->get_permalink(),
						'target' => '_blank',
					);
					if ( ! in_array( $_new_post->url, ( array ) $this->pmlay_settings['urls_shown'], true ) ) {
						// post excerpt
						$_thumbobj = $item->get_enclosure();
						$_thumbnail = ( is_object( $_thumbobj ) ) ? trim( $_thumbobj->get_thumbnail() ) : '';
						$_url_regex = '/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i';
						if ( false === ( bool ) preg_match( $_url_regex, $_thumbnail ) ) {
							$_enclosures = $item->get_enclosures();
							foreach ( $_enclosures as $_encl_id => $_encl_obj ) {
								$_thumbobj = $_encl_obj;
								$_thumbnail = ( is_object( $_encl_obj ) ) ? trim( $_encl_obj->get_thumbnail() ) : '';
								if ( '' !== $_thumbnail ) {
									break;
								}
							}
						}

						// No typical thumbnail found from feed. Check for YouTube thumbnails.
						if ( isset( $_thumbobj->thumbnails ) && is_array( $_thumbobj->thumbnails ) ) {
							$_thumbnail = reset( $_thumbobj->thumbnails );
						}

						if ( ( isset( $this->list_args['meta_key'] ) ) && ( '_thumbnail_id' === $this->list_args['meta_key'] ) && ( '' === $_thumbnail ) ) {
							// image required but absent so skip
							continue;
						}

						if ( empty( $_thumbnail ) ) {
							$_thumbnail = $this->get_thumbnail_from_children( $item->data['child'] );
						}

						$_excerpt = $item->get_description();
						if ( '' == trim( $_excerpt ) ) {
							$_excerpt = strip_tags( $item->get_content() );

							// Look for YouTube excerpts if above fails.
							if ( ! $_excerpt && isset( $_thumbobj->description ) ) {
								$_excerpt = $_thumbobj->description;
							}

							if ( 200 < strlen( $_excerpt ) ) {
								if ( 0 < strpos( $_excerpt, '.', 100 ) ) {
									$_excerpt = substr( $_excerpt, 0, strpos( $_excerpt, '.', 100 ) + 1 );
								} else {
									$_excerpt = substr( $_excerpt, 0, strpos( $_excerpt, ' ', 200 ) ) . ' ...';
								}
							}
						}
						$_new_post->post_excerpt = $_excerpt;
						// post date - calculate the time difference between local and GMT
						$_date = $item->get_date( 'F j Y, g:i a' );
						$_time_offset = ( 'UTC' == $item->get_date( 'T' ) ) ?time() - current_time( 'timestamp' ) : 0; // Driving.ca sets the published date to midnight UTC for all posts just to be difficult, so move that back into the previous day to avoid confusion
						$_time = strtotime( $_date ) - $_time_offset;
						$_new_post->post_date = $_time;
						$_new_post->post_modified = $_time;
						// post category
						$_category = $item->get_category();
						$_new_post->post_category = ( is_object( $_category ) ) ? $_category->get_term() : '';
						// post author
						$_author = $item->get_author();
						$_new_post->post_author = ( is_object( $_author ) ) ? $_author->get_name() : '';
						// post image
						$_new_post->thumbnail = $_thumbnail;
						$_new_post->fullimg = $_new_post->thumbnail;
						// post link target
						$_target = $this->get_target( $_new_post->url );
						$_new_post->target = $_target;
						// add post to array
						$this->pmlay_settings['list'][ $_post_count ] = $_new_post;

						$_post_count ++;
						if ( $_post_count >= $_post_max ) {
							break;
						}
					}
				}
				$_count ++;
			}
		}
	}

	/**
	* Search thumbnail from various feed types which SimplePie_Item's data[child] property has
	*
	* @param $children array SimplePie_Item's data[child] property
	*
	* @return string Thumbnail URL
	*/
	private function get_thumbnail_from_children( $children ) {
		if ( ! is_array( $children ) ) {
			return '';
		}

		foreach ( $children as $value ) {
			if ( ! is_array( $value ) || ! array_key_exists( 'thumbnail', $value ) || ! is_array( $value['thumbnail'] ) ) {
				continue;
			}

			foreach ( $value['thumbnail'] as $thumbnails ) {
				if ( ! is_array( $thumbnails ) || ! array_key_exists( 'attribs', $thumbnails ) || ! is_array( $thumbnails['attribs'] ) ) {
					continue;
				}

				foreach ( $thumbnails['attribs'] as $attribs ) {
					if ( is_array( $attribs ) && array_key_exists( 'url', $attribs ) && filter_var( $attribs['url'] , FILTER_VALIDATE_URL ) ) {
						return $attribs['url'];
					}
				}
			}
		}

		return '';
	}

	function list_custom( $_typ, $_list_id ) {
		$_postlist = apply_filters( 'pn_layouts_get_custom_list', $_typ, $_list_id );
		if ( ! empty( $_postlist ) && is_array( $_postlist ) ) {
			foreach ( $_postlist as $_post_count => $_new_post ) {
				$this->pmlay_settings['list'][ $_post_count ] = $_new_post;
			}
		}
	}

	function show_header( $_echo = true, $_fil = '0' ) {
		if ( false === $_echo ) {
			ob_start();
		}
		$this->choose_template( 'header', $_fil, true, true, false, false );
		$this->pmlay_count['header'] ++;
		if ( false === $_echo ) {
			$_ret = ob_get_contents();
			ob_end_clean();
			return $_ret;
		}
	}

	function get_pagination_data() {
		global $wp_query;
		$_total_pages = 0;
		// Don't print empty markup in archives if there's only one page.
		$_override = isset( $this->pmlay_settings['override'] ) ? trim( $this->pmlay_settings['override'] ) : '';
		// show pagination when using a feed for the base list
		if ( ( '' === $_override ) && ( ( $wp_query->max_num_pages < 2 ) && ( is_home() || ( is_archive() && ! is_category() && ! is_tag() ) || is_search() ) ) ) {
			return;
		}
		// get the paged number
		$this->page_num = intval( get_query_var( 'paged' ) );
		// if we're on 0 set it to 1
		if ( 0 === $this->page_num ) {
			$this->page_num = 1;
		}
		// collect some other vars
		$_posts_avail = intval( $this->pmlay_settings['posts_avail'] );
		if ( ( 0 === $_posts_avail ) || ( ( '' === $this->pmlay_settings['override'] ) && ( 1 === $this->page_num ) ) ) {
			$_offset = $this->get_page_offset();
			$_posts_avail = $this->get_page_posts_avail( $_offset );
		}
		// Var $_offset is the number of posts from the base list source that are displayed in outfits (not the base list or widgets) on page 1
		// _posts_avail is the number of posts from the base list source minus $_offset
		// some sites don't show baselist on page one though so account for that by giving them an extra page, (bool) $this->pmlay_count['show_baselist']
		// when a feed is the source on the home page
		$_total_pages = $this->get_page_total_pages( $_posts_avail );
		// figure out where to turn off links
		if ( 1 === $this->page_num ) {
			$previous_off = ' off';
			$previous_link = '#';
		} else {
			$previous_off = '';
			$previous_link = get_pagenum_link( $this->page_num - 1 );
		}
		// figure out where to turn off links
		if ( $this->page_num === $_total_pages ) {
			$next_off = ' off';
			$next_link = '#';
		} else {
			$next_off = '';
			$next_link = get_pagenum_link( $this->page_num + 1 );
		}
		// paginate using template
		$this->pagination_data = array(
			'previous_link' => $previous_link,
			'previous_off' => $previous_off,
			'next_link' => $next_link,
			'next_off' => $next_off,
			'paged' => $this->page_num,
			'total_pages' => $_total_pages,
		);
	}

	/**
	* Get the number of posts to offset the page by based on how many have been shown on preceding pages in the pagination
	*
	* @return $_posts_avail (integer) The number of posts available
	*/
	private function get_page_total_pages( $_posts_avail = 0 ) {
		$_total_pages = intval( $_posts_avail / 10 + 0.9 );
		if ( false === $this->pmlay_count['show_baselist'] ) {
			$_total_pages ++;
		}
		// there is at least one page always, even if it's empty
		if ( 0 >= $_total_pages ) {
			$_total_pages = 1;
		} elseif ( $this->page_num > $_total_pages ) {
			// if the total number of pages is less than the current page number then it isn't - set total pages to current page number, cause otherwise it looks stoopid
			$_total_pages = $this->page_num;
		}
		return intval( $_total_pages );
	}

	/**
	* Get the number of posts to offset the page by based on how many have been shown on preceding pages in the pagination
	*
	* @return $_offset (integer) The number of posts to offset the page by
	*/
	private function get_page_posts_avail( $_offset = 0 ) {
		global $wp_query;
		if ( '' === trim( $this->pmlay_settings['override'] ) ) {
			$_output = $wp_query->found_posts - $_offset; // total posts available for base lists displays = # in base list, minus the ones shown in the outfits	on page one
		} else {
			// some sites don't run the baselist so don't get override_max
			if ( 0 >= intval( $this->pmlay_settings['override_max'] ) ) {
				$this->pmlay_settings['override_max'] = $this->feed_post_count - 10; // limit one page per 10 posts plus page 1
			}
			$_output = intval( $this->pmlay_settings['override_max'] ) - $_offset;
		}
		return $_output;
	}

	/**
	* Get the number of posts to offset the page by based on how many have been shown on preceding pages in the pagination
	*
	* @return $_offset (integer) The number of posts to offset the page by
	*/
	private function get_page_offset() {
		if ( true === $this->is_mobile ) {
			$_output = intval( $this->pmlay_settings['phone1_offset'] );
		} else {
			$_output = intval( $this->pmlay_settings['page1_offset'] );
		}
		if ( 0 === $_output ) {
			$_output = intval( ( true === $this->is_mobile ) ? $this->pmlay_settings['phone1_offset'] : $this->pmlay_settings['page1_offset'] );
		}
		if ( 0 > $_output ) {
			$_output = 0;
		}
		return $_output;
	}

	/**
	* Echo pagination
	*
	* @return (null)
	*/
	function display_pagination() {
		$this->choose_template( 'pagination', '', true, false, false, true );
	}

	function display_news_ticker( $_pos, $_echo = true ) {
		$playlist_overridden_home = $this->pmlay_count['custom_main_video'];
		$playlist_overridden_category = $this->pmlay_settings['playlist_player'];

		for ( $_count_ticker = 1; $_count_ticker <= $this->max_ticker; $_count_ticker++ ) {
			$_return = '';
			$_ticker_key = 'news_ticker_' . intval( $_count_ticker );
			if ( isset( $this->pmlay_settings['news_ticker'][ $_count_ticker ] ) ) {
				$_news_ticker = $this->pmlay_settings['news_ticker'][ $_count_ticker ];
				if ( isset( $_news_ticker['type'] ) && '' !== trim( $_news_ticker['type'] ) ) {
					$_type = isset( $_news_ticker['type'] ) ? trim( $_news_ticker['type'] ) : ''; // VIP fix PHP warnings
					$_position = isset( $_news_ticker['position'] ) ? intval( $_news_ticker['position'] ) : ''; // VIP fix PHP warnings
					$_attribs = isset( $_news_ticker['attribs'] ) ? trim( $_news_ticker['attribs'] ) : ''; // VIP fix PHP warnings

					// Override the first video player along with the 'custom_main_video' setting
					if ( Utilities::is_community() && 'playlist-player' === $_type ) {
						if ( $playlist_overridden_home && 'home' === $this->pmlay_settings['term_type'] ) {
							$_attribs = $playlist_overridden_home;
							$playlist_overridden_home = null;
						} else if ( $playlist_overridden_category && 'category' === $this->pmlay_settings['term_type'] ) {
							$_attribs = $playlist_overridden_category;
							$playlist_overridden_category = null;
						}
					}

					$this->current_news_ticker_attribs = trim( $_attribs ); // phase this out in favour of $current_news_ticker_parameters
					$this->current_news_ticker_parameters = $this->get_ticker_attribs( $_attribs );
					if ( $_pos == $_position ) {
						ob_start();
						$this->choose_template( 'ticker', $_type, true, true, false, false );
						$_ticker_html = ob_get_contents();
						ob_end_clean();
						$ticker_attr = array( 'class' => 'pm_layouts_news_ticker', 'id' => 'pm_layouts_news_ticker_' . intval( $_count_ticker ) );
						$ticker_filter = apply_filters( 'pn_layouts_ticker_section_wrapper', $ticker_attr );
						$_return .= '<section class="' . esc_attr( $ticker_filter['class'] ) . '" id="' . esc_attr( $ticker_filter['id'] ) . '">';
						$_return .= $_ticker_html;
						$_return .= '</section>';
						if ( true === $_echo ) {
							$this->safe_echo( $_return ); // Early-escaped
						} else {
							return $_return;
						}
					}
				}
			}
		}
	}

	/**
	* Get the parameters for a single ticker and split them into an array
	*
	* @return array ticker parameters
	*/
	function get_ticker_attribs( $_attribs ) {
		$_output = array();
		$_attrib_str = trim( $_attribs );
		if ( 0 < strpos( $_attrib_str, '=' ) ) {
			// there is at least on key/value pair so explode it into an array of pairs
			$_attrib_ary = explode( '&', $_attrib_str );
			foreach ( $_attrib_ary as $_att ) {
				if ( 0 < strpos( $_att, '=' ) ) {
					$_ary = explode( '=', $_att );
					$_output[ $_ary[0] ] = $_ary[1];
				}
			}
		} else {
			// no key/value pair so this is just a string - return as aone-element array anyways for type consistency
			$_output = array( $_attrib_str );
		}
		return $_output;
	}

	/**
	* Look up a local post's additional data
	*
	* @param $_post (object) Post object
	* @param $size_full (string) Wordpress image size definition
	* @param $size_thumb (string) Wordpress image size definition
	*
	* @return $_post (object) Post object, now with additional data
	*/
	function setup_post( $_post, $size_full = 'full', $size_thumb = 'thumbnail' ) {
		$this->pmlay_settings['count_posts'] ++;
		// skip for WCM data true === $postmedia_layouts->pmlay_count['show_wcm']
		if ( true === $this->list_uses_wcm ) {
			$this->pmlay_settings['posts_shown'] .= ',' . $_post->ID;
		} else {
			// get sponsored data is the WCM hasn't already provided it
			$_post = $this->get_post_sponsored( $_post, $size_full, $size_thumb );
			// get post URL is the WCM hasn't already provided it
			$_post = $this->get_post_url( $_post );
			// get post image is the WCM hasn't already provided it
			$_post = $this->get_post_image( $_post );
			// AB: get post additional excerpt if the WCM hasn't already provided it
			$_post = $this->get_post_additional_excerpt( $_post );
			$_add_post_shown = true;
			$_add_post_shown = ( false === isset( $this->pmlay_settings['posts_shown'] ) ) ? false : $_add_post_shown;
			$_add_post_shown = ( false === isset( $this->pmlay_settings['remove_posts'] ) ) ? false : $_add_post_shown;
			$_add_post_shown = ( false === isset( $_post->ID ) ) ? false : $_add_post_shown;
			$_add_post_shown = ( ( false === $this->list_uses_wcm ) &&  ( 0 >= intval( $_post->ID ) ) ) ? false : $_add_post_shown;
			if ( true === $_add_post_shown ) {
				// CMJ : not used on feeds
				$this->pmlay_settings['posts_shown'] .= ',' . $_post->ID;
				if ( ( '' !== $_post->wcm_id ) && ( $_post->ID !== $_post->wcm_id ) ) {
					$this->pmlay_settings['posts_shown'] .= ',' . $_post->wcm_id;
				}
			}
		}
		$this->pmlay_settings['urls_shown'][] = $_post->url;
		if ( ( ( 0 < $this->pmlay_settings['term_id'] ) || ( 1 < $this->pmlay_count['display_id'] ) ) ) {
			// CMJ REMOVE: and not base list && ( 'baselist' !== $this->layout_area )
			$this->pmlay_settings['posts_cached'] .= ',' . $_post->ID;
		}
		// count the post against the base list if page 1 and post is in base list
		if ( isset( $_post->origin_id ) ) {
			$this->count_baselist_posts( $_post->origin_id, 1 );
		} else {
			$this->count_baselist_posts( $_post->ID, 1 );
		}
		if ( ( isset( $this->pmlay_settings['outfit_type'] ) ) && ( false === in_array( $this->pmlay_settings['outfit_type'], array( 'widget', 'baselist' ), true ) ) ) {
			$this->posts_displayed ++; // posts displayed so far on this page
		}
		return $_post;
	}

	/**
	* Look up a local post featured image
	*
	* @param $_post (object) Post object
	* @param $size_full (string) Wordpress image size definition
	* @param $size_thumb (string) Wordpress image size definition
	*
	* @return $_post (object) Post object, now with image data
	*/
	public function get_post_image( $_post, $size_full = 'full', $size_thumb = 'thumbnail' ) {
		if ( ! isset( $_post->thumbnail ) ) {
			if ( has_post_thumbnail( $_post->ID ) ) {
				$_thumb_id = get_post_thumbnail_id( $_post->ID );
				$imgdata = wp_get_attachment_image_src( $_thumb_id, 'full' );
				$imgintermediate = ( bool ) $imgdata[3];
				if ( true !== $imgintermediate ) {
					$size_thumb = 'thumbnail';
				}
				$_thumb = wp_get_attachment_image_src( $_thumb_id, $size_thumb );
				$_fullimg = wp_get_attachment_image_src( $_thumb_id, $size_full );
				$_post->thumbnail = $_thumb[0];
				$_post->fullimg = $_fullimg[0];
			} else {
				// CMJ: insert code here for non-local or non-WP lists
				$_post->thumbnail = '';
				$_post->fullimg = '';
			}
		}
		return $_post;
	}

	/**
	* Look up a local post URL
	*
	* @param $_post (object) Post object
	*
	* @return $_post (object) Post object, now with post URL data
	*/
	public function get_post_url( $_post ) {
		if ( ( ! isset( $_post->url ) )  || ( '' === trim( $_post->url ) ) ) {
			$_post->url = get_permalink( $_post->ID );
			$_post->target = $this->get_target( $_post->url ); // need list number
		}
		return $_post;
	}

	/**
	* Look up a local post sponsored data
	*
	* @param $_post (object) Post object
	*
	* @return $_post (object) Post object, now with post sponsored data
	*/
	public function get_post_sponsored( $_post ) {
		if ( ! isset( $_post->advertorial_class ) ) {
			$_post->advertorial_class = get_post_meta( $_post->ID, 'advertorial_type', true );
			$_post->advertorial_name = get_post_meta( $_post->ID, 'pn_adv_sponsor_name', true );
		}
		if ( 'advertisement' == $_post->advertorial_class ) {
			$sponsor_title = __( 'Advertisement', 'postmedia' );
		} elseif ( 'sponsored_content' == $_post->advertorial_class ) {
			$sponsor_title = __( 'Sponsored Content', 'postmedia' );
		} elseif ( 'joint_venture' == $_post->advertorial_class ) {
			$sponsor_title = __( 'Joint Venture', 'postmedia' );
		} else {
			$sponsor_title = '';
		}
		$_post->sponsor_title = $sponsor_title;
		// apparently we don't care to show the logo or link here - is sponsored used on any themes?
		return $_post;
	}

	/**
	* AB: Look up a local post additional Excerpt data
	*
	* @param $_post (object) Post object
	*
	* @return $_post (object) Post object, now with additional Excerpt data
	*/
	public function get_post_additional_excerpt( $_post ) {

		$_post->post_additional_excerpt = get_post_meta( $_post->ID, 'pni_additional_excerpt', true );

		return $_post;
	}

	/**
	* Insert the text placeholder used to locate the widget slot after the first post on mobile
	*
	* @return (null)
	*/
	function widget_after_first_article() {
		if ( ( $this->is_mobile ) && ( 1 === $this->posts_displayed ) && ( true === is_home() || true === is_category() || true === is_tag() ) ) {
			echo self::FIRST_WIDGET_MARKER; // if one post has been rendered, insert a marker for inserting the first set of widgets on mobile
		}
	}
	/**
	* Grabs the content from local, pointer or network post.
	*
	* @return $content
	*/
	function pn_post_to_content( $p ) {
		$post_id = null;
		if ( ! empty( $p->ID ) && 'pn_pointer' === $p->post_type ) {
			$post_id = get_post_meta( $p->ID, 'pn_wcm_id', true );
		}
		if ( ! get_option( 'wcm_enabled', false ) ) {
			$post_id = $p->ID;
		}
		if ( empty( $post_id ) ) {
			$post_id = isset( $p->wcm_id ) ? $p->wcm_id : $p->ID;
		}

		$content = new Content( $post_id, null, 0 !== $post_id );
		$content->from_layouts_post( $p );

		return $content;
	}

	/**
	* Eliminate duplicate posts from the list, i.e. those that have already been displayed on this page
	* loop through $this->pmlay_settings['list'] removing posts already shown
	* required to avoid using 'exclude' in wp_query for cacheability reasons
	* also check $_fields array for 'thumb' - if found exclude posts with no thumbnail from this list but not from subsequent ones
	* @parameter $_section_num (integer) The number of the section/outfit on the page
	*
	* @parameter $_fields (array) Additional fileds to test, specifically for image requirements
	*
	* @return null Sets object properties
	*/
	function remove_displayed_posts( $_section_num = 0, $_fields = array() ) {
		$_image_required = false;
		if ( ! $this->pmlay_settings['allow_posts_without_thumbs'] ) {
			if ( is_array( $_fields ) ) {
				if ( in_array( 'thumb', $_fields, true ) ) {
					$_image_required = true;
				}
			}
		}
		$_cnt_posts = count( $this->pmlay_settings['list'] );
		$_exclude_list = explode( ',', $this->pmlay_settings['posts_shown'] );
		$_keep_list = array();
		for ( $_cnt = 0; $_cnt < $_cnt_posts; $_cnt ++ ) {
			$_post = $this->pmlay_settings['list'][ $_cnt ];
			$_show_post = false;
			$_wcm_id = '';
			if ( false !== strpos( $_post->ID, '-' ) ) {
				$_wcm_id = $this->get_wcm_original_id( $_post );
			} elseif ( 0 === intval( $_post->ID ) ) {
				// keep all external stories, non-WCM, excluding those missing images potentially
				if ( false === $_image_required ) {
					$_show_post = true;
				} elseif ( isset( $_post->thumbnail ) && ( '' !== trim( $_post->thumbnail ) ) ) {
					$_show_post = true;
				}
			} else {
				$_wcm_id = trim( $this->get_wp_post_wcm_id( $_post ) );
			}
			if ( ( '' !== trim( $_wcm_id ) ) || ( 0 < intval( $_post->ID ) ) ) {
				// a valid WCM ID was found or this is a plain WP post with an ID, so assess it for exclusion
				$this->pmlay_settings['list'][ $_cnt ]->wcm_id = $_wcm_id; // if this post exists in the WCM as well, grab it's WCM ID
				if ( ( ! in_array( (string) $_post->ID, $_exclude_list, true ) ) && ( ( '' === $_wcm_id ) || ( ! in_array( $_wcm_id, $_exclude_list, true ) ) ) ) {
					// if the post ID is not in thelist of posts that have already been displayed then maybe show it
					$_allow_sponsored = isset( $this->pmlay_settings['outfits'][ $_section_num ]['allow_sponsored'] ) ? intval( $this->pmlay_settings['outfits'][ $_section_num ]['allow_sponsored'] ) : 0; // outfit allows sponsored posts in lists?
					$_adv_outfit_type = isset( $this->pmlay_settings['outfits'][ $_section_num ]['adv_type'] ) ? trim( $this->pmlay_settings['outfits'][ $_section_num ]['adv_type'] ) : ''; // advertorial type of this outfit
					$_adv_page_type = isset( $this->pmlay_settings['advertorial_type'] ) ? trim( $this->pmlay_settings['advertorial_type'] ) : ''; // advertorial type of this page

					// the list requires posts to have featured images
					if ( ! $this->has_post_thumbnail( $_post ) && $_image_required ) {
						continue;
					}

					// exclude sponsored content based on rules
					if ( 1 === $_allow_sponsored ) {
						// the outfit has been set to allow all sponsored posts
						$_show_post = true;
					} elseif ( ( false === in_array( $_adv_page_type, array( '', 'presented_by' ), true ) ) && ( 'widget' !== $this->pmlay_settings['outfit_type'] ) ) {
						// show any post regardless of sponsored type if the whole page is sponsored (except in widgets)
						$_show_post = true;
					} elseif ( ( isset( $this->pmlay_settings['adv_type'] ) ) && ( '' !== trim( $this->pmlay_settings['adv_type'] ) ) ) {
						// show any post regardless of sponsored type if this is a sponsored widget
						$_show_post = true;
					} elseif ( false === in_array( $_adv_outfit_type, array( '', '3' ), true ) ) {
							// show any post regardless of sponsored type if this outfit is sponsored
							$_show_post = true;
					} else {
						$_adv_post_type = trim( $this->get_post_meta( $_post, 'advertorial_type', true ) );
						if ( true === in_array( $_adv_post_type, array( '', 'presented_by' ), true ) ) {
							// keep if this is not a sponsored post
							$_show_post = true;
						}
					}
				}
			}
			if ( true === $_show_post ) {
				$_keep_list[] = $_post;
			}
		}
		// the array of post objects to be made available to the template for display on the page
		$this->pmlay_settings['list'] = $_keep_list;
		// can't add to the list of posts to exclude later until they are selected and displayed on the page in setup_post()
	}

	/**
	* Check for a feature image attached to the post
	* @param $_post (object) A post object
	*
	* @return (boolean) Has thumbnail or not
	*/
	function has_post_thumbnail( $_post ) {
		$_output = false;
		if ( isset( $_post->thumbnail ) && ( '' !== trim( $_post->thumbnail ) ) ) {
			$_output = true;
		} elseif ( has_post_thumbnail( $_post->ID ) ) {
			$_output = true;
		}
		return $_output;
	}

	function count_offset( $_count ) {
		// DEPRACATED - in each module count the posts from the base list
		return null;
	}

	function count_baselist_posts( $_postid, $_count ) {
		// REPLACES count_offset - in each module count the posts from the base list
		// possible remaining issue is posts with no image that are skipped and not shown on page 1 - these may not be included in the offset for page 2
		if ( ( true === $this->offset['gather'] ) && ( ! empty( $this->pmlay_settings['baselist_postids'] ) ) ) {
			// so maybe the best way is to get a list of ids for the base list first then compare against that
			if ( in_array( $_postid, $this->pmlay_settings['baselist_postids'], true ) ) {
				// on a post by post basis test to see if it is in the base list
				$this->offset['post_id'] = $_postid;
			}
		}
	}

	function set_exclude( $_term_id ) {
		$_exclude = '';
		if ( 2 <= $this->page_num ) {
			// carry forward excluded posts based on sections displayed above default list
			$_exclude = ',' . json_decode( get_transient( $this->transient_names['exclude'] ) ); // contains a comma-delimited list of all posts displayed on first page to exclude them from page 2 and beyond
		}
		return $_exclude;
	}

	function get_headtext() {
		$_section = intval( $this->pmlay_count['section'] );
		$_headnum = intval( $this->pmlay_count['header'] );
		if ( isset( $this->pmlay_settings['outfits'][ $_section ]['heads'][ $_headnum ] ) ) {
			$_headtxt = $this->pmlay_settings['outfits'][ $_section ]['heads'][ $_headnum ]['header'];
			$_headurl = $this->pmlay_settings['outfits'][ $_section ]['heads'][ $_headnum ]['headurl'];
			$_headimg = $this->pmlay_settings['outfits'][ $_section ]['heads'][ $_headnum ]['img'];
		} else {
			$_headtxt = '';
			$_headurl = '';
			$_headimg = '';
		}
		$custom_header = apply_filters( 'pn_layouts_get_custom_header', array(), $_headtxt, $_headurl, $_headimg );
		// only show header when there is a link
		// exception: first header on a category / tag page
		if ( ( '' != trim( $_headimg ) ) || ( ( '' != trim( $_headurl ) ) && ( '' != trim( $_headtxt ) ) ) ) {
			// link exists so return header data
			$_ret = array( 'text' => $_headtxt, 'url' => $_headurl, 'img' => $_headimg );
			// if is not home page and is first headline of first section - elimintaed requirement - notes preserved below in case need to restore
			// logic: if 0 < $this->pmlay_settings['term_id'] && 0 == $_section && 0 == $_headnum && '' != $_headtxt then	$_ret = array( 'text' => $_headtxt, 'url' => '' );
		} elseif ( ! empty( $custom_header ) ) {
			$_ret = $custom_header;
		} else {
			$_ret = array();
		}
		return $_ret;
	}

	function get_sponsor() {
		$_section = intval( $this->pmlay_count['section'] );
		$_sponsortype = $this->pmlay_settings['outfits'][ $_section ]['adv_type'];
		$adv_type_string = array(
			'advertisement',
			'sponsored_by',
			'promoted_by',
			'presented_by',
		);
		$_ret = array();
		if ( in_array( $_sponsortype, $adv_type_string, true ) ) {
			$_sponsortype = $adv_type_string[ $_sponsortype ];
			$_sponsorname = $this->pmlay_settings['outfits'][ $_section ]['adv_company'];
			$_sponsorlogo = $this->pmlay_settings['outfits'][ $_section ]['adv_logo'];
			$_sponsorurl = ( isset( $this->pmlay_settings['outfits'][ $_section ]['adv_url'] ) ) ? $this->pmlay_settings['outfits'][ $_section ]['adv_url'] : '';
			// only show header when there is a link
			// exception: first header on a category / tag page
			if ( ( '' != trim( $_sponsorlogo ) ) || ( ( '' != trim( $_sponsorurl ) ) && ( '' != trim( $_sponsorname ) ) ) ) {
				// link exists so return header data
				$_ret = array( 'adv_type' => $_sponsortype, 'adv_company' => $_sponsorname, 'adv_logo' => $_sponsorlogo, 'adv_url' => $_sponsorurl );
				// if is not home page and is first headline of first section - elimintaed requirement - notes preserved below in case need to restore
				// logic: if 0 < $this->pmlay_settings['term_id'] && 0 == $_section && 0 == $_headnum && '' != $_headtxt then $_ret = array( 'text' => $_headtxt, 'url' => '' );
			}
		}
		return $_ret;
	}

	function header_class() {
		$_ret = '';
		$_default = '';
		$_section_num = $this->pmlay_count['section'];
		$_header_num = $this->pmlay_count['header'];
		$_choose = $this->pmlay_count['choose_color'];
		$_default = ( isset( $this->pmlay_settings['outfits'][ $_section_num ]['heads'][ $_header_num ] ) ) ? $this->pmlay_settings['outfits'][ $_section_num ]['heads'][ $_header_num ]['style'] : ''; // take the choice made by the editor
		if ( 1 == $_choose ) {
			$_ret = $_default;
		}
		if ( '' == $_ret ) {
			if ( 'category' == $this->pmlay_settings['term_type'] ) {
				// if not allowed to choose and this is a category page use the class from it's top-level parent
				$_cat_tree = get_category_parents( intval( $this->pmlay_settings['term_id'] ), false, '|', true );
				if ( false !== strpos( $_cat_tree, '|' ) ) {
					$_top_cats = explode( '|', $_cat_tree );
					$_parent = $_top_cats[0];
					$_ret = strtolower( $_parent ); // need slug not name
				}
			} else {
				// if not allowed to choose or haven't chosen then select best class based on categories - this is primitive but works well enough for now
				if ( isset( $this->pmlay_settings['outfits'][ $_section_num ]['lists'][ $_header_num ] ) ) {
					$_ary = $this->pmlay_settings['outfits'][ $_section_num ]['lists'][ $_header_num ];
					$_type = $_ary['type'];
					if ( ( ( 'cat' == $_type ) || ( 'cax' == $_type ) ) && ( 0 != intval( $_ary['id'] ) ) ) {
						$_cat_tree = get_category_parents( intval( $_ary['id'] ), false, '|', true );
						if ( false !== strpos( $_cat_tree, '|' ) ) {
							$_top_cats = explode( '|', $_cat_tree );
							$_parent = $_top_cats[0];
							$_ret = strtolower( $_parent );
						}
					}
				} else {
					$_ret = '';
				}
			}
		}
		if ( '' == $_ret ) {
			$_ret = $_default; // take the choice made by the editor
		}
		return $_ret;
	}

	function get_author( $_pid, $_user ) {
		$_ret = '';
		$_uid = intval( $_user );
		if ( 1 === $_uid ) {
			$_author = get_post_meta( $_pid, 'byline', true );
			if ( '' !== trim( $_author ) ) {
				$_ret = $_author;
			}
		} elseif ( 0 < $_uid ) {
			$_author = get_userdata( $_uid );
			if ( '' !== trim( $_author->nicename ) ) {
				$_ret = '<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( $_uid ) ) . '">' . esc_html( $_author->nicename ) . '</a></span>';
			}
		} else {
			$_ret = $_user;
		}
		return $_ret;
	}

	function get_excerpt( $_post, $_pid = 0 ) {
		$_txt = '';
		if ( null !== $_post ) {
			if ( '' !== trim( $_post->post_additional_excerpt ) ) { // AB
				$_txt = $_post->post_additional_excerpt; // AB
			} elseif ( '' !== trim( $_post->post_excerpt ) ) {
				$_txt = $_post->post_excerpt;
			} elseif ( '' !== trim( $_post->post_content ) ) {
				$_txt = $_post->post_content;
				$_txt = strip_tags( $_txt, '<p><a><em><i>' );
				$_txt = apply_filters( 'pn_layouts_get_excerpt_content', $_txt );
				$_txt = preg_replace( '/\[[^\]]+\]/i', '', $_txt ); // strip out shortcodes
				if ( 200 < strlen( $_txt ) ) {
					if ( 0 < strpos( $_txt, '.', 100 ) ) {
						$_txt = substr( $_txt, 0, strpos( $_txt, '.', 100 ) + 1 );
					} else {
						$_txt = substr( $_txt, 0, strpos( $_txt, ' ', 200 ) ) . ' ...';
					}
				}
				$_txt = strip_tags( $_txt, '<p><a><em><i>' );
			}
		}
		return $_txt;
	}

	function get_cat_link( $_id ) {
		// ok, sometimes the lovely users will just delete a category which orphans posts that use it as a cat or main cat - need to trap that here or it throws a horrible horrible error
		$_ret = '';
		if ( 0 < $_id ) {
			$_cat_exists = false; // assume category doesn't exist
			$_term_exists = wpcom_vip_term_exists( $_id, 'category' );
			if ( is_array( $_term_exists ) ) {
				// Returns an array if the pairing exists
				$_cat_exists = true;
			} elseif ( 0 < intval( $_term_exists ) ) {
				// Returns ID integer if exists, returns 0 or null if term does not exist so convert this falsey response to integer
				$_cat_exists = true;
			}
			if ( true === $_cat_exists ) {
				$_cat_tree = get_category_parents( $_id, false, '|', true );
				if ( ! is_wp_error( $_cat_tree ) ) {
					if ( false !== strpos( $_cat_tree, '|' ) ) {
						$_top_cats = explode( '|', $_cat_tree );
						$_parent = $_top_cats[0];
						$_slug = strtolower( $_parent );
					}
					$_url = $this->get_term_link( $_id, 'category' );
					$_ret .= '<a href="' . esc_url( $_url ) . '" class="' . esc_attr( $_slug ) . '">';
					$_ret .= esc_html( get_cat_name( $_id ) ) . '</a>';
				}
			}
		}
		return $_ret;
	}

	function get_sponsored_keyword() {
		$_ret = array( 'display' => false, 'adv_type' => -1, 'keyword' => '', 'type' => false, 'company' => '', 'logo' => '', 'url' => '' );
		$_section = intval( $this->pmlay_count['section'] );
		if ( count( $this->pmlay_settings ) >= $_section ) {
			if ( isset( $this->pmlay_settings['outfits'][ $_section ]['sponsored'] ) ) {
				$_type = str_replace( '_', '', $this->pmlay_settings['outfits'][ $_section ]['adv_type'] );
				$_type = ( '' === $_type ) ? -1 : intval( $_type );
				$_ret['display'] = (bool) $this->pmlay_settings['outfits'][ $_section ]['sponsored'];
				$_ret['keyword'] = trim( $this->pmlay_settings['outfits'][ $_section ]['sponstext'] );
				$_ret['adv_type'] = $_type;
				$_ret['adv_abbr'] = ( isset( $this->adv_type[ $_type ] ) ) ? $this->adv_type[ $_type ] : '';
				$_ret['adv_company'] = trim( $this->pmlay_settings['outfits'][ $_section ]['adv_company'] );
				$_ret['adv_logo'] = trim( $this->pmlay_settings['outfits'][ $_section ]['adv_logo'] );
				$_ret['adv_url'] = trim( $this->pmlay_settings['outfits'][ $_section ]['adv_url'] );
			}
		}
		return $_ret;
	}

	function show_alt_title() {
		global $post;
		if ( is_single() ) {
			$_alt_title = trim( get_post_meta( $post->ID, '_pn_title_alternate', true ) );
			if ( '' != $_alt_title ) {
				echo '<meta itemprop="alternativeHeadline" content="' . esc_attr( $_alt_title ) . '" />' . "\n";
			}
		}
	}

	function get_video_id( $_default = null ) {
		$_video_id = '';
		$term_id = '';
		if ( isset( $this->pmlay_settings['video_id'] ) ) {
			$_video_id = $this->pmlay_settings['video_id'];
		} else {
			if ( 1 == intval( is_category() ) ) {
				// category page
				$term_id = get_queried_object_id();
			} elseif ( 1 == intval( is_single() ) ) {
				// post page
				$_custom = get_post_custom();
				$_is_advertorial = isset( $_custom['advertorial_meta_box'] ) ? esc_attr( $_custom['advertorial_meta_box'][0] ) : '';
				if ( '' === $_is_advertorial ) {
					// the post is editorial so use the video id in the main category
					global $post;
					$maincat = get_post_meta( $post->ID, '_pn_main_category', true );
					$term_id = $maincat;
				} else {
					// the post is an advertorial so use the video id in the widget
					$term_id = 0;
				}
			} elseif ( 1 == intval( is_tag() ) ) {
				// tag page
				$term_id = get_queried_object_id();
			} else {
				// home page
				$term_id = 0;
			}
			if ( 0 < $term_id ) {
				$_settings = $this->get_termdata( $term_id );
				$_video_id = $_settings['video_id'];
			}
		}
		if ( '' == $_video_id ) {
			$_video_id = $_default;
		}
		return $_video_id;
	}

	function pn_get_post_title( $_postid, $_title ) {
		$_alt = get_post_meta( $_postid, '_pn_title_alternate', true );
		if ( trim( $_alt ) ) {
			$_ret = $_alt;
		} else {
			$_ret = $_title;
		}
		return trim( strip_tags( $_ret ) );
	}

	function get_target( $_path, $_listnum = 0 ) {
		$_target = $this->pmlay_settings['target'];
		if ( '' == $_target ) {
			$_domain = str_replace( 'http://', '', get_option( 'siteurl' ) );
			$_target = ( ( false === strpos( $_path, $_domain ) ) ? '_blank' : '_self' );
		}
		return $_target;
	}

	public function widget_header_class() {
		$_ret = '';
		$_type = $this->pmlay_settings['list_type'];
		if ( ( 'cat' == $_type ) || ( 'cax' == $_type ) ) {
			$_cid = isset( $this->list_args['category__in'] ) ? intval( $this->list_args['category__in'] ) : 0;
			if ( 0 != $_cid ) {
				$_cat_tree = get_category_parents( intval( $_cid ), false, '|', true );
				if ( false !== strpos( $_cat_tree, '|' ) ) {
					$_top_cats = explode( '|', $_cat_tree );
					$_parent = $_top_cats[0];
					$_ret = strtolower( $_parent );
				}
			}
		}
		return $_ret;
	}

	function create_template_path( $_dir, $_fil ) {
		$_path = $_dir . sanitize_file_name( $_fil ) . '.php';
		return $_path;
	}

	/**
	* Specialized locate_template functionality to look in plugins folder and fallback gracefully into default templates
	*
	*/
	function choose_template( $_type, $_id = '0', $load = false, $require_once = true, $preview = false, $ignore_cache = true ) {
		$_type = sanitize_key( $_type );
		apply_filters( 'pn_layouts_add_outfits', $this );

		if ( true === in_array( $this->layout_area, array( 'sidebar', 'baselist' ), true ) ) {
			// force sidebar widgets and baselist to use live content
			$ignore_cache = true;
		}
		if ( true === in_array( $_type, array( 'outfit', 'phone', 'widget' ), true ) ) {
			$this->pmlay_settings['outfit_type'] = $_type;
		}
		if ( ( 'outfit' == $_type ) && ( $this->is_mobile() ) && ( ! is_admin() ) ) {
			// ADAPTIVE for mobile outfits - switch to phone outfits
			$_type = 'phone';
			$_id = $this->outfit_settings[ $_id ][1];
		}
		$this->pmlay_settings['template_type'] = $_type;
		if ( 'widget' === $this->pmlay_settings['outfit_type'] ) {
			// problem is that widget templates now call list templates so the type changes to 'list' before it gets posts so this should kick in only when a list has been called after a widget template has rendered
			$this->offset['gather'] = false; // don't count posts in widgets towards the number used on page 1
			$ignore_cache = true;
		}

		$this->template_path = apply_filters( 'pn_choose_template_path', $this->template_path, $_type, $_id );

		if ( '' === $this->template_path ) {
			$this->get_templates_folder();
		}
		if ( 'pagination' !== $_type ) {
			$_template_path = $this->template_path . $_type . '/';
			$_template_file = $this->create_template_path( $_template_path, $_type . '-' . $_id );
		} else {
			$_template_path = $this->template_path;
			$_template_file = $this->create_template_path( $_template_path, $_type );
		}
		// remove multiple fallbacks since this is a private plugin and the qa team will ensure templates are in place for all outfits
		if ( true === $load ) {
			// display the template as HTML on the front end
			$_outfit_displayed = false;
			if ( ( false === $ignore_cache ) && ( true === $this->use_outfit_cache ) && ( ( ( false === $this->cache_breaking_news ) || ( $this->pmlay_settings['term_id'] > 0 ) || 1 < $this->pmlay_count['display_id'] ) ) ) {
				// the first outfit (ON THE HOME PAGE) is always generated dynamically to ensure breaking news appears asap
				// use cached outfit if boolean is set and if this is not the first outfit
				// need to handle base list - always dynamic?
				$_cached_outfit_number = intval( $this->pmlay_count['display_id'] ); // zero is the baselist
				if ( ( false === $this->cache_breaking_news ) || ( 0 < $this->pmlay_settings['term_id'] ) || ( 0 <= $_cached_outfit_number ) ) {
					// skip outfit #1 on home page only
					if ( count( $this->outfit_cache ) > $_cached_outfit_number ) {
						$this->safe_echo( $this->outfit_cache[ $_cached_outfit_number ] ); // Early-escaped
						$_outfit_displayed = true;
					}
				}
			}
			if ( false === $_outfit_displayed ) {
				// if we're not using cached HTML or the cached HTML could not be displayed, then go get it fresh from the database
				// set this->update_outfit_cache to true ?
				if ( '' != $_template_file ) {
					// load outfit from templates and database
					load_template( $_template_file, false ); // need to count posts shown for override
				}
			}
		} elseif ( false == $load ) {
			// return the template file name on the back end
			return $_template_file;
		}
	}

	function pn_get_option( $option, $value ) {
		// Get the value from
		//  - VIP: wlo_update_option
		//  - Community: set_transient
		$wlo_enabled    = function_exists( 'wlo_get_option' );
		$is_multimarket = get_option( 'wcm_multi_market_frontend', false );
		$in_transient   = in_array( $option, $this->transient_names, true );

		$_ret = ( ! $wlo_enabled || $is_multimarket ) && $in_transient ? get_transient( $option ) : wlo_get_option( $option, $value );

		if ( $value === $_ret || empty( $_ret ) ) {
			// else if Large Options is not active OR there is no entry for this term (happens when site is set up without the LO plugin but then it's turned on later - preserves options)
			$_ret = get_option( $option, $value );
		}
		return apply_filters( 'pn_layouts_option', $_ret, $option );
	}

	function pn_update_option( $option, $newvalue ) {
		// Set the value to
		//  - VIP: wlo_update_option
		//  - Community: set_transient
		// if you set up the site without LO then turn it on, saving all the categories should transfer the options
		$wlo_enabled = function_exists( 'wlo_update_option' );
		$is_multimarket = get_option( 'wcm_multi_market_frontend', false );

		if ( ! $wlo_enabled || $is_multimarket ) {
			return set_transient( $option, $newvalue, 0 );
		}

		wlo_update_option( $option, $newvalue );
	}

	function concatenate_url( $_base, $_file ) {
		$_url = '';
		if ( '/' == substr( $_file, 0, 1 ) ) {
			$_file = substr( $_file, 1, 2000 );
			$_url = $_base . $_file;
		} elseif ( 'http' == substr( $_file, 0, 4 ) ) {
			$_url = $_file;
		} else {
			$_url = $_base . $_file;
		}
		return $_url;
	}

	public function get_termname( $_typ, $_id ) {
		$_ret = '';
		switch ( $_typ ) {
			case 'tag':
				$_list = get_tag( $_id );
				$_ret = ( is_object( $_list ) && isset( $_list->name ) ) ? $_list->name : '';
				break;
			case 'cat':
			case 'cax':
				$_list = get_category( $_id );
				if ( is_object( $_list ) ) {
					if ( isset( $_list->name ) ) {
						$_ret = $_list->name;
					}
				}
				break;
			case 'auth':
				$_list = get_user_by( 'id', $_id );
				if ( is_object( $_list ) ) {
					if ( isset( $_list->display_name ) ) {
						$_ret = $_list->display_name;
					}
				}
				break;
			case 'zon':
				$_list = get_term( $_id, 'zoninator_zones' );
				if ( is_object( $_list ) ) {
					if ( isset( $_list->name ) ) {
						$_ret = $_list->name;
					}
				}
				break;
			case 'ug':
			case 'ugc':
			case 'ugs':
				$_list = get_term( $_id, 'ef_usergroup' );
				if ( is_object( $_list ) ) {
					if ( isset( $_list->name ) ) {
						$_ret = $_list->name;
					}
				}
				break;
			case 'rss':
			case 'shar':
				$_ret = $_id;
				break;
			case 'api':
				$_ret = $_id;
				break;
			case 'chrt':
				$_ret = $_id;
				break;
			default:
				$_ret = apply_filters( 'pn_layouts_display_custom_selected_value', '', $_typ, $_id );
				break;
		}
		return $_ret;
	}

	function pn_get_date( $_date = '' ) {
		$_ret = '';
		$_time = 0;
		if ( preg_match( '/^[0-9]+$/', $_date ) ) {
			// date is a timestamp so this is easy
			$_time = intval( $_date );
		} elseif ( '' != $_date ) {
			// e.g. 2014-05-06T14:47:06Z
			if ( 'z' == strtolower( substr( $_date, -1 ) ) || ( '+0000' == substr( $_date, -5 ) ) ) {
				$_offset = time() - current_time( 'timestamp' ); // calculate the time difference between local and GMT
			} else {
				$_offset = 0;
			}
			$_time = strtotime( $_date ) - $_offset;
		}
		if ( 0 < $_time ) {
			if ( date( 'M j, Y', $_time ) === date( 'M j, Y' ) ) {
				$_ret = 'Today ' . date( 'g:i A', $_time );
			} elseif ( date( 'M j, Y', $_time - 24 * 3600 ) === date( 'M j, Y', time() - 24 * 3600 ) ) {
				$_ret = 'Yesterday ' . date( 'g:i A' );
			} elseif ( date( 'Y', $_time ) !== date( 'Y' ) ) {
				$_ret = date( 'M j, Y', $_time );
			} else {
				$_ret = date( 'M j', $_time );
			}
		}
		return $_ret;
	}

	/**
	* Set the page type: mobile/desktop + index/post
	*
	* @return (null)
	*/
	function get_device_page_type() {
		$_type = '';
		if ( true === $this->is_mobile ) {
			$_type .= 'm';
		} else {
			$_type .= 'd';
		}
		if ( true === is_single() ) {
			$_type .= 'p';
		} else {
			$_type .= 'i';
		}
		$this->device_page_type = $_type;
	}

	function get_outfit_count( $term_type = 'home' ) {
		$_count = 0;
		$_sect_max = intval( $this->pmlay_count[ 'sections_' . $term_type ] ); // CMJ MAX: section_home, section_category, section_tag
		$_limit = $_sect_max;
		for ( $_section = 0; $_section < $_limit; $_section ++ ) {
			if ( isset( $this->pmlay_settings['outfits'][ $_section ]['template'] ) ) {
				$_template = intval( $this->pmlay_settings['outfits'][ $_section ]['template'] );
				if ( -1 != $_template ) {
					if ( is_array( $this->outfit_settings[ $_template ] ) ) {
						if ( isset( $this->outfit_settings[ $_template ][3] ) ) {
							// the $this->outfit_settings array defines the number of widget/ad slots available for each outfit
							$_count += intval( $this->outfit_settings[ $_template ][3] );
						}
					}
				}
			}
		}
		// CMJ: for the purpose of the rules engine, this should be further reduced to account for slots that cannot display an ad due to in-outfit configuration
		// to do this we need to parse the page twice - once to asses the size of it and a second time to fill it up with content
		// or just parse this->outfit_settings and this->pmlay_settings['outfits'][ $_section_num ]['widget']
		$this->widget_slot_count = $_count; // total number of widget/ad slots available on the page
	}

	/**
	* Return or display the video player in one outfit - called from [theme]/pm_layouts/outfit/outfit-[0-9]+.php
	*
	* @param bool $_echo defines whether to return or echo the output
	*
	* @return (null|string)	$_output	HTML defining the video player
	*/
	public function get_outfit_video( $_echo = false, $_video_num = -1 ) {
		$_section_num = intval( $this->pmlay_count['section'] );
		$_videos = isset( $this->pmlay_settings['outfits'][ $_section_num ]['videos'] ) ? $this->pmlay_settings['outfits'][ $_section_num ]['videos'] : array();
		$_output_ary = array();
		$_output_html = '';
		if ( -1 < $_video_num ) {
			$_output_html = $this->get_video_html( $_video_num, $_videoid );
		} else {
			if ( is_array( $_videos ) ) {
				foreach ( $_videos as $_num => $_videoid ) {
					if ( '' !== trim( $_videoid ) ) {
						$_output_ary[ $_num ] = $this->get_video_html( $_num, $_videoid );
					}
				}
			}
		}
		if ( ( true === $_echo ) && ( -1 < $_video_num ) ) {
			$this->safe_echo( $_output_html ); // Early-escaped
		} else {
			return $_output_ary;
		}
	}

	/**
	* Return HTML used to display the video
	* use the video shortcode in pn-kaltura-shortcodes.php (pn-video-override plugin) to render the video channel player
	* shortcode e.g. [kaltura-widget entryid='0_gcz19vu0' playerid='layouts_0_3' playertype='channel']
	* use playertype='video' for single videos
	*
	* @param int $_video_num the order integer for the video in the outfit
	* @param string $_videoid The Kaltura/Brightcove ID for the video
	*
	* @return string	$_output	The HTML used to display the video
	*/
	public function get_video_html( $_video_num = 0, $_videoid = '' ) {
		$_output = '';
		if ( '' !== trim( $_videoid ) ) {
			if ( '*' !== substr( trim( $_videoid ), 0, 1 ) ) {
				$_identifier = 'layouts_' . $this->pmlay_count['section'] . '_' . $_video_num;
				$pn_video_options = get_option( 'pn_video_options' );
				if ( isset( $pn_video_options['enable_youtube'] ) && $pn_video_options['enable_youtube'] ) {
					$_output .= do_shortcode( "[video-playlist playlistid='" . $_videoid . "' playerid='" . $_identifier . "' ]" );
				} else {
					$_output .= do_shortcode( "[kaltura-widget entryid='" . $_videoid . "' playerid='" . $_identifier . "' playertype='channel']" );
				}
			}
		}
		return $_output;
	}

	/**
	* Return the plugin's version number
	*
	* return	string	PM_LAYOUT_VERSION	Plugin version number
	*/
	function get_version() {
		return PM_LAYOUT_VERSION;
	}

	/**
	* Return the path to the folder containing all the Layouts templates
	*
	* return	null
	*/
	public function get_templates_folder() {
		if ( '' === $this->template_path ) {
			if ( -1 === intval( $this->template_location ) ) {
				$this->template_location = intval( get_option( 'postmedia_layouts_template_location' ) );
			}
			if ( 1 === intval( $this->template_location ) ) {
				// CHILD THEME DIRECTORY
				$this->template_path = get_stylesheet_directory() . '/pm_layouts/';
				$this->template_url = get_stylesheet_directory_uri() . '/pm_layouts/';
			} else {
				// PARENT THEME DIRECTORY
				$this->template_path = get_template_directory() . '/pm_layouts/';
				$this->template_url = get_template_directory_uri() . '/pm_layouts/';
			}
		}
	}

	/**
	* Are we on a mobile or a desktop device?
	*
	* return	boolean
	*/
	function is_mobile() {
		if ( ( true === $this->is_mobile ) || ( false === $this->is_mobile ) ) {
			// check if already set and also whitelist possible values - return if $this->is_mobile has been set to a boolean value
			return $this->is_mobile;
		}
		return Utilities::is_mobile();
	}

	/**
	* Are we on an index page or not?
	*
	* return	boolean
	*/
	function is_index() {
		if ( is_category() || is_home() || is_tag() ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	* Get the link to a term page
	*
	* @param int $_id Identifier for the term
	* @param string $_type category | post_tag
	*
	* @return mixed $_output
	*/
	function get_term_link( $_id, $_type = 'category' ) {
		$_id = intval( $_id ); // since the functions below can accept different data types for the first parameter, force $_id to integer
		if ( function_exists( 'wpcom_vip_get_term_link' ) ) {
			$_url = wpcom_vip_get_term_link( $_id, $_type );
		} else {
			$_url = get_term_link( $_id, $_type ); // @codingStandardsIgnoreLine - fallback in case we deploy this plugin on a non-VIP site
		}
		// validate the return value
		$_url = is_wp_error( $_url ) ? '' : trim( $_url );
		return $_url;
	}

	/**
	* Set names for various transients used by this term (home / category / tag)
	*
	* @param string $_mode	device type (phone/other)
	* @param integer $_term_id	ID number defining the term (home=0 / category / tag)
	* @param string $_type type of transient name to retrieve, if '' then set all transient names to the gloabl properties instead
	*
	* @return (null|string) $_transient_name
	*/
	function get_transient_names( $_mode, $_term_id, $_type = '' ) {
		$_mode = ( 'desktop' === $_mode ) ? 'desktop' : 'phone'; // whitelist
		if ( '' === $_type ) {
			$client_id = get_option( 'wcm_client_id' );
			$this->transient_names['expire'] = 'pn_layouts_' . $_mode . '_expire_' . $_term_id . '_' . $client_id; // contains a timestamp indicating when to refresh the cached data
			$this->transient_names['outfits'] = 'pn_layouts_' . $_mode . '_outfits_' . $_term_id . '_' . $client_id; // contains a JSON-encoded array of outfit HTML blocks
			$this->transient_names['posts'] = 'pn_layouts_' . $_mode . '_posts_' . $_term_id . '_' . $client_id; // contains a comma-delimited list of posts displayed on this page to exclude them from sidebar lists
			$this->transient_names['exclude'] = 'pn_layouts_' . $_mode . '_exclude_' . $_term_id . '_' . $client_id;
			return null;
		} else {
			$_transient_name = 'pn_layouts_' . $_mode . '_' . $_type . '_' . $_term_id;
			return $_transient_name;
		}
	}

	/**
	* Force the system to refresh the transient data (Layouts HTML) for a given term on both mobile and desktop - used when data modified in admin
	*
	* @param integer $_term_id	ID number defining the term (home=0 / category / tag)
	* @param integer $_delay	number of seconds to delay refresh - for systems like Zones that receive multiple consecutive changes
	*
	* @return (null)
	*/
	public function expire_transient( $_term_id, $_delay ) {
		$_delay = intval( $_delay ); // type cast
		$_expire_timestamp = ( 0 === $_delay ) ? 0 : current_time( 'timestamp' ) + $_delay; // get current wp time and add delay if delay > 0 otherwise refresh right now
		$_transient_name = $this->get_transient_names( 'phone', $_term_id, 'expire' );
		set_transient( $_transient_name, $_expire_timestamp, 0 );
		$_transient_name = $this->get_transient_names( 'desktop', $_term_id, 'expire' );
		set_transient( $_transient_name, $_expire_timestamp, 0 );
	}

	/** From Core theme :: Utilities :: esc_layouts()
	* This function is to be used for escaping code from postmedia-layouts.
	* As postmedia-layouts early escapes html this function enables us to
	* standardize our own escaping of this content.
	*
	* @param  string $html Content to be escaped
	*
	* @return string       Safe content
	*/
	public function safe_echo( $_html ) {
		echo $_html; // @codingStandardsIgnoreLine - all html echoed here has been early escaped
	}

	/**
	* Get one (primary) local author for a locally-served post
	* Copied from Province Theme > pm_layouts > list-simple-discussion-li.php
	* CMJ to integrate WCM
	*
	*/
	public function get_post_author( $_post_id, $_post_type, $_post = null ) {
		$_output = new stdClass();
		$_output->slug = '';
		$_output->name = '';
		$_output->email = '';
		$_output->url = '';
		$_output->gravatar = '';
		if ( true === $this->list_uses_wcm ) {
			// Source: WCM
			if ( isset( $_post->author )  ) {
				$_uid = 0;
				$_slug = isset( $_post->author->slug ) ? trim( $_post->author->slug ) : '';
				$_url = isset( $_post->author->url ) ? trim( $_post->author->url ) : '';
				$_email = isset( $_post->author->email ) ? trim( $_post->author->email ) : '';
				$_name = isset( $_post->author->name ) ? trim( $_post->author->name ) : '';
				if ( '' !== $_url ) {
					$_output->url = $_url;
				} else {
					if ( '' !== $_slug ) {
						$_author = get_user_by( 'slug', $_slug ); // get from WP based on slug
						if ( isset( $_author->data->ID ) ) {
							$_uid = intval( $_author->data->ID );
						}
					}
					if ( 0 < $_uid ) {
						$_url = get_author_posts_url( $_uid, $_slug );
					}
				}
				if ( isset( $_post->author->photo->url ) ) {
					$_output->gravatar = '<img alt="' . esc_attr( $_name ) . '"
						src="' . esc_attr( $_post->author->photo->url ) . '"
						srcset="' . esc_attr( $_post->author->photo->url ) . '"
						class="avatar avatar-32 photo" height="32" width="32" />';
				}
				if ( ( '' !== $_email ) && ( '' === $_output->gravatar ) ) {
					if ( function_exists( 'pn_get_avatar' ) ) {
						$_output->gravatar = pn_get_avatar( $_email, '90', '', '' );
					}
				}
				$_output->slug = $_slug;
				$_output->name = $_name;
				$_output->email = $_email;
				$_output->url = $_url;
			}
		} else {
			// Source: Wordpress
			if ( 'pn_pointer' !== $_post_type ) {
				// Get the co-authors
				if ( ( 0 === intval( $_post_id ) ) && ( ! is_null( $_post ) )  && ( isset( $_post->ID ) ) ) {
					$_post_id = intval( $_post->ID );
				}
				if ( ( 0 === $_post_id ) && ( isset( $_post->post_author ) ) && ( '' !== trim( $_post->post_author ) ) ) {
					// RSS feed with authors
					$_output->name = trim( $_post->post_author );
				} else {
					// Local Wordpress
					if ( function_exists( 'get_coauthors' ) ) {
						if ( 0 < $_post_id ) {
							$authors = get_coauthors( $_post_id );
						}
					}
					$author_id = 0;
					if ( ! empty( $authors ) && is_array( $authors ) ) {
						// Use co-authors info
						$author_id = $authors[0]->ID;
					} else {
						// Use post info
						if ( 0 < $_post_id ) {
							$author_id = get_post_field( 'post_author', $_post_id );
						}
					}
					if ( 0 < $author_id ) {
						$_author = new WP_User( $author_id );
						if ( isset( $_author ) ) {
							if ( isset( $_author->data ) ) {
								$_author_name = ( isset( $_author->data->display_name ) ) ? trim( $_author->data->display_name ) : '';
								$_author_nicename = ( isset( $_author->data->user_nicename ) ) ? trim( $_author->data->user_nicename ) : '';
								$_author_url = get_author_posts_url( $author_id, $_author_nicename );
								$_output->name = trim( $_author_name );
								$_output->url = trim( $_author_url );
								$_output->slug = trim( $_author_nicename );
								$_output->email = ''; // get CMJ
								if ( function_exists( 'pn_get_avatar' ) ) {
									$_output->gravatar = pn_get_avatar( $author_id, '90', '', '' );
								}
							}
						}
					}
				}
			}
		}
		return $_output;
	}

	function get_post_category( $_post_id ) {
		$category_id = get_post_meta( intval( $_post_id ), '_pn_main_category', true );
		$current_category = get_category_parents( $category_id, false, '/' ,true );
		if ( '' == $category_id ) {
			$main_category = get_category( $category_id );
			$parent_category = get_category( $main_category->category_parent );
		} else {
			$current_category = explode( '/', $current_category );
			$get_category_id = wpcom_vip_get_category_by_slug( $current_category[0] );
			$parent_category_id = $get_category_id->term_id;
		}
		$parent_category = get_category( $parent_category_id );
		$_output = new stdClass();
		$_output->text = $parent_category->slug;
		$_output->url = $parent_category->slug;
		$_output->parent = '';
		return $parent_category->slug;
	}

	/**
	* Gets a local category from a slug - used for WCM
	*
	* @param  string $_category_slug Content to be escaped
	* @param  integer $_level The level of category to retrieve: 0 = self (e.g. NHL), 1 = parent (e.g. Hockey), 2 = top-level parent (e.g. Sports)
	*
	*/
	public function get_local_category( $_post ) {
		$_output = new stdClass;
		$_output->top_slug = '';
		$_output->slug = '';
		$_output->title = '';
		$_output->url = '';
		if ( true === $this->list_uses_wcm ) {
			// the top_slug is used to set css on elements on the post - it is the slug for the top level category
			$_top_slug = isset( $_post->categories->top ) ? $_post->categories->top : '';
			$_top_slug = $this->slugify( $_top_slug );
			$_output->top_slug = $_top_slug;
			$_cat_data = $this->get_wcm_category_by_path( $_post->categories->path );
			$_output->slug = $_cat_data->slug;
			$_output->title = $_cat_data->title;
			$_output->url = $_cat_data->url;
		} else {
			// get the local Wordpress data
			$_category_id = $this->get_wp_main_category( $_post );
			$_wp_category = get_term( $_category_id, 'category' );
			if ( ( ! is_null( $_wp_category ) ) && ( ! is_wp_error( $_wp_category ) ) ) {
				// category exists
				$_category_path = get_category_parents( $_category_id, false, '/' ,true ); // get the full path to the main category
				if ( ! is_wp_error( $_category_path ) ) {
					$_categories = explode( '/', $_category_path ); // turn the path into an array of categories
					$_top_slug = ( '' !== $_categories[0] ) ? trim( $_categories[0] ) : trim( $_categories[1] ); // grab the first category, excluding blankscaused by preceding /
					$_output->top_slug = $_top_slug;
				}
				if ( ( ! is_null( $_wp_category ) ) && ( ! is_wp_error( $_wp_category ) ) ) {
					$_output->slug = $_wp_category->slug;
					$_output->title = $_wp_category->name;
					$_output->url = $this->get_term_link( $_wp_category->term_id, 'category' ); // replaces get_category_link()
				}
			}
		}
		return $_output;
	}

	/**
	* Get a post's main category
	*
	* @param (object) $_post The post object
	*
	* @return (integer) $_output The main category ID
	*/
	private function get_wp_main_category( $_post ) {
		$_output = 0;
		if ( isset( $_post->ID ) ) {
			$_post_id = intval( $_post->ID );
			$_output = intval( get_post_meta( $_post_id, '_pn_main_category', true ) );
		}
		return $_output;
	}

	/**
	* Turn a phrase into a WP slug
	*
	* @param (string) $_text The phrase to be converted
	*
	* @return (string) $_output The slugified phrase
	*/
	private function get_wcm_category_by_path( $_path ) {
		$_output = new stdClass;
		$_output->slug = '';
		$_output->title = '';
		$_output->url = '';
		$_categories = explode( '/', $_path );
		$_categories = array_reverse( $_categories );
		$_count_cats = count( $_categories );
		for ( $x = 0; $x < $_count_cats; $x ++ ) {
			$_slug = $this->slugify( $_categories[ $x ] );
			if ( '' !== $_slug ) {
				$_wp_category = wpcom_vip_get_category_by_slug( $_slug );
				if ( false !== $_wp_category ) {
					$_output->slug = $_wp_category->slug;
					$_output->title = $_wp_category->name;
					$_output->url = $this->get_term_link( $_wp_category->term_id, 'category' ); // replaces get_category_link()
					break;
				}
			}
		}
		// save cats for future use
		return $_output;
	}

	/**
	* Turn a phrase into a WP slug
	*
	* @param (string) $_text The phrase to be converted
	*
	* @return (string) $_output The slugified phrase
	*/
	private function slugify( $_text ) {
		$_slug = strtolower( $_text ); // convert to lowercase
		$_slug = trim( $_slug ); // remove leading and trailing spaces
		$_slug = preg_replace( '/\s+/', '-', $_slug ); // convert all contiguous whitespace to a single hyphen
		return $_slug;
	}

	/**
	* Get post title either from WCM or local WP
	*
	* @param (object) $_post The post object
	*
	* @return (mixed) $_output The post title
	*/
	function get_post_title( $_post ) {
		if ( true === $this->list_uses_wcm ) {
			$_ret = isset( $_post->post_title ) ? trim( $_post->post_title ) : '';
		} else {
			$_alt = isset( $_post->ID ) ? get_post_meta( $_post->ID, '_pn_title_alternate', true ) : '';
			if ( trim( $_alt ) ) {
				$_ret = $_alt;
			} else {
				$_ret = isset( $_post->post_title ) ? trim( $_post->post_title ) : '';
			}
		}
		return trim( strip_tags( $_ret ) );
	}

	/**
	* Get basic post data either from WCM or local WP
	* This function can only reliably return properties shared by both WP and WCM post objects
	*
	* @param (object) $_post The post object
	* @param (string) $_key The post key
	*
	* @return (mixed) $_output The post data value
	*/
	public function get_post_data( $_post, $_key ) {
		$_post_keys = array( 'post_title', 'post_excerpt', 'post_content', 'post_date', 'post_name', 'post_type', 'url', 'target', 'author', 'category', 'video', 'gallery', 'thumbnail', 'fullimg' );
		$_post_keys = apply_filters( 'pn_layouts_post_fields', $_post_keys ); // allow external code to add or remove post fields
		$_output = '';
		// whitelist _key against _post_keys
		if ( is_object( $_post ) && isset( $_post->{ $_key } ) ) {
			$_output = $_post->{ $_key };
		}
		return $_output;
	}

	/**
	* Get post meta data either from WCM or local WP
	*
	* @param (object|integer) $_post Either the post object, or the post ID number (only included for backward compatibility, please always use the post object going forward)
	* @param (string) $_key The post meta key
	* @param (boolean) $_single Return a single meta value or an array of all available
	*
	* @return (mixed) $_output The post meta value
	*/
	public function get_post_meta( $_post, $_key = '', $_single = true ) {
		$_output = '';
		if ( is_object( $_post ) && isset( $_post->metadata->{ $_key } ) ) {
			if ( $_single ) {
				$_output = maybe_unserialize( $_post->metadata->{ $_key }[0] );
			} else {
				$_output = array_map( 'maybe_unserialize', $_post->metadata->{ $_key } );
			}
		} else {
			if ( is_object( $_post ) && isset( $_post->ID ) ) {
				$_post_id = intval( $_post->ID );
			} else {
				$_post_id = intval( $_post );
			}
			if ( 0 < $_post_id ) {
				$_output = get_post_meta( $_post_id, $_key, $_single );
			}
		}
		return $_output;
	}

	/**
	* Checks if the post has a gallery
	*
	* @param (object) $_post The post object
	*
	* @return (boolean) $_output True if the post has a gallery
	*/
	public function get_post_has_gallery( $_post ) {
		$_output = false;
		if ( isset( $_post->post_type ) ) {
			if ( 'gallery' === $_post->post_type ) {
				$_output = true;
			} else {
				$_gallery_id = $this->get_post_meta( $_post, 'featured-gallery-id', true );
				if ( ! empty( $_gallery_id ) ) {
					$_output = true;
				}
			}
		}
		return $_output;
	}

	/**
	* Get post media data from WCM or local WP or RSS
	*
	* @param (object) $_post The post object
	*
	* @return (mixed) $_output The post meta value
	*/
	public function get_post_media( $_post, $_size = 'thumb' ) {
		$_output = new stdClass();
		$_image = new stdClass();
		$_image->id = '';
		$_image->alt = '';
		$_image->src = '';
		$_image->height = 0;
		$_image->width = 0;
		$_video = new stdClass();
		$_video->id = '';
		$_video->title = '';
		$_video->inline = true;
		$_gallery = new stdClass();
		$settings = $this->pmlay_settings;
		$_wcm = $this->list_uses_wcm;
		if ( $_wcm || ( 'rss' === $settings['list_type'] ) ) {
			if ( $_wcm ) {
				// Get image from WCM
				$_mode = 'wcm';
				$_image->id = ( isset( $_post->image->origin_id ) ) ? $_post->image->origin_id : 0;
				$_image->height = ( isset( $_post->image->height ) ) ? intval( $_post->image->height ) : 0;
				$_image->width = ( isset( $_post->image->width ) ) ? intval( $_post->image->width ) : 0;
				$_image_alt = ( isset( $_post->image->title ) ) ? trim( $_post->image->title ) : '';
				$_image->alt = ( '' !== $_image_alt ) ? $_image_alt : ( isset( $_post->image->caption ) ? trim( $_post->image->caption ) : '' );
				$_image->src = ( isset( $_post->image->url ) ) ? $_post->image->url : '';
				if ( 0 < strpos( $_image->src, ' src="' ) ) {
					preg_match( '/src\=\"([^\"]+)\"/', $_image->src, $_matches );
					if ( isset( $_matches[1] ) ) {
						$_image->src = $_matches[1];
					}
				}
				$_video->id = ( isset( $_post->video->origin_id ) ) ? $_post->video->origin_id : '';
				if ( isset( $_post->video->title ) ) {
					$_video->title = trim( $_post->video->title );
				}
				if ( ( '' === $_video->title ) && isset( $_post->video->description ) ) {
					$_video->title = trim( $_post->video->description );
				}
				$_video->video_source = ( isset( $_post->video->video_source ) ) ? $_post->video->video_source : '';
				$_gallery = $_post->gallery;
			} else {
				// Get image from RSS - there is no video or gallery
				$_mode = 'rss';
				$_image->src = ( isset( $_post->thumbnail ) ) ? $_post->thumbnail : '';
			}
		} else {
			// Get image from Wordpress
			$_mode = 'wp';
			$_image->id = get_post_thumbnail_id( absint( $_post->ID ) );
			$_attachment = get_post( absint( $_image->id ) );
			$image_alt = get_post_meta( $_image->id, '_wp_attachment_image_alt', true );
			$image_caption = isset( $_attachment->post_excerpt ) ? $_attachment->post_excerpt : '';
			$_image_meta = get_post_meta( $_image->id, '_wp_attachment_metadata', true );
			$_img_size = ( 'thumb' === $_size ) ? 'thumbnail' : $_size; // awkward but needed for backward compatibility
			if ( ! empty( $_post->thumbnail ) ) {
				$_imgurl = ( 'thumb' === $_size ) ? $_post->thumbnail : $_post->fullimg;
			} else {
				$_imgurl = isset( $_attachment->guid ) ? $_attachment->guid : '';
			}
			if ( isset( $_image_meta['sizes'] ) ) {
				if ( isset( $_image_meta['sizes'][ $_img_size ] ) ) {
					$_image_size = $_image_meta['sizes'][ $_img_size ];
					$_image->height = ( isset( $_image_size['height'] ) ) ? intval( $_image_size['height'] ) : 0;
					$_image->width = ( isset( $_image_size['width'] ) ) ? intval( $_image_size['width'] ) : 0;
				}
			}
			$_image->alt = ( '' == $image_alt  ) ?	$image_caption : $image_alt ;
			$_image->src = $_imgurl;
			$_video->id = trim( get_post_meta( $_post->ID, 'pn_featured_video_id', true ) );
			$_video->title = trim( get_post_meta( $_post->ID, 'pn_featured_video_title', true ) );
			$_video->video_source = trim( get_post_meta( $_post->ID, 'pn_featured_video_source', true ) );
			$_gallery = trim( get_post_meta( $_post->ID, 'featured-gallery-id', true ) ); // get from gallery
		}
		$_video->divid = rand();
		$_output->mode = $_mode;
		$_output->posturl = isset( $_post->url ) ? $_post->url : '';
		$_output->image = $_image;
		$_output->video = $_video;
		$_output->gallery = $_gallery;
		return $_output;
	}

	public function get_post_thumbnail_id( $_post_id = 0 ) {
		$_output = '';
		$_output = get_post_thumbnail_id( absint( $_post_id ) );
		return $_output;
	}

	public function get_post_attachment( $_post_id = 0 ) {
		$_output = '';
		$_attachment = get_post( absint( $_post_id ) );
		$_alt = get_post_meta( $_post_id, '_wp_attachment_image_alt', true );
		$_caption = $_attachment->post_excerpt;
		$_title = $_attachment->post_title;
		return array(
			'title' => $_title,
			'alt' => $_alt,
			'caption' => $_caption,
		);
	}

	/**
	* Get booleans indicating is bubbles and pullquotes should be displayed
	*
	* @return (null)
	*/
	public function get_newsroom_settings() {
		if ( is_null( $this->enable_pullquotes ) ) {
			if ( function_exists( 'wlo_get_option' ) ) {
				$_settings = wlo_get_option( 'pn_newsroom_settings', array() );
			} else {
				$_settings = get_option( 'pn_newsroom_settings', array() );
			}
			$this->enable_pullquotes = ! empty( $_settings['enable_pullquotes'] );
			$this->enable_emotions = ! empty( $_settings['enable_emotion_bubbles'] );
		}
	}

	/**
	* Display the emotion bubble from the post using the Newsroom plugin public method
	*
	* @param (object|integer) $_post Either the post object, or the post ID number (only included for backward compatibility, please always use the post object going forward)
	*
	* @return (string)	The text of the emotion bubble post meta record
	*/
	public function get_post_emotion( $_post, $_max_count = 1 ) {
		global $pn_emotion_bubble;
		$_output = '';
		if ( is_object( $_post ) && isset( $_post->metadata->pn_emotion_bubble ) ) {
			$_output = $_post->metadata->pn_emotion_bubble;
			$this->count_emotion_bubbles ++;
		} else {
			if ( is_object( $_post ) && isset( $_post->ID ) ) {
				$_post_id = intval( $_post->ID );
			} else {
				$_post_id = intval( $_post );
			}
			if ( 0 < $_post_id ) {
				$this->get_newsroom_settings();
				if ( true === $this->enable_emotions ) {
					if ( isset( $pn_emotion_bubble ) && ( $_max_count > $this->count_emotion_bubbles ) ) {
						$emotion_bubble = $pn_emotion_bubble->get_emotion( $_post_id );
						if ( ( '' !== $emotion_bubble ) && ( 'NONE' !== $emotion_bubble ) ) {
							$_output = $emotion_bubble;
							$this->count_emotion_bubbles ++;
						}
					}
				}
			}
		}
		return $_output;
	}

	/**
	* Return the pullquote from the post using the Newsroom plugin public method
	*
	* @param (object|integer) $_post Either the post object, or the post ID number (only included for backward compatibility, please always use the post object going forward)
	* @param (integer) $_max_count defines the max number of pullquotes to return
	*
	* @return (null|array)	$_output	The text of the pullquote post meta record: array( 'text' (string), 'source' (string) );
	*/
	public function get_post_pullquote( $_post, $_max_count = 1 ) {
		global $pn_pullquote;
		$_output = null;
		if ( is_object( $_post ) && isset( $_post->metadata->pni_pull_quote ) ) {
			$_output = $_post->metadata->pni_pull_quote;
			$this->count_pull_quotes ++;
		} else {
			if ( is_object( $_post ) && isset( $_post->ID ) ) {
				$_post_id = intval( $_post->ID );
			} else {
				$_post_id = intval( $_post );
			}
			if ( 0 < $_post_id ) {
				$this->get_newsroom_settings();
				if ( true === $this->enable_pullquotes ) {
					if ( ( isset( $pn_pullquote ) ) && ( $_max_count > $this->count_pull_quotes ) ) {
						$_output = $pn_pullquote->get_pullquote( $_post_id );
						$this->count_pull_quotes ++;
					}
				}
			}
		}
		return $_output;
	}

	/**
	* Return the stocksymbols from the post using the Newsroom plugin public method
	*
	* @param (object|integer) $_post The post object
	*
	* @return (null|array)	$_output	The array of stock symbols
	*/
	public function get_post_stocksymbols( $_post ) {
		$_output = $this->get_post_meta( $_post, 'company_symbols', true );
		return $_output;
	}

	/**
	* Return the full list of tag slugs attached to one post
	*
	* @param (object|integer) $_post The post object
	*
	* @return $_output (array) The tag slugs
	*/
	public function get_post_tags( $_post ) {
		$_output = $this->get_post_terms( $_post, 'post_tag' );
		return $_output;
	}

	/**
	* Return the full list of category slugs attached to one post
	*
	* @param (object|integer) $_post The post object
	*
	* @return $_output (array) The category slugs
	*/
	public function get_post_categories( $_post ) {
		$_output = $this->get_post_terms( $_post, 'category' );
		return $_output;
	}

	/**
	* Return the full list of tag slugs attached to one post
	*
	* @param (object|integer) $_post The post object
	*
	* @return $_output (array) The tag slugs
	*/
	private function get_post_terms( $_post, $_tax = 'category' ) {
		$_output = array();
		$_tax = ( 'category' === $_tax ) ? 'category' : 'post_tag'; // whitelist taxonomy
		$_tax_property = ( 'category' === $_tax ) ? 'categories' : 'tags';
		if ( is_object( $_post ) && isset( $_post->{ $_tax_property } ) ) {
			// WCM data source
			if ( isset( $_post->{ $_tax_property }->all ) ) {
				$_output = $_post->{ $_tax_property }->all;
			}
		} else {
			$_terms = get_the_terms( $_post->ID, $_tax ); // replaces wp_get_post_terms because it is not cached
			if ( is_array( $_terms ) ) {
				foreach ( $_terms as $_term ) {
					if ( isset( $_term->slug ) ) {
						$_output[] = $_term->slug;
					}
				}
			}
		}
		return $_output;
	}

	/**
	* Get the sponsorship data for a post
	*
	* @param (object) $_post The post object
	*
	* @return (string)	The text of the emotion bubble post meta record
	*/
	public function get_post_sponsorship( $_post ) {
		$_output = new stdClass;
		$_output->sponsored = false;
		$_output->class = '';
		$_output->name = '';
		if ( is_object( $_post ) && isset( $_post->sponsored ) ) {
			$_output->sponsored = ( 'on' === $_post->sponsored ) ? true : false;
			$_output->class = $_post->advertorial_class;
			$_output->name = $_post->advertorial_name;
		} else {
			if ( isset( $_post->ID ) ) {
				$_sponsored = trim( get_post_meta( intval( $_post->ID ), 'advertorial_meta_box', true ) );
				if ( 'on' === $_sponsored ) {
					$_output->sponsored = true;
					$_output->class = trim( get_post_meta( intval( $_post->ID ), 'advertorial_type', true ) );
					$_output->name = trim( get_post_meta( intval( $_post->ID ), 'pn_adv_sponsor_name', true ) );
				}
			}
		}
		return $_output;
	}

	// WCM METHODS

	function get_wcm_settings() {
		$this->wcm_client_id = $this->get_wcm_client_id();
		$this->wcm_api_keys = $this->get_wcm_api_keys();
		$this->wcm_api_url = $this->get_wcm_api_url();
	}

	/**
	* Get post list from WCM
	*
	* @param $_typ (string) Type of post list (e.g. category, tag, zone)
	* @param $_params (object) Parameters for endpoint: list_id, list_id2, list_name
	*
	* @return (null) Fills object properties with values
	*/
	function list_wcm( $_typ, $_list_id = '', $_list_id2 = '', $_list_name = '', $_size = 50, $_from = 0 ) {
		if ( true === $this->site_uses_wcm() ) {
			$this->list_uses_wcm = true;
			if ( '' === $_list_name ) {
				if ( in_array( $_typ, array( 'cat', 'cax', 'tag' ), true ) ) {
					$_list_name = $this->get_list_name( $_typ, $_list_id );
				} elseif ( in_array( $_typ, array( 'shar' ), true ) ) {
					$_list_name = $_list_id;
				} elseif ( in_array( $_typ, array( 'zon' ), true ) ) {
					$_list_name = $_list_id;
				}
			}
			$_list_data = $this->get_wcm_data( $_typ, $_list_name, $_size, $_from );
			if ( false === $_list_data ) {
				// Failed to reach the WCM - Note: this is not an issue of reaching the WCM and getting empty result set back
				$_list = array();
			} elseif ( empty( $_list_data ) ) {
				// No content found in WCM
				$_list = array();
			} else {
				$_list = $this->wcm_to_post_list( $_list_data );
			}
		} else {
			$_list = array(); // only a transition issue so don't create fallback, per MMM, alternate would be to fall back to baselist
		}
		$this->pmlay_settings['list_name'] = ''; // Get Name from WCM
		$this->pmlay_settings['list_link'] = ''; // Get Link HTML from WCM
		$this->pmlay_settings['list'] = $_list;
		$this->pmlay_settings['posts_total'] = count( $_list );
	}

	/**
	* Get the name of the category or tag or WCM ID of Zone being requested so it can be passed to WCM
	*
	* @param $_typ (string) List type: cat, cax, tag, zon
	* @param $_list_id (integer) Term/Zone ID
	*
	* @return (string) Name of the term or WCM List ID of Zone
	*/
	function get_list_name( $_typ, $_list_id ) {
		$_name = '';
		if ( '' !== $_list_id ) {
			if ( 'zon' === $_typ ) {
				// get term meta
				$_name = $this->get_wcm_zone_id( $_list_id );
			} elseif ( 'shar' === $_typ ) {
				// get term meta
				$_name = $_list_id;
			} else {
				$_taxonomy = ( 'tag' === $_typ ) ? 'post_tag' : 'category';
				$_term = wpcom_vip_get_term_by( 'id', $_list_id, $_taxonomy, OBJECT );
				if ( isset( $_term->name ) ) {
					$_name = $_term->name;
				}
			}
		}
		return $_name;
	}

	/**
	* Get a WCM endpoint URL
	*
	* @param $_url (string) URL of the WCM endpoint
	* @param $_params (object) Parameters for endpoint: list_id, list_id2, list_name
	*
	* @return (array) List of elements
	*/
	public function get_wcm_zone_id( $zone_id = '' ) {
		$_output = get_term_meta( $zone_id, 'pn_wcm_id', true );
		if ( empty( $_output ) && function_exists( 'wlo_get_option' ) ) {
			$zone_key = 'pn_wcm_zoninator_zones_' . $zone_id;
			$_output = wlo_get_option( $zone_key, false );
		}
		return $_output;
	}

	/**
	* Get the WCM client ID for this market
	*
	* @return (string) WCM client ID
	*/
	public function get_wcm_api_keys() {
		return array(
			'read' => get_option( 'wcm_read_key', '' ),
			'write' => get_option( 'wcm_write_key', '' ),
		);
	}

	/**
	* Get the WCM API base URL
	*
	* @return (string) WCM URL
	*/
	public function get_wcm_api_url() {
		$_output = trim( get_option( 'wcm_api_url', '' ) );
		if ( '/' === substr( $_output, -1 ) ) {
			// remove a trailing / if one was entered
			$_output = substr( $_output, 0, -1 );
		}
		return $_output;
	}

	/**
	* Get the WCM client ID for this market
	*
	* @return (string) WCM client ID
	*/
	public function get_wcm_client_id() {
		return get_option( 'wcm_client_id', '' );
	}

	/**
	* Get a JSON-encoded post list object from a WCM endpoint
	*
	* @param $_url (string) URL of the WCM endpoint
	* @param $_params (object) Parameters for endpoint: list_id, list_id2, list_name
	*
	* @return (array) List of elements
	*/
	public function get_wcm_data( $_typ = 'cat', $_list_name = '', $_size = 50, $_from = 0 ) {
		if ( isset( $this->pmlay_count ) ) {
			$_outfit_num = isset( $this->pmlay_count['section'] ) ? intval( $this->pmlay_count['section'] ) : 0;
			$_list_num = isset( $this->pmlay_count['module'] ) ? intval( $this->pmlay_count['module'] ) : 0;
			$_include_network = isset( $this->pmlay_settings['outfits'][ $_outfit_num ]['lists'][ $_list_num ]['source'] ) ? $this->pmlay_settings['outfits'][ $_outfit_num ]['lists'][ $_list_num ]['source'] : 0;
		}
		$_client = $this->wcm_client_id;
		$_size = intval( $_size );
		$_size = ( 0 < $_size ) ? $_size : 40;
		$_list_name = strtolower( $_list_name );
		$_qs = array();
		$_qs['size'] = intval( $_size );
		$_qs['from'] = intval( $_from );
		if ( 'tag' === $_typ ) {
			if ( 0 === $_include_network ) {
				$_qs['clients'] = '[["' . $_client . '"]]';
			} else {
				$_qs['types'] = '[["!pointer"]]'; //exclude pointers from network tag searches
			}
			$_qs['expand_content'] = 'false';
			$_qs['tags'] = '[["' . $_list_name . '"]]';
			$_response = $this->get_list_data( 'content', '', $_qs );
		} elseif ( in_array( $_typ, array( 'cax', 'cat' ), true ) ) {
			if ( 0 === $_include_network ) {
				$_qs['clients'] = '[["' . $_client . '"]]';
			} else {
				$_qs['types'] = '[["!pointer"]]'; //exclude pointers from network category searches
			}
			$_qs['expand_content'] = 'true';
			$_qs['cats'] = '[["' . $_list_name . '"]]';
			$_response = $this->get_list_data( 'content', '', $_qs );
		} elseif ( 'zon' === $_typ ) {
			$_response = $this->get_list_data( 'lists', $_list_name, $_qs );
		} elseif ( 'shar' === $_typ ) {
			$_response = $this->get_list_data( 'lists', $_list_name, $_qs );
		}
		$_data = array();
		if ( false === $_response ) {
			// Failed to reach the WCM - Note: this is not an issue of reaching the WCM and getting empty result set back
			$_data = false;
		} else {
			if ( ( is_array( $_response ) ) && ( isset( $_response['body'] ) ) ) {
				$_data = json_decode( $_response['body'] );
			}
		}
		return $_data;
	}

	public function get_list_data( $_mode = 'content', $_suffix = '', $_qs = array() ) {
		$_api_url = $this->wcm_api_url . '/' . $_mode . '/' . $_suffix;
		//remove empty string values from query string array
		$_qs = array_diff( $_qs, array( '' ) );
		$_query_string = http_build_query( $_qs );
		$_api_url .= '?' . $_query_string;

		// Try to get the request from the cache.
		$transient_key = 'postmedia_layouts_wcm_' . md5( $_api_url );
		$transient = get_transient( $transient_key );
		if ( ! empty( $transient ) ) {
			return $transient;
		}

		// CMJ switch to PM Library - keep this as fallback
		$_read_key = $this->wcm_api_keys['read'];
		$_args = array(
			'method' => 'GET',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(
				'x-api-key' => $_read_key,
			),
			'body' => '',
		);
		$_response = vip_safe_wp_remote_get( $_api_url, null, 3, 3, 20, $_args );

		if ( ( is_wp_error( $_response ) ) || ( ( is_array( $_response ) ) && ( isset( $_response['response']['code'] ) ) && ( 200 !== $_response['response']['code'] ) ) ) {
			// if WCM is unavailable for any reason (AWS issues, Settings incorrect, read key revoked) fall back to WP
			// set this list to rerun the pull from Wordpress instead of WCM
			$this->list_uses_wcm = false;
			// set the rest of the lists to pull from Wordpress instead of WCM
			$this->pmlay_count['show_wcm'] = false;
			$_response = false;
			// set "Use WCM" flag in admin with update_option( 'postmedia_layouts_show_wcm', 0 ); ? // better to notify admin by email or Slack
		}

		// Save the response to the cache.
		set_transient( $transient_key, $_response, 5 * MINUTE_IN_SECONDS );

		return $_response;
	}

	/**
	* Convert WCM data returned to Layouts post list data format
	*
	* @param $_list (object) WCM post list object returned from endpoint
	*
	* @return (object) Layouts post list object set to $this->pmlay_settings['list']
	*/
	function wcm_to_post_list( $_list ) {
		$_output = array();
		foreach ( $_list as $_src ) {
			$_post = new stdClass();
			if ( is_object( $_src ) ) {
				if ( isset( $_src->_id ) ) {
					// _src->_id is the WCM ID and is needed to prevent duplicates from being displayed but for pointers, the original WCM ID is needed
					$_post->ID = $this->get_wcm_original_id( $_src );
					$_post->origin_id = intval( $_src->origin_id );
					$_post->post_title = '';
					if ( isset( $_src->titles ) ) {
						if ( isset( $_src->titles->alternate ) ) {
							$_post->post_title = trim( $_src->titles->alternate );
						}
						if ( ( '' === $_post->post_title ) && isset( $_src->titles->main ) ) {
							$_post->post_title = trim( $_src->titles->main );
						}
					}
					$_post->post_excerpt = isset( $_src->excerpt ) ? trim( $_src->excerpt ) : '';
					$_post->post_additional_excerpt = isset( $_src->metadata->pni_additional_excerpt[0] ) ? trim( $_src->metadata->pni_additional_excerpt[0] ) : ''; // AB
					$_post->post_content = $this->get_wcm_body_content( $_src, 'text' );
					if ( isset( $_src->type ) && ( 'pointer' === trim( $_src->type ) ) ) {
						$pub_date = isset( $_src->metadata->pn_pointer_date[0] ) ? trim( $_src->metadata->pn_pointer_date[0] ) : '';
					} else {
						$pub_date = isset( $_src->published_on ) ? trim( $_src->published_on ) : '';
					}
					$post_dt = empty( $pub_date ) ? '' : get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $pub_date ) ), 'Y-m-d H:i:s.000' );
					$_post->post_date = $post_dt;

					$_post->post_name = isset( $_src->origin_slug ) ? trim( $_src->origin_slug ) : '';
					$_post->post_type = isset( $_src->type ) ? trim( $_src->type ) : '';
					$_post->target = '_self';
					$_post->sponsored = isset( $_src->sponsored ) ? $_src->sponsored : null;
					$_post->advertorial_class = isset( $_src->sponsored->type ) ? trim( $_post->sponsored->type ) : '';
					$_post->advertorial_name = isset( $_src->sponsored->label ) ? trim( $_post->sponsored->label ) : '';
					$_post->metadata = isset( $_src->metadata ) ? $_src->metadata : null;
					$_post->categories = isset( $_src->taxonomies ) ? $this->get_wcm_post_categories( $_src->taxonomies ) : null;
					$_post->tags = isset( $_src->taxonomies ) ? $this->get_wcm_post_tags( $_src->taxonomies ) : null;
					$_post->post_category = isset( $_src->taxonomies->main_taxonomies ) ? $_src->taxonomies->main_taxonomies[0] : null;
					$_post->gallery = isset( $_src->featured_media->gallery ) ? $_src->featured_media->gallery : null;
					// featured video
					$_post->video = isset( $_src->featured_media->video ) ? $_src->featured_media->video : null;
					// featured images
					$_post->image = isset( $_src->featured_media->image ) ? $_src->featured_media->image : null;
					// for image backward compatibility
					$_image = isset( $_src->featured_media->image->url ) ? $_src->featured_media->image->url : null;
					$_post->thumbnail = $_image;
					$_post->fullimg = $_image;
					$_post->linkout = $this->get_wcm_post_linkout( $_src );
					if ( $_post->linkout ) {
						$_post->target = '_blank';
					}

					$_post->url = $this->get_wcm_post_url( $_src, $_linkout );
					$_post->author = null;
					if ( isset( $_src->credits->authors ) ) {
						$_author_post = new stdClass;
						if ( ( isset( $_src->type ) ) && ( 'pointer' === trim( $_src->type ) ) ) {
							// the "author" of a pointer is the WP user who created it, not the original author
							$_post->author = new stdClass();
							$_post->author->name = ( isset( $_src->metadata->pn_pointer_byline[0] ) ) ? trim( $_src->metadata->pn_pointer_byline[0] ) : '';
							$_post->author->email = ( isset( $_src->metadata->pn_pointer_author_email[0] ) ) ? trim( $_src->metadata->pn_pointer_author_email[0] ) : '';
							$_post->author->gravatar = '';
							if ( ( '' !== $_post->author->email ) && ( function_exists( 'pn_get_avatar' ) ) ) {
								$_post->author->gravatar = pn_get_avatar( $_email, '90', '', '' );
							}
							$_author = ( '' !== $_post->author->email ) ?  get_user_by( 'email', $_post->author->email ) : false;
							$_post->author->url = ( ( false !== $_author ) && ( isset( $_author->user_url ) ) ) ?  trim( $_author->user_url ) : '';
							$_post->author->slug = ( ( false !== $_author ) && ( isset( $_author->user_nicename ) ) ) ?  trim( $_author->user_nicename ) : '';
						} else {
							$_author_post->author = ( isset( $_src->credits->authors[0] ) ) ? $_src->credits->authors[0] : null;
							$_post->author = $this->get_post_author( 0, 'post', $_author_post );
						}
					}
					$_output[] = $_post;
				}
			}
		}
		return $_output;
	}

	/**
	* Determine if a post should link out to the original or show on market as whitelabel
	*
	* @parameter $_post (object) The WCM post object
	*
	* @return $_output (boolean) Link out or not
	*/
	private function get_wcm_post_linkout( $_post ) {
		if ( ( isset( $_post->linkout ) ) && ( true === $_post->linkout ) ) {
			// if the post itself is set to linkout then return true
			return true;
		}
		if ( isset( $this->pmlay_count['section'] ) ) {
			$_section = intval( $this->pmlay_count['section'] );
			if ( isset( $this->pmlay_settings['outfits'][ $_section ]['linkout'] ) ) {
				$_linkout = intval( $this->pmlay_settings['outfits'][ $_section ]['linkout'] );
				if ( 1 === $_linkout ) {
					return true;
				}
			}
		}

		if ( isset( $this->pmlay_settings['module_link_out'] ) ) {
			$_linkout = (int) $this->pmlay_settings['module_link_out'];

			if ( 1 === $_linkout ) {
				return true;
			}
		}
		$_use_origin_url = $this->get_wcm_use_original_url( $_post ); // can be depracated from returning URL but still valid logic
		if ( false !== $_use_origin_url ) {
			return true;
		}
		return false;
	}

	/**
	* Return the local URL to a WCM post
	* Format: http://[local_domain]/[category_path]/[post_slug]/wcm/[wcm_id]
	* (e.g. http://ottawacitizen.com/news/local-news/cat-up-tree/wcm/b078135b-2d30-463d-b7e4-94db1d86e6dd)
	*
	* @parameter $_post (object) The WCM post object
	*
	* @return $_output (string) The local URL to a WCM post
	*/
	public function get_wcm_post_url( $_post, $_linkout = 0 ) {
		// use original URL for some clients
		// e.g. origin_url = 'http://new.nationalpost.wpdstg1.canada.com/pmn/news-pmn/trump-lynch-action-on-clinton-inquiry-totally-illegal'
		// e.g. origin_url_path = 'pmn/news-pmn/trump-lynch-action-on-clinton-inquiry-totally-illegal'
		// e.g. client_id = '5397ae6c-b0a3-48aa-9c73-6bc923625b39'
		$_use_origin_url = $this->get_wcm_use_original_url( $_post );
		if ( false !== $_use_origin_url ) {
			return $_use_origin_url;
		}
		$_output = '';
		$_wcm_id = $this->get_wcm_original_id( $_post, $_linkout = 0 );
		if ( '' === trim( $_wcm_id ) ) {
			return $_output;
		}
		if ( ( isset( $_post->origin_url_path ) ) && ( '' !== trim( $_post->origin_url_path ) ) ) {
			$_url = trim( $_post->origin_url_path );
			$_url = trim( $_url, '/' );
			$_output .= '/' . $_url;
		}

		$origin_url = isset( $_post->origin_url ) ? $_post->origin_url : '';
		$origin_cms = isset( $_post->origin_cms ) ? $_post->origin_cms : '';
		if ( ! DataHelper::is_local( $origin_url, $origin_cms ) ) {
			$_output .= '/wcm/' . $_wcm_id;
		}

		return $_output;
	}

	/**
	* Return the linkout URL to a WCM post or false
	* should be blended into get_wcm_post_linkout() since the URL is defined now in Library
	*
	* @parameter $_post (object) The WCM post object
	*
	* @return $_output (string) The local URL to a WCM post
	*/
	private function get_wcm_use_original_url( $_post ) {
		if ( false === isset( $_post->type ) ) {
			return false;
		}
		if ( 'pointer' === $_post->type ) {
			if ( ( false === isset( $_post->metadata->pn_pointer_ext_url ) ) || ( '' === trim( $this->pmlay_count['wcm_origin_client_domain'] ) ) ) {
				return false;
			}
			if ( false === isset( $_post->metadata->pn_pointer_ext_url[0] ) ) {
				return false;
			}
			// 1. If there is no relative URL, this is an external link. Always
			// use pn_pointer_ext_url.
			// 2. If there *is* a relative URL, we might want to whitelabel the
			// content instead of using the external link. If the external link
			// is from a sister site, use pn_pointer_ext_url (do not whitelabel).
			$_url = trim( $_post->metadata->pn_pointer_ext_url[0] );
			$relative_url = isset( $_post->metadata->pn_pointer_url[0] ) ? $_post->metadata->pn_pointer_url[0] : null;
			if ( empty( $relative_url ) || false !== strpos( $_url, $this->pmlay_count['wcm_origin_client_domain'] ) ) {
				// if there is no relative URL, or the global override for client domain is containined in the external URL, then link out
				return trim( $_url );
			}
		} else {
			if ( false !== isset( $_post->metadata->_external_permalink[0] ) ) {
				$_url = trim( $_post->metadata->_external_permalink[0] );
				if ( '' !== $_url ) {
					// if there is a non-empty external URL then linkout
					return $_url;
				}
			}
			// test that the post was created by this client
			if ( ( false === isset( $_post->client_id ) ) || ( '' === trim( $_post->client_id ) ) ) {
				return false;
			}
			if ( trim( $this->pmlay_count['wcm_origin_client'] ) === trim( $_post->client_id ) ) {
				if ( ( isset( $_post->origin_url ) ) && ( '' !== trim( $_post->origin_url ) ) ) {
					// if this post was created by the origin client and the URL to that site is non-empty then linkout
					return trim( $_post->origin_url );
				}
			}
		}
		return false;
	}

	/**
	* Return the original WCM ID for a post returned by the WCM
	*
	* @parameter $_post (object) The WCM post object
	*
	* @return (string) The original WCM ID
	*/
	private function get_wcm_original_id( $_post ) {
		$_wcm_id = '';
		// Post Copied
		if ( isset( $_post->metadata->pn_wcm_id )  && isset( $_post->metadata->pn_wcm_origin_id ) ) { // post copy
			$_wcm_id = is_array( $_post->metadata->pn_wcm_id ) ? trim( $_post->metadata->pn_wcm_id[0] ) : trim( $_post->metadata->pn_wcm_id );
		}
		// Pointer
		if ( isset( $_post->metadata->pn_wcm_origin_id ) && 'pointer' === $_post->type ) {
			$_wcm_id = is_array( $_post->metadata->pn_wcm_origin_id ) ? trim( $_post->metadata->pn_wcm_origin_id[0] ) : trim( $_post->metadata->pn_wcm_origin_id );
		}

		if ( '' === $_wcm_id ) {
			// if this is a post look first in the property of the object returned from WCM API
			$_wcm_id = isset( $_post->_id ) ? trim( $_post->_id ) : '';
		}
		if ( '' === $_wcm_id ) {
			// if this is a post and no ID has been found yet, check in the property assigned after feed ingestion which mimics the schema of a WP post object
			$_wcm_id = isset( $_post->ID ) ? trim( $_post->ID ) : '';
		}
		return $_wcm_id;
	}

	/**
	* Return the body content, either complete, or text only
	*
	* @parameter $_post (object) The WCM post object
	* @parameter $_mode (string) Whether to return text only or full html (text|all)
	*
	* @return $_output (string) The body content for the post in text or HTML
	*/
	public function get_wcm_body_content( $_post, $_mode ) {
		// iterate through content_elements
		$_output = '';
		if ( isset( $_post->content_elements ) ) {
			foreach ( $_post->content_elements as $_elem ) {
				if ( isset( $_elem->type ) ) {
					switch ( $_elem->type ) {
						case 'text':
							$_output .= $this->get_wcm_element_text( $_elem );
							break;
						case 'raw_html':
							if ( 'all' === $_mode ) {
								$_output .= $this->get_wcm_element_text( $_elem );
							}
							break;
						case 'oembed':
							if ( 'all' === $_mode ) {
								$_output .= $this->get_wcm_element_oembed( $_elem );
							}
							break;
						case 'image':
							if ( 'all' === $_mode ) {
								$_output .= $this->get_wcm_element_image( $_elem );
							}
							break;
						default:
							break;
					}
				}
			}
		}
		if ( 'text' === $_mode ) {
			$_output = strip_tags( $_output );
		}
		return $_output;
	}

	/**
	* Return an HTML-formatted image from the body content
	*
	* @parameter $_content (object) The WCM content_element object
	*
	* @return $_output (string) The HTML representation of an inline image
	*/
	public function get_wcm_element_image( $_content ) {
		$_output = '';
		if ( isset( $_content->url ) && ( '' !== trim( $_content->url ) ) ) {
			$_output = '<img class="wp-post-image" src="' . esc_url( trim( $_content->url ) ) . '"';
			if ( isset( $_content->title ) && ( '' !== trim( $_content->title ) ) ) {
				$_output .= ' alt="' . trim( $_content->title ) . '"';
			}
			if ( isset( $_content->width ) && ( 0 < intval( $_content->width ) ) ) {
				$_output .= ' width="' . intval( $_content->width ) . '"';
			}
			if ( isset( $_content->height ) && ( 0 < intval( $_content->height ) ) ) {
				$_output .= ' height="' . intval( $_content->height ) . '"';
			}
			$_output .= ' />' . "\n\n";
		}
		return $_output;
	}

	/**
	* Return a plain text element from the body content, excluding embed codes
	*
	* @parameter $_content (object) The WCM content_element object
	*
	* @return $_output (string) The text element suffixed with two newline characters
	*/
	public function get_wcm_element_text( $_content ) {
		$_text = isset( $_content->content ) ? trim( $_content->content ) : '';
		if ( '' !== $_text ) {
			if ( ( '[' === substr( $_text, 0, 1 ) ) && ( ']' === substr( $_text, -1 ) ) ) {
				$_text = ''; // exclude untranslated embed codes
			}
		}
		$_output = ( '' !== $_text ) ? $_text . "\n\n" : '';
		return $_output;
	}

	/**
	* Return an oembed element from the body content
	*
	* @parameter $_content (object) The WCM content_element object
	*
	* @return $_output (string) The oembed element suffixed with two newline characters
	*/
	public function get_wcm_element_oembed( $_content ) {
		$_output .= isset( $_content->html ) ? $_content->html . "\n\n" : '';
		return $_output;
	}

	/**
	* Return the name of the category, it's parent, and the top-level category above it from a WCM post
	*
	* @parameter $_tax (string) The WCM taxonomy object
	*
	* @return $_output (object) The name of the category, it's parent, and the top-level category above it
	*/
	private function get_wcm_post_categories( $_tax ) {
		$_output = new stdClass;
		$_output->name = '';
		$_output->parent = '';
		$_output->top = '';
		$_cat_path = '';
		if ( ( isset( $_tax->main_taxonomies ) ) && ( is_array( $_tax->main_taxonomies ) )  && ( 0 < count( $_tax->main_taxonomies ) ) ) {
			$_cat = $_tax->main_taxonomies[0];
		} elseif ( ( isset( $_tax->categories ) ) && ( is_array( $_tax->categories ) )  && ( 0 < count( $_tax->categories ) ) ) {
			$_cat = $_tax->categories[0];
		}
		$_output->name = isset( $_cat->name ) ? trim( $_cat->name ) : '';
		$_cat_path = isset( $_cat->path ) ? trim( $_cat->path ) : '';
		if ( '' !== $_cat_path ) {
			$_cat_parents = explode( '/', $_cat_path );
			$_cat_lcase = strtolower( $_output->name );
			$_cat_count = count( $_cat_parents );
			for ( $_count = 0; $_count < $_cat_count; $_count ++ ) {
				if ( ( '' === $_cat_parents[ $_count ] ) || ( $_cat_lcase === $_cat_parents[ $_count ] ) ) {
					unset( $_cat_parents[ $_count ] );
				}
			}
			$_cat_parents = array_values( $_cat_parents );
			$_cat_count = count( $_cat_parents );
			if ( 0 < $_cat_count ) {
				$_output->parent = $_cat_parents[ $_cat_count - 1 ];
				$_output->top = $_cat_parents[0];
			}
		}
		$_output->path = $_cat_path;
		$_all = array();
		if ( isset( $_tax->categories ) ) {
			foreach ( $_tax->categories as $_term ) {
				$_all[] = isset( $_term->slug ) ? $_term->slug : '';
			}
		}
		$_output->all = $_all;
		return $_output;
	}

	/**
	* Return the full list of tag slugs attached to one post
	*
	* @parameter $_tax (string) The WCM taxonomy object
	*
	* @return $_output (object) The tag slugs atm
	*/
	private function get_wcm_post_tags( $_tax ) {
		$_output = new stdClass;
		$_all = array();
		if ( isset( $_tax->tags ) ) {
			foreach ( $_tax->tags as $_term ) {
				$_all[] = isset( $_term->slug ) ? $_term->slug : '';
			}
		}
		$_output->all = $_all;
		return $_output;
	}

	/**
	* Get a WCM ID from a WP post
	*
	* @param $_post (object) WP post object
	*
	* @return (string) WCM ID, blank if  none exists
	*/
	private function get_wp_post_wcm_id( $_post ) {
		$_wcm_id = '';
		if ( ( is_object( $_post ) ) && ( isset( $_post->ID ) ) ) {
			// if this is a pointer
			$_wcm_id = trim( get_post_meta( $_post->ID, 'pn_wcm_origin_id', true ) );
			if ( '' === $_wcm_id ) {
				// if this is a post
				$_wcm_id = get_post_meta( $_post->ID, 'pn_wcm_id', true );
			}
		}
		return $_wcm_id;
	}

	/**
	* Helper function to indicate whether the site is meant to pull post list data from WCM of locally
	*
	* @return (boolean) Uses WCM or not
	*/
	public function site_uses_wcm() {
		$_output = false;
		if ( isset( $this->pmlay_count['show_wcm'] ) ) {
			$_show_wcm = $this->pmlay_count['show_wcm'];
		} else {
			$_show_wcm = ( 1 === intval( get_option( 'postmedia_layouts_show_wcm', 1 ) ) ) ? true : false;
			$this->pmlay_count['show_wcm'] = $_show_wcm;
		}
		$_output = ( true === $_show_wcm ) ? true : false; // force type
		return $_output;
	}

	// SIDEBAR METHODS OK

	/**
	* Determine whether or not the sidebar should be displayed on this page
	*
	* @return (null)
	*/
	function set_sidebar_on_off() {
		// get the base setting from options
		$_show_sidebar = ( false === $this->pmlay_count['show_sidebar'] ) ? false : true;
		// if this site can show sidebars determine if it should based on device and page
		if ( true === $_show_sidebar ) {
			if ( ( ( 1 < intval( $this->page_num ) ) && ( true === $this->is_mobile() ) ) || ( false === $this->is_mobile ) ) {
				$_show_sidebar = true;
			} else {
				$_show_sidebar = false;
			}
		}
		$this->pmlay_count['show_sidebar'] = $_show_sidebar;
	}

	/**
	* Return HTML for one DFP bigbox ad
	*
	* @param integer $_position If set overrides the count of ads displayed - most common use will be on pages showing only two ads where the second should be loc=bot (pos = 2)
	* @param string $_size Height options for ad
	*
	* @return string
	*/
	public function get_dfp_ad( $_size = 'tall', $_pos = null ) {
		// change sizes based on outfit height
		$this->dfpad_size = $_size;
		$_output = '';
		if ( ! is_null( $_pos ) ) {
			$_pos = intval( $_pos );
			if ( 0 <= $_pos ) {
				$this->ad_rules_displayed_count = $_pos;
			}
		}
		// if there are only two ads on the page and this is the second ad, use bottom targetting instead of mid
		if ( ( 2 === $this->count_ads ) && ( 1 === $this->ad_rules_displayed_count ) ) {
			$this->ad_rules_displayed_count = 2;
		}
		// if there are only two ads on this page then show the second as bot not mid
		// embedded in outfits: count outfits, calc ads from sb + outfit override, walk array
		// sb on any other page except post: walk array
		// sb on post: calc post height and # widgets, walk array
		$default_ad_slugs = array( 'big-box', 'big-box-mid', 'big-box-bottom' );
		$ad_slugs = apply_filters( 'pn_layouts_ad_slugs', $default_ad_slugs );
		switch ( $this->ad_rules_displayed_count ) {
			case 0:
				$_position = 'top'; // first ad displayed using ad rules
				$_ad_slug = $ad_slugs[0];
				$_ad_mode = ( true === $this->is_index() ) ? 'no-resize' : 'resize';
				break;
			case 1:
				$_position = 'mid'; // second ad displayed using ad rules
				$_ad_slug = $ad_slugs[1];
				$_ad_mode = 'resize';
				break;
			case 2:
				$_position = 'bot'; // third ad displayed using ad rules
				$_ad_slug = $ad_slugs[2];
				$_ad_mode = 'resize';
				break;
			default:
				$_position = 'bot' . ( $this->ad_rules_displayed_count - 1 ); // 4th+ ad displayed using ad rules
				$_ad_slug = $ad_slugs[2];
				$_ad_mode = 'resize';
				break;
		}
		$this->ad_rules_position = $_position;
		add_action( 'pn_dfpads_mapping_sizes', array( $this, 'pn_dfpads_mapping_sizes' ), 10, 1 );
		$_params = array( 'loc' => $_position );
		$_targeting_id_name = 'gpt-bigbox' . $_position;
		// echo the ad HTML to memory then dump it to the return variable
		$_html_before = '<div id="' . esc_attr( 'postmedia_layouts_ad-' . $_position ) . '" class="widget-wrap clearfix widget_postmedia_layouts_ad"><div class="widget">';
		$_html_before = apply_filters( 'pn_layouts_ad_widget_before', $_html_before );
		$_html_after = '</div></div>';
		$_html_after = apply_filters( 'pn_layouts_ad_widget_after', $_html_after );
		$_output = '';
		$_output .= $_html_before;
		ob_start();
		pn_dfp_ads()->call_ad( $_ad_slug, $_params, false, false, $_targeting_id_name, '', $_ad_mode );
		$_output .= ob_get_contents();
		ob_end_clean();
		$_output .= $_html_after;
		// because we now insert widgets early, the hard returns need to be stripped out or WP will replace them with <p> tags
		$_output = str_replace( "\n", ' ', $_output );
		$this->ad_rules_displayed_count ++;
		return $_output;
	}

	function is_dfp_widget( $_key ) {
		$_output = false;
		if ( ( 'postmedia_layouts_ad' === substr( $_key, 0, 20 ) ) || ( 'pn_dfpad' == substr( $_key, 0, 8 ) ) ) {
			$_output = true;
		}
		return $_output;
	}

	/**
	* Indicate if we want this page to display the sidebar widgets inline with the content
	* get_device_page_type = mobile and index or post page
	*
	* @return (null|boolean)
	*/
	function use_sidebar_slotting() {
		// should the page try to slot sidebar widgets into the main content?
		$this->sidebar_slotting = false;
		// removed from conditional since all sites insert widgets between outfits  && ( 'mp' === $this->device_page_type || true === $this->pmlay_settings['sidebar'] )
		if ( $this->is_mobile ) {
			// on mobile and this page uses a sidebar
			if ( is_home() || is_category() || is_tag() || ( is_single() && ( 'post' == get_post_type() ) ) ) {
				// this is the home page, a category page, a tag page, or a regular post
				$this->sidebar_slotting = true;
			}
		}
	}

	/** Displays the sidebar simply and completely
	*
	* @return (null)
	*/
	function layout_sidebar() {
		do_action( 'before_sidebar' );
		$_sidebar_html = $this->get_sidebar();
		$this->safe_echo( $_sidebar_html ); // Early-escaped
		do_action( 'after_sidebar' );
	}




		// SIDEBAR METHODS

	/**
	* Start with the array returned from get sidebar widgets() above and reordere it for the current page
	* Also split it into right and left sidebars - only used where widgets are displayed in sidebars, not where they are inserted into outfits
	*
	* @return (null)
	*/
	function get_sidebar_widget_list() {
		// get the array of widgets available on this page
		$_widgets = $this->get_sidebar_widgets();
		$_output = array();
		$_widget_group = array();
		// get the array of all widget settings in this instance of Wordpress
		$_count = 0;
		// for each widget, put it into the output array grouped by sidebar choice (right sidebar or left) and ordered by position
		foreach ( $_widgets as $_slug => $_html ) {
			// e.g. $_slug === 'text-3'
			if ( preg_match( '/^(.+?)-(\d+)$/', $_slug, $matches ) ) {
				$id_base = $matches[1]; // e.g. 'text'
				$widget_number = intval( $matches[2] ); // e.g. 3
			} else {
				$id_base = $_slug;
				$widget_number = null;
			}
			// get the array of settings for all widgets of this type (e.g. text, dfp) - skip this step if it's already been performed
			if ( ! isset( $_widget_group[ $id_base ] ) ) {
				$_widget_group[ $id_base ] = get_option( 'widget_' . $id_base );
			}
			// get the settings for this particular widget
			$_widget_settings = $_widget_group[ $id_base ][ $widget_number ];
			// sidebar choice is a user-selected integer indicating if the widget should appear in the right sidebar or the left one - not all sites support two sidebars
			$_sidebar_choice = isset( $_widget_settings['sidebar_choice'] ) ? intval( $_widget_settings['sidebar_choice'] ) : 0;
			// max_height - if > 0 then this needs to be treated as a sticky widget and given a larger div to float in
			$_max_height = isset( $_widget_settings['max_height'] ) ? intval( $_widget_settings['max_height'] ) : 0;
			if ( ( false === $this->is_mobile ) && ( 0 < $_max_height ) ) {
				// add class and style="height:max_heightpx;"
				$_html = preg_replace( '/(\<div[^\>]*)\>/', '$1 style="height:' . intval( $_max_height ) . 'px;">', $_html, 1 );
				$_widget_class = 'widget-sticky';
				if ( $this->is_dfp_widget( $_slug ) ) {
					$_widget_class .= ' adsizewrapper';
				}
				$_html = preg_replace( '/(\<div.*?class="[^\"]*)(".*?\>)/', '$1 ' . esc_attr( $_widget_class ) . ' $2', $_html, 1 );
			}
			// position allows the system to re-order the widgets on mobile, and differently on index pages from on post pages
			if ( $this->is_mobile ) {
				if ( $this->is_index ) {
					$_position = ( isset( $_widget_settings['index_position'] ) ? intval( $_widget_settings['index_position'] ) : 0 );
				} else {
					$_position = ( isset( $_widget_settings['post_position'] ) ? intval( $_widget_settings['post_position'] ) : 0 );
				}
			} else {
				// on desktop, widgets are displayed in the order they appear in the WP admin screen - i.e. do not re-order
				$_position = $_count;
			}
			// add the widget to the output array - grouped by sidebar choice first, then position
			$_output[ $_sidebar_choice ][ $_position ][] = array(
				'slug' => $_slug,
				'height' => $_max_height,
				'html' => $_html,
			);
			// need to process these to replace ads with correctly targetted ones as well as ad placeholders
			$_count ++;
		}
		// the resulting array can be displayed either as a whole or in two separate sidebars
		$this->widgets_sorted = $_output;
	}

	/**
	* Parse widget and replace ad placeholders with correctly targetted ads - not used in outfits
	* Need size
	*
	* @param $_html text HTML for the widget
	*
	* @return (null)
	*/
	public function parse_widget_placeholder( $_html, $_size = 'tall' ) {
		if ( false !== strpos( $_html, $this->widget_placeholder_text ) ) {
			$_widget = $this->get_dfp_ad( $_size );
			// replace the placeholder text with the DFP ad code
			$_html = str_replace( $this->widget_placeholder_text, $_widget, $_html );
		}
		return $_html;
	}

	public function widget_display_callback( $instance, $widget_class, $args ) {
		if ( ( false !== $instance ) && ( is_array( $instance ) ) ) {
			// remove widgets that should not be shown on the page based on page type index/post
			$page_display = ( array_key_exists( 'page_display', $instance ) ) ? intval( $instance['page_display'] ) : 0;
			if ( ( 2 == $page_display ) && ( is_category() || is_home() || is_tag() ) ) {
				return false; // do not display this widget on index pages
			} elseif ( ( 1 == $page_display ) && ( is_single() ) ) {
				return false; // do not display this widget on post pages
			}
		}
		if ( true === $this->pmlay_count['show_sidebar'] ) {
			// filter the widget display when shown in a sidebar, not blended with outfits
			// for posts that are displayed, add tracking data to the class element - moved from [theme]/functions/extras.php
			$widget_count = ( array_key_exists( 'widget_count', $this->pmlay_count ) ) ? intval( $this->pmlay_count['widget_count'] ) : -1;
			$widget_attributes = ' class="track_event-' . esc_attr( $widget_count ) . ' ';
			$args['before_widget'] = str_replace( 'class="', $widget_attributes, $args['before_widget'] );
			$widget_class->widget( $args, $instance );
			return false;
		} else {
			// for sites that blend widgets into outfits, just return the widget instance data as is
			return $instance;
		}
	}

	public function sidebars_widgets( $depracated ) {
		return null;
	}

	function count_widgets( $params ) {
		$this->pmlay_count['widget_count'] ++;
		return $params;
	}

	function get_widget_args() {
		$_args = array(
			'posts_max' => $this->pmlay_settings['max_posts'],
			'list_type' => $this->pmlay_settings['list_type'],
			'list_id' => $this->pmlay_settings['_listid'],
			'use_labels' => $this->pmlay_settings['use_labels'],
			'echo' => false,
		);
		return $_args;
	}

	/**
	* Use the DFP filter to change the ad object returned - particularly sizes
	*
	*/
	function pn_dfpads_mapping_sizes( $o_ad ) {
		if ( ( true === is_single() ) || ( true === is_page() ) || ( 'tall' === $this->dfpad_size ) ) {
			// only apply to short ad slots on index pages
			return $o_ad;
		}
		if ( ( 'top' !== $this->ad_rules_position ) && ( 'mid' !== $this->ad_rules_position ) ) {
			return $o_ad;
		}
		$a_options = get_option( 'pn_dfpads' );
		if ( false === is_array( $o_ad->sizes ) ) {
			return $o_ad;
		}
		foreach ( $o_ad->sizes as $_skey => $_size ) {
			if ( intval( $this->pmlay_count['secondary_ad_height'] ) <= intval( $a_options['sizes'][ $_size ]->height ) ) {
				unset( $o_ad->sizes[ $_skey ] );
			}
		}
		return $o_ad;
	}

	/**
	* Determine if this page renders widgets in sidebars or inserts them into the main content well
	* Depracated
	*
	* @return bool Renders sidebar or not
	*/
	public function is_sidebar() {
		return null;
	}

	/**
	* Helper function for page 2 of index on mobile and author, search pages on mobile where ads need to be inserted between posts based on the number of posts in the list
	*
	* @param integer $_count_posts the number of posts in the list
	*
	* @return integer post number after which to show ad, -1 = none
	*/
	function get_mobile_ad_position( $_count_posts ) {
		$ad_position = -1; // default: 1 - 3 posts so show just one ad at end, or not mobile, so irrelevant
		if ( $this->is_mobile ) {
			if ( 0 < $_count_posts ) {
				if ( 6 < $_count_posts ) {
					// more than 6 posts so show ads after third post and at end
					$ad_position = 3;
				} elseif ( 3 < $_count_posts ) {
					// 4 - 6 posts so show ads at start and end
					$ad_position = 0;
				}
			}
		}
		return $ad_position;
	}

	/**
	* Displays widgets inline in slots - early-escaped
	* Uses $this->show_slot_widgets()
	*
	* @param $_mode string Show all widgets or just the ones for this slot
	*
	*/
	function show_widget_slot( $_mode = '' ) {
		// display widgets inline with the Layouts lists and story page content - used only on mobile
		$_ret = '';
		if ( false === $this->sidebar_slotting ) {
			return $_ret; // only run this on the front end, on post and index pages
		}
		if ( 0 < count( $this->widget_list ) ) {
			$this->sidebar_adwords = 120; // start again on the ads at the end of the page and between slots
			if ( 'all' == $_mode ) {
				$_start_pos = $this->sidebar_current_position;
				for ( $x = $_start_pos; $x <= 11; $x ++ ) {
					$_ret .= $this->display_widgets_in_slot();
				}
			} else {
				// show all widgets in this position
				$_ret .= $this->display_widgets_in_slot();
			}
		}
		// allow themes to modify the returned HTML
		$_ret = apply_filters( 'pn_layouts_adjust_widget', $_ret );
		return $_ret;
	}

	/**
	* Displays widgets inline with the Layouts lists and story page content - used only on mobile
	* Note that this function returns HTML from the $this->sidebar_widgets array which comes from $this->get sidebar widgets()
	* This is all early-escaped and will therefore not be late-escaped when echoed. We could use wp_kses, but since widgets contain JS and CSS, there is very little point filtering the code but then letting everything through.
	*
	* @param $_mode string Show all widgets or just the ones for this slot
	* @param $_slot_number int The slot on the page where the HTML will be displayed
	*
	*/
	function show_slot_widgets( $_mode = '', $_slot_number = -1 ) {
		$_ret = '';
		if ( -1 !== $_slot_number ) {
			$this->sidebar_current_position = $_slot_number;
		}
		// show all widgets in this position
		foreach ( $this->widget_list as $_widget_id ) {
			if ( isset( $this->sidebar_positions[ $this->sidebar_slug ][ $_widget_id ] ) ) {
				if ( intval( $this->sidebar_positions[ $this->sidebar_slug ][ $_widget_id ] ) === $this->sidebar_current_position ) {
					$_ret .= $_widget_id;
					unset( $this->sidebar_widgets[ $_widget_id ] ); // widget has been displayed so remove it from the array
				}
			}
		}
		$this->sidebar_current_position ++; // increment the position by one to the next slot
		return $_ret;
	}

	/**
	* Render the whole sidebar to an array ($this->sidebar_widgets)
	* pn-widget-device-visibility plugin has already stripped out those that do not belong on mobile based on widget settings
	* go through the array and find the widget id (e.g. tag_cloud-2) and use this as the array key
	* later fill up a second array ($this->sidebar_positions) with the positions each widget should take using the same ids as keys
	*
	* @return array Widget HTML, early-escpaed, in an array that can later be sorted and selectively displayed
	*/
	function get_sidebar_widgets() {
		if ( ! empty( $this->sidebar_widgets ) ) {
			return $this->sidebar_widgets;
		}
		$_output = array();
		// CMJ: on post pages this process is somehow incrementing $this->sidebar_current_position presumably by calling show_ widget_slot()
		if ( function_exists( 'easy_sidebars' ) ) {
			$_eside = easy_sidebars();
			$o_sidebar = $_eside->has_sidebar();
			if ( isset( $o_sidebar->id ) ) {
				$this->sidebar_slug = 'easy_sidebars-' . $o_sidebar->id;
				$this->widget_slugs = get_option( 'sidebars_widgets' );
				if ( isset( $this->widget_slugs[ $this->sidebar_slug ] ) ) {
					$this->widget_list = $this->widget_slugs[ $this->sidebar_slug ];
					if ( is_single() && $this->is_mobile ) {
						$_idx = 'post_position';
					} elseif ( $this->is_mobile ) {
						$_idx = 'index_position';
					} else {
						$_idx = '';
					}
					foreach ( $this->widget_list as $_key => $widget_id ) {
						if ( preg_match( '/^(.+?)-(\d+)$/', $widget_id, $matches ) ) {
							$id_base = $matches[1];
							$widget_number = intval( $matches[2] );
						} else {
							$id_base = $widget_id;
							$widget_number = null;
						}
						$_widgets = get_option( 'widget_' . $id_base );
						$_widget = $_widgets[ $widget_number ];
						// limit display by page type
						$_page_display = isset( $_widget['page_display'] ) ? intval( $_widget['page_display'] ) : 0;
						// show on   0 = all pages, 1 = index pages, 2 = non-index pages
						if ( ( is_single() ) && ( 1 === $_page_display ) ) {
							unset( $this->widget_list[ $_key ] );
							continue; // skip this widget on index pages
						} elseif ( ( 2 === $_page_display ) && ( ! is_single() ) ) {
							unset( $this->widget_list[ $_key ] );
							continue; // skip this widget on post pages
						}
						// limit display by device type
						$_display_desktop = isset( $_widget['display_desktop'] ) ? intval( $_widget['display_desktop'] ) : 0;
						$_display_phone = isset( $_widget['display_phone'] ) ? intval( $_widget['display_phone'] ) : 0;
						$_display_tablet = isset( $_widget['display_tablet'] ) ? intval( $_widget['display_tablet'] ) : 0;
						if ( 0 === $_display_desktop + $_display_phone + $_display_tablet ) {
							$_display_desktop = 1;
							$_display_phone = 1;
							$_display_tablet = 1;
						}
						if ( true === $this->is_mobile ) {
							if ( 0 === $_display_phone ) {
								unset( $this->widget_list[ $_key ] );
								continue;
							}
						} else {
							if ( 0 === $_display_desktop ) {
								unset( $this->widget_list[ $_key ] );
								continue;
							}
						}
						$_widget['slug'] = $widget_id;
						$_widget['type'] = $id_base;
						$_key = ( '' !== $_idx ) ? ( isset( $_widget[ $_idx ] ) ? intval( $_widget[ $_idx ] ) : 0 ) : 0;
						// make sorted widget list here
						$_output[ $_key ][] = $_widget;
						// count the ads in the sidebar
						if ( false !== strpos( $widget_id, 'postmedia_layouts_ad' ) ) {
							$this->count_ads ++;
						}
					}
				}
			}
		}
		return $_output;
	}

	/**
	* Insert placeholders for widget slots from the sidebar into posts on mobile web
	*
	* @param $_content string The HTML of the post
	*
	* @return string The HTML of the post
	*/
	function set_post_widget_slots( $_content ) {
		if ( true === $this->pmlay_count['show_wcm'] || get_option( 'wcm_enabled', false ) ) {
			// skip if this is WCM content
			return $_content;
		}
		if ( '' == trim( $_content ) ) {
			return $_content;
		}
		// put widgets into the content on post pages on mobile
		// this gets run in many contexts on the post page esp in sidebar widgets - shut it off there
		if ( ! in_the_loop() ) {
			return $_content; // not in the loop so exit
		}
		if ( ( ! $this->is_mobile )
			|| ( ! $this->pmlay_settings['sidebar'] )
			|| ( ! ( is_single() && ( true === in_array( get_post_type(), array( 'post', 'gallery' ), true ) ) ) )
			) {
			return $_content; // only run this on the front end, on regular post pages
		}
		// but this may run multiple times on a regular post page, so we need to run it just on the right one
		$_output = '';
		$_words = 0;
		$_paragraphs = explode( "\n", $_content );
		$_append_txt = '';
		$_position = 1;
		$_content_blocks = array();
		$_block = '';
		foreach ( $_paragraphs as $_key => $_html ) {
			if ( '' !== trim( $_html ) ) {
				// concatenate the story back together one paragraph at a time
				$_block .= $_html . $_append_txt . "\n\n";
				// count the words in this paragraph - images and embeds count for the full interslot word amount
				$_words += $this->count_element_words( $_html ); // add the # words in this para to the running total to determine when to insert another widget slot
				if ( $this->words_per_slot < $_words ) {
					// show widgets in "slots" at various positions throughout story
					// widget slots are defined between paragraphs or standalone shortcodes and must be at least $this->words_per_slot words apart. A standalone shortcode counts as $this->words_per_slot words
					if ( 10 >= $_position ) {
						$_content_blocks[] = $_block;
						$_block = '';
					}
					$_position ++;
					$_words = 0;
				}
			}
		}
		$_content_blocks[] = $_block;
		// If we revert this functionality and display the remaining widgets above the comments and pagination, use this: $_ret .= $this->show_ widget_slot( 'all' );
		$_output = $this->slot_post_widgets( $_content_blocks );
		return $_output;
	}

	/**
	* Insert widgets from the sidebar into posts on mobile web
	* @param $_content string The HTML of the post
	*
	* @return string The HTML of the post
	*/
	private function slot_post_widgets( $_blocks ) {
		$_content = '';
		if ( ( false === $this->is_mobile ) || ( empty( $_blocks ) ) ) {
			return $_content;
		}
		if ( true === $this->post_widgets_slotting_done ) {
			// whitelabel plugin seems to cause the 'the_content' filter to trigger more than once, but we wan this to happen on the last execution, not the first one that has content, so reset and run again
			// reset Layouts widget slot
			$this->sidebar_current_position = 0;
			// reset Layouts ad count
			$this->ad_rules_displayed_count = 0;
			// reset DFP ad count (in DFP plugin) too
		}
		$this->sidebar_adwords = 0;
		$this->sidebar_widgets = $this->get_sidebar_widgets();
		$this->sidebar_current_position = 0;
		foreach ( $_blocks as $_html ) {
			if ( 10 >= $this->sidebar_current_position ) {
				$_widgets = $this->display_widgets_in_slot();
				$_content .= $_widgets;
			}
			$_content .= $_html;
		}
		// fill in remaining slots
		$_fill_start = $this->sidebar_current_position;
		for ( $x = $_fill_start; $x <= 10; $x ++ ) {
			$_widgets = $this->display_widgets_in_slot();
			$_content .= $_widgets;
		}
		$this->sidebar_current_position = 99; // the end of the content slot
		$_widgets = $this->display_widgets_in_slot();
		$_content .= $_widgets;
		$this->post_widgets_slotting_done = true;
		return $_content;
	}

	private function count_element_words( $_html ) {
		$_output = 0;
		if ( ( false === strpos( $_html, '<img' ) ) && ( false === strpos( $_html, '[' ) ) ) {
			$_output = str_word_count( strip_tags( $_html ) );
		} else {
			$_output = $this->words_per_slot;
		}
		return $_output;
	}

	function set_wcm_content_elements( $_content_elements, $content, $is_display ) {
		if ( false === $this->pmlay_count['show_wcm'] ) {
			// skip if this is not WCM content
			return $_content_elements;
		}
		if ( true === empty( $_content_elements ) ) {
			return $_content_elements;
		}
		if ( ! $is_display ) {
			return $_content_elements; // not in the loop so exit
		}
		if ( ( ! $this->is_mobile )
			|| ( ! $this->pmlay_settings['sidebar'] )
			|| ( ! ( is_single() && ( true === in_array( $content->type, array( 'post', 'gallery' ), true ) ) ) )
			) {
			return $_content_elements; // only run this on the front end, on regular post pages
		}
		$_output = array();
		$_count = 0;
		$_position = 1;
		$_words = 120; // so the second slot is after para 1
		$_max_elements = count( $_content_elements );
		if ( true === $this->post_widgets_slotting_done ) {
			// whitelabel plugin seems to cause the 'the_content' filter to trigger more than once, but we wan this to happen on the last execution, not the first one that has content, so reset and run again
			// reset Layouts widget slot
			$this->sidebar_current_position = 0;
			// reset Layouts ad count
			$this->ad_rules_displayed_count = 0;
			// reset DFP ad count (in DFP plugin) too
		}
		$this->sidebar_adwords = 0;
		$this->sidebar_widgets = $this->get_sidebar_widgets();
		$this->sidebar_current_position = 0;
		// Note: $_count = -1 appends to the end of the array
		$_output = $this->insert_content_element( $_output, $_position );
		$_count ++;
		$_in_paragraph = false;
		for ( $x = 0; $x < $_max_elements; $x ++ ) {
			$_element = $_content_elements[ $x ];
			// add the original content element from WCM
			$_element_id = $_count + 1; // starts at 1, not 0
			$_element['_id'] = $_element_id; // have to renumber all elements because of insertion
			$_output[] = $_element;
			$_count ++;
			// check if a slot for widgets is warranted
			if ( 'text' === $_element['type'] ) {
				$_element_words = str_word_count( strip_tags( $_element['content'] ) );
			} else {
				$_element_words = $this->words_per_slot;
			}
			if ( isset( $_element['paragraph'] ) ) {
				if ( 'open' === $_element['paragraph'] ) {
					$_in_paragraph = true;
				} elseif ( 'close' === $_element['paragraph'] || 'wrap' === $_element['paragraph'] ) {
					$_in_paragraph = false;
				}
			}
			$_words += intval( $_element_words ); // add the # words in this para to the running total to determine when to insert another widget slot
			if ( $this->words_per_slot < $_words && ! $_in_paragraph ) {
				// show widgets in "slots" at various positions throughout story
				// widget slots are defined between paragraphs or standalone shortcodes and must be at least $this->words_per_slot words apart. A standalone shortcode counts as $this->words_per_slot words
				if ( 10 >= $_position ) {
					$_output = $this->insert_content_element( $_output, $_position );
					$_count ++;
				}
				$_position ++;
				$_words = 0;
			}
		}
		// we may run out of content before we run out of widget slots so add them now
		$_fill_start = $this->sidebar_current_position;
		for ( $x = $_fill_start; $x <= 10; $x ++ ) {
			$_output = $this->insert_content_element( $_output, $_position );
			$_count ++;
		}
		$this->sidebar_current_position = 99; // the end of the content slot
		$_output = $this->insert_content_element( $_output, $_position );
		$_count ++;
		$this->post_widgets_slotting_done = true;
		return $_output;
	}

	private function insert_content_element( $_output, $_position = 0 ) {
		$_widgets = $this->display_widgets_in_slot();
		$_output = Postmedia\Web\Data\DataHelper::insert_content_element( $_output, 'raw_html', $_widgets, -1 );
		return $_output;
	}

	function display_widgets_in_slot() {
		// display widgets inline with the Layouts lists and story page content - used only on mobile
		$_output = '';
		if ( false === $this->sidebar_slotting ) {
			return $_output; // only run this on the front end, on post and index pages
		}
		$this->sidebar_current_position = ( 10 >= $this->sidebar_current_position ) ? $this->sidebar_current_position : 99;
		if ( false === isset( $this->sidebar_widgets[ $this->sidebar_current_position ] ) ) {
			$this->sidebar_current_position ++; // increment the position by one to the next slot
			return $_output;
		}
		foreach ( $this->sidebar_widgets[ $this->sidebar_current_position ] as $_widget ) {
			$_output .= $this->get_widget_html( $_widget['slug'] );
		}
		if ( 99 > $this->sidebar_current_position ) {
			$this->sidebar_current_position ++; // increment the position by one to the next slot
		}
		// allow themes to modify the returned HTML
		$_output = apply_filters( 'pn_layouts_adjust_widget', $_output );
		return $_output;
	}

	/**
	* Return or display one widget from the sidebar - called from [theme]/pm_layouts/outfit/outfit-[0-9]+.php
	*
	* @param (array) $_sizes Ad sizes
	* @param (bool) $_echo depracated
	* @param (bool) $_ad_only depracated
	*
	* @return (null|string)
	*/
	public function get_outfit_widget( $_sizes = array( 'tall' ), $_echo = false, $_ad_only = false ) {
		$_output[0] = array(
			'slug' => '',
			'html' => '',
		);
		$_section_num = intval( $this->pmlay_count['section'] );
		$_count_slots = count( $_sizes );
		for ( $_slot = 0; $_slot < $_count_slots; $_slot ++ ) {
			// usage defined by $this->pmlay_settings['outfits'][ $_section_num ]['widget'][ $_slot ]['use'] 0 = ad/widget, 1 = ad only, 2 = widget only, 3 = neither
			$_slot_widget_id = trim( $this->pmlay_settings['outfits'][ $_section_num ]['widgets'][ $_slot ] ); // auto, dfp, none
			// when default outfits are being used on a page (usually tag) there will be no config for widgets but we want to pull from sidebar
			if ( '' === $_slot_widget_id ) {
				$_slot_widget_id = 'auto';
			}
			$_widget_html = '';
			$_widget_slug = '';
			if ( 'none' === $_slot_widget_id ) {
				continue;
			} elseif ( 'dfpad' === $_slot_widget_id ) {
				// force a DFP Ad in this slot
				$_widget_slug = $_slot_widget_id; // get settings for next dfp ad
				$_widget_html = $this->get_dfp_ad( $_sizes[ $_slot ] ); // 'tall', 'short'
				$_output[ $_slot ] = array(
					'slug' => $_widget_slug,
					'html' => $_widget_html,
				);
				continue;
			}
			if ( 'auto' === $_slot_widget_id ) {
				// show a widget from this page's sidebar assigned in this slot
				$_widget_slug = array_shift( $this->widget_list );
				if ( false !== strpos( $_widget_slug, 'postmedia_layouts_ad' ) ) {
					$_widget_html = $this->get_dfp_ad( $_sizes[ $_slot ] );
				} else {
					$_widget_html = $this->get_widget_html( $_widget_slug );
				}
				$this->current_widget_slot ++;
			} else {
				// show a widget selected for this outfit in this slot
				$_widget_slug = $_slot_widget_id; // replace with value from an array
				$_outfit_type = $this->pmlay_settings['outfit_type']; // hold this value for later - should always be outfit atm but things change
				$this->pmlay_settings['outfit_type'] = 'widget';
				if ( false !== strpos( $_widget_slug, 'postmedia_layouts_ad' ) ) {
					$_widget_html = $this->get_dfp_ad( $_sizes[ $_slot ] );
				} else {
					$_widget_html = $this->get_widget_html( $_widget_slug );
				}
				$this->pmlay_settings['outfit_type'] = $_outfit_type; // restore original value
				// option to add some class to the widget wrapper
				if ( false !== strpos( $_widget_slug, 'pn_kaltura_playlist' ) ) {
					$_widget_html = preg_replace( '/(\<div.*?class\=\".*?track_event[^\"]*)([^\>]+\>)/', '$1 clearfix pn_kaltura_playlist $2', $_widget_html, 1 );
				} elseif ( false !== strpos( $_widget_slug, 'pn_kaltura' ) ) {
					$_widget_html = preg_replace( '/(\<div.*?class\=\".*?track_event[^\"]*)([^\>]+\>)/', '$1 clearfix pn_kaltura $2', $_widget_html, 1 );
				}
			}
			$_output[ $_slot ] = array(
				'slug' => $_widget_slug,
				'html' => $_widget_html,
			);
		}
		return $_output;
	}

	private function get_widget_html( $_widget_slug, $_size = '' ) {
		if ( false !== strpos( $_widget_slug, 'postmedia_layouts_ad' ) ) {
			$_widget_html = $this->get_dfp_ad( $_size );
			$_widget_html = $this->set_widget_html_wrappers( $_widget_html, $_widget_slug );
			return $_widget_html;
		}
		global $wp_registered_sidebars;
		global $wp_registered_widgets;
		$_widget_html = '';
		if ( isset( $wp_registered_widgets[ $_widget_slug ] ) ) {
			$_widget_source = ( isset( $this->pmlay_count['widget_source'] ) ) ? trim( $this->pmlay_count['widget_source'] ) : '';
			$_sidebar = $wp_registered_sidebars[ $_widget_source ];
			$_params = array_merge(
				array(
					array_merge(
						is_array( $_sidebar ) ? $_sidebar : array(),
						array(
							'widget_id' => $_widget_slug,
							'widget_name' => $wp_registered_widgets[ $_widget_slug ]['name'],
						)
					)
				),
				( array ) $wp_registered_widgets[ $_widget_slug ]['params']
			);
			// Substitute HTML id and class attributes into before_widget
			$_classname = '';
			foreach ( (array) $wp_registered_widgets[ $_widget_slug ]['classname'] as $cn ) {
				if ( is_string( $cn ) ) {
					$_classname .= '_' . $cn;
				} elseif ( is_object( $cn ) ) {
					$_classname .= '_' . get_class( $cn );
				}
			}
			$_classname = ltrim( $_classname, '_' );
			$_params[0]['before_widget'] = sprintf( $_params[0]['before_widget'], $_widget_slug, $_classname );
			$callback = $wp_registered_widgets[ $_widget_slug ]['callback'];
			if ( is_callable( $callback ) ) {
				ob_start();
				call_user_func_array( $callback, $_params );
				$_widget_html = ob_get_contents();
				ob_end_clean();
				$_widget_html = $this->set_widget_html_wrappers( $_widget_html, $_widget_slug );
			}
		}
		return $_widget_html;
	}

	private function set_widget_html_wrappers( $_widget_html = '', $_widget_slug = '' ) {
		$_max_height = $this->get_widget_height_by_slug( $_widget_slug );
		$_widget_html = $this->set_widget_html_height( $_widget_html, $_widget_slug, $_max_height );
		if ( $this->is_mobile && is_single() ) {
			$_widget_html = '<div class="l-sidebar" style="float:none !important">' . $_widget_html . '</div>';
		}
		return $_widget_html;
	}

	private function set_widget_html_height( $_widget_html = '', $_widget_slug = '', $_max_height = 0 ) {
		if ( ( true === $this->is_mobile ) || ( 0 >= $_max_height ) || ( '' === $_widget_html ) || ( '' === $_widget_slug ) ) {
			return $_widget_html;
		}
		// add class and style="height:max_heightpx;"
		$_widget_html = preg_replace( '/(\<div[^\>]*)\>/', '$1 style="height:' . intval( $_max_height ) . 'px;">', $_widget_html, 1 );
		$_widget_class = 'widget-sticky';
		if ( $this->is_dfp_widget( $_widget_slug ) ) {
			$_widget_class .= ' adsizewrapper';
		}
		$_widget_html = preg_replace( '/(\<div.*?class="[^\"]*)(".*?\>)/', '$1 ' . esc_attr( $_widget_class ) . ' $2', $_widget_html, 1 );
		return $_widget_html;
	}

	private function is_sponsored_category( $_category_id = 0 ) {
		$_category_id = intval( $_category_id );
		if ( ( true === $this->is_index() ) ) {
			// get category sponsorship from WP - WCM is irrelevant since taxonomy is local
			if ( 0 === $_category_id ) {
				// if no category id passed then get it from WP
				$_category_object = get_category( get_query_var( 'cat' ) );
				$_category_id = isset( $_category_object->term_id ) ? intval( $_category_object->term_id ) : 0;
			}
			if ( 0 < $_category_id ) {
				$_category_meta = array_filter( (array) get_option( sprintf( 'category_%d_meta', $_category_id ) ) );
				if ( ! empty( $_category_meta ) && is_array( $_category_meta ) ) {
					$sponsorship_type = esc_attr( $_category_meta['logo_label'] );
					if ( '' !== $sponsorship_type ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	private function is_sponsored() {
		if ( ( true === $this->is_index() ) ) {
			return $this->is_sponsored_category( 0 );
		} elseif ( true === is_single() ) {
			// if post is sponsored or post's main category is sponsored return true
			global $content;
			if ( isset( $content ) ) {
				$_sponsor = $content->get_sponsor();
				if ( null !== $_sponsor ) {
					return true;
				}
			}
		}
		return false;
	}

	private function is_guid( $guid ) {
		$is_guid = false;
		if ( ! empty( $guid ) ) {
			$pattern = '/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i';
			$is_guid = preg_match( $pattern, $guid );
		}
		return $is_guid;
	}

	/**
	* Display the sidebars defined in $this->get_sidebar_ widget_list()
	* Used directly in themes as part of function pn_postmedia_sidebar( $_choice = -1 )
	* Needs to be able to select among right, left, both
	*
	* @param $_choice integer The sidebar(s) to display (0 = right, 1 = left, -1 = both)
	*
	* @return (null)
	*/
	public function display_sorted_sidebar( $_choice = -1 ) {
		global $paged;
		$_shorten_sidebar = true;
		if ( ( true === $this->is_index() ) && ( 1 < $paged ) && ( true === $this->is_sponsored() ) ) {
			$_shorten_sidebar = false;
		}

		if ( empty( $this->sidebar_widgets ) ) {
			$this->sidebar_widgets = $this->get_sidebar_widgets();
		}
		$_choice = intval( $_choice );
		if ( 0 <= $_choice ) {
			$_choice = ( 0 === $_choice ) ? 0 : 1; // whitelist
		} else {
			$_choice = -1; // show whole sidebar
		}
		$_post_height = $this->get_post_height();
		$_ary_widgets = array(
			'weather' => 120,
			'traffic' => 120,
			'teamscoreboard' => 600,
			'postmedia_layouts_ad' => 600,
		);
		$_ary_widgets = apply_filters( 'pn_layouts-display_sorted_sidebar-ary_widgets', $_ary_widgets );
		$_widget_pixels = apply_filters( 'pn_layouts-display_sorted_sidebar-widget_pixels', 400 );
		$_sidebar_height = 0;
		// have to pass through this twice to determine how many ads can be shown so the second of 2 can be set to bot, when only two can be shown
		$_widgets_show = array();
		$this->count_ads = 0;
		// on desktop all widgets will be in the first element since there are no slots
		if ( ( isset( $this->sidebar_widgets[0] ) ) && ( is_array( $this->sidebar_widgets[0] ) ) ) {
			foreach ( $this->sidebar_widgets[0] as $_widget_data ) {
				// if the height of the widget has been set manually use that
				$_widget_sidebar = isset( $_widget_data['sidebar_choice'] ) ? intval( $_widget_data['sidebar_choice'] ) : -1;
				if ( ( 0 <= $_choice ) && ( $_choice !== $_widget_sidebar ) ) {
					continue; // skip this widget because it's in the wrong sidebar
				}
				$_widgets_show[] = $_widget_data;
				$_widget_slug = isset( $_widget_data['slug'] ) ? trim( $_widget_data['slug'] ) : '';
				if ( ( false !== strpos( $_widget_slug, 'pn_dfpad' ) ) || ( false !== strpos( $_widget_slug, 'postmedia_layouts_ad' ) ) ) {
					$this->count_ads ++;
				}
				// remove widgets if sponsored content is not set
				if ( true === $_shorten_sidebar ) {
					$_widget_height = $this->get_widget_height( $_widget_data, $_ary_widgets, $_widget_pixels );
					// add this widget height to the growing sidebar height
					$_sidebar_height += $_widget_height;
					if ( 2 <= $this->count_ads && ( ( $_post_height - $_widget_height ) < $_sidebar_height ) ) {
						// if the current sidebar height is greater than the story height - $_widget_pixels then stop displaying widgets
						break;
					}
				}
			}
			foreach ( $_widgets_show as $_widget_data ) {
				$_widget_slug = isset( $_widget_data['slug'] ) ? trim( $_widget_data['slug'] ) : '';
				$_html = $this->get_widget_html( $_widget_slug );
				// for ads need to swap mid to bot when only two appear on page, but don't know until done
				$this->safe_echo( $_html ); // Early-escaped
			}
		}
	}

	private function get_widget_height_by_slug( $_widget_slug = '' ) {
		$_height = 0;
		foreach ( $this->sidebar_widgets as $_slot ) {
			foreach ( $_slot as $_widget ) {
				if ( isset( $_widget['slug'] ) && ( trim( $_widget['slug'] ) === $_widget_slug ) ) {
					$_height = isset( $_widget['max_height'] ) ? intval( $_widget['max_height'] ) : 0;
					break;
				}
			}
		}
		return $_height;
	}

	private function get_widget_height( $_widget_data, $_ary_widgets, $_widget_pixels ) {
		$_widget_height = isset( $_widget_data['max_height'] ) ? intval( $_widget_data['max_height'] ) : 0;
		if ( 0 >= $_widget_height ) {
			// different default widget height by widget type
			$_widget_type = isset( $_widget_data['type'] ) ? trim( $_widget_data['type'] ) : '';
			if ( isset( $_ary_widgets[ $_widget_type ] ) ) {
				// use the specific default widget height
				$_widget_height = intval( $_ary_widgets[ $_widget_type ] );
			} else {
				// use the global default widget height
				$_widget_height = $_widget_pixels;
			}
		}
		return $_widget_height;
	}

	/**
	* Retrieve the full sidebar from Wordpress and return it as HTML
	*
	* @return $_html (string) HTML for the sidebar
	*/
	private function get_sidebar() {
		ob_start();
		if ( function_exists( 'easy_sidebars' ) ) {
			$_eside = easy_sidebars();
			$_eside->sidebar();
		} else {
			get_sidebar();
		}
		$_html = ob_get_contents();
		ob_end_clean();
		$_html = '<aside class="l-sidebar">' . $_html . '</aside>';
		$_html = $this->parse_sidebar_ads( $_html );
		$_html = apply_filters( 'pn_layouts_sidebar_html', $_html );
		return $_html;
	}

	/**
	* Go through the sidebar and wherever there's a Layouts Ad widget replace it with the apprpriate Bigbox ad
	* CMJ - can't do this because it increments counter in DFP
	*/
	function parse_sidebar_ads( $_sidebar_html ) {
		$_sidebar_ary = explode( $this->widget_placeholder_text, $_sidebar_html );
		$_return = '';
		$_count = 0;
		foreach ( $_sidebar_ary as $_html ) {
			if ( 0 !== $_count ) {
				$_return .= $this->get_dfp_ad();
			}
			$_return .= $_html;
			$_count ++;
		}
		// reset the ad count
		$this->ad_rules_displayed_count = 0;
		return $_return;
	}
}
