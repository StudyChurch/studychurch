<?php

namespace StudyChurch\API;

use BP_REST_Members_Endpoint;
use WP_Error;
use WP_REST_Server;
use StudyChurch\API\Auth\User;
use WP_REST_Request;

class Users extends BP_REST_Members_Endpoint {

	public function __construct() {
		parent::__construct();

		$this->namespace = studychurch()->get_api_namespace();
		$this->rest_base = 'users';
	}

	public function register_routes() {

		parent::register_routes();

	}

	/**
	 * Update schema
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

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

		return $params;
	}

	/**
	 * Register custom fields for Users
	 *
	 * @author Tanner Moushey
	 */
	protected function get_additional_fields( $object_type = null ) {
		$fields = parent::get_additional_fields( $object_type );

		$fields['studies'] = [
			'get_callback' => [ $this, 'get_studies' ],
			'update_callback' => [ $this, 'update_studies' ],
			'schema'       => [
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The studies attached to this group', studychurch()->get_id() ),
				'type'        => 'array',
			],
		];



		return $fields;
	}

	/**
	 * @param array           $object
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function add_additional_fields_to_object( $object, $request ) {
		$object = parent::add_additional_fields_to_object( $object, $request );

		$object['can']['create_study'] = user_can( $object['id'], 'create_study' );
		$object['can']['create_group'] = user_can( $object['id'], 'create_group' );

		return $object;
	}

	/**
	 * Get the studies for the queried user
	 *
	 * @param $object
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_studies( $object ) {
		$studies  = studychurch()->study::get_user_studies( $object['id'] );
		$gstudies = [];

		foreach ( $studies as $study ) {
			$gstudies[] = studychurch()->study::get_data( $study );
		}

		return $gstudies;
	}

	/**
	 * Handle Update for user studies
	 *
	 * @param $value
	 * @param $object
	 *
	 * @author Tanner Moushey
	 */
	public function update_studies( $value, $object ) {
		if ( ! is_array( $value ) ) {
			$value = [];
		}

		studychurch()->study::update_user_studies( $object->ID, array_map( 'absint', $value ) );
	}

}
