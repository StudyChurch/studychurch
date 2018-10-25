<?php

namespace StudyChurch;

use WP_Error;

/**
 * Awesome Support API main plugin class.
 *
 * @since 1.0.0
 */
class API {

	/**
	 * @var object StudyChurch\API\Auth\Init
	 */
	public $auth;

	/**
	 * Instance of this loader class.
	 *
	 * @since    0.1.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * StudyChurch constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->actions();
		$this->filters();
	}

	/**
	 * Handle Actions
	 */
	protected function actions() {
		add_action( 'rest_api_init', array( $this, 'load_api_routes' ) );
	}

	/**
	 * Handle Filters
	 */
	protected function filters() {
		add_filter( 'rest_group_prepare_value', array( $this, 'get_group_from_slug' ), 10, 2 );
	}

	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	protected function includes() {
		$this->auth = API\Auth\Init::get_instance();
	}


	/** Actions ******************************************************/

	/**
	 * Load APIs that are not loaded automatically
	 */
	public function load_api_routes() {
		$controller = new API\Passwords();
		$controller->register_routes();

		$controller = new API\Attachments();
		$controller->register_routes();

		$controller = new API\Authenticate();
		$controller->register_routes();

		register_rest_field( 'group', 'studies', array(
			'get_callback' => [ $this, 'get_rest_field_study' ],
			'schema'       => array(
				'context'     => [ 'view', 'edit' ],
				'description' => __( 'The studies attached to this group', studychurch()->get_id() ),
				'type'        => 'array',
			),
		) );

	}

	/** Filters ******************************************************/

	/**
	 * @param \WP_REST_Response $response
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed
	 * @author Tanner Moushey
	 */
	public function get_group_from_slug( $response, $request ) {
		if ( $response->get_data()['id'] ) {
			return $response;
		}

		if ( ! $group_id = groups_get_id( $request->get_url_params()['id'] ) ) {
			return $response;
		}

		remove_filter( 'rest_group_prepare_value', array( $this, 'get_group_from_slug' ), 10 );

		$request->set_url_params( array( 'id' => $group_id ) );
		$request->set_route( '/buddypress/v1/groups/' . $group_id );

		$endpoint = new \BP_REST_Groups_Endpoint();
		$group    = $endpoint->get_group_object( $request );

		return $endpoint->prepare_item_for_response( $group, $request );
	}

	/** Additional API fields ******************************************************/

	/**
	 * Get the studies for the queried group
	 *
	 * @param $object
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_rest_field_study( $object ) {
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


	/** Helper methods ******************************************************/


	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}