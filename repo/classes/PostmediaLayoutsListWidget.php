<?php
/**
* Don't load this file directly
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'widgets_init', 'postmedia_layouts_register_list_widget' );

/**
* Register the widget
*
* @return null
*/
function postmedia_layouts_register_list_widget() {
	register_widget( 'PostmediaLayoutsListWidget' );
}

class PostmediaLayoutsListWidget extends WP_Widget {

	private $widget_count = 0;

	/**
	* Fire up the widget
	*
	* @return null
	*/
	function __construct() {
		parent::__construct(
			'pmlay_lists_widget',
			'Lists',
			array( 'description' => __( 'Lists Widget', 'postmedia' ) )
		);
	}

	/**
	* Display the widget
	*
	* @param $args array additional data for widget display (before_widget,after_widget)
	* @param $instance array instance data for widget
	* @return null
	*/
	public function widget( $args, $instance ) {
		global $postmedia_layouts;
		$_kses = array(
			'div' => array(
				'id' => array(),
				'class' => array(),
			),
			'span' => array(
				'id' => array(),
				'class' => array(),
			),
		);
		$this->widget_count++;
		// acquire and validate all data
		$checked = $instance['advertorial_check_box'] ? 'true' : 'false';
		$_header = isset( $instance['header'] ) ? trim( $instance['header'] ) : '';
		$_footer = isset( $instance['footer'] ) ? trim( $instance['footer'] ) : '';
		$_header_target = isset( $instance['header_target'] ) ? intval( $instance['header_target'] ) : 0;
		$_footer_target = isset( $instance['footer_target'] ) ? intval( $instance['footer_target'] ) : 0;
		$_deprac_url = isset( $instance['url'] ) ? trim( $instance['url'] ) : '';
		$_header_url = isset( $instance['header_url'] ) ? trim( $instance['header_url'] ) : '';
		$_footer_url = isset( $instance['footer_url'] ) ? trim( $instance['footer_url'] ) : '';
		if ( '' === $_footer_url ) {
			// backward compatibility to move old url to new footer_url
			$_footer_url = $_deprac_url;
		}
		$_height = isset( $instance['height'] ) ? intval( $instance['height'] ) : 0;
		$_use_labels = isset( $instance['use_labels'] ) ? intval( $instance['use_labels'] ) : 0;
		$_module = isset( $instance['module'] ) ? intval( $instance['module'] ) : 0;
		$_link_out = isset( $instance['link_out'] ) ? intval( $instance['link_out'] ) : 0;
		$_count = isset( $instance['count'] ) ? intval( $instance['count'] ) : 4;
		$_type = isset( $instance['type'] ) ? $instance['type'] : 'cat';
		$_adv_type = isset( $instance['adv_type'] ) ? $instance['adv_type'] : '';
		$_adv_comp_name = isset( $instance['adv_comp_name'] ) ? trim( $instance['adv_comp_name'] ) : '';
		$_adv_logo_label = ucfirst( str_replace( '_', ' ', $_adv_type ) );
		$_adv_company_image = isset( $instance['adv_company_image'] ) ? trim( $instance['adv_company_image'] ) : '';
		$_adv_info_box = isset( $instance['adv_info_box'] ) ? trim( $instance['adv_info_box'] ) : '';
		$_adv_logo_click_url = isset( $instance['adv_logo_click_url'] ) ? $instance['adv_logo_click_url'] : '';
		$_listid = isset( $instance['listid'] ) ? $instance['listid'] : '';
		// apply data to Layouts object properties
		$postmedia_layouts->pmlay_settings['module_header'] = $_header;
		$postmedia_layouts->pmlay_settings['module_footer'] = $_footer;
		$postmedia_layouts->pmlay_settings['module_head_target'] = $_header_target;
		$postmedia_layouts->pmlay_settings['module_foot_target'] = $_footer_target;
		$postmedia_layouts->pmlay_settings['module_head_url'] = $_header_url;
		$postmedia_layouts->pmlay_settings['module_foot_url'] = $_footer_url;
		$postmedia_layouts->pmlay_settings['module_url'] = $_url;
		$postmedia_layouts->pmlay_settings['module_link_out'] = $_link_out;
		$postmedia_layouts->pmlay_settings['height'] = $_height;
		$postmedia_layouts->pmlay_settings['use_labels'] = $_use_labels;
		$postmedia_layouts->pmlay_settings['checked'] = $checked;
		$postmedia_layouts->pmlay_settings['adv_type'] = ( 'true' === $checked ) ? $_adv_type : '';
		$postmedia_layouts->pmlay_settings['adv_comp_name'] = $_adv_comp_name;
		$postmedia_layouts->pmlay_settings['adv_logo_label'] = $_adv_logo_label;
		$postmedia_layouts->pmlay_settings['adv_company_image'] = $_adv_company_image;
		$postmedia_layouts->pmlay_settings['adv_info_box'] = $_adv_info_box;
		$postmedia_layouts->pmlay_settings['adv_logo_click_url'] = $_adv_logo_click_url;
		$postmedia_layouts->pmlay_settings['_listid'] = $_listid;
		$postmedia_layouts->pmlay_settings['list_type'] = $_type;
		$postmedia_layouts->pmlay_settings['max_posts'] = $_count;
		$postmedia_layouts->pmlay_count['widget'] = $_module;
		// render the widget
		echo ( isset( $args['before_widget'] ) ) ? wp_kses( $args['before_widget'], $_kses ) : '';
		$postmedia_layouts->choose_template( 'widget', $_module, true, true, false, true );
		echo ( isset( $args['after_widget'] ) ) ? wp_kses( $args['after_widget'], $_kses ) : '';
	}

