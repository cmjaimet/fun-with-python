<?php

use Postmedia\Web\Theme\TermMeta;

class PostmediaLayoutsTermMeta extends TermMeta {

	/**
	 * List of taxonomies.
	 * @var array
	 */
	protected $taxonomies = array( 'category', 'post_tag' );

	/**
	 * Section title.
	 * @var string
	 */
	protected $title = 'Layouts Settings';

	/**
	 * Registers fields.
	 */
	protected function register_fields() {
		$this->add_field( 'text', 'pmlayouts_override', 'Override', array( 'help' => 'Override the source for all outfits on this page using a feed URL or a WCM list ID.' ) );
		$this->add_field( 'text', 'pmlayouts_playlist_player', 'Playlist Player', array( 'help' => 'This will override the first Playlist Player of the content widget. Enter the playlist ID here.' ) );
	}
}

if ( get_option( 'wcm_multi_market', false ) ) {
	new PostmediaLayoutsTermMeta();
}
