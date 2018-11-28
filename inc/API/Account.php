<?php

namespace StudyChurch\API;

use BP_REST_Members_Endpoint;
use WP_Error;

class Account extends BP_REST_Members_Endpoint {

	public function __construct() {
		parent::__construct();

		$this->namespace = studychurch()->get_api_namespace();
		$this->rest_base = 'account';
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

}
