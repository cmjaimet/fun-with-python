<?php

use Postmedia\Web\Content;
use Postmedia\Web\Utilities;

global $postmedia_layouts;

$hidden_posts = false;
$vc_enabled = PostmediaLayoutsVideoCenter::vc_enabled();

$header = $postmedia_layouts->show_header( false );
if ( empty( $header ) && isset( $postmedia_layouts->pmlay_settings['list_name'] ) ) {
	$header = $postmedia_layouts->pmlay_settings['list_name'];
}

$allowed = array(
	'a' => array(
		'href' => array(),
	),
);
if ( ! empty( $header ) ) {
	echo '<h2>' . wp_kses( $header, $allowed ) . '</h2>';
}

foreach ( $postmedia_layouts->pmlay_settings['list'] as $count => $layouts_post ) {
	if ( $count >= $postmedia_layouts->pmlay_settings['max_posts'] ) {
		break;
	}

	if ( Utilities::is_mobile() ) {
		$article_class = 'sixcol';
		$article_class .= 0 === $count % 2 ? ' first' : '';
		$article_class .= 1 === $count % 2 ? ' last' : '';
	} else {
		$article_class = 'threecol';
		$article_class .= 0 === $count % 4 ? ' first' : '';
		$article_class .= 3 === $count % 4 ? ' last' : '';
	}

	$layouts_post = $postmedia_layouts->setup_post( $layouts_post );
	$media = $postmedia_layouts->get_post_media( $layouts_post );
	$content = pn_post_to_content( $layouts_post );
	$tracking_val = postmedia_theme()->event_tracking()->get_data();

	$date = isset( $content->data['modified_on'] ) ? $content->data['modified_on'] : '';
	if ( empty( $date ) ) {
		$date = isset( $content->data['published_on'] ) ? $content->data['published_on'] : '';
	}

	if ( 4 <= $count && 0 === $count % 4 ) {
		echo '<div class="twelvecol row hidden">';
		$hidden_posts = true;
	} else if ( 0 === $count % 4 ) {
		echo '<div class="twelvecol row">';
	}

	// VC turned on: make sure posts with featured videos act link and act correctly.
	if ( isset( $media->video->id ) && ! empty( $media->video->id ) && $vc_enabled ) {
		$content->origin->url = 'https://www.youtube.com/watch?v=' . $media->video->id;
	}

	// If post has YT URL, add class to highlight playable.
	if ( false !== strpos( $content->url(), 'youtube.com' ) ) {
		$article_class .= ' has-video';
	}
	?>

	<article class="<?php echo esc_attr( $article_class ); ?>" itemscope itemtype="http://schema.org/NewsArticle" data-event-tracking="<?php echo esc_attr( $tracking_val ); ?>">
		<?php
		// If YT post, get image link without resizing.
		if ( 'youtube_video' === $layouts_post->post_type ) {
			$image = $content->featured_image();
		} else {
			$image = $content->featured_image( 'small' );
		}

		if ( isset( $image->url ) ) {
		?>
		<figure class="thumbnail">
			<a href="<?php echo esc_url( $content->url() ); ?>">
				<img src="<?php echo esc_url( $image->url ); ?>">
			</a>
		</figure>
		<?php } ?>

		<span class="article-author-time-ago">
			<?php
			// Formatting the date so it returns in a format that WPE can understand
			$date = str_replace( '.v', '.000', $date );
			echo esc_html( human_time_diff( strtotime( $date ), current_time( 'timestamp' ) ) . ' ago' );
			?>
		</span>

		<?php
		$title = isset( $content->data['titles']['main'] ) ? $content->data['titles']['main'] : $layouts_post->post_title;
		echo '<a href="' . esc_url( $content->url() ) . '"><h4 class="article-title">' . esc_html( $title ) . '</h4></a>';
		?>
	</article>

	<?php
	// 4th item in each row, we've reached max # of desired posts,
	// OR we've reached max # of available posts and available posts is less than desired posts... close row div.
	$available_posts = count( $postmedia_layouts->pmlay_settings['list'] );
	$maximum_posts = $postmedia_layouts->pmlay_settings['max_posts'];

	if ( 3 === $count % 4 || $count + 1 === $maximum_posts ||
	( $available_posts === $count + 1 && $available_posts < $maximum_posts ) ) {
		echo '</div>';
	}
}

$button_text = $postmedia_layouts->pmlay_settings['button_label'];
$button_link = $postmedia_layouts->pmlay_settings['button_link'];

// Provide both in backend and you'll get a custom button regardless of if there are more posts to show.
if ( ! empty( $button_text ) && ! empty( $button_link ) ) {
	echo '<div class="row-bottom">';
	echo '<a href="' . esc_url( $button_link ) . '" class="list-button">' . esc_html( $button_text ) . '</a>';
	echo '</div>';
} else if ( $hidden_posts ) {
	$button_text = ! empty( $button_text ) ? $button_text : 'Load More';
	echo '<div class="row-bottom">';
	echo '<a href="#" class="list-button load-more">' . esc_html( $button_text ) . '</a>';
	echo '</div>';
}
