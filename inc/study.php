<?php

namespace StudyChurch;

class Study {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var Study\Actions
	 */
	public $actions;

	/**
	 * Only make one instance of the Study
	 *
	 * @return Study
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Study ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'wp_ajax_sc_save_answer', array( $this, 'save_answer' ) );
		add_action( 'template_redirect', array( $this, 'setup_study_group' ) );
		add_action( 'template_redirect', array( $this, 'redirect_on_empty' ) );
		add_action( 'wp_head', array( $this, 'print_styles' ) );
		add_action( 'pre_get_posts', array( $this, 'study_archive' ) );

		add_filter( 'private_title_format', array( $this, 'private_title_format' ), 10, 2 );
		add_filter( 'user_has_cap', array( $this, 'private_study_cap' ), 10, 4 );
		add_filter( 'get_page_uri', array( $this, 'allow_private_parent' ), 10, 2 );

		// CPT
		add_action( 'init', array( $this, 'study_cpt' ) );

		// API
		add_action( 'rest_api_init', 'study_api_init', 0 );
		add_action( 'init', 'sc_study_extra_api_post_type_arguments', 11 );

		// Groups
		add_action( 'bp_init', array( $this, 'register_group_extension' ) );

		$this->actions = Study\Actions::get_instance();
	}

	public function register_group_extension() {
		// if we aren't in a group, don't bother
		if ( ! bp_is_group() ) {
			return;
		}

		bp_register_group_extension( 'StudyChurch\Study\Group' );
	}

	public function study_api_init() {
		global $study_api_sc_study;

		$study_api_sc_study = new Study\API();
		$study_api_sc_study->register_routes();
	}

	public function sc_study_extra_api_post_type_arguments() {
		global $wp_post_types;

		$wp_post_types['sc_study']->show_in_rest          = true;
		$wp_post_types['sc_study']->rest_base             = 'study';
		$wp_post_types['sc_study']->rest_controller_class = 'WP_REST_Posts_Controller';
	}

	/**
	 * If the study does not have an introduction, redirect to the first chapter
	 */
	public function redirect_on_empty() {
		if ( ! is_singular( 'sc_study' ) ) {
			return;
		}

		// if we are not on the main study page continue
		if ( get_the_ID() != sc_get_study_id( get_the_ID() ) ) {
			return;
		}

		if ( get_the_content() ) {
			return;
		}

		$nav = sc_study_get_navigation( get_the_ID() );

		// if we have no content for this page, redirect to the first item
		if ( ! empty( $nav[0] ) ) {
			wp_safe_redirect( get_the_permalink( $nav[0]->ID ) );
			die();
		}

	}

	/**
	 * Save study answers via ajax
	 */
	public function save_answer() {

		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			wp_send_json_error();
		}

		$data = array(
			'comment_post_ID'      => absint( $_POST['post_id'] ),
			'comment_ID'           => absint( $_POST['comment_id'] ),
			'comment_author'       => wp_slash( $user->display_name ),
			'comment_author_email' => wp_slash( $user->user_email ),
			'comment_author_url'   => wp_slash( $user->user_url ),
			'comment_content'      => $_POST['answer'],
			'comment_parent'       => 0,
			'user_id'              => $user->ID,
		);

		global $post;
		$post = get_post( $data['comment_post_ID'] );

		$lesson_id = wp_get_post_parent_id( $data['comment_post_ID'] );

		$activity_meta = array(
			'action'            => sprintf( __( '%s answered a question in <a href="%s#post-%s">%s</a>' ), $user->display_name, get_permalink( $lesson_id ), $data['comment_post_ID'], get_the_title( $lesson_id ) ),
			'content'           => wp_filter_kses( $data['comment_content'] ),
			'component'         => buddypress()->groups->id,
			'type'              => 'answer_update',
			'user_id'           => $user->ID,
			'item_id'           => absint( $_POST['group_id'] ),
			'recorded_time'     => bp_core_current_time(),
			'secondary_item_id' => $data['comment_post_ID'],
			'hide_sitewide'     => false,
		);

		if ( $data['comment_ID'] ) {
			wp_update_comment( $data );
			$activity_meta['id'] = sc_answer_get_activity_id( $data['comment_ID'] );
		} else {
			$data['comment_ID'] = wp_new_comment( $data );
		}

		update_comment_meta( $data['comment_ID'], 'group_id', absint( $_POST['group_id'] ) );

		$this->setup_study_group( $data['comment_post_ID'] );

		if ( ! sc_answer_is_private( $data['comment_post_ID'] ) ) {
			$activity_id = bp_activity_add( $activity_meta );
			update_comment_meta( $data['comment_ID'], 'activity_id', $activity_id );
		}

