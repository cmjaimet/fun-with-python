<?php

class PostmediaLayoutsConfiguration {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'configuration_serialize_option_value', array( $this, 'filter_configuration_serialize_option_value' ), 10, 2 );
		add_filter( 'configuration_unserialize_option_value', array( $this, 'filter_configuration_unserialize_option_value' ), 10, 2 );
	}

	/**
	 * @param  mixed  $option_value
	 * @param  string $option_name
	 * @return mixed
	 */
	public function filter_configuration_serialize_option_value( $option_value, $option_name ) {
		if ( 0 === strpos( $option_name, 'pmlayouts_lists_' ) ) {
			if ( Postmedia\Web\Utilities::is_json( $option_value ) ) {
				return $option_value;
			}
			return wp_json_encode( $option_value );
		}
		return $option_value;
	}

	/**
	 * @param  mixed  $option_value
	 * @param  string $option_name
	 * @return mixed
	 */
	public function filter_configuration_unserialize_option_value( $option_value, $option_name ) {
		if ( 0 === strpos( $option_name, 'pmlayouts_lists_' ) ) {
			return json_decode( $option_value, true );
		}
		return $option_value;
	}
}

new PostmediaLayoutsConfiguration();
