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

		$user = get_userdata( $object['id'] );
		
		return $object;
	}


	/**
	 * Retrieves the current user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|WP_Error Array on success, or WP_Error object on failure.
	 */
	public function get_me( $request ) {

		$user = new User( wp_get_current_user() );

		$user_new = $this->do_rest_request( '/studychurch/v1/users/' . $user->ID );

		$groups = $this->do_rest_request( '/studychurch/v1/groups', array(
			'show_hidden' => true,
			'user_id'     => $user->ID,
			'status'      => 'hidden',
			'members'     => 'all',
		) );

		$studies = $this->do_rest_request( '/studychurch/v1/studies', array(
			'status'   => 'any',
			'per_page' => 100,
			'orderby'  => 'title',
			'order'    => 'asc',
			'author'   => $user->ID
		) );

		$response = [
			'user_new' => $user_new,
			'groups'   => $groups,
			'studies'  => $studies,
			'user'     => [
				'avatar'    => [
					'img'  => bp_get_displayed_user_avatar( [
						'type'    => 'full',
						'html'    => true,
						'item_id' => $user->ID
					] ),
					'full' => bp_get_displayed_user_avatar( [
						'type'    => 'full',
						'html'    => false,
						'item_id' => $user->ID
					] ),
				],
				'id'        => $user->ID,
				'name'      => $user->display_name,
				'username'  => $user->user_login,
				'firstName' => $user->first_name,
				'lastName'  => $user->last_name,
				'email'     => $user->user_email,
				'groups'    => $groups,
				'studies'   => $studies,
			],
		];

		return $response;
	}

	/**
	 * Handle internal REST request
	 *
	 * @param       $route
	 * @param array $atts
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	protected function do_rest_request( $route, $atts = array() ) {
		$request = new WP_REST_Request( 'GET', $route );
		$request->set_query_params( $atts );
		$response = rest_do_request( $request );
		$server   = rest_get_server();

		return $server->response_to_data( $response, false );
	}


}
