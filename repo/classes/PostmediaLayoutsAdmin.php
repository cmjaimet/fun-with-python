<?php
/* ADMIN CODE FOR LAYOUTS */

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Postmedia\Web\Utilities;

$postmedia_layouts_admin = new PostmediaLayoutsAdmin(); //Start the engine

/**
* Layouts Admin: defines the class that handles the back end.
*
* @since 2.1.0
*/
class PostmediaLayoutsAdmin {

	/**
	* Define different header classes
	*/

	const SETTINGS_PAGE_KEY = 'postmedia-layouts';

	private $list_style = array();
	public $list_type = array();
	public $adv_type = array();
	public $list_target = array(
		'' => 'Automatic',
		'_self' => 'Same Window',
		'_blank' => 'New Window',
		);
	public $sidebar_taxonomy = 'easy_sidebars';
	private $split_term_id = 0; // when WP 4.2 executes a term split, grab the id here to assign the new layout to
	private $layouts = null;

	/**
	* Let's get this party started...
	*
	* @return null
	*/
	function __construct() {
		add_action( 'init', array( 'PostmediaLayoutsVideoCenter', 'init' ) );
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	* Initialize the object
	*
	* @return null
	*/
	function init() {
		global $postmedia_layouts;
		$this->layouts = $postmedia_layouts;
		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		$this->get_list_types();
		$this->get_adv_types();
		$this->get_list_styles();
	}

	/**
	* Initialize the admin components of the object
	*
	* @return null
	*/
	function admin_init() {
		add_filter( 'manage_edit-category_columns', array( $this, 'manage_category_columns' ) );
		add_filter( 'manage_category_custom_column', array( $this, 'manage_category_custom_fields' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueues' ) );
		add_action( 'create_term', array( $this, 'term_save' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'term_save' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'term_save' ), 10, 3 );
		add_action( 'split_shared_term', array( $this, 'term_split' ), 10, 4 );
		// show on category edit page
		add_action( 'category_edit_form', array( $this, 'term_form' ) );
		// show on tag edit page
		add_action( 'edit_tag_form', array( $this, 'term_form' ) );
		add_action( 'wp_ajax_json_pmlay_showtemplate', array( $this, 'json_pmlay_showtemplate' ) );
		add_action( 'wp_ajax_json_pmlay_termsearch', array( $this, 'json_pmlay_termsearch' ) );
		add_action( 'in_widget_form', array( $this, 'widget_admin' ), 10, 3 );
		add_filter( 'widget_update_callback', array( $this, 'widget_update' ), 10, 3 );
		add_filter( 'zoninator_add_zone_posts', array( $this, 'refresh_zone_cache' ), 10, 3 );
	}

	/**
	* Call Postmedia_Layouts::expire_transient( $_term_id ) when key data in a term (category/tag/home) is altered
	*
	* @param integer $_id	ID number defining the term (home=0 / category / tag), zone, or post
	* @param integer $_mode	source of update (term, zone, post)
	* @param integer $_delay	number of seconds to delay refresh - for systems like Zones that receive multiple consecutive changes
	* @return null
	*/
	function expire_transient_html( $_id = 0, $_mode = 'term', $_delay = 0 ) {
		// if criteria met, expire the transient
		// _mode options are ( '' = term, 'zone' = Zoninator, 'post' = post )
		$_id = intval( $_id ); // force type casting
		// whitelist in conditional
		if ( 'term' === $_mode ) {
			// update term edited
			$this->layouts->expire_transient( $_id, $_delay );
		} elseif ( 'post' === $_mode ) {
			// update terms based on post $_id edited
			// determine the main category ID and update that
			$_term_id = intval( get_post_meta( $_id, '_pn_main_category', true ) );
			if ( 0 < $_main_category_id ) {
				$this->layouts->expire_transient( $_term_id, $_delay );
			}
			// update home page too? what about other index pages - I don't love this because the lookups will be nasty so skip for now
		}
	}

	/**
	* Set the home page full-page HTML cache to be refreshed when a zone is edited (30 second delay to avoid multiple sequential refreshes)
	*
	* @param $_post_id integer The post ID number
	* @param $_term_obj object The data associated with this zone - currently only used for validation
	* @return null
	*/
	function refresh_zone_cache( $_post_id, $_term_obj ) {
		if ( is_object( $_term_obj ) ) {
			if ( isset( $_term_obj->term_id ) ) {
				// if this zone is on the home page then update that cache - for now just skip the test since that uses more time than it saves probably
				// but don't do it over and over again, since zones receive multiple consecutive updates - set the update time to 30 seconds in the future
				$_delay = 30;
				$this->expire_transient_html( 0, 'term', $_delay );
			}
		}
	}

	/**
	* Enqueue CSS and JavaScript but only on appropriate pages of the back end
	*
	* @return null
	*/
	function admin_enqueues() {
		$_obj = get_current_screen();
		$_screen = isset( $_obj->base ) ? $_obj->base : '';
		// only on dashboard and tags edit and cats edit pages plus settings page
		$_ary_ok = array( 'dashboard_page_home_layout', 'widgets', 'term', 'edit-tags', 'settings_page_postmedia-layouts', 'post' );
		if ( in_array( $_screen, $_ary_ok, true ) ) {
			wp_enqueue_style( 'postmedia_layouts_jqueryui', PM_LAYOUT_URI . 'css/jquery-ui.css' );
			wp_enqueue_style( 'postmedia_layouts_css', PM_LAYOUT_URI . 'css/admin.css' );
			wp_enqueue_script( 'postmedia_layouts_js', PM_LAYOUT_URI . 'js/admin.js' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_script( 'jquery-ui-tooltip' );
			wp_enqueue_script( 'jquery-ui-accordion' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_media(); // allows media selector for images on widgets' advertorial form
			do_action( 'pn_layouts_enqueue_admin', '' );
		}
	}

	/**
	* Create the various admin menu elements: options page, home page layouts, as well as alternate category and tags layouts pages where taxonomy pages are unavailable due to role (e.g. AdOps, Web Editor)
	*
	* @return null
	*/
	function create_menu() {
		$_access = $this->get_detailed_capability();
		add_options_page( 'Layouts', 'Layouts', 'manage_options', self::SETTINGS_PAGE_KEY, array( $this, 'settings_page' ), '' ); //create new top-level menu
		add_action( 'admin_init', array( $this, 'register_settings' ) ); //call register settings function
		add_dashboard_page( 'Layouts', 'Home Layout', $_access['capab'], 'home_layout', array( $this, 'term_form' ), '', 6 );
		if ( ! current_user_can( 'manage_categories' ) ) {
			// let newsroom admin and web editors change Layouts without changing categories/tags
			add_submenu_page( 'edit.php', 'Categories', 'Categories', $_access['capab'], 'admin_list_cats', array( $this, 'admin_list_cats' ) );
			add_submenu_page( 'edit.php', 'Tags', 'Tags', $_access['capab'], 'admin_list_tags', array( $this, 'admin_list_tags' ) );
		}
	}

	/**
	* Gathers, sorts, and returns outfits for inclusion in drop menus
	*
	* @return array $_outfits lists all available outfit templates
	*/
	function list_outfits() {
		$_outfits = array();
		$_outfits[-1] = '--- None -----';

		// Add any additional outfits to the dropdown that may live outside the site theme.
		$this->layouts = apply_filters( 'pn_layouts_add_outfits', $this->layouts );

		foreach ( $this->layouts->outfit_settings as $_key => $_val ) {
			$_outfits[ $_key ] = ( ( true === $_val[2] ) ? '** ' : '' ) . $_val[0];
		}
		ksort( $_outfits );
		return $_outfits;
	}

	/**
	* Displays the admin page elements required to manage all Layouts settings for a single term page (including the home page)
	*
	* @param object $tag contains the term data
	* @return array $_outfits lists all available outfit templates
	*/
	function term_form( $tag ) {
		$_widget_avail_list = $this->get_avail_widget_list();
		$_access = $this->get_detailed_capability();
		$_outfits = $this->list_outfits();
		$_get_data = $this->get_sanitized_input_data( 'get', '', '' );
		$_taxonomy = $this->get_sanitized_input_single( 'taxonomy', $_get_data, 'text', '' );
		$_message = $this->get_sanitized_input_single( 'message', $_get_data, 'text', '' );
		$_page_type = $this->get_sanitized_input_single( 'page', $_get_data, 'text', '' );
		$_tag_id = $this->get_sanitized_input_single( 'tag_ID', $_get_data, 'int', 0 ); // get tag_ID from URL qs or set it to zero
		$_mode = ( 'home_layout' === $_page_type ) ? 'single' : 'taxonomy';
		$_is_backup = 0;
		if ( '' !== $_access['role'] ) {
			if ( '' === trim( $_message ) ) {
				$_is_backup = isset( $_get_data['bak'] ) ? intval( $_get_data['bak'] ) : 0;
			}
			$_post_data = $this->get_sanitized_input_data( 'post', 'pm_layout_noncename', PM_LAYOUT_URI );
			if ( ! empty( $_post_data ) ) {
				if ( isset( $_post_data['tag_ID'] ) ) {
					// if the form has been submitted use the form's version of tag_ID, not the URL's
					$_tag_id = intval( $_post_data['tag_ID'] );
				}
				if ( isset( $_post_data['term_type'] ) ) {
					// save the form
					$this->term_save( 0, 0, $_post_data['term_type'] );
					$_is_backup = 0; // if the previous screen was an attempt to restore from back up, don't now show the backup again
				}
			}
		}
		if ( is_object( $tag ) ) {
			$_tag_id = intval( $tag->term_id ); // override all other sources of tag_ID based on the value passed to the method
		}
		$_allow_feed_override = false;
		$_obj = get_current_screen();
		$_screen = isset( $_obj->id ) ? $_obj->id : '';
		// only show backup if requested
		$_use_backup = ( 1 === intval( $_is_backup ) ) ? true : false;
		if ( 0 === $_tag_id ) {
			// home layout page
			$_section_max = intval( $this->layouts->pmlay_count['sections_home'] );
			$_tax = 'home';
			$_term_type = $_tax;
			$_tag_name = '';
			$_this_url = '/wp-admin/index.php?page=home_layout';
		} elseif ( 'category' === $_taxonomy ) {
			// home layout page for category - no taxonomy editing
			$_section_max = intval( $this->layouts->pmlay_count['sections_category'] );
			$_tax = 'category';
			$_term_type = $_tax;
			$_tag_name = get_the_category_by_ID( $_tag_id );
			$_allow_feed_override = true; // a feed can be set to override the default list
			$_this_url = '/wp-admin/index.php?page=home_layout&taxonomy=category&tag_ID=' . intval( $_tag_id ) . '';
		} elseif ( 'tag' === $_taxonomy ) {
			// home layout page for tag - no taxonomy editing
			$_section_max = intval( $this->layouts->pmlay_count['sections_tag'] );
			$_tax = 'tag';
			$_term_type = $_tax;
			$_term = get_term( $_tag_id, 'post_tag' );
			$_tag_name = $_term->name;
			$_this_url = '/wp-admin/index.php?page=home_layout&taxonomy=tag&tag_ID=' . intval( $_tag_id ) . '';
		} elseif ( 'edit-post_tag' === $_screen ) {
			// regular tags page - allows taxonomy editing
			$_section_max = intval( $this->layouts->pmlay_count['sections_tag'] );
			$_tax = '';
			$_term_type = 'tag';
			$_tag_name = '';
			$_this_url = '/wp-admin/edit-tags.php?action=edit&taxonomy=post_tag&tag_ID=' . intval( $_tag_id ) . '&post_type=post';
		} else {
			// regular category page - allows taxonomy editing
			$_section_max = intval( $this->layouts->pmlay_count['sections_category'] );
			$_tax = '';
			$_term_type = 'category';
			$_tag_name = '';
			$_allow_feed_override = true; // a feed can be set to override the default list
			$_this_url = '/wp-admin/edit-tags.php?action=edit&taxonomy=category&tag_ID=' . intval( $_tag_id ) . '&post_type=post';
		}
		$this->layouts->pmlay_settings = $this->layouts->get_termdata( $_tag_id, $_term_type, $_use_backup );
		if ( 'single' === $_mode ) {
			echo '<form method="POST" id="pm_layouts_form_container">' . "\n";
		}
		if ( 'single' === $_mode ) {
			echo '<div class="wrap">';
		}
		echo '<div id="pmlay_box" class="">';
		wp_nonce_field( PM_LAYOUT_URI, 'pm_layout_noncename' );
		echo '<input type="hidden" name="sections_max" id="sections_max" value="' . intval( $_section_max ) . '" class="configuration-ignore" />';
		echo '<input type="hidden" name="mode" id="mode" value="' . esc_attr( $_mode ) . '" class="configuration-ignore" />';
		echo '<input type="hidden" name="term_id" id="term_id" value="' . esc_attr( $_tag_id ) . '" class="configuration-ignore" />';
		echo '<input type="hidden" name="term_type" id="term_id" value="' . esc_attr( $_term_type ) . '" class="configuration-ignore" />';
		echo '<input type="hidden" id="pmlay_sidebar" name="pmlay_sidebar" value="1" />'; // always show the sidebar
		echo '<h2>Edit ';
		if ( '' !== trim( $_tag_name ) ) {
			echo '"' . esc_html( $_tag_name ) . '" ';
		}
		echo esc_html( ucfirst( $_tax ) ) . ' Layout</h2>';

		// if this is a home_layout form on a category page (i.e. not taxonomy, add the native ad settings elements - if they exist
		if ( ( 'single' === $_mode ) && ( 'category' === $_term_type ) ) {
			if ( function_exists( 'pn_advertorial_category_box' ) ) {
				$_GET['tag_ID'] = $_tag_id; // function uses the query string array value so set that since it's done differently in this i/f
				pn_advertorial_category_box( ( object ) array( 'term_id' => $_tag_id ) ); // pass as an object since that's how Wordpress does it on regular taxonomy pages
			}
		}

		echo '<div class="inside full-size">';
		echo '<div class="configuration-ignore" id="pm_layouts_form">';

		echo '<table class="form-table">';
		echo '<tbody>';

		$this->term_form_video_field();

		$this->term_form_sidebar_field( $_mode, $_tag_id, $_tax );

		$this->term_form_override_field( $_allow_feed_override );

		// Add any additional form fields above widgets and outfits config.
		do_action( 'pn_layouts_admin_display_form', $_taxonomy );

		echo '</tbody>';
		echo '</table>'; // end table

		$this->term_form_tickers( $_section_max );

		/*** DISPLAY OUTFIT OPTIONS ***/
		echo '<div style="width:100%;height:30px;float:none;margin-right:10px;">';
		echo '<h3 style="display:inline;">Note: All post lists, except external feeds, will be pulled from ' . ( true === $this->layouts->site_uses_wcm() ? 'WCM' : 'Wordpress' ) . '.</h3>';

		$this->term_form_restore_button( $_tag_id, $_this_url );

		echo '</div>';
		echo '<ul id="pn_outfit_boxes" class="postmedia_layouts_box metabox-holder clearfix">';
		$_section_js = array();
		for ( $section_num = 0; $section_num < $_section_max; $section_num ++ ) {
			$_cssdisplay = 'block';
			if ( isset( $this->layouts->pmlay_settings['outfits'][ $section_num ] ) ) {
				$_outfit_data = $this->layouts->pmlay_settings['outfits'][ $section_num ];
			} else {
				$_outfit_data = array();
			}
			$_template = -1;
			if ( ( isset( $_outfit_data['template'] ) ) && ( '' !== trim( $_outfit_data['template'] ) ) ) {
				$_template = $_outfit_data['template'];
			}
			echo '<li id="pn_outfit_box_' . intval( $section_num ) . '" class="postbox pn_outfit_box" style="display:' . esc_attr( $_cssdisplay ) . ';">';
			echo '<h3 class="pn_outfit_title">';
			echo 'Outfit ' . intval( $section_num + 1 ) . '<span id="pn_outfit_title_' . intval( $section_num ) . '"></span>';
			echo '</h3>';
			echo '<div class="inside clearfix">';
			echo '<div id="pmlay_wrap_' . intval( $section_num ) . '" class="pmlay_wrap">';
			echo '<div class="pmlay_wrap_inside" style="height:600px;">';
			echo '<table class="form-table">';
			echo '<tbody>';
			echo '<input type="hidden" id="pmlay_order_' . intval( $section_num ) . '" name="pmlay_order_' . intval( $section_num ) . '" value="' . intval( $section_num ) . '" class="configuration-ignore" />';

			$this->term_form_templates( $section_num, $_section_max, $_outfits, $_template );

			$this->term_form_native( $section_num, $_outfit_data );

			$this->term_form_sponsored( $section_num, $_outfit_data );

			$this->term_form_additional_settings( $section_num, $_outfit_data );

			$this->term_form_video( $section_num, $_outfit_data );

			$this->term_form_widgets( $section_num, $_outfit_data, $_widget_avail_list, $this->layouts->pmlay_count['widget_max'] );

			$this->term_form_headings( $section_num, $_outfit_data, $this->layouts->pmlay_count['header_max'] );

			$this->term_form_lists( $section_num, $_outfit_data, $this->layouts->pmlay_count['module_max'] );

			echo '</tbody>';
			echo '</table>';
			echo '</div>'; //pmlay_wrap inside
			echo '</div>'; //pmlay_wrap
			echo '<a href="javascript:pn_set_layouts_preview(0);" class="set_mobile_preview">Mobile</a>';
			echo '<a href="javascript:pn_set_layouts_preview(1);" class="set_desktop_preview layouts_preview_active">Desktop</a>';
			echo '<div id="pmlay_template_section_' . intval( $section_num ) . '" class="inside template-holder widget desktop_template"></div>'; // displays the desktop mockup template
			echo '<div id="pmlay_phone_section_' . intval( $section_num ) . '" class="inside template-holder widget mobile_template"></div>'; // displays the mobile mockup template
			echo '</div>' . "\n"; //.inside
			echo '</li>'; // section postbox
			$_header = isset( $_outfit_data['heads'][0]['header'] ) ? $_outfit_data['heads'][0]['header'] : '';
			$_section_js[ $section_num ] = $_header;
		}
		echo '</ul>';

		$this->term_form_save_button( $_mode );

		echo '</div>';
		echo '</div>';
		if ( 'admin' === $_access['role'] ) {
			echo '<div style="background-color:#FFF;">';
			echo '<h1 onClick="jQuery( \'#pn_layouts_admin_display_json\' ).css( \'display\', \'block\' );">Admin Only - click to show JSON</h1>';
			echo '<table id="pn_layouts_admin_display_json" style="display:none;">';
			echo '<tr>';
			echo '<td><h2>Live JSON: </h2></td>';
			echo '<td><h2>Backup JSON: </h2></td>';
			echo '</tr>';
			echo '<tr valign="top">';
			for ( $x = 0; $x <= 1; $x ++ ) {
				$_layouts = $this->layouts->get_layouts_data( $_tag_id, ( 0 === $x ? false : true ) );
				echo '<td>';
				echo '<div style="width:100%;height:800px;overflow:scroll;"><pre>';
				esc_html( print_r( $_layouts, false ) ); // @codingStandardsIgnoreLine - display for admin debugging only
				echo '</pre></div>';
				echo '</td>';
			}
			echo '</tr>';
			echo '</table>';
			echo '</div>';
		}
		echo '</div>';
		echo '<script type="text/javascript">' . "\n";
		echo "pm_layout_path = '" . esc_url( PM_LAYOUT_URI ) . "';\n";
		foreach ( $_section_js as $_section_num => $_section_head ) {
			echo 'pm_json_show_template( ' . intval( $_section_num ) . ", '" . esc_attr( $_section_head ) . "' );\n";
		}
		echo "ajaxurl = '" . esc_url( admin_url( 'admin-ajax.php' ) ) . "';\n";
		echo 'pn_outfit_names = { ';
		$_count_outfits = 0;
		foreach ( $_outfits as $_key => $_val ) {
			if ( -1 !== $_key ) {
				// skip the -1 => --- None --- option altogether
				if ( 0 < $_count_outfits ) {
					echo ', ';
				}
				echo wp_json_encode( $_key ) . ': ' . wp_json_encode( $_val );
				$_count_outfits ++;
			}
		}
		echo " };\n";
		echo 'pn_access_role = ' . wp_json_encode( $_access['role'] ) . ";\n";
		echo '</script>' . "\n";
	}

	/**
	* Retrieve dynamic & curated lists from WCM and put them in an HTML element for JS
	*
	* @return null
	*/
	private function term_form_wcm_lists() {
		$_qs = array(
			'size' => 50,
			'from' => 0,
		);
		$_list_data = $this->layouts->get_list_data( 'lists', '', $_qs );
		$_lists = array();
		if ( ( is_array( $_list_data ) ) && ( isset( $_list_data['body'] ) ) ) {
			$_list_body = json_decode( $_list_data['body'] );
			foreach ( $_list_body as $_key => $_val ) {
				$_lists[ $_val->type ][ $_val->_id ] = $_val->title;
			}
		}
		echo '<input type="hidden" id="pmlay_wcm_lists" value="' . esc_attr( wp_json_encode( $_lists ) ) . '" />';
	}

	/**
	* Display the term outfit native eligible settings
	* @param $_section_num (string) Current outfit number
	*
	* @return null
	*/
	private function term_form_native( $section_num, $_outfit_data ) {
		$_sponsored = isset( $_outfit_data['sponsored'] ) ? intval( $_outfit_data['sponsored'] ) : 0;
		$_sponstext = isset( $_outfit_data['sponstext'] ) ? trim( $_outfit_data['sponstext'] ) : '';
		echo '<tr class="form-field">';
		echo '<th valign="top" scope="row">';
		echo '<label for="pmlay_tmpl_' . intval( $section_num ) . '">Native eligible?</label> ';
		echo '</th>';
		echo '<td>';
		echo '<div class="box-1 first">';
		echo '<input type="checkbox" ' . checked( $_sponsored, 1, false ) . ' id="pmlay_spons_' . intval( $section_num ) . '" name="pmlay_spons_' . intval( $section_num ) . '" class="pmlay_spons_check" value="1" />';
		echo '</div>';
		echo '<div class="box-5">';
		echo '<select id="pmlay_sponscat_' . intval( $section_num ) . '" class="pmlay_sponscat" style="width:auto !important;">' . "\n";
		echo '<option value="">-- Categories --</option>' . "\n";
		$_categories = get_terms( 'category', array( 'hide_empty' => false ) );
		foreach ( $_categories as $_cat ) {
			echo '<option value="' . esc_attr( $_cat->slug ) . '">' . esc_html( $_cat->name ) . '</option>' . "\n";
		}
		echo '</select>' . "\n";
		echo '</div>';
		echo '<div class="box-6">';
		echo '<input type="text" placeholder="Keyword" id="pmlay_sponskw_' . intval( $section_num ) . '" name="pmlay_sponskw_' . intval( $section_num ) . '" class="pmlay_sponskw" style="" value="' . esc_attr( $_sponstext ) . '" />';
		echo '</div>';
		echo '</td>';
		echo '</tr>';
	}

	/**
	* Display the term outfit additional settings
	* @param $_section_num (string) Current outfit number
	* @param $_outfit_data (array) Full outfit data
	*
	* @return null
	*/
	private function term_form_additional_settings( $section_num, $_outfit_data ) {
		$_allow_sponsored = isset( $_outfit_data['allow_sponsored'] ) ? intval( $_outfit_data['allow_sponsored'] ) : 0;
		$_linkout = isset( $_outfit_data['linkout'] ) ? trim( $_outfit_data['linkout'] ) : '';
		echo '<tr class="form-field">';
		echo '<td colspan="2">';
		echo '<div class="box-1 first">';
		echo '</div>';
		echo '<div class="box-5">';
		echo '<input type="checkbox" ' . checked( $_allow_sponsored, 1, false ) . ' id="pmlay_allow_sponsored_' . intval( $section_num ) . '" name="pmlay_allow_sponsored_' . intval( $section_num ) . '" style="width:auto !important;" value="1" />';
		echo 'Allow sponsored posts</div>';
		echo '<div class="box-6">';
		echo '<input type="checkbox" ' . checked( $_linkout, 1, false ) . ' id="pmlay_linkout_' . intval( $section_num ) . '" name="pmlay_linkout_' . intval( $section_num ) . '" style="width:auto !important;" value="1" />';
		echo 'Link out</div>';
		echo '</td>';
		echo '</tr>';
	}

	/**
	* Display the term outfit sponsored settings
	* @param $_section_num (string) Current outfit number
	* @param $_outfit_data (array) Configuration data for this outfit
	*
	* @return null
	*/
	private function term_form_sponsored( $section_num, $_outfit_data ) {
		$_adv_type = isset( $_outfit_data['adv_type'] ) ? trim( $_outfit_data['adv_type'] ) : '';
		$_adv_company = isset( $_outfit_data['adv_company'] ) ? trim( $_outfit_data['adv_company'] ) : '';
		$_adv_logo = isset( $_outfit_data['adv_logo'] ) ? trim( $_outfit_data['adv_logo'] ) : '';
		$_adv_url = isset( $_outfit_data['adv_url'] ) ? trim( $_outfit_data['adv_url'] ) : '';
		echo '<tr class="form-field" id="pmlay_sponsored_form_' . intval( $section_num ) . '">';
		echo '<th valign="top" scope="row">';
		echo '<label for="pmlay_tmpl_' . intval( $section_num ) . '">Sponsored outfit?</label> ';
		echo '</th>';
		echo '<td>';
		echo '<div class="box-6 first">';
		echo '<select id="pmlay_adv_type_' . intval( $section_num ) . '" name="pmlay_adv_type_' . intval( $section_num ) . '" style="width:48%" >';
		echo '<option value="" selected="selected">Please select type</option>';
		foreach ( $this->adv_type as $_key => $_val ) {
			$_val = ucwords( str_replace( '_', ' ', $_val ) );
			echo '<option value="' . esc_attr( $_key ) . '" ' . selected( $_adv_type, $_key, false ) . '>' . esc_html( $_val ) . '</option>' . "\n";
		}
		echo '</select>' . "\n";
		echo '</div>';
		echo '<div class="box-6 last">';
		echo '<input type="text" placeholder="Company" id="pmlay_adv_company_' . intval( $section_num ) . '" name="pmlay_adv_company_' . intval( $section_num ) . '" value="' . esc_attr( $_adv_company ) . '" style="" />';
		echo '</div>';
		echo '<div class="clear"></div>';
		echo '<div class="box-6 first">';
		echo '<input type="text" placeholder="Logo URL" id="pmlay_adv_logo_' . intval( $section_num ) . '" name="pmlay_adv_logo_' . intval( $section_num ) . '" value="' . esc_attr( $_adv_logo ) . '" style="" />';
		echo '</div>';
		echo '<div class="box-6 last">';
		echo '<input type="text" placeholder="Link URL" id="pmlay_adv_url_' . intval( $section_num ) . '" name="pmlay_adv_url_' . intval( $section_num ) . '" value="' . esc_attr( $_adv_url ) . '" style="" />';
		echo '</div>';
		echo '</td>';
		echo '</tr>';
	}

	/**
	* Display the term outfit video playlists
	* @param $_section_num (string) Current outfit number
	* @param $_outfit_data (array) Configuration data for this outfit
	*
	* @return null
	*/
	private function term_form_video( $section_num, $_outfit_data ) {
		echo '<tr class="form-field" id="pmlay_videobox_' . intval( $section_num ) . '">';
		echo '<th valign="top" scope="row">';
		echo '<label for="pmlay_videoid_' . intval( $section_num ) . '" id="pmlay_videolbl_' . intval( $section_num ) . '">Video Channels</label> ';
		echo '</th>';
		echo '<td>';
		for ( $x = 0; $x < intval( $this->layouts->pmlay_count['video_max'] ); $x ++ ) {
			// change data stored here$_ary[ $section_order ][ 'videoid_' . $x ]
			$_videoid = isset( $_outfit_data['videos'][ $x ] ) ? trim( $_outfit_data['videos'][ $x ] ) : '';
			echo '<div class="box-2 pmlay_video' . ( 0 === $x ? ' first' : '' ) . '" id="pmlay_videobox_' . intval( $section_num ) . '_' . intval( $x ) . '">';
			echo '<input type="text" placeholder="ID" id="pmlay_videoid_' . intval( $section_num ) . '_' . intval( $x ) . '" name="pmlay_videoid_' . intval( $section_num ) . '_' . intval( $x ) . '" value="' . esc_attr( $_videoid ) . '" class="pmlay_video_input" />';
			echo '</div>';
		}
		echo '</td>';
		echo '</tr>';
	}

	/**
	* Display the term outfit widgets
	* @param $_section_num (string) Current outfit number
	* @param $_outfit_data (array) Configuration data for this outfit
	* @param $_widget_avail_list (array) List of available widgets for insertion
	*
	* @return null
	*/
	private function term_form_widgets( $section_num, $_outfit_data, $_widget_avail_list, $_widget_max = 0 ) {
		for ( $_widget_num = 0; $_widget_num < intval( $_widget_max ); $_widget_num ++ ) {
			$_selected = '';
			if ( isset( $_outfit_data['widgets'] ) ) {
				if ( isset( $_outfit_data['widgets'][ $_widget_num ] ) ) {
					$_selected = trim( $_outfit_data['widgets'][ $_widget_num ] );
				}
			}
			// change data stored here
			echo '<tr class="form-field pmlay_widget" id="pmlay_widgetbox_' . intval( $section_num ) . '_' . intval( $_widget_num ) . '">';
			echo '<th valign="top" scope="row">';
			echo '<label for="pmlay_widget_' . intval( $section_num ) . '_' . intval( $_widget_num ) . '">Widget #' . intval( $_widget_num + 1 ) . '</label> ';
			echo '</th>';
			echo '<td>';
			echo '<div class="box-6 first">';
			echo '<select id="pmlay_widget_id_' . intval( $section_num ) . '_' . intval( $_widget_num ) . '" name="pmlay_widget_id_' . intval( $section_num ) . '_' . intval( $_widget_num ) . '" class="pmlay_widget_input" >';
			echo '<option value="auto" ' . selected( $_selected, 'auto', false ) . '>-- Automatic (from sidebar) --</option>';
			echo '<option value="none" ' . selected( $_selected, 'none', false ) . '>-- No Widget --</option>';
			echo '<option value="dfpad" ' . selected( $_selected, 'dfpad', false ) . '>-- DFP Ad --</option>';
			foreach ( $_widget_avail_list as $_widget_slug => $_widget_title ) {
				echo '<option value="' . esc_attr( $_widget_slug ) . '" ' . selected( $_selected, $_widget_slug, false ) . '>';
				echo esc_html( $_widget_title ) . '</option>';
			}
			echo '</select>';
			echo '</div>';
			echo '</td>';
			echo '</tr>';
		}
	}

	/**
	* Display the term outfit headings
	* @param $_section_num (string) Current outfit number
	* @param $_outfit_data (array) Configuration data for this outfit
	*
	* @return null
	*/
	private function term_form_headings( $section_num, $_outfit_data, $_header_max = 0 ) {
		$_list_style = $this->list_style;
		for ( $x = 0; $x < intval( $_header_max ); $x ++ ) {
			$_header = isset( $_outfit_data['heads'][ $x ]['header'] ) ? $_outfit_data['heads'][ $x ]['header'] : '';
			$_headurl = isset( $_outfit_data['heads'][ $x ]['headurl'] ) ? $_outfit_data['heads'][ $x ]['headurl'] : '';
			$_headimg = isset( $_outfit_data['heads'][ $x ]['img'] ) ? $_outfit_data['heads'][ $x ]['img'] : '';
			$_headstyle = isset( $_outfit_data['heads'][ $x ]['style'] ) ? $_outfit_data['heads'][ $x ]['style'] : '';
			echo '<tr class="form-field pmlay_head" id="pmlay_headbox_' . intval( $section_num ) . '_' . intval( $x ) . '">';
			echo '<th valign="top" scope="row">';
			echo '<label for="pmlay_style_' . intval( $section_num ) . '_' . intval( $x ) . '">Heading #' . ( intval( $x ) + 1 ) . '</label>' . "\n";
			echo '</th>';
			echo '<td>';
			echo '<div class="box-4 first">';
			echo '<select id="pmlay_style_' . intval( $section_num ) . '_' . intval( $x ) . '" name="pmlay_style_' . intval( $section_num ) . '_' . intval( $x ) . '" class="pmlay_style">';
			foreach ( $_list_style as $_key => $_val ) {
				echo '<option value="' . esc_attr( $_key ) . '" ' . selected( $_headstyle, $_key, false ) . '>' . esc_html( $_val ) . '</option>' . "\n";
			}
			echo '</select>';
			echo '</div>';
			echo '<div class="box-4">';
			// INPUT: header text
			echo '<input type="text" placeholder="Title" name="pmlay_header_' . intval( $section_num ) . '_' . intval( $x ) . '" id="pmlay_header_' . intval( $section_num ) . '_' . intval( $x ) . '" class="pmlay_header" value="' . esc_attr( $_header ) . '" />';
			echo '</div>';
			echo '<div class="box-4 last"><input type="text" placeholder="Link URL" name="pmlay_headurl_' . intval( $section_num ) . '_' . intval( $x ) . '" id="pmlay_headurl_' . intval( $section_num ) . '_' . intval( $x ) . '" class="pmlay_header" value="' . esc_attr( $_headurl ) . '" /></div>';
			echo '</td>';
			echo '</tr>';
		}
	}

	/**
	* Display the term outfit lists
	* @param $_section_num (string) Current outfit number
	* @param $_outfit_data (array) Configuration data for this outfit
	*
	* @return null
	*/
	private function term_form_lists( $section_num, $_outfit_data, $_outfit_max = 0 ) {
		for ( $x = 0; $x < $_outfit_max; $x++ ) {
			$_type = isset( $_outfit_data['lists'][ $x ]['type'] ) ? $_outfit_data['lists'][ $x ]['type'] : '';
			$_target = isset( $_outfit_data['lists'][ $x ]['target'] ) ? $_outfit_data['lists'][ $x ]['target'] : '';
			$_list_id = isset( $_outfit_data['lists'][ $x ]['id'] ) ? $_outfit_data['lists'][ $x ]['id'] : '';
			$_list_id2 = isset( $_outfit_data['lists'][ $x ]['id2'] ) ? $_outfit_data['lists'][ $x ]['id2'] : '';
			$_list_name = isset( $_outfit_data['lists'][ $x ]['name'] ) ? $_outfit_data['lists'][ $x ]['name'] : '';
			$_labels = isset( $_outfit_data['lists'][ $x ]['labels'] ) ? intval( $_outfit_data['lists'][ $x ]['labels'] ) : 0;
			$_thumbs = isset( $_outfit_data['lists'][ $x ]['thumbs'] ) ? intval( $_outfit_data['lists'][ $x ]['thumbs'] ) : 0;
			$_source = isset( $_outfit_data['lists'][ $x ]['source'] ) ? intval( $_outfit_data['lists'][ $x ]['source'] ) : 0;
			$_showlist = isset( $_outfit_data['lists'][ $x ]['showlist'] ) ? intval( $_outfit_data['lists'][ $x ]['showlist'] ) : 1;
			$video_posts_only = isset( $_outfit_data['lists'][ $x ]['video_posts_only'] ) ? intval( $_outfit_data['lists'][ $x ]['video_posts_only'] ) : 0;
			$_list_button_label = isset( $_outfit_data['lists'][ $x ]['button_label'] ) ? $_outfit_data['lists'][ $x ]['button_label'] : '';
			$_list_button_link = isset( $_outfit_data['lists'][ $x ]['button_link'] ) ? $_outfit_data['lists'][ $x ]['button_link'] : '';
			$_is_usergroup = ( 'ug' === substr( $_type, 0, 2 ) ? true : false );
			$_is_usergroup_cat = ( $_is_usergroup && ( 3 === strlen( $_type ) ) ? true : false );
			if ( $_is_usergroup ) {
				$_list_term = $this->layouts->get_termname( 'ug', $_list_id );
				$_list_term2 = $this->layouts->get_termname( 'cat', $_list_id2 );
			} else {
				$_list_term = $this->layouts->get_termname( $_type, $_list_id );
				$_list_term2 = $this->layouts->get_termname( $_type, $_list_id2 );
			}
			$_field = $section_num . '_' . $x;
			echo '<tr class="form-field pmlay_list " id="pmlay_listbox_' . intval( $section_num ) . '_' . intval( $x ) . '">';
			echo '<th valign="top" scope="row"><label for="pmlay_target_' . esc_attr( $_field ) . '">List #' . intval( $x + 1 ) . '</div></th>';
			echo '<td>';
			// Select a href target
			echo '<div class="box-4 first">';
			echo '<select id="pmlay_target_' . esc_attr( $_field ) . '" name="pmlay_target_' . esc_attr( $_field ) . '" class="pmlay_type">';
			foreach ( $this->list_target as $_key => $_val ) {
				echo '<option value="' . esc_attr( $_key ) . '" ' . selected( $_target, $_key, false ) . '>' . esc_html( $_val ) . '</option>' . "\n";
			}
			echo '</select>';
			echo '</div>';
			// Select list source type
			echo '<div class="box-4">';
			echo '<select id="pmlay_type_' . esc_attr( $_field ) . '" name="pmlay_type_' . esc_attr( $_field ) . '" class="pmlay_type" onChange="pm_set_term( \'' . esc_attr( $_field ) . '\', \'\', \'\', \'\' )">';
			foreach ( $this->list_type as $_key => $_val ) {
				echo '<option value="' . esc_attr( $_key ) . '" ' . selected( $_type, $_key, false ) . '>' . esc_html( $_val ) . '</option>' . "\n";
			}
			echo '</select>';
			echo '</div>';
			// Story list source #1
			echo '<div class="box-4">';
			echo '<input type="hidden" name="pmlay_id_' . esc_attr( $_field ) . '" id="pmlay_id_' . esc_attr( $_field ) . '" value="' . esc_attr( $_list_id ) . '" />';
			echo '<input type="hidden" name="pmlay_id_2_' . esc_attr( $_field ) . '" id="pmlay_id_2_' . esc_attr( $_field ) . '" value="' . esc_attr( $_list_id2 ) . '" />';
			echo '<input type="hidden" name="pmlay_name_' . esc_attr( $_field ) . '" id="pmlay_name_' . esc_attr( $_field ) . '" value="' . esc_attr( $_list_name ) . '" />';
			echo '<input type="text" placeholder="Keyword" name="pmlay_search_' . esc_attr( $_field ) . '" id="pmlay_search_' . esc_attr( $_field ) . '" class="pmlay_search_idx" value="' . esc_attr( $_list_term ) . '" />';
			echo '</div>';
			// Story list source #2
			echo '<div id="pmlay_searchbox2_' . esc_attr( $_field ) . '" class="box-3 pn_layouts_search2 last" ' . ( $_is_usergroup_cat ? ' style="display:block;"' : '' ) . '>';
			echo '<input type="text" placeholder="Keyword2" name="pmlay_search2_' . esc_attr( $_field ) . '" id="pmlay_search2_' . esc_attr( $_field ) . '" class="pmlay_search_idx" value="' . esc_attr( $_list_term2 ) . '" />';
			echo '</div>';
			// Row #2
			echo '<div class="pmlay_show clearfix">';
			// Checkbox show category labels on posts in list
			echo '<div class="box-4 first pmlay_labels">';
			echo '<input type="checkbox" name="pmlay_labels_' . esc_attr( $_field ) . '" id="pmlay_labels_' . esc_attr( $_field ) . '" value="1" ' . checked( $_labels, 1, false ) . ' class="pmlay_labels" />';
			echo '<p>Labels</p>';
			echo '</div>';
			if ( true === $this->layouts->site_uses_wcm() ) {
				// Checkbox use all WCM content available or only content from local site as publisher - only visible when WCM active
				echo '<div class="box-4 first pmlay_source" id="pmlay_source_div_' . esc_attr( $_field ) . '"';
				if ( true === ( in_array( $_type, array( 'auth', 'ug', 'ugc', 'ugs', 'rss', 'chrt', 'zon', 'shar' ), true ) ) ) {
					echo ' style="display:none;"';
				}
				echo '>';
				echo '<input type="checkbox" name="pmlay_source_' . esc_attr( $_field ) . '" id="pmlay_source_' . esc_attr( $_field ) . '" value="1" ' . checked( $_source, 1, false ) . ' class="pmlay_source" />';
				echo '<p>Include Network</p>';
				echo '</div>';
			} else {
				echo '<input type="hidden" name="pmlay_source_' . esc_attr( $_field ) . '" id="pmlay_source_' . esc_attr( $_field ) . '" value="' . intval( $_source ) . '" class="pmlay_source" />';
			}
			// Checkbox - Allows posts without thumbnails from RSS & WCM Shared
			if ( current_user_can( 'manage_options' ) ) {
				echo '<div class="box-4 first pmlay_thumbs" id="pmlay_thumbs_div_' . esc_attr( $_field ) . '"';
				if ( 'rss' !== $_type && 'shar' !== $_type ) {
					echo 'style="display:none"';
				}
				echo ' />';

				echo '<input type="checkbox" name="pmlay_thumbs_' . esc_attr( $_field ) . '" id="pmlay_thumbs_' . esc_attr( $_field ) . '" value="1" ' . checked( $_thumbs, 1, false ) . ' class="pmlay_thumbs" />';
				echo '<p>Allow posts without images</p>';
				echo '</div>';
			}
			// selected source
			echo '<div id="pmlay_show_' . esc_attr( $_field ) . '" class="box-2"><span id="pmlay_showspan_' . esc_attr( $_field ) . '">' . esc_html( $_list_term ) . '</span></div>' . "\n";
			// selected source #2
			echo '<div id="pmlay_show_2_' . esc_attr( $_field ) . '" class="box-2 pn_layouts_search2 last" ' . ( $_is_usergroup_cat ? ' style="display:block;"' : '' ) . '><span id="pmlay_showspan_2_' . esc_attr( $_field ) . '">' . esc_html( $_list_term2 ) . '</span></div>' . "\n";
			echo '</div>';
			// Source options from AJAX
			echo '<div id="pmlay_opts_' . esc_attr( $_field ) . '" class="pmlay_opts"></div>';
			echo '<div>';
			if ( true === $this->layouts->site_uses_wcm() ) {
				echo '<input type="checkbox" name="pmlay_video_posts_only_' . esc_attr( $_field ) . '" id="pmlay_video_posts_only_' . esc_attr( $_field ) . '" value="1" ' . checked( $video_posts_only, 1, false ) . ' class="pmlay_video_posts_only" />';
				echo '<p>Remove posts that have no featured video</p>';
			}
			echo '<div class="horizontal-lists-only">';
			echo '<h4>List Button Configuration</h4>';
			echo '<input type="text" placeholder="Button Label" style="float:left; width: 49%;" id="pmlay_button_label_' . esc_attr( $_field ) . '" name="pmlay_button_label_' . esc_attr( $_field ) . '" class="pmlay_button_label" value="' . esc_attr( $_list_button_label ) . '"/>';
			echo '<input type="text" placeholder="Button Link" style="float:right; width: 49%;" id="pmlay_button_link_' . esc_attr( $_field ) . '" name="pmlay_button_link_' . esc_attr( $_field ) . '" class="pmlay_button_link" value="' . esc_attr( $_list_button_link ) . '"/>';
			echo '</div>'; // End .horizontal-lists-only
			echo '</div>';
			echo '</td>';
			echo '</tr>';
		}
	}

	/**
	* Display the term template drop menu
	* @param $_section_num (string) Current outfit number
	* @param $_section_max (integer) Max number of outfits
	* @param $_outfits (array) Template list
	* @param $_template (string) Template selected
	*
	* @return null
	*/
	private function term_form_templates( $_section_num, $_section_max, $_outfits, $_template ) {
		echo '<tr class="form-field">';
		echo '<th valign="top" scope="row">';
		echo '<input type="hidden" id="list_count_' . intval( $_section_num ) . '" name="list_count_' . intval( $_section_num ) . '" value="' . intval( $_section_max ) . '" class="configuration-ignore" />';
		echo '<label for="pmlay_tmpl_' . intval( $_section_num ) . '">Display</label> ';
		echo '</th>';
		echo '<td>';
		echo '<div class="box-6 first">';
		echo '<select id="pmlay_tmpl_' . intval( $_section_num ) . '" name="pmlay_tmpl_' . intval( $_section_num ) . '" class="pmlay_tmpl">';
		foreach ( $_outfits as $_key => $_val ) {
			echo '<option value="' . esc_attr( $_key ) . '" ' . selected( $_template, $_key, false ) . '>' . esc_html( $_val ) . '</option>' . "\n";
		}
		echo "</select>\n";
		echo '</div>';
		echo '</td>';
		echo '</tr>';
	}

	/**
	* Display the term sidebar field
	* @param $_mode (string) Page mode
	* @param $_tag_id (integer) Term ID
	* @param $_tax (string) Taxonomy type
	*
	* @return null
	*/
	private function term_form_sidebar_field( $_mode, $_tag_id, $_tax ) {
		// if this is a category or tag page (but not home page) and it is using page=home_layout then add in the EasySidebar selector
		if ( ( 'single' === $_mode ) && ( 0 < $_tag_id ) && ( class_exists( 'EasySidebars', false ) ) ) {
			$o_options = ( get_option( $this->sidebar_taxonomy ) );
			// borrowed from EasySidebars plugin where it is in a private method
			$id_selected = 1;
			$s_term = $_tax . '-' . $_tag_id;
			$id_selected = (int) wpcom_vip_get_term_by( 'slug', $s_term, $this->sidebar_taxonomy )->description;
			$_display = array();
			if ( is_object( $o_options ) ) {
				$_sidebars = $o_options->sidebars;
				foreach ( $_sidebars as $_obj ) {
					$_display[ $_obj->id ] = $_obj->title . ( '' !== trim( $_obj->description ) ? ' - ' . $_obj->description : '' );
				}
				asort( $_display );
			}
			echo '<tr class="form-field">';
			echo '<th valign="top" scope="row"><label for="easy_sidebars">Easy Sidebars</label></th>';
			echo '<td>';
			echo '<select id="easy_sidebars" name="easy_sidebars_layouts">';
			echo '<option value="0">(default)</option>';
			foreach ( $_display as $_id => $_title ) {
				echo '<option value="' . intval( $_id ) . '"' . ( ( intval( $_id ) === $id_selected ) ? ' selected="selected"' : '' ) . '>' . esc_html( $_title ) . '</option>' . "\n";
			}
			echo '</select>' . "\n";
			echo '</td>';
			echo '</tr>';
		}
	}

	/**
	* Display the term feed override field
	* @param $_allow (boolean) Display the field?
	*
	* @return null
	*/
	private function term_form_override_field( $_allow ) {
		if ( true === $_allow ) {
			echo '<tr class="form-field">';
			echo '<th valign="top" scope="row">';
			$_override = isset( $this->layouts->pmlay_settings['override'] ) ? $this->layouts->pmlay_settings['override'] : '';
			$_target = isset( $this->layouts->pmlay_settings['target'] ) ? $this->layouts->pmlay_settings['target'] : '';
			echo '<label for="pmlay_target_base">Feed</label>';
			echo '</th>';
			echo '<td>';
			echo '<select id="pmlay_target_base" name="pmlay_target_base" class="pmlay_type" title="Choose how links will open.">';
			foreach ( $this->list_target as $_key => $_val ) {
				echo '<option value="' . esc_attr( $_key ) . '" ' . selected( $_target, $_key, false ) . '>' . esc_html( $_val ) . '</option>' . "\n";
			}
			echo '</select>';
			echo '<input type="text" id="pmlay_override_uri" name="pmlay_override_uri" value="' . esc_attr( $_override ) . '" />';
			echo '<p class="description">Add the full URL to an RSS or Atom feed to display instead of posts on this page.</p>';
			echo '</td>';
			echo '</tr>';
		}
	}

	/**
	* Display the term video ID field
	*
	* @return null
	*/
	private function term_form_video_field() {
		$_video_id = isset( $this->layouts->pmlay_settings['video_id'] ) ? $this->layouts->pmlay_settings['video_id'] : '';
		echo '<tr class="form-field">';
		echo '<th valign="top" scope="row">';
		echo '<label for="pmlay_video_id">Video ID</label>';
		echo '</th>';
		echo '<td>';
		echo '<input type="text" id="pmlay_video_id" name="pmlay_video_id" value="' . esc_attr( $_video_id ) . '" />';
		echo '<p class="description">Enter the Kaltura video list ID to be used by the player in the right rail, if one exists.</p>';
		echo '</td>';
		echo '</tr>';
	}

	/**
	* Display the restore from backup button in the term form
	* @param $_mode (string) Save mode
	*
	* @return null
	*/
	private function term_form_save_button( $_mode ) {
		if ( 'single' === $_mode ) {
			// This is the dashboard screen so editing the home page config - need save button as well
			echo '<div style=" margin-top:20px;" class="clear" />';
			echo '<input type="submit" name="submit" value="Save" class="button button-primary button-large" style="width:250px;margin-left:0px;"/>';
			echo '<div class="button button-primary button-large" style="float:right;"><input type="checkbox" name="pn_backup" id="pn_backup" value="1" class="configuration-ignore" /><label for="pn_backup">Save as Backup</label></div>';
			echo '</div>';
			echo '</div>' . "\n";
			echo '</form>' . "\n";
		} else {
			echo '<div class="button button-primary button-large" style="float:right;"><input type="checkbox" name="pn_backup" value="1" class="configuration-ignore" />Save as Backup</div>';
		}
	}

	/**
	* Display the restore from backup button in the term form
	* @param $_tag_id (integer) Term ID
	* @param $_this_url (string) URL to restore backup
	*
	* @return null
	*/
	private function term_form_restore_button( $_tag_id, $_this_url = '' ) {
		$_lists_bak = $this->layouts->pn_get_option( 'pmlayouts_lists_bak_' . $_tag_id, 0 );
		//only admin can restore from backup
		if ( '' !== $_lists_bak ) {
			echo '<a href="' . esc_url( $_this_url ) . '&bak=1" class="button button-primary button-large" style="float:right;display:block;">Restore Backup</a>';
		} else {
			echo '<span style="float:right;display:block;">No Backup Available</span>';
		}
	}

	/**
	* Display the ticker list in the term form
	* @param $_section_max (integer) Max number of outfits
	*
	* @return null
	*/
	private function term_form_tickers( $_section_max ) {
		$_tickers = $this->list_tickers();
		echo '<ul id="pn_ticker_list"><li>';
		echo '<h3 class="pn_outfit_title">Content Widgets</h3>';
		echo '<div>';
		echo '<table class="form-table">';

		if ( Utilities::is_nexus() && 'home' === $this->layouts->pmlay_settings['term_type'] ) :
			?>
			<thead>
				<p class="description">The first Playlist Player can be overridden by markets <a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::SETTINGS_PAGE_KEY . '#inp-custom-video' ) ) ?>">here</a>.</p>
			</thead>
			<?php
		endif;

		echo '<tbody>';
		for ( $_count_ticker = 1; $_count_ticker <= $this->layouts->max_ticker; $_count_ticker ++ ) {
			$_ticker_key = 'news_ticker_' . intval( $_count_ticker );
			$_news_ticker = isset( $this->layouts->pmlay_settings['news_ticker'][ $_count_ticker ]['type'] ) ? $this->layouts->pmlay_settings['news_ticker'][ $_count_ticker ]['type'] : '';
			$_news_ticker_position = isset( $this->layouts->pmlay_settings['news_ticker'][ $_count_ticker ]['position'] ) ? intval( $this->layouts->pmlay_settings['news_ticker'][ $_count_ticker ]['position'] ) : 0;
			$_news_ticker_attribs = isset( $this->layouts->pmlay_settings['news_ticker'][ $_count_ticker ]['attribs'] ) ? trim( $this->layouts->pmlay_settings['news_ticker'][ $_count_ticker ]['attribs'] ) : '';
			echo '<tr class="form-field">' . "\n";
			echo '<th valign="top" scope="row">';
			echo '<label for="pmlay_video_id">Content Widget #' . intval( $_count_ticker ) . '</label>';
			echo '</th>' . "\n";
			echo '<td>';
			echo '<div class="box-6 first">';
			echo '<select id="pmlay_' . esc_attr( $_ticker_key ) . '" name="pmlay_' . esc_attr( $_ticker_key ) . '" class="pmlay_type" title="Choose a content widget for this page">';
			foreach ( $_tickers as $_key => $_val ) {
				echo '<option value="' . esc_attr( $_key ) . '" ' . selected( $_news_ticker, $_key, false ) . '>' . esc_html( $_val ) . '</option>' . "\n";
			}
			echo '</select>';
			echo '</div>';
			echo '<div class="box-6 last">';
			echo '<select id="pmlay_' . esc_attr( $_ticker_key ) . '_position" name="pmlay_' . esc_attr( $_ticker_key ) . '_position" class="pmlay_type" title="Choose a location for widget #1">';
			echo '<option value="-1">Top Position</option>' . "\n";
			echo '<option value="99"' . selected( $_news_ticker_position, '99', false ) . '>Before Outfit #1</option>' . "\n";
			for ( $x = 1; $x <= $_section_max; $x ++ ) {
				echo '<option value="' . intval( $x ) . '" ' . selected( $_news_ticker_position, $x, false ) . '>After Outfit #' . intval( $x ) . '</option>' . "\n";
			}
			echo '</select>';
			echo '</div>';
			echo '<div class="box-12 clear">';
			echo '<span style="font-weight:bold; float:left; margin-top:4px;">Attributes:</span><input type="text" id="pmlay_' . esc_attr( $_ticker_key ) . '_attribs" name="pmlay_' . esc_attr( $_ticker_key ) . '_attribs" value="' . esc_attr( $_news_ticker_attribs ) . '" style="width:85%; float:right; margin-right:0px;" />';
			echo '</div>';
			echo '</td>' . "\n";
			echo '</tr>' . "\n";
		}
		echo '</tbody>';
		echo '</table>'; // end table
		echo '</div></li></ul>'; // end ticker area
		echo '<p class="description">Content widgets display a dynamic widget on the page. Attributes allow you to pass details to the widget, like a video list ID code.</p>' . "\n";
	}

	/**
	* A previously shared term has been split so capture the new term_id so it can be applied in $this->term_save()
	*
	* @param int $term_id ID of the formerly shared term.
	* @param int $new_term_id ID of the new term created for the $term_taxonomy_id.
	* @param int $term_taxonomy_id ID for the term_taxonomy row affected by the split.
	* @param string $taxonomy Taxonomy for the split term.
	* @return null
	*/
	function term_split( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
		// this fires too early to save or copy the WLO directly so pocket the new_term_id and update the term WLO later
		$this->split_term_id = $new_term_id;
	}

	/**
	* Sanitize text data input from forms
	*
	* @param $_txt string The text data submitted
	* @return string Sanitized text
	*/
	function sanitize_text_input( $_txt ) {
		$_txt = wp_unslash( $_txt );
		$_txt = sanitize_text_field( $_txt );
		return $_txt;
	}

	/**
	* Save the Layouts data attached to a term or the home page
	*
	* @param int $term_id ID of the term being edited
	* @param int $tt_id ID for the term_taxonomy row affected
	* @param string $taxonomy Taxonomy for the term.
	* @return null
	*/
	function term_save( $term_id, $tt_id, $taxonomy ) {
		// CMJVIP : 917 / term_save() : Suggestion: This function is very heavily indented, which makes it very hard to understand. It would benefit from returning when the conditions are not met. That would reduce the indenting a bit.
		// CMJVIP : 1008 / term_save(): Suggestion: If no data would be put into the $_outfit_data, use continue immediately when entering the for-loop. This change would make the code more readable.
		// CMJVIP : 1032 / term_save(): Suggestion: Use continue to skip, instead of having a very long if ()
		$_tag_type = $this->term_save_get_post_type( $taxonomy );
		if ( '' === $_tag_type ) {
			return;
		}
		// A wp_termmeta table would be ideal for this but none exists
		// change this to let manage_layouts roles change some data and manage_advertising roles manage other - those with both manage both
		// do this by loading the existing data here then replacing only some of it
		$_access = $this->get_detailed_capability();
		$_post_data = $this->get_sanitized_input_data( 'post', 'pm_layout_noncename', PM_LAYOUT_URI );
		if ( empty( $_post_data ) ) {
			return; // nonce failed
		}
		$term_id = $this->get_sanitized_input_single( 'term_id', $_post_data, 'int', 0 ); // term_id or 0 for home page
		$_mode = $this->get_sanitized_input_single( 'mode', $_post_data, 'text', '' );
		// first, if this is a home_layout submission, process the native ads settings - if it exists
		if ( ( 'single' === $_mode ) && ( 'category' === $_tag_type ) ) {
			if ( function_exists( 'pn_advertorial_save_category_box' ) ) {
				pn_advertorial_save_category_box( $term_id );
			}
		}
		// next start by retrieving the existing data, then replace it element by element with form data where permitted by capability
		$_ary = array();
		// how many sections can there be?
		$_section_max = $this->get_sanitized_input_single( 'sections_max', $_post_data, 'int', intval( $this->layouts->pmlay_count['sections_max'] ) );
		//easy_sidebars_layouts - save if on category/tag single page
		$_sidebar_id = $this->get_sanitized_input_single( 'easy_sidebars_layouts', $_post_data, 'text', '' );

		// Make sure saving function is aware of any additional layouts provided by plugin.
		$this->layouts = apply_filters( 'pn_layouts_add_outfits', $this->layouts );

		if ( '' !== $_sidebar_id ) {
			$_sidebar_id = intval( $_sidebar_id );
			// save code borrowed from EasySidebars where it is a private method
			$o_term = false;
			$s_term = $_tag_type . '-' . $term_id; //category-99, tag-99
			$a_term = array( 'description' => $_sidebar_id, 'slug' => $s_term );
			if ( wpcom_vip_term_exists( $s_term, $this->sidebar_taxonomy ) ) {
				$o_term = wpcom_vip_get_term_by( 'slug', $s_term, $this->sidebar_taxonomy );
			}
			if ( false !== $o_term ) {
				if ( 0 < intval( $_sidebar_id ) ) {
					wp_update_term( $o_term->term_id, $this->sidebar_taxonomy, $a_term );
				} else {
					wp_delete_term( $o_term->term_id, $this->sidebar_taxonomy );
				}
			}
			if ( ( ! $o_term ) && ( 0 < intval( $_sidebar_id ) ) ) {
				wp_insert_term( $s_term, $this->sidebar_taxonomy, $a_term );
			}
		}
		$_ary['video_id'] = $this->get_sanitized_input_single( 'pmlay_video_id', $_post_data, 'text', '' );

		// these things an editor can change
		$_ary['override'] = $this->get_sanitized_input_single( 'pmlay_override_uri', $_post_data, 'text', '' );
		$_ary['target'] = $this->get_sanitized_input_single( 'target', $_post_data, 'text', '' );
		// loop through the sections
		for ( $section_num = 0; $section_num < $_section_max; $section_num ++ ) {
			$_section_template = $this->get_sanitized_input_single( 'pmlay_tmpl_' . $section_num, $_post_data, 'text', '' );
			if ( '' === $_section_template ) {
				continue;
			}
			$_tpl_id = $_section_template;
			$section_order = $this->get_sanitized_input_single( 'pmlay_order_' . $section_num, $_post_data, 'int', 0 );
			$section_order = $this->get_sanitized_input_single( 'pmlay_order_' . $section_num, $_post_data, 'int', 0 );
			$section_sponsored = $this->get_sanitized_input_single( 'pmlay_spons_' . $section_num, $_post_data, 'int', 0 );
			$section_sponstext = $this->get_sanitized_input_single( 'pmlay_sponskw_' . $section_num, $_post_data, 'text', '' );
			$section_allow_sponsored = $this->get_sanitized_input_single( 'pmlay_allow_sponsored_' . $section_num, $_post_data, 'int', 0 );
			$section_linkout = $this->get_sanitized_input_single( 'pmlay_linkout_' . $section_num, $_post_data, 'int', 0 );
			// if outfit cannot be sponsored save blanks for those fields
			$_adv_type = '';
			$_adv_company = '';
			$_adv_logo = '';
			$_adv_url = '';
			if ( isset( $this->layouts->outfit_settings[ $_tpl_id ][2] ) && true === $this->layouts->outfit_settings[ $_tpl_id ][2] ) {
				// this outfit supports sponsoring
				$_adv_type = $this->get_sanitized_input_single( 'pmlay_adv_type_' . $section_num, $_post_data, 'text', '' );
				$_adv_company = $this->get_sanitized_input_single( 'pmlay_adv_company_' . $section_num, $_post_data, 'text', '' );
				$_adv_logo = $this->get_sanitized_input_single( 'pmlay_adv_logo_' . $section_num, $_post_data, 'text', '' );
				$_adv_url = $this->get_sanitized_input_single( 'pmlay_adv_url_' . $section_num, $_post_data, 'text', '' );
			}
			$_outfit_data = array(
				'template' => $_section_template,
				'sponsored' => $section_sponsored,
				'sponstext' => $section_sponstext,
				'allow_sponsored' => $section_allow_sponsored,
				'linkout' => $section_linkout,
				'adv_type' => $_adv_type,
				'adv_company' => $_adv_company,
				'adv_logo' => $_adv_logo,
				'adv_url' => $_adv_url,
			);
			$_list_count = $this->get_sanitized_input_single( 'list_count_' . $section_num, $_post_data, 'int', 0 );
			// headers by outfit
			$_outfit_data['heads'] = $this->term_save_get_outfit_heads( $_post_data, $section_num );
			// outfits by outfit
			$_mod_max = intval( $this->layouts->pmlay_count['module_max'] );
			$_outfit_data['lists'] = $this->term_save_get_outfit_lists( $_post_data, $section_num, $_mod_max, $_list_count );
			// videos by outfit
			$_video_max = intval( $this->layouts->pmlay_count['video_max'] );
			$_outfit_data['videos'] = array();
			for ( $x = 0; $x < $_video_max; $x++ ) {
				$_videoid = $this->get_sanitized_input_single( 'pmlay_videoid_' . $section_num . '_' . $x, $_post_data, 'text', '' );
				$_videoid = trim( $_videoid );
				if ( '' !== $_videoid ) {
					$_outfit_data['videos'][ $x ] = $_videoid;
				}
			}
			// widgets by outfit
			$_widget_max = intval( $this->layouts->pmlay_count['widget_max'] );
			$_outfit_data['widgets'] = array();
			$_has_widgets = false;
			for ( $x = 0; $x < $_widget_max; $x++ ) {
				$_widget_id = $this->get_sanitized_input_single( 'pmlay_widget_id_' . $section_num . '_' . $x, $_post_data, 'text', 'auto' );
				if ( 'auto' !== $_widget_id ) {
					$_has_widgets = true;
				}
				$_outfit_data['widgets'][ $x ] = $_widget_id;
			}
			$_ary['outfits'][ $section_order ] = $_outfit_data;
		}
		// these things anyone can change
		$_ary['sidebar'] = $this->get_sanitized_input_single( 'pmlay_sidebar', $_post_data, 'int', 0 );
		for ( $_cnt = 1; $_cnt <= $this->layouts->max_ticker; $_cnt ++ ) {
			$_ticker_key = 'news_ticker_' . intval( $_cnt );
			$_ticker_data = $this->get_sanitized_input_single( 'pmlay_' . $_ticker_key, $_post_data, 'text', '' );
			$_ticker_position = $this->get_sanitized_input_single( 'pmlay_' . $_ticker_key . '_position', $_post_data, 'int', 0 );
			$_ticker_attribs = $this->get_sanitized_input_single( 'pmlay_' . $_ticker_key . '_attribs', $_post_data, 'text', '' );
			$_ary['news_ticker'][ $_cnt ] = array(
				'type' => $_ticker_data,
				'position' => $_ticker_position,
				'attribs' => $_ticker_attribs,
			);
		}

		// Modify any additional settings you wish to save within the WLO post.
		$_ary = apply_filters( 'pn_layouts_save_wlo_options', $_ary, $_post_data );

		if ( 0 !== $this->split_term_id ) {
			// if WP 4.2 has split the term then save to the current taxonomy with the new term_id
			$term_id = $this->split_term_id;
		}
		$this->layouts->set_layouts_data( $term_id, $_ary, false );
		$_pn_backup = $this->get_sanitized_input_single( 'pn_backup', $_post_data, 'text', '' );
		if ( '' !== $_pn_backup ) {
			// save a copy as a backup that can be restored - admin only
			$this->layouts->set_layouts_data( $term_id, $_ary, true );
		}
		$this->expire_transient_html( $term_id, 'term' );
		do_action( 'pn_layouts_expire_all_caches' );
	}

	private function term_save_get_outfit_heads( $_post_data, $section_num = 0 ) {
		$_output = array();
		for ( $x = 0; $x < $this->layouts->pmlay_count['header_max']; $x ++ ) {
			$_headstyle = $this->get_sanitized_input_single( 'pmlay_style_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			$_headurl = $this->get_sanitized_input_single( 'pmlay_headurl_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			$_headimg = $this->get_sanitized_input_single( 'pmlay_headimg_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			$_header_text = $this->get_sanitized_input_single( 'pmlay_header_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			// escaped characters count as two when serializing but get cut to one when saving to the db e.g. input O'Brien => serialized to s:8:"O\'Brien" => db inserted as s:8:"O'Brien" => returns error on unserialize because character count does not match
			$_header_text = str_replace( "\'", "'", $_header_text );
			$_header_text = str_replace( '\"', '"', $_header_text );
			$_header_text = trim( $_header_text );
			if ( '' !== $_header_text ) {
				$_output[ $x ] = array(
					'style' => $_headstyle,
					'headurl' => $_headurl,
					'img' => $_headimg,
					'header' => $_header_text,
				);
			}
		}
		return $_output;
	}

	private function term_save_get_outfit_lists( $_post_data, $section_num = 0, $_mod_max = 0, $_list_count = 0 ) {
		$_output = array();
		for ( $x = 0; $x < $_mod_max; $x++ ) {
			$_lists_data = array();
			if ( $x >= $_list_count ) {
				break;
			}
			$_inp_target = $this->get_sanitized_input_single( 'pmlay_target_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			$_inp_type = $this->get_sanitized_input_single( 'pmlay_type_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			if ( false === in_array( $_inp_target, array_keys( $this->list_target ), true ) ) {
				$_inp_target = '';
			}
			if ( false === in_array( $_inp_type, array_keys( $this->list_type ), true ) ) {
				$_inp_type = '';
			}
			$_inp_video_posts_only = $this->get_sanitized_input_single( 'pmlay_video_posts_only_' . $section_num . '_' . $x, $_post_data, 'int', 0 );
			$_inp_list_button_label = $this->get_sanitized_input_single( 'pmlay_button_label_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			$_inp_list_button_link = $this->get_sanitized_input_single( 'pmlay_button_link_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			$_inp_list_id = $this->get_sanitized_input_single( 'pmlay_id_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			$_inp_list_id2 = $this->get_sanitized_input_single( 'pmlay_id_2_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			$_inp_list_name = $this->get_sanitized_input_single( 'pmlay_name_' . $section_num . '_' . $x, $_post_data, 'text', '' );
			$_inp_labels = $this->get_sanitized_input_single( 'pmlay_labels_' . $section_num . '_' . $x, $_post_data, 'int', 0 );
			$_inp_thumbs = $this->get_sanitized_input_single( 'pmlay_thumbs_' . $section_num . '_' . $x, $_post_data, 'int', 0 );
			$_inp_source = $this->get_sanitized_input_single( 'pmlay_source_' . $section_num . '_' . $x, $_post_data, 'int', 0 );
			$_inp_showlist = $this->get_sanitized_input_single( 'pmlay_showlist_' . $section_num . '_' . $x, $_post_data, 'int', 0 );
			if ( ( '' === $_inp_list_name ) && ( true === in_array( $_inp_type, array( 'cat', 'cax', 'tag' ), true ) ) ) {
				// if the term name has not been saved then get it and save it - WCM queries require the term name, not the ID
				$_inp_list_name = $this->layouts->get_list_name( $_inp_type, $_inp_list_id );
			}
			$_lists_data = array(
				'id' => $_inp_list_id,
				'id2' => $_inp_list_id2,
				'name' => $_inp_list_name,
				'type' => $_inp_type,
				'target' => $_inp_target,
				'labels' => $_inp_labels,
				'thumbs' => $_inp_thumbs,
				'source' => $_inp_source,
				'showlist' => $_inp_showlist,
				'video_posts_only' => $_inp_video_posts_only,
				'button_label' => $_inp_list_button_label,
				'button_link' => $_inp_list_button_link,
			);
			if ( '' !== $_inp_list_id ) {
				$_output[ $x ] = $_lists_data; // newer v.3.0
			}
		}
		return $_output;
	}

	private function term_save_get_post_type( $taxonomy = '' ) {
		if ( 'post_tag' === $taxonomy ) {
			return 'tag';
		} elseif ( 'category' === $taxonomy ) {
			return 'category';
		} elseif ( 'home' === $taxonomy ) {
			return 'home';
		} else {
			return '';
		}
	}

	/**
	* Modify ALL widgets on the site to allow selection of display parameters - widgets can be set to appear on all pages, index pages only, or post pages only - they can also be positioned between content elements on mobile - on desktop they appear in the sidebar in the order that they are arranged in the admin
	*
	* @param object $widget (WP_Widget) The widget instance, passed by reference
	* @param null $return Return null if new fields are added
	* @param array $instance An array of the widget's settings.
	* @return null
	*/
	public function widget_admin( $widget, $return, $instance ) {
		$instance['page_display'] = isset( $instance['page_display'] ) ? intval( $instance['page_display'] ) : 0;
		$instance['index_position'] = isset( $instance['index_position'] ) ? intval( $instance['index_position'] ) : 99;
		$instance['post_position'] = isset( $instance['post_position'] ) ? intval( $instance['post_position'] ) : 99;
		$instance['sidebar_choice'] = isset( $instance['sidebar_choice'] ) ? intval( $instance['sidebar_choice'] ) : 0;
		$instance['max_height'] = isset( $instance['max_height'] ) ? intval( $instance['max_height'] ) : 0;
		?>
		<table>
		<tr>
			<td>
				<label for="<?php echo esc_attr( $widget->get_field_id( 'page_display' ) ); ?>">On Page:</label>
			</td>
			<td>
				<select id="<?php echo esc_attr( $widget->get_field_id( 'page_display' ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( 'page_display' ) ); ?>">
					<option value="0" <?php echo selected( 0, $instance['page_display'], false ); ?> >All Pages</option>
					<option value="1" <?php echo selected( 1, $instance['page_display'], false ); ?> >Index Pages Only</option>
					<option value="2" <?php echo selected( 2, $instance['page_display'], false ); ?> >Posts Only</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="<?php echo esc_attr( $widget->get_field_id( 'index_position' ) ); ?>">Mobile Index:</label>
			</td>
			<td>
				<select id="<?php echo esc_attr( $widget->get_field_id( 'index_position' ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( 'index_position' ) ); ?>">
					<option value="-1" <?php echo selected( -1, $instance['index_position'], false ); ?> >Before Content</option>
					<?php
					echo '<option value="0" ' . selected( 0, $instance['index_position'], false ) . '>After Post #1</option>' . "\n";
					for ( $x = 1; $x <= 9; $x ++ ) {
						echo '<option value="' . intval( $x ) . '" ' . selected( $x, $instance['index_position'], false ) . '>After Outfit #' . intval( $x ) . '</option>';
					}
					?>
					<option value="99" <?php selected( 99, $instance['index_position'] ); ?> >After Content</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="<?php echo esc_attr( $widget->get_field_id( 'post_position' ) ); ?>">Mobile Post:</label>
			</td>
			<td>
				<select id="<?php echo esc_attr( $widget->get_field_id( 'post_position' ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( 'post_position' ) ); ?>">
					<option value="0" <?php echo selected( 0, $instance['post_position'], false ); ?> >Before Content</option>
					<?php
					for ( $x = 1; $x <= 10; $x ++ ) {
						echo '<option value="' . intval( $x ) . '" ' . selected( $x, $instance['post_position'], false ) . '>Postion #' . intval( $x ) . '</option>';
					}
					?>
					<option value="99" <?php selected( 99, $instance['post_position'] ); ?> >After Content</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="<?php echo esc_attr( $widget->get_field_id( 'sidebar_choice' ) ); ?>">Sidebar:</label>
			</td>
			<td>
				<select id="<?php echo esc_attr( $widget->get_field_id( 'sidebar_choice' ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( 'sidebar_choice' ) ); ?>">
					<option value="0" <?php echo selected( 0, $instance['sidebar_choice'], false ); ?> >Right Rail</option>
					<option value="1" <?php echo selected( 1, $instance['sidebar_choice'], false ); ?> >Left Rail</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="<?php echo esc_attr( $widget->get_field_id( 'max_height' ) ); ?>">Max Height:</label>
			</td>
			<td>
				<input type="text" id="<?php echo esc_attr( $widget->get_field_id( 'max_height' ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( 'max_height' ) ); ?>"
					value="<?php echo intval( $instance['max_height'] ); ?>" style="width:60px;text-align:right;" /> px
			</td>
		</tr>
		</table>
		<?php
	}

	/**
	* Filter a widget’s settings before saving to add the data from $this->widget_admin()
	*
	* @param array $instance The current widget instance's settings.
	* @param array $new_instance array of new widget settings.
	* @param array $old_instance array of old widget settings.
	* @return null
	*/
	public function widget_update( $instance, $new_instance, $old_instance ) {
		$instance['page_display'] = intval( $new_instance['page_display'] );
		$instance['index_position'] = intval( $new_instance['index_position'] );
		$instance['post_position'] = intval( $new_instance['post_position'] );
		$instance['sidebar_choice'] = intval( $new_instance['sidebar_choice'] );
		$instance['max_height'] = intval( $new_instance['max_height'] );
		return $instance;
	}

	/**
	* Modifies the list (array) of columns on the admin category list page
	*
	* @param array $columns slug => column title
	* @return array $columns
	*/
	function manage_category_columns( $columns ) {
		$columns['pn_isfeed'] = 'Feed';
		return $columns;
	}

	/**
	* Echoes data to custom columns defined in $this->manage_category_columns()
	*
	* @param null $deprecated
	* @param string $column_name column slug
	* @return null
	*/
	function manage_category_custom_fields( $deprecated, $column_name, $_term_id ) {
		if ( 'pn_isfeed' === $column_name ) {
			$_layouts = $this->layouts->get_layouts_data( $_term_id, false );
			$_url = isset( $_layouts['override'] ) ? trim( $_layouts['override'] ) : '';
			if ( '' !== $_url ) {
				$_list_id = intval( $_url );
				if ( 0 < $_list_id ) {
					$_site = get_option( 'postmedia_layouts_domain' );
					$_url = 'http://app.canada.com/SouthPARC/service.svc/Content?callingSite=' . $_site . '&contentId=' . $_list_id . '&format=atom&AllLinks=false';
				}
				echo '<a href="' . esc_url( $_url ) . '" target="_blank" style="width:100%;text-align:center;">Feed</a>';
			}
		}
	}

	/**
	* Echo a list of categories in a custom page for users whose role precludes them from access to taxonomy editing (e.g. AdOps, Web Editor)
	*
	* @return null
	*/
	function admin_list_cats() {
		echo '<h1>Edit Category Layouts</h1>' . "\n";
		$args = array(
			'type' => 'post',
			'child_of' => 0,
			'parent' => '',
			'orderby' => 'name',
			'order' => 'ASC',
			'hide_empty' => 0,
			'hierarchical' => 1,
			'number' => '',
			'taxonomy' => 'category',
			'pad_counts' => false,
		);
		$_terms = get_categories( $args );
		$_max = count( $_terms );
		echo '<p>' . intval( $_max ) . ' categories found.</p>';
		if ( 0 < $_max ) {
			$_maxcol = ceil( $_max / 4 );
			echo '<table class="wp-list-table widefat fixed tags">' . "\n";
			echo '<thead>' . "\n";
			echo '<tr>' . "\n";
			echo '<th scope="col" class="manage-column">Category</th>' . "\n";
			echo '<th scope="col" class="manage-column">Website</th>' . "\n";
			echo '<th scope="col" class="manage-column">Slug</th>' . "\n";
			echo '<th scope="col" class="manage-column">Posts</th>' . "\n";
			echo '</tr>' . "\n";
			echo '</thead>' . "\n";
			echo '<tbody id="the-list" data-wp-lists="list:tag">' . "\n";
			$_count = 0;
			foreach ( $_terms as $_term ) {
				$_term_id = intval( $_term->cat_ID ); // @codingStandardsIgnoreLine - Variable "cat_ID" is not in valid snake_case format
				echo '<tr ' . ( 0 === $_count % 2 ? 'class="alternate"' : '' ) . '>' . "\n";
				echo '<td><strong><a class="row-title" href="/wp-admin/index.php?page=home_layout&taxonomy=category&tag_ID=' . intval( $_term_id ) . '">' . esc_html( $_term->name ) . '</a></strong></td>' . "\n";
				echo '<td><a href="' . esc_url( $this->layouts->get_term_link( $_term_id, 'category' ) ) . '" target="_blank">View</a></td>' . "\n"; // replaced get_category_link
				echo '<td>' . esc_html( $_term->slug ) . '</td>' . "\n";
				echo '<td><a href="/wp-admin/edit.php?category_name=' . esc_attr( $_term->slug ) . '" style="font-size:14px;text-decoration:none;">' . intval( $_term->count ) . '</a></td>';
				echo '</tr>' . "\n";
				$_count ++;
			}
			echo '</table>' . "\n";
		}
	}

	/**
	* Echo a list of tags in a custom page for users whose role precludes them from access to taxonomy editing (e.g. AdOps, Web Editor)
	*
	* @return null
	*/
	function admin_list_tags() {
		$_post_data = $this->get_sanitized_input_data( 'get', 'pm_layout_noncename', PM_LAYOUT_URI );
		$_kw = $this->get_sanitized_input_single( 'kw', $_post_data, 'text', '' );
		$_empty = $this->get_sanitized_input_single( 'empty', $_post_data, 'int', 0 );
		echo '<h1>Edit Tag Layouts</h1>' . "\n";
		echo '<form method="GET" action="/wp-admin/edit.php">' . "\n";
		wp_nonce_field( PM_LAYOUT_URI, 'pm_layout_noncename' );
		echo '<input type="hidden" name="page" value="admin_list_tags">' . "\n";
		echo '<input type="text" name="kw" value="' . esc_attr( $_kw ) . '">' . "\n";
		echo '<input type="submit" value="Search" class="button button-primary">' . "\n";
		echo '<input type="checkbox" name="empty" value="1"' . checked( $_empty, 1, false ) . '>Include unused tags' . "\n";
		echo '</form>' . "\n";
		$_hide_empty = ( 0 === $_empty ) ? true : false;
		if ( '' === $_kw ) {
			return;
		}
		$_args = array(
			'orderby' => 'name',
			'order' => 'ASC',
			'hide_empty' => $_hide_empty,
			'number' => '100',
			'fields' => 'all',
			'hierarchical' => true,
			'search' => $_kw,
		);
		$_terms = get_terms( array( 'post_tag' ), $_args );
		$_max = count( $_terms );
		echo '<p>' . intval( $_max ) . ' tags found.</p>';
		if ( 0 >= $_max ) {
			return;
		}
		$_maxcol = ceil( $_max / 4 );
		echo '<table class="wp-list-table widefat fixed tags">' . "\n";
		echo '<thead>' . "\n";
		echo '<tr>' . "\n";
		echo '<th scope="col" class="manage-column">Tag</th>' . "\n";
		echo '<th scope="col" class="manage-column">Website</th>' . "\n";
		echo '<th scope="col" class="manage-column">Slug</th>' . "\n";
		echo '<th scope="col" class="manage-column">Posts</th>' . "\n";
		echo '</tr>' . "\n";
		echo '</thead>' . "\n";
		echo '<tbody id="the-list" data-wp-lists="list:tag">' . "\n";
		$_count = 0;
		foreach ( $_terms as $_term ) {
			echo '<tr ' . ( 0 === $_count % 2 ? 'class="alternate"' : '' ) . '>' . "\n";
			echo '<td><strong><a class="row-title" href="/wp-admin/index.php?page=home_layout&taxonomy=tag&tag_ID=' . intval( $_term->term_id ) . '">' . esc_html( $_term->name ) . '</a></strong></td>' . "\n";
			echo '<td><a href="' . esc_url( $this->layouts->get_term_link( $_term->term_id, 'post_tag' ) ) . '" target="_blank">View</a></td>' . "\n"; // replaced get_tag_link
			echo '<td>' . esc_html( $_term->slug ) . '</td>' . "\n";
			echo '<td><a href="/wp-admin/edit.php?tag=' . esc_attr( $_term->slug ) . '" style="font-size:14px;text-decoration:none;">' . intval( $_term->count ) . '</a></td>';
			echo '</tr>' . "\n";
			$_count ++;
		}
		echo '</table>' . "\n";
	}

	/**
	* Look up terms to display as selectable options in admin when the user is typing a keyword to select a term for a list in an outfit - prints array of terms as JSON string back to AJAX call
	*
	* @return null
	*/
	function json_pmlay_termsearch() {
		$_output = array();
		$_access = $this->get_detailed_capability();
		if ( '' === $_access['role'] ) {
			return true;
		}
		$_post_data = $this->get_sanitized_input_data( 'post', 'nonce', PM_LAYOUT_URI );
		$_fld = $this->get_sanitized_input_single( 'fld', $_post_data, 'text', '0_0' );
		// first or second parameter to be queried against - only relevent for lists using two parameters, e.g. Usergroup + Category
		$_mode = $this->get_sanitized_input_single( 'mode', $_post_data, 'text', '' );
		$_tax = $this->get_sanitized_input_single( 'taxonomy', $_post_data, 'text', 'cat' );
		$_kw = $this->get_sanitized_input_single( 'kw', $_post_data, 'text', '' );
		$_kw = strtolower( $_kw );
		$ary_list_type = array(
			'' => '',
			'auth' => 'auth',
			'cat' => 'category',
			'cax' => 'category',
			'ug' => 'usergroup',
			'ugc' => 'usergroup',
			'ugs' => 'usergroup',
			'chrt' => 'chartbeat',
			'rss' => '',
			'tag' => 'post_tag',
			'wcm' => '',
			'zon' => 'zoninator_zones',
			'shar' => 'shared',
		);
		$ary_list_type = apply_filters( 'pn_layouts_json_termsearch', $ary_list_type );
		if ( isset( $ary_list_type[ $_tax ] ) ) {
			$_tax_name = $ary_list_type[ $_tax ];
			$_output['fld'] = $_fld;
			$_output['terms'] = array();
			$_ary = array();
			if ( '' !== $_kw ) {
				// Find and return all authors in descending order by post count
				switch ( $_tax_name ) {
					case 'auth':
						$_ary = $this->get_source_list_auth( $_kw );
						break;
					case 'usergroup':
						$_ary = $this->get_source_list_usergroup( $_mode, $_kw );
						break;
					case 'chartbeat':
						$_ary = $this->get_source_list_chartbeat( $_kw );
						break;
					case 'shared':
						$_ary = $this->get_source_list_shared( $_kw );
						break;
					default:
						$_ary = $this->get_source_list_terms( $_tax_name, $_kw );
						break;
				}
				$_ary = apply_filters( 'pn_layouts_json_termsearch_' . $_tax_name, $_ary, $_kw, $_mode );
				$_output['terms'] = $_ary;
			}
		}
		print( wp_json_encode( $_output ) );
		die();
	}

	/**
	* Look up list of custom lists by keyword, for $this->json_pmlay_termsearch()
	* For MVP, WCM zones will not have a lookup and will require that the operator know the ID
	* @param $_kw (string) The text to search for when creating the list
	*
	* @return (array) array of arrays containing list
	*/
	private function get_source_list_shared( $_kw ) {
		$_ary = array();
		return $_ary;
	}

	/**
	* Look up list of author lists by keyword, for $this->json_pmlay_termsearch() - in MVP post lists from user groups will be pulled from local WP data
	* Even when the list of posts will be pulled from WCM, get the list of terms from Wordpress locally
	* @param $_kw (string) The text to search for when creating the list
	*
	* @return (array) array of arrays containing list
	*/
	private function get_source_list_auth( $_kw ) {
		$_kw = sanitize_key( $_kw );
		// get data from transient if available
		$_transient_key = 'pn_layouts_auth_' . $_kw;
		$_transient = $this->get_source_list_transient( $_transient_key );
		if ( ! empty( $_transient ) ) {
			return $_transient;
		}
		$_ary = array();
		// get data from Wordpress
		$args = array(
			'search' => '*' . $_kw . '*',
			'search_columns' => array( 'user_nicename' ),
			'orderby' => 'user_nicename',
			'order' => 'DESC',
			'fields' => 'all_with_meta',
		);
		$_rs = new WP_User_Query( $args );
		$_post_count = -1; // count_user_posts( $aryrs->ID ) ... disappointed this number is not available through WP_User_Query, oh well
		foreach ( $_rs->results as $_row ) {
			$_output[] = array(
				$_row->ID,
				$_row->display_name,
				$_post_count,
			);
		}
		$_expiration = 10 * 60; // 10 minutes
		set_transient( $_transient_key, $_output, $_expiration );
		return $_output;
	}

	/**
	* Look up list of usergroup lists by keyword, for $this->json_pmlay_termsearch() - in MVP post lists from user groups will be pulled from local WP data
	* Even when the list of posts will be pulled from WCM, get the list of terms from Wordpress locally
	* @param $_mode (string) The type of usergroup to select
	* @param $_kw (string) The text to search for when creating the list
	*
	* @return (array) array of arrays containing list
	*/
	private function get_source_list_usergroup( $_mode, $_kw ) {
		$_kw = sanitize_key( $_kw );
		// get data from transient if available
		$_transient_key = 'pn_layouts_ug_' . $_kw;
		$_transient = $this->get_source_list_transient( $_transient_key );
		if ( ! empty( $_transient ) ) {
			return $_transient;
		}
		$_output = array();
		// get data from Wordpress
		$_tax_name_new = ( '' === $_mode ) ? 'ef_usergroup' : 'category';
		$args = array(
			'search' => $_kw,
			'orderby' => 'count',
			'order' => 'DESC',
			'hide_empty' => false,
			'number' => 10,
		);
		$_rs = get_terms( $_tax_name_new, $args );
		if ( 0 < count( $_rs ) ) {
			$_post_count = -1; // was $_row->count
			foreach ( $_rs as $_row ) {
				$_output[] = array(
					$_row->term_id,
					str_replace( "'", '', $_row->name ),
					$_post_count,
				);
			}
		}
		$_expiration = 10 * 60; // 10 minutes
		set_transient( $_transient_key, $_output, $_expiration );
		return $_output;
	}

	/**
	* Look up list of chartbeat lists by keyword, for $this->json_pmlay_termsearch()
	* @param $_kw (string) The text to search for when creating the list
	*
	* @return (array) array of arrays containing list
	*/
	private function get_source_list_chartbeat( $_kw ) {
		$_kw = sanitize_key( $_kw );
		$_output = array();
		$aryrs = explode( ',', get_option( 'postmedia_layouts_chartbeat_sections' ) );
		$_count = count( $aryrs );
		$user_post_count = -1;
		for ( $x = 0; $x < $_count; $x++ ) {
			$_name = trim( $aryrs[ $x ] );
			if ( false !== strpos( strtolower( $_name ), $_kw ) ) {
				$_output[] = array( $_name, $_name, $user_post_count );
			}
		}
		return $_output;
	}

	/**
	* Look up list of term lists by keyword, for $this->json_pmlay_termsearch()
	* Even when the list of posts will be pulled from WCM, get the list of terms from Wordpress locally
	* @param $_typ (string) Type of term to look up (category, post_tag, zoninator_zones)
	* @param $_kw (string) The text to search for when creating the list
	*
	* @return (array) array of arrays containing list
	*/
	private function get_source_list_terms( $_typ, $_kw ) {
		// CMJVIP : 1549 / get_source_list_terms(): Blocker: The results of get_terms() should be cached for at least 10 minutes, see comment for 1452 above.
		$_kw = sanitize_key( $_kw );
		$_output = array();
		$_types = array( 'category', 'post_tag', 'zoninator_zones' );
		// whitelist types
		if ( false === in_array( $_typ, $_types, true ) ) {
			return $_output;
		}
		// get data from transient if available
		$_transient_key = 'pn_layouts_' . $_typ . '_' . $_kw;
		$_transient = $this->get_source_list_transient( $_transient_key );
		if ( ! empty( $_transient ) ) {
			return $_transient;
		}
		$args = array(
			'taxonomy' => $_typ,
			'search' => $_kw,
			'orderby' => 'count',
			'order' => 'DESC',
			'hide_empty' => false,
			'number' => 10,
		);
		$_rs = get_terms( $args );
		foreach ( $_rs as $_row ) {
			$_post_count = ( 'zoninator_zones' !== $_typ ) ? $_row->count : -1;
			$_output[] = array(
				$_row->term_id,
				$_row->name,
				$_post_count,
			);
		}
		$_expiration = 10 * 60; // 10 minutes
		set_transient( $_transient_key, $_output, $_expiration );
		return $_output;
	}

	private function get_source_list_transient( $_key = '' ) {
		$_key = sanitize_key( $_key );
		if ( '' === $_key ) {
			return array();
		}
		$_transient = get_transient( $_key );
		if ( ( false !== $_transient ) && ( is_array( $_transient ) ) ) {
			return $_transient;
		}
		return array();
	}

	/**
	* Look up outfit template in [child/parent theme]/pm_layouts/oftpl/oftpl-#.php and [child/parent theme]/pm_layouts/phtpl/phtpl-#.php - prints template array as JSON string back to AJAX call
	*
	* @return null
	*/
	function json_pmlay_showtemplate() {
		$_output = array();
		$_access = $this->get_detailed_capability();
		if ( '' === $_access['role'] ) {
			return true; // editors only
		}
		$_post_data = $this->get_sanitized_input_data( 'post', 'nonce', PM_LAYOUT_URI );
		$_template_id = $this->get_sanitized_input_single( 'num', $_post_data, 'text', '0' );
		$_output['uri'] = '';
		$_output['html'] = '';
		$_output['phone'] = '';
		$_header_count = 0;
		$_list_count = 0;
		$_video_count = 0;
		$_widget_count = 0;
		$_template_file = $this->layouts->choose_template( 'oftpl', $_template_id, false, true, true );
		if ( file_exists( $_template_file ) ) {
			$_html = file_get_contents( $_template_file ); // local file so don't use wpcom_vip_file_get_contents
			if ( false !== $_html ) {
				// replace template shortcodes with live values
				$_theme_url = $this->layouts->template_url; // need url not path
				$_html = str_replace( '[theme_url]', $_theme_url, $_html );
				$_header_count = intval( preg_match_all( '/class\=\"[^\"]*pmadmin\_title\_[0-9]+[^\"]*\"/', $_html, $_ary ) );
				$_list_count = intval( preg_match_all( '/class\=\"[^\"]*pmadmin\_module\_[0-9]+[^\"]*\"/', $_html, $_ary ) );
				$_video_count = intval( preg_match_all( '/class\=\"[^\"]*pmadmin\_video\_[0-9]+[^\"]*\"/', $_html, $_ary ) );
				$_widget_count = intval( preg_match_all( '/class\=\"[^\"]*pmadmin\_widget\_[0-9]+[^\"]*\"/', $_html, $_ary ) );
				$_label_count = intval( preg_match_all( '/class\=\"[^\"]*pmadmin_cat[^\"]*\"/', $_html, $_ary ) );
				$_output['uri'] = $_template_file;
				$_output['html'] = $_html;
			}
		}
		$_phone_id = $this->layouts->outfit_settings[ $_template_id ][1];
		$_template_file = $this->layouts->choose_template( 'phtpl', $_phone_id, false, true, true );
		if ( file_exists( $_template_file ) ) {
			$_html = file_get_contents( $_template_file ); // local file so don't use wpcom_vip_file_get_contents
			if ( false !== $_html ) {
				// replace template shortcodes with live values
				$_theme_url = $this->layouts->template_url; // need url not path
				$_html = str_replace( '[theme_url]', $_theme_url, $_html );
				$_output['phone'] = $_html;
			}
		}
		$_output['headers'] = $_header_count;
		$_output['modules'] = $_list_count;
		$_output['videos'] = $_video_count;
		$_output['widgets'] = $_widget_count;
		$_output['labels'] = $_label_count;
		print( wp_json_encode( $_output ) );	// encode output in JSON + send it back to the caller
		die();
	}

	/**
	* Sanitize default outfits
	*
	* @return string JSON encoded array
	*/
	function sanitize_default_outfits() {
		$_section_max = 10;
		$_output = array();
		$_post_data = $this->get_sanitized_input_data( 'post', 'pm_layout_noncename', PM_LAYOUT_URI );
		for ( $_section_num = 1; $_section_num <= $_section_max; $_section_num ++ ) {
			$_section_val = $this->get_sanitized_input_single( 'postmedia_layouts_default_outfits[' . $_section_num . ']', $_post_data, 'int', -1 );
			$_output[ $_section_num ] = $_section_val;
		}
		return wp_json_encode( $_output );
	}

	/**
	* Create an array of content widgets - that can be delivered between outfits on index pages - from the child and parent theme folders
	*
	* @return array $_output list of content widgets
	*/
	private function list_tickers() {
		$_output = array();
		$_output[''] = '--- No Display ---';
		$_folder = $this->layouts->template_path . 'ticker/';
		if ( file_exists( $_folder ) ) {
			$_handle = opendir( $_folder );
			if ( false !== $_handle ) {
				while ( false !== ( $_slug = readdir( $_handle ) ) ) {
					if ( 'ticker-' === substr( $_slug, 0, 7 ) ) {
						$_slug = substr( $_slug, 7, 1000 );
						$_slug = str_replace( '.php', '', $_slug );
						$_slug = sanitize_key( $_slug );
						$_ticker_name = $_slug;
						$_ticker_name = str_replace( '-', ' ', $_ticker_name );
						$_ticker_name = ucwords( $_ticker_name );
						$_output[ $_slug ] = $_ticker_name;
					}
				}
				closedir( $_handle );
			}
		}
		return $_output;
	}

	/**
	* Sets $access['capab'] which is the capab used to test permission to access admin back end of Layouts
	*
	* @return array $_ret array of capability and role of the current user
	*/
	function get_detailed_capability() {
		$_ret = array( 'capab' => '', 'role' => '' );
		if ( current_user_can( 'manage_layouts' ) ) {
			$_ret['capab'] = 'manage_layouts';
		} else {
			$_ret['capab'] = 'manage_advertising'; // let advertising roles in here too then limit what they can do
		}
		 // sets $access['role'] which defines what data a user can edit
		if ( ( current_user_can( 'manage_layouts' ) ) && ( current_user_can( 'manage_advertising' ) ) ) {
			$_ret['role'] = 'admin';
		} elseif ( current_user_can( 'manage_layouts' ) ) {
			$_ret['role'] = 'editor';
		} elseif ( current_user_can( 'manage_advertising' ) ) {
			$_ret['role'] = 'advertising';
		}
		return $_ret;
	}

	/**
	* Register Layouts settings
	*
	* @return null
	*/
	function register_settings() {
		//register our settings
		// CMJ: condense these into one json-encoded
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_domain', array( $this, 'sanitize_text_input' ) ); // (string) passed to SouthPARC's query service to build atom feeds
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_chartbeat_sections', array( $this, 'sanitize_text_input' ) ); // (string) list of sections in Chartbeat for our website
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_chartbeat_host', array( $this, 'sanitize_text_input' ) ); // (string) chartbeat host domain
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_chartbeat_apikey', array( $this, 'sanitize_text_input' ) ); // (string) chartbeat API key
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_sections_home', array( $this, 'sanitize_text_input' ) ); // (int) number of outifts available on home page
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_sections_category', array( $this, 'sanitize_text_input' ) ); // (int) number of outifts available on each category page
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_sections_tag', array( $this, 'sanitize_text_input' ) ); // (int) number of outifts available on each tag page
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_choose_color', array( $this, 'sanitize_text_input' ) ); // (boolean) determines whether or not editors can choose headline colors
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_phone_widgets', array( $this, 'sanitize_text_input' ) ); // (string) list of widgets that can be displayed on phones
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_show_sidebar', array( $this, 'sanitize_text_input' ) ); // (boolean) display sidebar on page 1 of index pages?
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_show_baselist', array( $this, 'sanitize_text_input' ) ); // (boolean) display baselist on page 1 of index pages?
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_show_wcm', array( $this, 'sanitize_text_input' ) ); // (boolean) use WCM as a source
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_outfit_order_small', array( $this, 'sanitize_text_input' ) );
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_outfit_order_medium', array( $this, 'sanitize_text_input' ) );
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_outfit_order_large', array( $this, 'sanitize_text_input' ) );
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_custom_main_video', array( $this, 'sanitize_text_input' ) );
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_show_vc', array( $this, 'sanitize_text_input' ) ); // (boolean) use video center options in layouts?
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_widget_source', array( $this, 'sanitize_text_input' ) ); // text slug for a sidebar to use as the source for outfit insertable widgets - edit term drop menu
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_secondary_ad_height', array( $this, 'sanitize_text_input' ) );
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_wcm_origin_client', array( $this, 'sanitize_text_input' ) );
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_wcm_origin_client_domain', array( $this, 'sanitize_text_input' ) );
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_template_location', array( $this, 'sanitize_text_input' ) ); // (int) template location: child or parent theme
		register_setting( 'postmedia_layouts-settings-group', 'postmedia_layouts_default_outfits', array( $this, 'sanitize_default_outfits' ) ); // (int) template location: child or parent theme
	}

	/**
	* Register Form for gathering settings data
	*
	* @return null
	*/
	function settings_page() {
		global $wp_registered_sidebars;
		if ( false === current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
		<h2>Postmedia Layouts Plugin Settings</h2>
		<p style="font-weight:bold;">Do not change any of this unless you <u>absolutely</u> know you're doing. Please.</p>
		<form method="post" action="options.php"><?php
		wp_nonce_field( PM_LAYOUT_URI, 'pm_layout_noncename' );
		settings_fields( 'postmedia_layouts-settings-group' );
		do_settings_sections( 'postmedia_layouts-settings-group' );
		$_sections_home = isset( $this->layouts->pmlay_count['sections_home'] ) ? intval( $this->layouts->pmlay_count['sections_home'] ) : 8;
		$_sections_category = isset( $this->layouts->pmlay_count['sections_category'] ) ? intval( $this->layouts->pmlay_count['sections_category'] ) : 6;
		$_sections_tag = isset( $this->layouts->pmlay_count['sections_tag'] ) ? intval( $this->layouts->pmlay_count['sections_tag'] ) : 4;
		$_section_max = isset( $this->layouts->pmlay_count['sections_max'] ) ? intval( $this->layouts->pmlay_count['sections_max'] ) : 10;
		$_choose_color = ( isset( $this->layouts->pmlay_count['choose_color'] ) ) ? intval( $this->layouts->pmlay_count['choose_color'] ) : 1;
		$_show_sidebar = ( isset( $this->layouts->pmlay_count['show_sidebar'] ) ) ? intval( $this->layouts->pmlay_count['show_sidebar'] ) : 1;
		$_show_baselist = ( isset( $this->layouts->pmlay_count['show_baselist'] ) ) ? intval( $this->layouts->pmlay_count['show_baselist'] ) : 1;
		$_show_wcm = ( isset( $this->layouts->pmlay_count['show_wcm'] ) ) ? intval( $this->layouts->pmlay_count['show_wcm'] ) : 0;
		$_show_vc = ( isset( $this->layouts->pmlay_count['show_vc'] ) ) ? intval( $this->layouts->pmlay_count['show_vc'] ) : 0;

		$_postmedia_layouts_outfit_order_small = ( isset( $this->layouts->pmlay_count['outfit_order']['small'] ) ) ? implode( ',', $this->layouts->pmlay_count['outfit_order']['small'] ) : '';
		$_postmedia_layouts_outfit_order_medium = ( isset( $this->layouts->pmlay_count['outfit_order']['medium'] ) ) ? implode( ',', $this->layouts->pmlay_count['outfit_order']['medium'] ) : '';
		$_postmedia_layouts_outfit_order_large = ( isset( $this->layouts->pmlay_count['outfit_order']['large'] ) ) ? implode( ',', $this->layouts->pmlay_count['outfit_order']['large'] ) : '';
		$_postmedia_layouts_custom_main_video = ( isset( $this->layouts->pmlay_count['custom_main_video'] ) ) ? trim( $this->layouts->pmlay_count['custom_main_video'] ) : '';

		$_widget_source = ( isset( $this->layouts->pmlay_count['widget_source'] ) ) ? trim( $this->layouts->pmlay_count['widget_source'] ) : '';
		$_secondary_ad_height  = ( isset( $this->layouts->pmlay_count['secondary_ad_height'] ) ) ? intval( $this->layouts->pmlay_count['secondary_ad_height'] ) : 250;
		$_wcm_origin_client = ( isset( $this->layouts->pmlay_count['wcm_origin_client'] ) ) ? trim( $this->layouts->pmlay_count['wcm_origin_client'] ) : '';
		$_wcm_origin_client_domain = ( isset( $this->layouts->pmlay_count['wcm_origin_client_domain'] ) ) ? trim( $this->layouts->pmlay_count['wcm_origin_client_domain'] ) : '';
		$_template_location = intval( get_option( 'postmedia_layouts_template_location' ) );
		$_default_outfits = get_option( 'postmedia_layouts_default_outfits' );
		if ( is_string( $_default_outfits ) ) {
			$_default_outfits = json_decode( $_default_outfits, true );
		}
		$_dfp_ads = get_option( 'pn_dfpads' );
		?>
		<table id="pmlayouts_table" class="form-table pm_settings">
		<tr valign="top">
			<th colspan="2"><h2>Layout Settings</h2></th>
		</tr>
		<tr valign="top">
			<th scope="row">Website Domain</th>
			<td><input type="text" name="postmedia_layouts_domain" class="pmlayouts_textin" value="<?php echo esc_attr( get_option( 'postmedia_layouts_domain' ) ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Maximum Outfits:</th>
			<td>
			Home Page: <select name="postmedia_layouts_sections_home">
			<?php
			for ( $x = 0; $x <= $_section_max; $x ++ ) {
				echo '<option value="' . intval( $x ) . '"' . selected( $_sections_home, $x, false ) . '>' . intval( $x ) . '</option>' . "\n";
			}
			echo '</select>' . "\n";
			echo 'Category Pages: <select name="postmedia_layouts_sections_category">' . "\n";
			for ( $x = 0; $x <= $_section_max; $x ++ ) {
				echo '<option value="' . intval( $x ) . '"' . selected( $_sections_category, $x, false ) . '>' . intval( $x ) . '</option>' . "\n";
			}
			echo '</select>';
			echo 'Tag Pages: <select name="postmedia_layouts_sections_tag">' . "\n";
			for ( $x = 0; $x <= $_section_max; $x ++ ) {
				echo '<option value="' . intval( $x ) . '"' . selected( $_sections_tag, $x, false ) . '>' . intval( $x ) . '</option>' . "\n";
			}
			echo '</select>';
			?></td>
		</tr>
		<tr valign="top">
			<th colspan="2"><h2>Default Outfit Templates</h2></th>
		</tr>
		<?php
		for ( $_section_num = 1; $_section_num <= $_section_max; $_section_num ++ ) {
			echo '<tr valign="top">';
			echo '<th scope="row">Outfit #' . intval( $_section_num ) . '</th>';
			echo '<td>';
			echo '<select name="postmedia_layouts_default_outfits[' . intval( $_section_num ) . ']">';
			echo '<option value="-1">--- None -----</option>';
			foreach ( $this->layouts->outfit_settings as $_outfit_id => $_outfit_data ) {
				if ( ( isset( $_default_outfits[ $_section_num ] ) ) && ( '' !== trim( $_default_outfits[ $_section_num ] ) ) ) {
					$_default = intval( $_default_outfits[ $_section_num ] );
				} else {
					$_default = -1;
				}
				echo '<option value="' . intval( $_outfit_id ) . '" ' . selected( $_default, intval( $_outfit_id ), true ) . '>';
				echo esc_html( $_outfit_data[0] ) . '</option>';
			}
			echo '</select></td>';
			echo '</tr>' . "\n";
		}
		?>
		<tr valign="top">
			<th scope="row">Template Location</th>
			<td><select name="postmedia_layouts_template_location">
				<option value="0" <?php selected( $_template_location, 0, true ); ?>>Parent Theme</option>
				<option value="1" <?php selected( $_template_location, 1, true ); ?>>Child Theme</option>
			</select></td>
		</tr>
		<tr valign="top">
			<th scope="row">Outfit Widget Sidebar</th>
			<td>
			<?php
			echo '<select name="postmedia_layouts_widget_source">';
			foreach ( $wp_registered_sidebars as $_sidebar_slug => $_sidebar_ary ) {
				echo '<option value="' . esc_attr( $_sidebar_slug ) . '" ' . selected( $_widget_source, $_sidebar_slug, true ) . '>';
				echo esc_html( $_sidebar_ary['name'] ) . '</option>' . "\n";
			}
			echo '</select>';
			?>
		</td>
		</tr>
		<tr valign="top">
			<th scope="row">2&deg; Ad Height</th>
			<td align="left" style="float:left;text-align:left;"><input type="number" min="100" max="2000" id="postmedia_layouts_secondary_ad_height" name="postmedia_layouts_secondary_ad_height" value="<?php echo esc_attr( $_secondary_ad_height ); ?>" style="float:left;" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">WCM Client Origin ID</th>
			<td align="left" style="float:left;text-align:left;"><input type="text" id="postmedia_layouts_wcm_origin_client" name="postmedia_layouts_wcm_origin_client" value="<?php echo esc_attr( $_wcm_origin_client ); ?>" style="float:left;width:500px;" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">WCM Client Origin Domain</th>
			<td align="left" style="float:left;text-align:left;"><input type="text" id="postmedia_layouts_wcm_origin_client_domain" name="postmedia_layouts_wcm_origin_client_domain" value="<?php echo esc_attr( $_wcm_origin_client_domain ); ?>" style="float:left;width:500px;" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Editors Can Choose Headline Colors</th>
			<td align="left" style="float:left;text-align:left;"><input type="checkbox" id="postmedia_layouts_choose_color" name="postmedia_layouts_choose_color" value="1" <?php echo checked( $_choose_color, 1, false ); ?> style="float:left;" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Site Uses Sidebar</th>
			<td align="left" style="float:left;text-align:left;"><input type="checkbox" id="postmedia_layouts_show_sidebar" name="postmedia_layouts_show_sidebar" value="1" <?php echo checked( $_show_sidebar, 1, false ); ?> style="float:left;" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Site Uses Baselist</th>
			<td align="left" style="float:left;text-align:left;"><input type="checkbox" id="postmedia_layouts_show_baselist" name="postmedia_layouts_show_baselist" value="1" <?php echo checked( $_show_baselist, 1, false ); ?> style="float:left;" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Site Uses WCM</th>
			<td align="left" style="float:left;text-align:left;"><input type="checkbox" id="postmedia_layouts_show_wcm" name="postmedia_layouts_show_wcm" value="1" <?php echo checked( $_show_wcm, 1, false ); ?> style="float:left;" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Site Uses Video Center</th>
			<td align="left" style="float:left;text-align:left;"><input type="checkbox" id="postmedia_layouts_show_vc" name="postmedia_layouts_show_vc" value="1" <?php echo checked( $_show_vc, 1, false ); ?> style="float:left;" /></td>
		</tr>
		<?php if ( Utilities::is_nexus() ) : ?>
			<tr valign="top">
				<th colspan="2">
					<h2>Home Page Outfit Settings</h2>
					<p class="help" style="font-weight:normal">Specifies outfits shown on the home page for each market size. If blank, all outfits will be shown for sites with that market size. Separate numbers by commas. eg. 1,2,4,6</p>
					<p class="help" style="font-weight:normal">Home page outfits can be set in Dashboard &gt; Home Layout.</p>
					<p class="help" style="font-weight:normal">Market size can be set in Settings &gt; Theme (Common).</p>
				</th>
			</tr>
			<tr valign="top">
				<th scope="row">Small Site Outfits</th>
				<td align="left" style="float:left;text-align:left;"><input type="text" id="postmedia_layouts_outfit_order_small" name="postmedia_layouts_outfit_order_small" value="<?php echo esc_attr( $_postmedia_layouts_outfit_order_small ); ?>" style="float:left;width:500px;" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Medium Site Outfits</th>
				<td align="left" style="float:left;text-align:left;"><input type="text" id="postmedia_layouts_outfit_order_medium" name="postmedia_layouts_outfit_order_medium" value="<?php echo esc_attr( $_postmedia_layouts_outfit_order_medium ); ?>" style="float:left;width:500px;" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Large Site Outfits</th>
				<td align="left" style="float:left;text-align:left;"><input type="text" id="postmedia_layouts_outfit_order_large" name="postmedia_layouts_outfit_order_large" value="<?php echo esc_attr( $_postmedia_layouts_outfit_order_large ); ?>" style="float:left;width:500px;" /></td>
			</tr>
			<tr valign="top">
				<th scope="row" id="inp-custom-video">Custom Playlist Player</th>
				<td align="left" style="float:left;text-align:left;">
					<input type="text" id="postmedia_layouts_custom_main_video" name="postmedia_layouts_custom_main_video" value="<?php echo esc_attr( $_postmedia_layouts_custom_main_video ); ?>" style="width:500px;" />
					<p class="description">This will override the first Playlist Player of the homepage. Enter the playlist ID here.</p>
				</td>
			</tr>
		<?php endif; ?>
		<tr valign="top">
			<th colspan="2"><h2>Chartbeat Settings</h2></th>
		</tr>
		<tr valign="top">
			<th scope="row">Host</th>
			<td><input type="text" name="postmedia_layouts_chartbeat_host" class="pmlayouts_textin" value="<?php echo esc_attr( get_option( 'postmedia_layouts_chartbeat_host' ) ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">API Key</th>
			<td><input type="text" name="postmedia_layouts_chartbeat_apikey" class="pmlayouts_textin" value="<?php echo esc_attr( get_option( 'postmedia_layouts_chartbeat_apikey' ) ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Sections</th>
			<td><textarea name="postmedia_layouts_chartbeat_sections" style="width:100%;height:120px;" wrap><?php echo esc_textarea( get_option( 'postmedia_layouts_chartbeat_sections' ) ); ?></textarea></td>
		</tr>
		</table>
		<?php submit_button(); ?>
		</form>
		</div>
		<?php
	}

	/**
	* Get a list of all widgets available to be inserted as is into an outfit - drawn from a sidebar
	* Could get all widgets from $wp_widget_factory but this is better
	*
	* @return array Widgets
	*/
	private function get_avail_widget_list() {
		global $wp_registered_widgets; // array of all widgets in all sidebars
		global $pn_teamscoreboard_teams;
		$_widgets_excluded = array( 'postmedia_layouts_ad', 'pn_dfpad' ); // removed pmlay_lists_widget
		$_output = array();
		// get the sidebar selected to hold these widgets
		$_widget_source = ( isset( $this->layouts->pmlay_count['widget_source'] ) ) ? trim( $this->layouts->pmlay_count['widget_source'] ) : '';
		if ( '' === $_widget_source ) {
			return $_output;
		}
		// if there is a selected sidebar then get all widgets
		$_widgets = get_option( 'sidebars_widgets' );
		if ( ( ! is_array( $_widgets ) ) && ( ! isset( $_widgets[ $_widget_source ] ) ) ) {
			return $_output;
		}
		// there are widgets in this selected sidebar
		foreach ( $_widgets[ $_widget_source ] as $_widget_slug ) {
			$_widget_title = '';
			if ( ! isset( $wp_registered_widgets[ $_widget_slug ] ) ) {
				continue;
			}
			if ( ! isset( $wp_registered_widgets[ $_widget_slug ]['callback'][0] ) ) {
				continue;
			}
			$_widget_id_base = $wp_registered_widgets[ $_widget_slug ]['callback'][0]->id_base;
			if ( in_array( $_widget_id_base, $_widgets_excluded, true ) ) {
				continue;
			}
			// some widgets are not allowed and are stripped out at this step - esp. Layouts' widget Lists
			$widgets_option_name = $wp_registered_widgets[ $_widget_slug ]['callback'][0]->option_name;
			$widgets_option_number = intval( $wp_registered_widgets[ $_widget_slug ]['params'][0]['number'] );
			$widgets_data = get_option( $widgets_option_name );
			if ( ( ! is_array( $widgets_data ) ) || ( ! isset( $widgets_data[ $widgets_option_number ] ) ) ) {
				continue;
			}
			if ( 'teamscoreboard' === $_widget_id_base ) {
				$_team_type = $widgets_data[ $widgets_option_number ]['sport-type'][0];
				$_widget_title = strtoupper( str_replace( '_teams', '', $_team_type ) );
				if ( isset( $pn_teamscoreboard_teams[ $_team_type ] ) ) {
					$_team_index = $widgets_data[ $widgets_option_number ]['team-index'];
					if ( isset( $pn_teamscoreboard_teams[ $_team_type ][ $_team_index ] ) ) {
						$_widget_title .= ' - ' . trim( $pn_teamscoreboard_teams[ $_team_type ][ $_team_index ] );
					}
				}
			} elseif ( isset( $widgets_data[ $widgets_option_number ]['title'] ) ) {
				if ( true === isset( $widgets_data[ $widgets_option_number ]['title'] ) ) {
					$_widget_title = trim( $widgets_data[ $widgets_option_number ]['title'] );
				}
			}
			if ( '' === $_widget_title ) {
				if ( true === isset( $widgets_data[ $widgets_option_number ]['header'] ) ) {
					$_widget_title = trim( $widgets_data[ $widgets_option_number ]['header'] );
				}
			}
			// prepend the widget type label
			$_widget_title = $wp_registered_widgets[ $_widget_slug ]['name'] . (  ( '' !== $_widget_title ) ? ': ' : '' ) . $_widget_title;
			$_output[ $_widget_slug ] = $_widget_title;
		}
		asort( $_output );
		return $_output;
	}

	private function get_list_types() {
		$_ary = array(
			'cat' => 'Category (+ subs)',
			'cax' => 'Category (only)',
			'tag' => 'Tag',
			'zon' => 'Zoninator',
			'ug' => 'Usergroup',
			'ugc' => 'Usergroup+Cat',
			'ugs' => 'Usergroup+Cat+Subs',
			'auth' => 'Author',
			'shar' => 'WCM Shared',
			'rss' => 'External Feed',
			'chrt' => 'Chartbeat',
		);
		// Adding custom list types through the filter
		$_ary = apply_filters( 'pn_layouts_add_custom_type', $_ary );
		$this->list_type = $_ary;
	}

	private function get_adv_types() {
		$_ary = array(
			'advertisement',
			'sponsored_by',
			'promoted_by',
			'presented_by',
		);
		// Filter the array of advertorial types
		$_ary = apply_filters( 'pn_layouts_set_adv_types', $_ary );
		$this->adv_type = $_ary;
	}

	private function get_list_styles() {
		$_ary = array(
			'arts' => 'Arts',
			'business' => 'Business',
			'driving' => 'Driving',
			'jobs' => 'Jobs',
			'life' => 'Life',
			'news' => 'News',
			'obits' => 'Obits',
			'opinion' => 'Opinion',
			'sports' => 'Sports',
		);
		// Filter the array of post list styles
		$_ary = apply_filters( 'pn_layouts_set_list_styles', $_ary );
		$this->list_style = $_ary;
	}

	/**
	* Retrieve one piece of data from a submitted form or query string and sanitize the hell out of it
	* Any call to this method will already have verified the nonce
	*
	* @param text $_mode type of request data post|get
	* @param text $_nonce_key input key of nonce value
	* @param text $_nonce_expected expected nonce value
	* @return array input data unslashed and sanitized, with nonce verification
	*/
	private function get_sanitized_input_data( $_mode = 'post', $_nonce_key = '', $_nonce_expected = '' ) {
		$_input = array();
		$_output = array();
		$_nonce_val = '';
		// handle nonce verification
		if ( '' !== $_nonce_key ) {
			if ( 'post' === $_mode ) {
				if ( isset( $_POST[ $_nonce_key ] ) ) {
					$_nonce_val = sanitize_text_field( wp_unslash( $_POST[ $_nonce_key ] ) ); // @codingStandardsIgnoreLine - Processing form data without nonce verification, chicken - egg - chicken
				}
			} elseif ( 'get' === $_mode ) {
				if ( isset( $_GET[ $_nonce_key ] ) ) {
					$_nonce_val = sanitize_text_field( wp_unslash( $_GET[ $_nonce_key ] ) );
				}
			}
		}
		if ( ( '' === $_nonce_key ) || ( wp_verify_nonce( $_nonce_val, $_nonce_expected ) ) ) {
			// no nonce required or nonce is valid
			if ( 'post' === $_mode ) {
				$_input = $_POST;
			} elseif ( 'get' === $_mode ) {
				$_input = $_GET;
			}
			if ( ! empty( $_input ) ) {
				foreach ( $_input as $_key => $_val ) {
					if ( ! is_array( $_val ) ) {
						$_output[ $_key ] = $this->sanitize_text_input( $_val );
					} else {
						$_output[ $_key ] = $_val; // handle array
					}
				}
			}
		}
		return $_output;
	}

	private function get_sanitized_input_single( $_key, $_data, $_type = 'text', $_default = '' ) {
		$field = null;
		if ( isset( $_data['postmedia_layouts_default_outfits'] ) ) {
			$field = 'postmedia_layouts_default_outfits';
		}
		if ( $field ) {
			$_data[ $field ] = Utilities::flatten( $_data[ $field ], $field );
			$_data = array_merge( $_data, $_data[ $field ] );
			unset( $_data[ $field ] );
		}
		$_output = $_default;
		if ( isset( $_data[ $_key ] ) ) {
			if ( 'int' === $_type ) {
				$_output = intval( $_data[ $_key ] );
			} else {
				$_output = sanitize_text_field( $_data[ $_key ] );
			}
		}
		return $_output;
	}
}
