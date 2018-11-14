<?php

namespace StudyChurch\API;

use BP_REST_Activity_Endpoint;
use WP_Error;
use WP_Comment_Query;

class Answers extends BP_REST_Activity_Endpoint {

	public function __construct() {
		parent::__construct();

		$this->namespace = studychurch()->get_api_namespace();
		$this->rest_base = 'answers';
	}

	public function register_routes() {

		parent::register_routes();

	}

	/**
	 * Checks if the comment can be read.
	 *
	 * @since 4.7.0
	 *
	 * @param \WP_Comment      $comment Comment object.
	 * @param \WP_REST_Request $request Request data to check.
	 *
	 * @return bool Whether the comment can be read.
	 */
	protected function check_read_permission( $comment, $request ) {
		if ( ! empty( $comment->comment_post_ID ) ) {
			$post = get_post( $comment->comment_post_ID );
			if ( $post ) {
				if ( $this->check_read_post_permission( $post, $request ) && 1 === (int) $comment->comment_approved ) {
					return true;
				}
			}
		}

		if ( 0 === get_current_user_id() ) {
			return false;
		}

		if ( ! empty( $comment->user_id ) && get_current_user_id() === (int) $comment->user_id ) {
			return true;
		}

		return current_user_can( 'edit_comment', $comment->comment_ID );
	}

	/**
	 * Checks if a comment can be edited or deleted.
	 *
	 * @since 4.7.0
	 *
	 * @param object $comment Comment object.
	 *
	 * @return bool Whether the comment can be edited or deleted.
	 */
	protected function check_edit_permission( $comment ) {
		if ( 0 === (int) get_current_user_id() ) {
			return false;
		}

		if ( ! empty( $comment->user_id ) && get_current_user_id() === (int) $comment->user_id ) {
			return true;
		}

		if ( ! current_user_can( 'moderate_comments' ) ) {
			return false;
		}

		return current_user_can( 'edit_comment', $comment->comment_ID );
	}


}
