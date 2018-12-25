<?php

namespace StudyChurch\API;

use BP_REST_Groups_Endpoint;
use WP_Error;
use BP_Groups_Member;

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
	 * Update a group.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$group_id = groups_create_group( $this->prepare_item_for_database( $request ) );

		// If the update was fired but returned an error,
		// send a custom error to the api.
		if ( ! is_numeric( $group_id ) ) {
			return new WP_Error( 'rest_user_cannot_update_group',
				__( 'Cannot update existing group.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		if ( ! empty( $request['studies'] ) ) {
			groups_update_groupmeta( $group_id, '_sc_study', array_unique( array_map( 'absint', $request['studies'] ) ) );
		}

		$group = $this->get_group_object( $group_id );

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $group, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group is updated via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param \BP_Groups_Group  $group    The updated group.
		 * @param \WP_REST_Response $response The response data.
		 * @param \WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'rest_group_update_item', $group, $response, $request );

		return $response;
	}

	/**
	 * Update schema
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['component']['enum'][]                         = 'study';
		$schema['properties']['id']['readonly']                              = false;
		$schema['properties']['description']['properties']['raw']['context'] = [ 'view', 'edit' ];

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

		$fields['invite'] = [
			'get_callback' => [ $this, 'get_group_invite_link' ],
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The invite link for this group', studychurch()->get_id() ),
				'type'        => 'string',
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
				'link'        => get_permalink( $study ),
				'title'       => get_the_title( $study ),
				'description' => apply_filters( 'the_excerpt', get_post( $study )->post_excerpt ),
				'thumbnail'   => get_the_post_thumbnail_url( $study, 'medium' ),
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
		return [
			'members' => BP_Groups_Member::get_group_member_ids( $object['id'] ),
			'admins'  => BP_Groups_Member::get_group_administrator_ids( $object['id'] ),
			'mods'    => BP_Groups_Member::get_group_moderator_ids( $object['id'] )
		];

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

	public function get_group_invite_link( $object ) {
		return sprintf( "%s?group=%s&key=%s", trailingslashit( home_url( 'join' ) ), $object['slug'], sc_get_group_invite_key( $object['id'] ) );
	}

}
