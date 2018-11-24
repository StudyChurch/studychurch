<?php

namespace StudyChurch\API;

use BP_REST_Activity_Endpoint;
use WP_Error;

class Activity extends BP_REST_Activity_Endpoint {

	public function __construct() {
		parent::__construct();

		$this->namespace = studychurch()->get_api_namespace();
		$this->rest_base = 'activity';
	}

	public function register_routes() {

		parent::register_routes();

	}

	/**
	 * Customize item creation for answers
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|\WP_REST_Request|\WP_REST_Response
	 * @author Tanner Moushey
	 */
	public function create_item( $request ) {

		if ( ! in_array( 'answer_update', array( $request['type'] ) ) ) {
			return parent::create_item( $request );
		}

		$request->set_param( 'context', 'edit' );
		$prepared_activity = $this->prepare_item_for_database( $request );

		$activity_id = bp_activity_add( $prepared_activity );

		if ( ! is_numeric( $activity_id ) ) {
			return new WP_Error( 'rest_user_cannot_create_activity',
				__( 'Cannot create new activity.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$activity = bp_activity_get( array(
			'in'               => $activity_id,
			'display_comments' => 'threaded',
			'show_hidden'      => $request['hidden'],
		) );

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $activity['activities'][0], $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity is created via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param /BP_Activity_Activity $activity The created activity.
		 * @param /WP_REST_Response     $response The response data.
		 * @param /WP_REST_Request      $request  The request sent to the API.
		 */
		do_action( 'rest_activity_create_item', $activity, $response, $request );

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

		$schema['properties']['component']['enum'][] = 'study';
		$schema['properties']['id']['readonly'] = false;

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

}
