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
	 * @param array           $object
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function add_additional_fields_to_object( $object, $request ) {
		$object = parent::add_additional_fields_to_object( $object, $request );

		if ( $request->get_route() != '/' . $this->namespace . '/' . $this->rest_base . '/me' ) {
			return $object;
		}

		$object['can']['create_study'] = user_can( $object['id'], 'create_study' );
		$object['can']['create_group'] = user_can( $object['id'], 'create_group' );

		return $object;
	}

}
