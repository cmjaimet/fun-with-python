<?php
/**
 * Section: Horizontal lists.
 *
 * Description: 4 horizontal lists.
 */

global $postmedia_layouts;
$sponsored = $postmedia_layouts->get_sponsored_keyword();
?>

<section id="section_<?php echo esc_attr( $postmedia_layouts->pmlay_count['display_id'] ); ?>">
	<div class="outfit horizontal-lists mobile row clearfix">

		<?php if ( ! empty( $sponsored['adv_company'] ) ) { ?>
			<div class="sponsor-logo">
				<?php if ( ! empty( $sponsored['adv_url'] ) && ! empty( $sponsored['adv_logo'] ) ) { ?>
					<a href="<?php echo esc_url( $sponsored['adv_url'] ); ?>" target="_blank">
						<img src="<?php echo esc_url( $sponsored['adv_logo'] ); ?>">
					</a>
				<?php } ?>
			</div>
			<div class="sponsored">
				<div class="sponsored-by">
					<i class="fas fa-info-circle"></i>
					<span>
						<?php echo esc_html( str_replace( '_', ' ', $sponsored['adv_abbr'] ) ); ?>
						<a href="<?php echo esc_url( $sponsored['adv_url'] ); ?>" target="_blank">
							<?php echo esc_html( $sponsored['adv_company'] ); ?>
						</a>
					</span>
				</div>
			</div>
		<?php } ?>

		<div class="list-row">
			<?php
			$_args = array(
				'template' => 'hl',
				'posts_max' => 12,
			);
			$postmedia_layouts->display_list( $_args );
			?>
		</div>
		<div class="list-row">
			<?php
			$_args = array(
				'template' => 'hl',
				'posts_max' => 12,
			);
			$postmedia_layouts->display_list( $_args );
			?>
		</div>
		<div class="list-row">
			<?php
			$_args = array(
				'template' => 'hl',
				'posts_max' => 12,
			);
			$postmedia_layouts->display_list( $_args );
			?>
		</div>
		<div class="list-row">
			<?php
			$_args = array(
				'template' => 'hl',
				'posts_max' => 12,
			);
			$postmedia_layouts->display_list( $_args );
			?>
		</div>
	</div>
</section>
