<?php

namespace StudyChurch;

class Organization {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the Organization
	 *
	 * @return Organization
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Organization ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'bp_init', [ $this, 'organization_group_type' ] );
	}

	public function organization_group_type() {
		bp_groups_register_group_type( 'organization' );
	}
}