	/**
	* Form to manage the data in the widget
	*
	* @param $instance array instance data for widget
	* @return null
	*/
	public function form( $instance ) {
		global $postmedia_layouts, $postmedia_layouts_admin;
		$_header = isset( $instance['header'] ) ? trim( $instance['header'] ) : '';
		$_footer = isset( $instance['footer'] ) ? trim( $instance['footer'] ) : '';
		$_header_target = isset( $instance['header_target'] ) ? intval( $instance['header_target'] ) : 0;
		$_footer_target = isset( $instance['footer_target'] ) ? intval( $instance['footer_target'] ) : 0;
		$_deprac_url = isset( $instance['url'] ) ? trim( $instance['url'] ) : '';
		$_header_url = isset( $instance['header_url'] ) ? trim( $instance['header_url'] ) : '';
		$_footer_url = isset( $instance['footer_url'] ) ? trim( $instance['footer_url'] ) : '';
		if ( '' === $_footer_url ) {
			// backward compatibility to move old url to new footer_url
			$_footer_url = $_deprac_url;
		}
		$_height = isset( $instance['height'] ) ? intval( $instance['height'] ) : 0;
		$_use_labels = isset( $instance['use_labels'] ) ? intval( $instance['use_labels'] ) : 0;
		$_module = isset( $instance['module'] ) ? intval( $instance['module'] ) : 0;
		$_count = isset( $instance['count'] ) ? intval( $instance['count'] ) : 4;
		$_link_out = isset( $instance['module'] ) ? intval( $instance['link_out'] ) : 0;
		$_type = isset( $instance['type'] ) ? $instance['type'] : 'cat';
		$_list_id = isset( $instance['listid'] ) ? $instance['listid'] : '';
		$_list_term = $postmedia_layouts->get_termname( $_type, $_list_id );
		if ( ( isset( $instance['advertorial_check_box'] ) ) && ( 'true' === $instance['advertorial_check_box'] ) ) {
			$checked = 'true';
		} else {
			$checked = 'false';
		}
		$adv_div_style = ( ( isset( $instance['advertorial_check_box'] ) ) && ( 'true' === $instance['advertorial_check_box'] ) ) ? 'display:block;' : 'display:none;';
		$_adv_type = isset( $instance['adv_type'] ) ? $instance['adv_type'] : '';
		$_adv_comp_name = isset( $instance['adv_comp_name'] ) ? trim( $instance['adv_comp_name'] ) : '';
		$_adv_company_image = isset( $instance['adv_company_image'] ) ? trim( $instance['adv_company_image'] ) : '';
		$_adv_info_box = isset( $instance['adv_info_box'] ) ? trim( $instance['adv_info_box'] ) : '';
		$_adv_logo_click_url = isset( $instance['adv_logo_click_url'] ) ? $instance['adv_logo_click_url'] : '';
		$_field = $this->get_field_id( 'listid' );
		wp_nonce_field( PM_LAYOUT_URI, 'pm_layout_noncename' );

		echo '<table>' . "\n";

		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'header' ) ) . '">Header</label></td>' . "\n";
		echo '<td><input type="text" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" name="' . esc_attr( $this->get_field_name( 'header' ) ) . '" value="' . esc_attr( $_header ) . '" class="pmlay_input" /></td>';
		echo '</tr>' . "\n";
		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'header_url' ) ) . '">URL</label></td>' . "\n";
		echo '<td><input type="text" id="' . esc_attr( $this->get_field_id( 'header_url' ) ) . '" name="' . esc_attr( $this->get_field_name( 'header_url' ) ) . '" value="' . esc_attr( $_header_url ) . '" class="pmlay_input" /></td>';
		echo '</tr>' . "\n";
		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'header_target' ) ) . '">New tab</label></td>' . "\n";
		echo '<td><input type="checkbox" id="' . esc_attr( $this->get_field_id( 'header_target' ) ) . '" name="' . esc_attr( $this->get_field_name( 'header_target' ) ) . '" value="1" ' . checked( 1, $_header_target, false ) . 'class="checkbox pm_layouts_advertorial_check_box" /></td>';
		echo '</tr>' . "\n";

		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'footer' ) ) . '">Footer</label></td>' . "\n";
		echo '<td><input type="text" id="' . esc_attr( $this->get_field_id( 'footer' ) ) . '" name="' . esc_attr( $this->get_field_name( 'footer' ) ) . '" value="' . esc_attr( $_footer ) . '" class="pmlay_input" /></td>';
		echo '</tr>' . "\n";
		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'footer_url' ) ) . '">URL</label></td>' . "\n";
		echo '<td><input type="text" id="' . esc_attr( $this->get_field_id( 'footer_url' ) ) . '" name="' . esc_attr( $this->get_field_name( 'footer_url' ) ) . '" value="' . esc_attr( $_footer_url ) . '" class="pmlay_input" /></td>';
		echo '</tr>' . "\n";
		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'footer_target' ) ) . '">New tab</label></td>' . "\n";
		echo '<td><input type="checkbox" id="' . esc_attr( $this->get_field_id( 'footer_target' ) ) . '" name="' . esc_attr( $this->get_field_name( 'footer_target' ) ) . '" value="1" ' . checked( 1, $_footer_target, false ) . 'class="checkbox pm_layouts_advertorial_check_box" /></td>';
		echo '</tr>' . "\n";

		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'height' ) ) . '">Height</label></td>' . "\n";
		echo '<td><input type="text" id="' . esc_attr( $this->get_field_id( 'height' ) ) . '" name="' . esc_attr( $this->get_field_name( 'height' ) ) . '" value="' . esc_attr( $_height ) . '" class="pmlay_input" /></td>';
		echo '</tr>' . "\n";

		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'use_labels' ) ) . '">Labels</label></td>' . "\n";
		echo '<td><select id="' . esc_attr( $this->get_field_id( 'use_labels' ) ) . '" name="' . esc_attr( $this->get_field_name( 'use_labels' ) ) . '">';
		echo '<option value="0" ' . selected( $_use_labels, 0, false ) . '>-- None --</option>' . "\n";
		echo '<option value="1" ' . selected( $_use_labels, 1, false ) . '>Show Categories</option>' . "\n";
		echo '</select></td>' . "\n";
		echo '</tr>' . "\n";

		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'module' ) ) . '">Module</label></td>' . "\n";
		echo '<td><select id="' . esc_attr( $this->get_field_id( 'module' ) ) . '" name="' . esc_attr( $this->get_field_name( 'module' ) ) . '">';
		foreach ( $postmedia_layouts->widget_settings as $_key => $_ary ) {
			echo '<option value="' . esc_attr( $_key ) . '" ' . selected( $_module, $_key, false ) . '>' . esc_html( $_ary[0] ) . '</option>' . "\n";
		}
		echo '</select></td>' . "\n";
		echo '</tr>' . "\n";

		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'link_out' ) ) . '">Link Out</label></td>' . "\n";
		echo '<td><input type="checkbox" id="' . esc_attr( $this->get_field_id( 'link_out' ) ) . '" name="' . esc_attr( $this->get_field_name( 'link_out' ) ) . '" value="1" ' . checked( 1, $_link_out, false ) . 'class="checkbox pm_layouts_advertorial_check_box" /></td>';
		echo '</tr>' . "\n";

		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'count' ) ) . '"># Posts</label></td>' . "\n";
		echo '<td><select id="' . esc_attr( $this->get_field_id( 'count' ) ) . '" name="' . esc_attr( $this->get_field_name( 'count' ) ) . '">';
		for ( $x = 1; $x <= 20; $x++ ) {
			echo '<option value="' . intval( $x ) . '" ' . selected( $_count, $x, false ) . '>' . intval( $x ) . '</option>';
		}
		echo '</select></td>' . "\n";
		echo '</tr>' . "\n";

		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $this->get_field_id( 'type' ) ) . '">Type</label></td>' . "\n";
		echo '<td><select id="' . esc_attr( $this->get_field_id( 'type' ) ) . '" name="' . esc_attr( $this->get_field_name( 'type' ) ) . '" class="pmlay_type" onChange="pm_lookup_terms( this, \'sel\' )">';
		foreach ( $postmedia_layouts_admin->list_type as $_key => $_val ) {
			echo '<option value="' . esc_attr( $_key ) . '" ' . selected( $_type, $_key, false ) . '>' . esc_html( $_val ) . '</option>' . "\n";
		}
		echo '</select></td>' . "\n";
		echo '</tr>' . "\n";

		echo '<tr>' . "\n";
		echo '<td><label for="' . esc_attr( $_field ) . '">List</label></td>' . "\n";
		echo '<td><input type="text" name="pmlay_search_' . esc_attr( $_field ) . '" id="' . esc_attr( $_field ) . '" class="pmlay_search pmlay_input" value="" ';
		echo ' onkeyup="pm_lookup_terms( this, \'kw\' )" /></td>';
		echo '</tr>' . "\n";

		echo '</table>' . "\n";

		echo '<div class="pmlay_widget_listid">';
		echo '<input type="hidden" id="pmlay_id_' . esc_attr( $_field ) . '" name="' . esc_attr( $this->get_field_name( 'listid' ) ) . '" class="pmlay_id" value="' . esc_attr( $_list_id ) . '" />';
		echo '<div id="pmlay_show_' . esc_attr( $_field ) . '" class="pmlay_show">' . ( ( '' == $_list_term ) ? '' : '<span>' . esc_html( $_list_term ) . '</span>' ) . '</div>' . "\n";
		echo '</div>';
		echo '<div id="pmlay_opts_' . esc_attr( $_field ) . '" class="pmlay_opts"></div>';
		echo '<div style="clear:both;">';

		// Advertorial Stuff
		echo '<input class="checkbox pm_layouts_advertorial_check_box"
			id="' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '"
			name="' . esc_attr( $this->get_field_name( 'advertorial_check_box' ) ) . '"
			type="checkbox" value="true" ' . checked( 'true', $checked, false ) . '
			onClick="javascript:pm_layouts_display_advertorial_form( \'' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '\' );" />';
		echo '<label for="' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '">This List is an advertorial </label>';
		echo '</div>';
		echo '<div id="div_' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '" class="adv_listing" style="' . esc_html( $adv_div_style ) . 'background:#f5f5f5; padding:5px;"><h3>Advertorial Settings</h3>';
		echo '<p><label for="' . esc_attr( $this->get_field_id( 'adv_type' ) ) . '">Adv Type</label>' . "\n";
		echo '<select id="' . esc_attr( $this->get_field_id( 'adv_type' ) ) . '" name="' . esc_attr( $this->get_field_name( 'adv_type' ) ) . '" class="pmlay_type">';
		echo '<option value="" selected="selected">Please select type</option>';
		foreach ( $postmedia_layouts_admin->adv_type as $_slug ) {
			$_val = ucfirst( str_replace( '_', ' ', $_slug ) );
			echo '<option value="' . esc_attr( $_slug ) . '" ' . selected( $_adv_type, $_slug, false ) . '>' . esc_html( $_val ) . '</option>' . "\n";
		}
		echo '</select></p>' . "\n";

		echo '<p><label for="' . esc_attr( $this->get_field_id( 'adv_comp_name' ) ) . '">Company Name</label>' . "\n";
		echo '<input type="text" id="' . esc_attr( $this->get_field_id( 'adv_comp_name' ) ) . '" name="' . esc_attr( $this->get_field_name( 'adv_comp_name' ) ) . '" value="' . esc_attr( $_adv_comp_name ) . '" class="pmlay_input" />';
		echo '</p>' . "\n";

		echo '<p><label for="' . esc_attr( $this->get_field_id( 'adv_info_box' ) ) . '">Info Box</label>' . "\n";
		echo '<textarea class="large-text pmlay_input" cols="50" rows="5" id="' . esc_attr( $this->get_field_id( 'adv_info_box' ) ) . '" name="' . esc_attr( $this->get_field_name( 'adv_info_box' ) ) . '">' . esc_textarea( $_adv_info_box ) . '</textarea>';
		echo '</p>' . "\n";

		echo '<p><input type="text"
			id="imginp_' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '"
			name="' . esc_attr( $this->get_field_name( 'adv_company_image' ) ) . '"
			value="' . esc_url( $_adv_company_image ) . '" class="adv_logo_url pmlay_input" />';
		echo '</p>' . "\n";

		echo '<p><label for="' . esc_attr( $this->get_field_id( 'adv_logo_click_url' ) ) . '">Logo URL</label>' . "\n";
		echo '<input type="text" id="' . esc_attr( $this->get_field_id( 'adv_logo_click_url' ) ) . '" name="' . esc_attr( $this->get_field_name( 'adv_logo_click_url' ) ) . '" value="' . esc_url( $_adv_logo_click_url ) . '" class="pmlay_input" />';
		echo '</p>' . "\n";

		echo '<button class="list-adv-logo-button button-primary button" type="button"
			id="imgon_' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '"
			onClick="pm_layouts_get_advertorial_image(\'' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '\')"
			>Choose Image</button> ';
		echo '<button class="list-adv-remove-button button-primary button" type="button"
			id="imgoff_' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '"
			onClick="pm_layouts_rem_advertorial_image(\'' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '\')"
			>Remove Image</button>';

		echo '<div class="adv-logo-image" style="margin-top:10px;"
			id="imgdiv_' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '"
			>';
		echo '<img src="' . esc_url( $_adv_company_image ) . '" width="200px" height="150px"
			' . ( ( ! $_adv_company_image ) ? ' style="display:none;"' : '' ) . '
			id="pn_imgadv_' . esc_attr( $this->get_field_id( 'advertorial_check_box' ) ) . '"
			>';
		echo '</div>';
		echo '</div>';// Advertorial Stuff
	}

	/**
	* Update the widget
	*
	* @param $new_instance array new instance data for widget from submitted form
	* @param $old_instance array old instance data for widget from database
	* @return array revised $instance
	*/
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['header'] = wp_strip_all_tags( $new_instance['header'] );
		$instance['footer'] = wp_strip_all_tags( $new_instance['footer'] );
		$instance['header_url'] = wp_strip_all_tags( $new_instance['header_url'] );
		$instance['footer_url'] = wp_strip_all_tags( $new_instance['footer_url'] );
		$instance['header_target'] = intval( $new_instance['header_target'] );
		$instance['footer_target'] = intval( $new_instance['footer_target'] );
		$instance['url'] = '';
		$instance['height'] = intval( $new_instance['height'] );
		$instance['use_labels'] = intval( $new_instance['use_labels'] );
		$instance['module'] = wp_strip_all_tags( $new_instance['module'] );
		$instance['link_out'] = intval( $new_instance['link_out'] );
		$instance['count'] = wp_strip_all_tags( $new_instance['count'] );
		$instance['type'] = wp_strip_all_tags( $new_instance['type'] );
		$instance['adv_type'] = wp_strip_all_tags( $new_instance['adv_type'] );
		$instance['adv_comp_name'] = wp_strip_all_tags( $new_instance['adv_comp_name'] );
		$instance['adv_company_image'] = wp_strip_all_tags( $new_instance['adv_company_image'] );
		$instance['adv_info_box'] = wp_strip_all_tags( $new_instance['adv_info_box'] );
		$instance['adv_logo_click_url'] = wp_strip_all_tags( $new_instance['adv_logo_click_url'] );
		$instance['listid'] = wp_strip_all_tags( $new_instance['listid'] );
		$instance['advertorial_check_box'] = $new_instance['advertorial_check_box'];
		$alloptions = wp_cache_get( 'alloptions', 'options' );
		return $instance;
	}
}