		ob_start();
		global $sc_answer;
		$sc_answer = get_comment( $data['comment_ID'] );
		get_template_part( 'partials/study-element', 'answers' );
		$data['answers'] = ob_get_clean();

		wp_send_json_success( $data );
	}

	/**
	 * Setup global for current group and redirect if user does not have access to this
	 * study
	 */
	public function setup_study_group( $study_id = false ) {
		if ( empty( $study_id ) && ! is_singular( 'sc_study' ) ) {
			return;
		}

		$doing_ajax = ( defined( 'DOING_AJAX' ) && DOING_AJAX );

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! $group_id = sc_get_study_user_group_id( $study_id ) ) {

			// allow editors and up to proceed
			if ( current_user_can( 'edit_post', $study_id ) ) {
				return;
			}

			// only redirect if this is not an ajax call
			if ( empty( $doing_ajax ) && ! apply_filters( 'sc_allow_personal_studies', false, $study_id ) ) {
				wp_safe_redirect( bp_loggedin_user_domain() );
				die();
			} else {
				return;
			}

		}

		bp_has_groups( 'include=' . $group_id );
		bp_groups();
		bp_the_group();

		// only redirect if this is not an ajax call
		if ( empty( $doing_ajax ) && ! bp_get_group_id() ) {
			wp_safe_redirect( bp_loggedin_user_domain() );
			die();
		}

	}

	/**
	 * Remove "Private:" label from private sc_study posts
	 *
	 * @param $format
	 * @param $post
	 *
	 * @return string
	 */
	public function private_title_format( $format, $post ) {
		if ( 'sc_study' != $post->post_type ) {
			return $format;
		}

		return '%s';
	}

	public function private_study_cap( $allcaps, $caps, $args, $user ) {
		if ( empty( $user->ID ) ) {
			return $allcaps;
		}

		// we are only interested in private posts capability
		if ( ! in_array( 'read_private_posts', $caps ) ) {
			return $allcaps;
		}

		// this user can already ready private posts
		if ( isset( $allcaps['read_private_posts'] ) && $allcaps['read_private_posts'] ) {
			return $allcaps;
		}

		// make sure this is a study
		if ( empty( $args[2] ) || 'sc_study' != get_post_type( absint( $args[2] ) ) ) {
			return $allcaps;
		}

		// make sure this user has access to this study
		if ( ! sc_user_can_access_study( absint( $args[2] ), $user->ID ) ) {
			return $allcaps;
		}

		$allcaps['read_private_posts'] = true;

		return $allcaps;
	}

	public function print_styles() {
		if ( ! is_singular( 'sc_study' ) ) {
			return;
		} ?>
		<style>
			@page {
				size: 8.5in 11in;
				margin: 10%;
			}
		</style>
		<?php
	}

	public function study_archive( $query ) {
		if ( is_admin() ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( 'sc_study' != $query->get( 'post_type' ) ) {
			return;
		}

		if ( ! $query->is_archive ) {
			return;
		}

		$query->set( 'post_parent', 0 );
	}

	public function allow_private_parent( $uri, $page ) {
		if ( 'sc_study' != $page->post_type ) {
			return $uri;
		}

		$uri = $page->post_name;

		foreach ( $page->ancestors as $parent ) {
			$parent = get_post( $parent );
			if ( in_array( $parent->post_status, array( 'publish', 'private' ) ) ) {
				$uri = $parent->post_name . '/' . $uri;
			}
		}

		return $uri;
	}

	public function  study_cpt() {
		$labels = array(
			'name'               => _x( 'Studies', 'post type general name', 'sc' ),
			'singular_name'      => _x( 'Study', 'post type singular name', 'sc' ),
			'add_new_item'       => __( 'Add New Study', 'sc' ),
			'new_item'           => __( 'New Study', 'sc' ),
			'edit_item'          => __( 'Edit Study', 'sc' ),
			'view_item'          => __( 'View Study', 'sc' ),
			'all_items'          => __( 'All Studies', 'sc' ),
			'search_items'       => __( 'Search Studies', 'sc' ),
			'not_found'          => __( 'No studies found.', 'sc' ),
			'not_found_in_trash' => __( 'No studies found in Trash.', 'sc' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'rewrite'            => array(
				'slug'       => 'studies',
				'with_front' => false,
			),
			'hierarchical'       => true,
			'has_archive'        => true,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-welcome-write-blog',
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'page-attributes' )
		);

		register_post_type( 'sc_study', $args );
	}


}
