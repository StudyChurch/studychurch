<?php global $groups_template; ?>
<div id="buddypress" class="row">

	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

		<div class="columns medium-4 sidebar">

			<?php if ( bp_is_item_mod() || bp_is_item_admin() ) : ?>
				<a href="<?php bp_group_admin_permalink(); ?>" class="group-edit-link right"><i class="fa fa-pencil"></i></a>
			<?php endif; ?>

			<h3><a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a></h3>

			<p><?php echo apply_filters( 'the_content', wp_kses_post( $groups_template->group->description ) ); ?></p>

			<?php if ( $study_id = sc_get_group_study_id() ) : ?>
				<hr />
				<h4><?php _e( 'Study', 'sc' ); ?></h4>
				<h5 class="no-margin"><a href="<?php echo get_the_permalink( $study_id ); ?>"><?php echo get_the_title( $study_id ); ?></a></h5>
				<div class="description"><?php echo apply_filters( 'the_excerpt', get_post( $study_id )->post_excerpt ); ?></div>
			<?php elseif( $study = groups_get_groupmeta( bp_get_group_id(), 'study_name', true ) ) : ?>
				<hr />
				<h4><?php _e( 'Study', 'sc' ); ?></h4>
				<h5><?php echo esc_html( $study ); ?></h5>
			<?php elseif( sc_user_can_manage_group() ) : ?>
				<hr />
				<h4><?php _e( 'Study', 'sc' ); ?></h4>
				<h5><a href="<?php bp_group_admin_permalink(); ?>study/"><?php _e( 'Add a study', 'sc' ); ?></a></h5>
			<?php endif; ?>

			<hr />

			<?php if ( bp_group_is_mod() || bp_group_is_admin() ) : ?>
				<h4>
					<?php _e( 'Invitation Link', 'sc' ); ?> <br />
					<span class="description small"><?php _e( 'Use this link to invite members to join this group.', 'sc' ); ?></span>
				</h4>
				<p class="small">
					<textarea rows="3" class="group-invite-link no-margin"><?php printf( "%s?group=%s&key=%s", trailingslashit( home_url( 'join' ) ), bp_get_group_slug(), sc_get_group_invite_key() ); ?></textarea>
					<span class="description"><?php _e( 'Use ctr + c or cmd + c to copy.', 'sc' ); ?></span>
				</p>
			<?php endif; ?>

			<hr />

			<h4><?php _e( 'Members', 'sc' ); ?></h4>
			<table class="item-list members-list" style="width: 100%;">
				<tbody>
				<?php if ( bp_group_has_members( array(
					'per_page'            => 1000,
					'exclude_admins_mods' => 0
				) ) ) : while ( bp_group_members() ) : bp_group_the_member(); ?>
					<tr>
						<td width="70"><?php bp_group_member_avatar_thumb(); ?></td>
						<td><?php bp_group_member_name(); ?></td>
					</tr>
				<?php endwhile; endif; ?>
				</tbody>
			</table>

		</div>

		<div class="columns medium-8">

			<?php bp_core_render_message(); ?>

			<?php /*

			if ( bp_is_group_admin_page() ) {
				bp_get_template_part( 'groups/single/admin' );
			} elseif ( 'assignments' == bp_current_action() ) {
				bp_get_template_part( 'groups/single/assignments' );
			} else {
				bp_get_template_part( 'groups/single/activity' );
			} */ ?>

			<?php
			if ( bp_is_group_home() ) :

				if ( bp_group_is_visible() ) {

					// Use custom front if one exists
					$custom_front = bp_locate_template( array( 'groups/single/front.php' ), false, true );
					if     ( ! empty( $custom_front   ) ) : load_template( $custom_front, true );

					// Default to activity
					elseif ( bp_is_active( 'activity' ) ) : bp_get_template_part( 'groups/single/activity' );

					// Otherwise show members
					elseif ( bp_is_active( 'members'  ) ) : bp_groups_members_template_part();

					endif;

				} else {

					/**
					 * Fires before the display of the group status message.
					 *
					 * @since BuddyPress (1.1.0)
					 */
					do_action( 'bp_before_group_status_message' ); ?>

					<div id="message" class="info">
						<p><?php bp_group_status_message(); ?></p>
					</div>

					<?php

					/**
					 * Fires after the display of the group status message.
					 *
					 * @since BuddyPress (1.1.0)
					 */
					do_action( 'bp_after_group_status_message' );

				}

			// Not looking at home
			else :

				// Group Admin
				if     ( bp_is_group_admin_page() ) : bp_get_template_part( 'groups/single/admin'        );

				// Group Activity
				elseif ( bp_is_group_activity()   ) : bp_get_template_part( 'groups/single/activity'     );

				// Group Members
				elseif ( bp_is_group_members()    ) : bp_groups_members_template_part();

				// Group Invitations
				elseif ( bp_is_group_invites()    ) : bp_get_template_part( 'groups/single/send-invites' );

				// Old group forums
				elseif ( bp_is_group_forum()      ) : bp_get_template_part( 'groups/single/forum'        );

				// Membership request
				elseif ( bp_is_group_membership_request() ) : bp_get_template_part( 'groups/single/request-membership' );

				// Anything else (plugins mostly)
				else                                : bp_get_template_part( 'groups/single/plugins'      );

				endif;

			endif;
			?>
		</div>

	<?php endwhile; endif; ?>

</div><!-- #buddypress -->
