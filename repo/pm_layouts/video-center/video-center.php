<?php
use Postmedia\Web\Content;
use Postmedia\Web\Utilities;

$first_video = PostmediaLayoutsVideoCenter::$first_video;
?>
<div class="video-center-placeholder"></div>
<div class="video-center <?php echo esc_attr( Utilities::is_mobile() ? 'mobile' : '' ); ?>">
	<div class="sticky-video-container">
		<div id="youtube-video" data-id="<?php echo esc_attr( $first_video->video_id ); ?>" data-origin="<?php echo esc_attr( site_url() ); ?>"></div>
	</div>
	<ul class="youtube-data">
		<li class="title"><h3><a href="<?php echo esc_url( $first_video->url ); ?>" target="_blank"><?php echo esc_html( $first_video->post_title ); ?></a></h3></li>
		<li class="show-details">Show Video Details<div class="arrow"></div></li>
		<li class="hide-details">Hide Video Details<div class="arrow"></div></li>
		<li class="excerpt"><?php echo esc_html( $first_video->post_excerpt ); ?></li>
		<li class="author">By <?php echo esc_html( $first_video->post_author ); ?></li>
		<li class="date">Published <?php echo esc_html( $first_video->formatted_date ); ?></li>
		<?php
		if ( isset( $first_video->related_links ) ) {
			echo '<li class="related">Related: ';
			foreach ( $first_video->related_links as $count => $rl ) {
				echo '<a href="' . esc_url( $rl->url ) . '">' . esc_html( $rl->text ) . '</a>';

				if ( isset( $first_video->related_links[ $count + 1 ] ) ) {
					echo ', ';
				}
			}
			echo '</li>';
		}
		?>
		<?php if ( true === apply_filters( 'display_video_center_sharing', false ) ) : ?>
		<ul class="social-bar">
			<ul class="icons">
				<li class="twitter first">
					<a href="#" data-url="https://twitter.com/share?url=" title="Twitter"></a>
				</li>
				<li class="facebook">
					<a href="#" data-url="https://www.facebook.com/sharer/sharer.php?u=" title="Facebook"></a>
				</li>
				<li class="email">
					<a href="#" data-url="" title="Email"></a>
				</li>
			</ul>
		</ul>
		<?php endif; ?>
	</ul>
	<div class="close"></div>
</div>
