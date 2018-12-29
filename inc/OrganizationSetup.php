<?php

namespace StudyChurch;

class OrganizationSetup {

	const TYPE = 'organization';

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the OrganizationSetup
	 *
	 * @return OrganizationSetup
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof OrganizationSetup ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'bp_groups_admin_meta_boxes', [ $this, 'organization_meta_boxes' ] );
		add_action( 'bp_group_admin_edit_after', [ $this, 'organization_meta_save' ] );
		add_action( 'bp_init', [ $this, 'organization_group_type' ] );
	}

	/**
	 * Add meta box
	 *
	 * @author Tanner Moushey
	 */
	public function organization_meta_boxes() {
		add_meta_box( 'sc_organization_meta', __( 'Organization ', studychurch()->get_id() ), [ $this, 'org_meta_cb' ], get_current_screen()->id, 'side', 'core' );
	}

	/**
	 * Register Organization group type
	 *
	 * @author Tanner Moushey
	 */
	public function organization_group_type() {
		bp_groups_register_group_type( self::TYPE );
	}

	/**
	 * Save meta
	 *
	 * @param $group_id
	 *
	 * @author Tanner Moushey
	 */
	public function organization_meta_save( $group_id ) {
		if ( ! isset( $_POST['group-member-limit'] ) ) {
			return;
		}

		$org = new Organization( $group_id );
		$org->update_member_limit( absint( $_POST['group-member-limit'] ) );
	}

	/**
	 * Meta callback
	 *
	 * @param $item
	 *
	 * @author Tanner Moushey
	 */
	public function org_meta_cb( $item ) {

		if ( self::TYPE !== bp_groups_get_group_type( $item->id, true ) ) {
			printf( '<p>This group is not an Organization</p>' );
			return;
		}

		$org = new Organization( $item->id ); ?>

		<div class="bp-groups-settings-section" id="bp-groups-settings-section-invite-status">
			<fieldset>
				<legend><?php _e( 'What is the member limit for this organization?', studychurch()->get_id() ); ?></legend>
				<label for="sc-group-member-limit"><input type="number" name="group-member-limit" id="sc-group-member-limit" value="<?php echo $org->get_member_limit(); ?>" /></label>
			</fieldset>
		</div>
		<?php
	}

}