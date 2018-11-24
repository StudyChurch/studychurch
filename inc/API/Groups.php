<?php

namespace StudyChurch\API;

use BP_REST_Groups_Endpoint;
use WP_Error;

class Groups extends BP_REST_Groups_Endpoint {

	public function __construct() {
		parent::__construct();

		$this->namespace = studychurch()->get_api_namespace();
		$this->rest_base = 'groups';
	}

	public function register_routes() {

		parent::register_routes();

	}

	/**
	 * Add support for retrieving the group by slug
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return WP_Error|\WP_REST_Request
	 * @author Tanner Moushey
	 */
	public function get_item( $request ) {

		// check if this is a group slug or not. If not, proceed as normal
		if ( $group_id = groups_get_id( $request->get_url_params()['id'] ) ) {
			$request->set_url_params( array( 'id' => $group_id ) );
		}

		return parent::get_item( $request );

	}

	/**
	 * Update schema
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['component']['enum'][] = 'study';
		$schema['properties']['id']['readonly']      = false;

		return $schema;
	}

	/**
	 * Update params
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['component']['enum'][] = 'study';

		return $params;
	}

	/**
	 * Register custom fields for Groups
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	protected function get_additional_fields( $object_type = null ) {
		$fields = parent::get_additional_fields( $object_type );

		$fields['studies'] = [
			'get_callback' => [ $this, 'get_studies' ],
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The studies attached to this group', studychurch()->get_id() ),
				'type'        => 'array',
			],
		];

		$fields['members'] = [
			'get_callback' => [ $this, 'get_group_members' ],
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The members that belong to this group', studychurch()->get_id() ),
				'type'        => 'array',
			],
		];

		return $fields;
	}

	/**
	 * Get the studies for the queried group
	 *
	 * @param $object
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_studies( $object ) {
		$studies  = studychurch()->study::get_group_studies( $object['id'] );
		$gstudies = [];

		foreach ( $studies as $study ) {
			$gstudies[] = [
				'id'          => $study,
				'link'        => studychurch()->study::get_group_link( $study, $object['id'] ),
				'title'       => get_the_title( $study ),
				'description' => apply_filters( 'the_excerpt', get_post( $study )->post_excerpt ),
			];
		}

		return $gstudies;
	}

	/**
	 * Get the members for this group
	 *
	 * @param $object
	 * @param $field_name
	 * @param $request
	 * @param $object_type
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_group_members( $object, $field_name, $request, $object_type ) {

		if ( 'hide' == $request['members'] ) {
			return [];
		}

		if ( empty( $request['members'] ) ) {
			$roles = array( 'member', 'mod', 'admin' );
		} else {
			$roles = explode( ',', $request['members'] );
		}

		$members = groups_get_group_members( [
			'group_id'   => $object['id'],
			'per_page'   => 100,
			'group_role' => $roles,
		] );

		if ( empty( $members['members'] ) ) {
			return [];
		}

		$group_members = [];
		foreach ( $members['members'] as $member ) {
			$group_members[] = [
				'id'           => $member->ID,
				'username'     => $member->user_nicename,
				'name'         => $member->display_name,
				'admin'        => $member->is_admin,
				'mod'          => $member->is_mod,
				'lastActivity' => $member->last_activity,
				'avatar'       => [
					'img'  => bp_core_fetch_avatar( [ 'type' => 'full', 'html' => true, 'item_id' => $member->ID ] ),
					'full' => bp_core_fetch_avatar( [ 'type' => 'full', 'html' => false, 'item_id' => $member->ID ] ),
				],
			];
		}

		return $group_members;

	}

}
