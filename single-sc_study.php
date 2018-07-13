<?php
/**
 * The Template for displaying all single posts.
 *
 * @package    WordPress
 * @subpackage BuddyBoss
 * @since      BuddyBoss 3.0
 */

get_header();

$elements = new WP_Query( array(
	'post_type'      => 'sc_study',
	'post_parent'    => get_the_ID(),
	'meta_key'       => '_sc_data_type',
	'posts_per_page' => 9999,
	'orderby'        => 'menu_order',
	'order'          => 'ASC',
) );

$study_id = sc_get_study_id();

get_comments(); ?>

<div class="row">
	<div id="buddypress" class="small-12 columns" role="main">
		<div id="content" role="main">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php sc_study_navigation(); ?>

				<div class="row">
					<?php if ( has_post_thumbnail( $study_id ) ) : ?>
						<div class="medium-9 small-centered small-12 columns">

							<div class="row">
								<div class="medium-3 columns">
									<p class="text-center"><?php echo get_the_post_thumbnail( $study_id, 'large' ); ?></p>
								</div>

								<div class="medium-9 columns">
									<h4 style="margin-bottom:0; color:#999;"><?php echo get_the_title( $study_id ); ?> <?php if ( current_user_can( 'edit_post', $study_id ) ) : ?>
											<span class="small">(<a href="/study-edit/?action=edit&study=<?php echo absint( $study_id ); ?>"><?php _e( 'Edit this study', 'sc' ); ?></a>)
											</span><?php endif; ?></h4>
									<?php get_template_part( 'partials/study' ); ?>
									<h6 class="study-actions">
										<?php if ( bp_get_group_id() ) : ?>
											<a href="<?php echo bp_get_group_permalink(); ?>"><i class="fa fa-users"></i> <?php bp_group_name(); ?></a>&nbsp; &nbsp;
										<?php endif; ?>
										<a href="#" data-reveal-id="study-chapters"><i class="fa fa-list"></i> <?php _e( 'All Chapters', 'sc' ); ?></a>&nbsp; &nbsp;

										<?php if ( apply_filters( 'sc_study_show_print', true, $study_id ) ) : ?>
											<a href="#" onclick="window.print(); return false;"><i class="fa fa-print"></i> <?php _e( 'Print this lesson', 'sc' ); ?></a>
										<?php endif; ?>
									</h6>
								</div>
							</div>
						</div>
					<?php else : ?>
						<div class="small-12 text-center columns">
							<h4 style="margin-bottom:0; color:#999;"><?php echo get_the_title( $study_id ); ?> <?php if ( current_user_can( 'edit_post', $study_id ) ) : ?>
									<span class="small">(<a href="/study-edit/?action=edit&study=<?php echo absint( $study_id ); ?>"><?php _e( 'Edit this study', 'sc' ); ?></a>)
									</span><?php endif; ?></h4>
							<?php get_template_part( 'partials/study' ); ?>
							<h6 class="study-actions">
								<?php if ( bp_get_group_id() ) : ?>
									<a href="<?php echo bp_get_group_permalink(); ?>"><i class="fa fa-users"></i> <?php bp_group_name(); ?>
									</a>&nbsp; &nbsp;
								<?php endif; ?>
								<a href="#" data-reveal-id="study-chapters"><i class="fa fa-list"></i> <?php _e( 'All Chapters', 'sc' ); ?>
								</a>&nbsp; &nbsp;

								<?php if ( apply_filters( 'sc_study_show_print', true, $study_id ) ) : ?>
									<a href="#" onclick="window.print(); return false;"><i class="fa fa-print"></i> <?php _e( 'Print this lesson', 'sc' ); ?>
									</a>
								<?php endif; ?>
							</h6>
						</div>
					<?php endif; ?>
				</div>


				<div class="row">

					<div class="study-content medium-9 medium-centered small-12 columns">


						<?php while ( $elements->have_posts() ) : $elements->the_post(); ?>

							<?php get_template_part( 'partials/study', 'element' ); ?>

						<?php endwhile; ?>

						<?php wp_reset_postdata(); ?>

						<?php do_action( 'sc_study_chapter_after', get_the_ID() ); ?>

					</div>

				</div>

			<?php endwhile; // end of the loop. ?>
		</div>

	</div>
</div>

<?php sc_study_navigation(); ?>

<div id="study-chapters" class="reveal-modal text-centered small" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
	<h2 ><?php echo get_the_title( $study_id ); ?> Chapters</h2>
	<ul class="side-nav">
		<?php sc_study_index(); ?>
	</ul>
</div>

<?php get_footer(); ?>


