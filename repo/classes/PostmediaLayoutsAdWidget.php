<?php
/**
* Don't load this file directly
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'widgets_init', 'postmedia_layouts_register_ad_widget' );

/**
* Register the widget
*
* @return null
*/
function postmedia_layouts_register_ad_widget() {
	register_widget( 'PostmediaLayoutsAdWidget' );
}

class PostmediaLayoutsAdWidget extends WP_Widget {

	/**
	* Fire up the widget
	*
	* @return null
	*/
	function __construct() {
		parent::__construct(
			'postmedia_layouts_ad',
			'Layouts Ad',
			array( 'description' => __( 'Layouts Ad', 'postmedia' ) )
		);
	}

	/**
	* Display the widget
	*
	* @param $args array additional data for widget display (before_widget,after_widget)
	* @param $instance array instance data for widget
	* @return null
	*/
	function widget( $a_widgets, $a_attr ) {
		// this widget is just a placeholder that Layouts strips out and replaces with a DFP bigbox ad according to its internal rules
		global $postmedia_layouts;
		$_kses = array(
			'div' => array(
				'id'    => array(),
				'class' => array(),
			),
			'span' => array(
				'id'    => array(),
				'class' => array(),
			),
		);

		$max_height = $a_attr['max_height'];
		$is_sticky  = ( false === Postmedia\Web\Utilities::is_mobile() ) && ( $max_height > 0 );

		if ( $is_sticky ) {
			echo '<div class="widget-sticky" style="height:' . esc_attr( $max_height ) . 'px;">';
		}

		echo ( isset( $a_widgets['before_widget'] ) ) ? wp_kses( $a_widgets['before_widget'], $_kses ) : '';
		echo esc_html( $postmedia_layouts->widget_placeholder_text );
		echo ( isset( $a_widgets['after_widget'] ) ) ? wp_kses( $a_widgets['after_widget'], $_kses ) : '';

		if ( $is_sticky ) {
			echo '</div>';
		}
	}

	/**
	* Form to manage the data in the widget - no configuration for this widget, handled entirely by Layouts
	*
	* @param $instance array instance data for widget
	* @return null
	*/
	public function form( $instance ) {
	}

	/**
	* Update the widget - no data to update
	*
	* @param $new_instance array new instance data for widget from submitted form
	* @param $old_instance array old instance data for widget from database
	* @return array $new_instance
	*/
	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}
}
