<?php
/**
 * REST API for Zeko QA
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_QA_REST_API. */
class Zeko_QA_REST_API {

	/**
	 * Db.
	 *
	 * @var mixed Db.
	 */
	private $db;
	/**
	 * Namespace.
	 *
	 * @var mixed Namespace.
	 */
	private $namespace = 'zeko-qa/v1';

	/**
	 * Construct.
	 *
	 * @param mixed $db Db.
	 */
	public function __construct( $db ) {
		$this->db = $db;
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/questions',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_questions' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_list_args(),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_question' ),
					'permission_callback' => array( $this, 'is_logged_in' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/questions/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_question' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => 'PUT,PATCH',
					'callback'            => array( $this, 'update_question' ),
					'permission_callback' => array( $this, 'is_logged_in' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_question' ),
					'permission_callback' => array( $this, 'is_logged_in' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/questions/(?P<id>\d+)/answers',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_answers' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_answer' ),
					'permission_callback' => array( $this, 'is_logged_in' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/answers/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT,PATCH',
					'callback'            => array( $this, 'update_answer' ),
					'permission_callback' => array( $this, 'is_logged_in' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_answer' ),
					'permission_callback' => array( $this, 'is_logged_in' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/vote',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'vote' ),
				'permission_callback' => array( $this, 'is_logged_in' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/topics',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_topics' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'/tags',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_tags' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_user_profile' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'/spaces',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_spaces' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_space' ),
					'permission_callback' => array( $this, 'is_logged_in' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/spaces/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_space' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'/spaces/(?P<id>\d+)/join',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'join_space' ),
				'permission_callback' => array( $this, 'is_logged_in' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/spaces/(?P<id>\d+)/leave',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'leave_space' ),
				'permission_callback' => array( $this, 'is_logged_in' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/spaces/(?P<id>\d+)/posts',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'post_to_space' ),
				'permission_callback' => array( $this, 'is_logged_in' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/bounties',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_bounty' ),
				'permission_callback' => array( $this, 'is_logged_in' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/bounties/(?P<id>\d+)/award',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'award_bounty' ),
				'permission_callback' => array( $this, 'is_logged_in' ),
			)
		);
	}

	/**
	 * Logged in.
	 */
	public function is_logged_in() {
		return is_user_logged_in();
	}

	/**
	 * List args.
	 */
	private function get_list_args() {
		return array(
			'per_page' => array(
				'default'           => 20,
				'sanitize_callback' => 'absint',
			),
			'page'     => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'filter'   => array(
				'default'           => 'trending',
				'sanitize_callback' => 'sanitize_key',
			),
			'search'   => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Questions.
	 *
	 * @param mixed $request Request.
	 */
	public function get_questions( $request ) {
		$per_page = $request->get_param( 'per_page' );
		$page     = $request->get_param( 'page' );
		$filter   = $request->get_param( 'filter' );
		$search   = $request->get_param( 'search' );
		$offset   = ( $page - 1 ) * $per_page;

		if ( 'trending' === $filter ) {
			$questions = $this->db->get_trending_questions( $per_page, $offset );
		} elseif ( 'unanswered' === $filter ) {
			$questions = $this->db->get_unanswered_questions( $per_page, $offset );
		} else {
			$questions = $this->db->get_questions(
				array(
					'limit'  => $per_page,
					'offset' => $offset,
					'search' => $search,
				)
			);
		}

		$data = array_map( array( $this, 'format_question' ), $questions );
		return rest_ensure_response( $data );
	}

	/**
	 * Question.
	 *
	 * @param mixed $request Request.
	 */
	public function get_question( $request ) {
		$id       = $request->get_param( 'id' );
		$question = $this->db->get_question( $id );
		if ( ! $question || ! $this->db->can_view_question( $question ) ) {
			return new WP_Error( 'not_found', __( 'Question not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $this->format_question_full( $question ) );
	}

	/**
	 * Create question.
	 *
	 * @param mixed $request Request.
	 */
	public function create_question( $request ) {
		$title   = sanitize_text_field( $request->get_param( 'title' ) );
		$content = wp_kses_post( $request->get_param( 'content' ) );
		$user_id = get_current_user_id();

		if ( empty( $title ) || empty( $content ) ) {
			return new WP_Error( 'missing_fields', __( 'Title and content are required.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		if ( mb_strlen( $title ) < 10 || mb_strlen( $title ) > 200 ) {
			return new WP_Error( 'invalid_title', __( 'Title must be 10-200 characters.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		if ( mb_strlen( wp_strip_all_tags( $content ) ) < 10 ) {
			return new WP_Error( 'invalid_content', __( 'Content must be at least 10 characters.', 'zeko-qa' ), array( 'status' => 400 ) );
		}
		$slug     = sanitize_title( $title );
		$existing = $this->db->get_question_by_slug( $slug );
		if ( $existing ) {
			$slug .= '-' . time();
		}

		$question_id = $this->db->insert_question(
			array(
				'title'   => $title,
				'slug'    => $slug,
				'content' => $content,
				'user_id' => $user_id,
			)
		);

		$topic_ids = $request->get_param( 'topic_ids' );
		if ( ! empty( $topic_ids ) && is_array( $topic_ids ) ) {
			$topic_ids = array_slice( array_map( 'absint', $topic_ids ), 0, 5 );
			foreach ( array_filter( $topic_ids ) as $topic_id ) {
				global $wpdb;
				$table = $this->db->get_table_question_topics();
				$wpdb->insert(
					$table,
					array(
						'question_id' => $question_id,
						'topic_id'    => $topic_id,
					),
					array( '%d', '%d' )
				);
			}
		}

		return rest_ensure_response( $this->format_question_full( $this->db->get_question( $question_id ) ), 201 );
	}

	/**
	 * Update question.
	 *
	 * @param mixed $request Request.
	 */
	public function update_question( $request ) {
		$id       = $request->get_param( 'id' );
		$user_id  = get_current_user_id();
		$question = $this->db->get_question( $id );

		if ( ! $question ) {
			return new WP_Error( 'not_found', __( 'Question not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}

		if ( absint( $question->user_id ) !== $user_id ) {
			return new WP_Error( 'forbidden', __( 'You cannot edit this question.', 'zeko-qa' ), array( 'status' => 403 ) );
		}

		$data = array();
		if ( $request->get_param( 'title' ) ) {
			$data['title'] = sanitize_text_field( $request->get_param( 'title' ) );
		}
		if ( $request->get_param( 'content' ) ) {
			$data['content'] = wp_kses_post( $request->get_param( 'content' ) );
		}

		$this->db->update_question( $id, $data );
		return rest_ensure_response( $this->format_question_full( $this->db->get_question( $id ) ) );
	}

	/**
	 * Delete question.
	 *
	 * @param mixed $request Request.
	 */
	public function delete_question( $request ) {
		$id       = $request->get_param( 'id' );
		$user_id  = get_current_user_id();
		$question = $this->db->get_question( $id );

		if ( ! $question ) {
			return new WP_Error( 'not_found', __( 'Question not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}

		if ( absint( $question->user_id ) !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'forbidden', __( 'You cannot delete this question.', 'zeko-qa' ), array( 'status' => 403 ) );
		}

		$this->db->update_question( $id, array( 'status' => 'deleted' ) );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * Answers.
	 *
	 * @param mixed $request Request.
	 */
	public function get_answers( $request ) {
		$question_id = $request->get_param( 'id' );
		$question    = $this->db->get_question( $question_id );
		if ( ! $question || ! $this->db->can_view_question( $question ) ) {
			return new WP_Error( 'not_found', __( 'Question not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}
		$answers = $this->db->get_answers( $question_id );
		$data    = array_map( array( $this, 'format_answer' ), $answers );
		return rest_ensure_response( $data );
	}

	/**
	 * Create answer.
	 *
	 * @param mixed $request Request.
	 */
	public function create_answer( $request ) {
		$question_id = $request->get_param( 'id' );
		$content     = wp_kses_post( $request->get_param( 'content' ) );
		$user_id     = get_current_user_id();

		if ( empty( $content ) ) {
			return new WP_Error( 'missing_fields', __( 'Content is required.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		$question = $this->db->get_question( $question_id );
		if ( ! $question || ! $this->db->can_view_question( $question ) ) {
			return new WP_Error( 'not_found', __( 'Question not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}

		$answer_id = $this->db->insert_answer(
			array(
				'question_id' => $question_id,
				'user_id'     => $user_id,
				'content'     => $content,
			)
		);

		if ( $question->user_id && absint( $question->user_id ) !== $user_id ) {
			$this->db->insert_notification(
				array(
					'user_id'     => $question->user_id,
					'action'      => 'answered your question',
					'object_id'   => $question_id,
					'object_type' => 'question',
					'actor_id'    => $user_id,
				)
			);
		}

		return rest_ensure_response( $this->format_answer( $this->db->get_answer( $answer_id ) ), 201 );
	}

	/**
	 * Update answer.
	 *
	 * @param mixed $request Request.
	 */
	public function update_answer( $request ) {
		$id      = $request->get_param( 'id' );
		$user_id = get_current_user_id();
		$answer  = $this->db->get_answer( $id );

		if ( ! $answer ) {
			return new WP_Error( 'not_found', __( 'Answer not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}

		if ( absint( $answer->user_id ) !== $user_id ) {
			return new WP_Error( 'forbidden', __( 'You cannot edit this answer.', 'zeko-qa' ), array( 'status' => 403 ) );
		}

		$content = wp_kses_post( $request->get_param( 'content' ) );
		if ( ! empty( $content ) ) {
			$this->db->update_answer( $id, array( 'content' => $content ) );
		}

		return rest_ensure_response( $this->format_answer( $this->db->get_answer( $id ) ) );
	}

	/**
	 * Delete answer.
	 *
	 * @param mixed $request Request.
	 */
	public function delete_answer( $request ) {
		$id      = $request->get_param( 'id' );
		$user_id = get_current_user_id();
		$answer  = $this->db->get_answer( $id );

		if ( ! $answer ) {
			return new WP_Error( 'not_found', __( 'Answer not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}

		if ( absint( $answer->user_id ) !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'forbidden', __( 'You cannot delete this answer.', 'zeko-qa' ), array( 'status' => 403 ) );
		}

		global $wpdb;
		$wpdb->delete( $this->db->get_table_answers(), array( 'id' => $id ), array( '%d' ) );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * Vote.
	 *
	 * @param mixed $request Request.
	 */
	public function vote( $request ) {
		$item_id   = absint( $request->get_param( 'item_id' ) );
		$item_type = sanitize_key( $request->get_param( 'item_type' ) );
		$vote_type = sanitize_key( $request->get_param( 'vote_type' ) );
		$user_id   = get_current_user_id();

		if ( ! $item_id || ! in_array( $item_type, array( 'question', 'answer' ), true ) || ! in_array( $vote_type, array( 'up', 'down' ), true ) ) {
			return new WP_Error( 'invalid_params', __( 'Invalid parameters.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		$existing = $this->db->get_user_vote( $user_id, $item_id, $item_type );
		$table    = 'question' === $item_type ? $this->db->get_table_questions() : $this->db->get_table_answers();

		// Reputation + badges — must mirror the AJAX vote path so both entry.
		// points award the same points (upvote_received / upvote_removed).
		$item_owner_id = 0;
		$item          = 'question' === $item_type ? $this->db->get_question( $item_id ) : $this->db->get_answer( $item_id );
		$item_owner_id = $item ? absint( $item->user_id ) : 0;

		if ( $item_owner_id && $item_owner_id !== $user_id ) {
			if ( 'up' === $vote_type && ! ( $existing && 'up' === $existing->vote_type ) ) {
				$points = ( 'answer' === $item_type ) ? 10 : 5;
				$this->db->log_reputation( $item_owner_id, $points, 'upvote_received', $item_id, $item_type );
			}
			if ( $existing && 'up' === $existing->vote_type && 'up' !== $vote_type ) {
				$points = ( 'answer' === $item_type ) ? -10 : -5;
				$this->db->log_reputation( $item_owner_id, $points, 'upvote_removed', $item_id, $item_type );
			}
			$this->db->check_and_award_badges( $item_owner_id );
		}

		if ( $existing ) {
			if ( $existing->vote_type === $vote_type ) {
				global $wpdb;
				$wpdb->delete( $this->db->get_table_votes(), array( 'id' => $existing->id ), array( '%d' ) );
				$col = 'up' === $vote_type ? 'upvotes' : 'downvotes';
				$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET {$col} = GREATEST({$col} - 1, 0) WHERE id = %d", $item_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
				return rest_ensure_response(
					array(
						'vote_state' => '',
						'net_votes'  => $this->db->get_net_votes( $item_id, $item_type ),
					)
				);
			}

			global $wpdb;
			$old_col = 'up' === $existing->vote_type ? 'upvotes' : 'downvotes';
			$new_col = 'up' === $vote_type ? 'upvotes' : 'downvotes';
			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET {$old_col} = GREATEST({$old_col} - 1, 0), {$new_col} = {$new_col} + 1 WHERE id = %d", $item_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			$wpdb->update( $this->db->get_table_votes(), array( 'vote_type' => $vote_type ), array( 'id' => $existing->id ), array( '%s' ), array( '%d' ) );
			return rest_ensure_response(
				array(
					'vote_state' => $vote_type,
					'net_votes'  => $this->db->get_net_votes( $item_id, $item_type ),
				)
			);
		}

		$this->db->insert_vote(
			array(
				'user_id'   => $user_id,
				'item_id'   => $item_id,
				'item_type' => $item_type,
				'vote_type' => $vote_type,
			)
		);

		$col = 'up' === $vote_type ? 'upvotes' : 'downvotes';
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET {$col} = {$col} + 1 WHERE id = %d", $item_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		return rest_ensure_response(
			array(
				'vote_state' => $vote_type,
				'net_votes'  => $this->db->get_net_votes( $item_id, $item_type ),
			)
		);
	}

	/**
	 * Topics.
	 *
	 * @param mixed $request Request.
	 */
	public function get_topics( $request ) {
		unset( $request );
		$topics = $this->db->get_topics( array( 'limit' => 100 ) );
		$data   = array_map(
			function ( $t ) {
				return array(
					'id'             => (int) $t->id,
					'name'           => $t->name,
					'slug'           => $t->slug,
					'description'    => $t->description,
					'question_count' => (int) $t->question_count,
					'followers'      => (int) $t->followers,
				);
			},
			$topics
		);
		return rest_ensure_response( $data );
	}

	/**
	 * Tags.
	 *
	 * @param mixed $request Request.
	 */
	public function get_tags( $request ) {
		unset( $request );
		$tags = $this->db->get_tags( array( 'limit' => 100 ) );
		$data = array_map(
			function ( $t ) {
				return array(
					'id'             => (int) $t->id,
					'name'           => $t->name,
					'slug'           => $t->slug,
					'question_count' => (int) $t->question_count,
				);
			},
			$tags
		);
		return rest_ensure_response( $data );
	}

	/**
	 * User profile.
	 *
	 * @param mixed $request Request.
	 */
	public function get_user_profile( $request ) {
		$user_id = $request->get_param( 'id' );
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error( 'not_found', __( 'User not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}

		$stats = $this->db->get_user_qa_stats( $user_id );

		$profile = array(
			'id'           => (int) $user_id,
			'display_name' => $user->display_name,
			'stats'        => $stats,
		);

		// Never expose the login (username) — it enables account enumeration;.
		// only the owner or an admin may see their own.
		if ( is_user_logged_in() && (int) get_current_user_id() === (int) $user_id ) {
			$profile['user_login'] = $user->user_login;
		}

		return rest_ensure_response( $profile );
	}

	/**
	 * Format question.
	 *
	 * @param mixed $q Q.
	 */
	private function format_question( $q ) {
		return array(
			'id'           => (int) $q->id,
			'title'        => $q->title,
			'slug'         => $q->slug,
			'content'      => $q->content,
			'user_id'      => (int) $q->user_id,
			'answer_count' => (int) ( $q->answer_count ?? 0 ),
			'upvotes'      => (int) $q->upvotes,
			'downvotes'    => (int) $q->downvotes,
			'views'        => (int) $q->views,
			'status'       => $q->status,
			'created_at'   => $q->created_at,
		);
	}

	/**
	 * Format question full.
	 *
	 * @param mixed $q Q.
	 */
	private function format_question_full( $q ) {
		$data            = $this->format_question( $q );
		$data['answers'] = array_map( array( $this, 'format_answer' ), $this->db->get_answers( $q->id ) );
		$data['topics']  = $this->db->get_question_topics_by_question( $q->id );
		$data['tags']    = $this->db->get_question_tags( $q->id );
		return $data;
	}

	/**
	 * Format answer.
	 *
	 * @param mixed $a A.
	 */
	private function format_answer( $a ) {
		return array(
			'id'          => (int) $a->id,
			'question_id' => (int) $a->question_id,
			'user_id'     => (int) $a->user_id,
			'content'     => $a->content,
			'is_accepted' => (bool) $a->is_accepted,
			'upvotes'     => (int) $a->upvotes,
			'downvotes'   => (int) $a->downvotes,
			'created_at'  => $a->created_at,
		);
	}

	/**
	 * Spaces.
	 *
	 * @param mixed $request Request.
	 */
	public function get_spaces( $request ) {
		$per_page = $request->get_param( 'per_page' ) ?: 20;
		$page     = $request->get_param( 'page' ) ?: 1;
		$offset   = ( $page - 1 ) * $per_page;
		$spaces   = $this->db->get_spaces(
			array(
				'limit'       => $per_page,
				'offset'      => $offset,
				'public_only' => true,
			)
		);
		$data     = array_map(
			function ( $s ) {
				return array(
					'id'           => (int) $s->id,
					'name'         => $s->name,
					'slug'         => $s->slug,
					'description'  => $s->description,
					'member_count' => (int) $s->member_count,
					'is_public'    => (bool) $s->is_public,
					'created_at'   => $s->created_at,
				);
			},
			$spaces
		);
		return rest_ensure_response( $data );
	}

	/**
	 * Space.
	 *
	 * @param mixed $request Request.
	 */
	public function get_space( $request ) {
		$id    = $request->get_param( 'id' );
		$space = $this->db->get_space( $id );
		if ( ! $space ) {
			return new WP_Error( 'not_found', __( 'Space not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}
		// Private/invite-only spaces are only visible to members and admins.
		if ( ! $space->is_public ) {
			$current_user_id = get_current_user_id();
			$is_visible      = (int) $space->creator_id === (int) $current_user_id
				|| current_user_can( 'manage_options' )
				|| ( $current_user_id && $this->db->is_space_member( $space->id, $current_user_id ) );
			if ( ! $is_visible ) {
				return new WP_Error( 'not_found', __( 'Space not found.', 'zeko-qa' ), array( 'status' => 404 ) );
			}
		}
		$data = array(
			'id'           => (int) $space->id,
			'name'         => $space->name,
			'slug'         => $space->slug,
			'description'  => $space->description,
			'member_count' => (int) $space->member_count,
			'is_public'    => (bool) $space->is_public,
			'created_at'   => $space->created_at,
			'posts'        => $this->db->get_space_posts( $space->id, 20 ),
		);
		return rest_ensure_response( $data );
	}

	/**
	 * Create space.
	 *
	 * @param mixed $request Request.
	 */
	public function create_space( $request ) {
		$name        = sanitize_text_field( $request->get_param( 'name' ) );
		$description = sanitize_textarea_field( $request->get_param( 'description' ) );
		$user_id     = get_current_user_id();

		if ( empty( $name ) ) {
			return new WP_Error( 'missing_fields', __( 'Name is required.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		$slug     = sanitize_title( $name );
		$existing = $this->db->get_space_by_slug( $slug );
		if ( $existing ) {
			$slug .= '-' . time();
		}

		$space_id = $this->db->create_space(
			array(
				'name'        => $name,
				'slug'        => $slug,
				'description' => $description,
				'creator_id'  => $user_id,
				'is_public'   => absint( $request->get_param( 'is_public' ) ?? 1 ),
			)
		);

		$space = $this->db->get_space( $space_id );
		return rest_ensure_response(
			array(
				'id'           => (int) $space->id,
				'name'         => $space->name,
				'slug'         => $space->slug,
				'description'  => $space->description,
				'is_public'    => (bool) $space->is_public,
				'member_count' => (int) $space->member_count,
				'created_at'   => $space->created_at,
			)
		);
	}

	/**
	 * Join space.
	 *
	 * @param mixed $request Request.
	 */
	public function join_space( $request ) {
		$id      = $request->get_param( 'id' );
		$user_id = get_current_user_id();
		$space   = $this->db->get_space( $id );
		if ( ! $space ) {
			return new WP_Error( 'not_found', __( 'Space not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}
		if ( ! $space->is_public ) {
			return new WP_Error( 'invite_only', __( 'This space is by invitation only.', 'zeko-qa' ), array( 'status' => 403 ) );
		}
		$result = $this->db->join_space( $id, $user_id );
		if ( ! $result ) {
			return new WP_Error( 'already_member', __( 'You are already a member.', 'zeko-qa' ), array( 'status' => 400 ) );
		}
		return rest_ensure_response( array( 'joined' => true ) );
	}

	/**
	 * Leave space.
	 *
	 * @param mixed $request Request.
	 */
	public function leave_space( $request ) {
		$id      = $request->get_param( 'id' );
		$user_id = get_current_user_id();
		$result  = $this->db->leave_space( $id, $user_id );
		return rest_ensure_response( array( 'left' => (bool) $result ) );
	}

	/**
	 * Post to space.
	 *
	 * @param mixed $request Request.
	 */
	public function post_to_space( $request ) {
		$id        = $request->get_param( 'id' );
		$item_id   = absint( $request->get_param( 'item_id' ) );
		$item_type = sanitize_key( $request->get_param( 'item_type' ) );
		$user_id   = get_current_user_id();

		if ( ! $item_id || ! in_array( $item_type, array( 'question', 'answer' ), true ) ) {
			return new WP_Error( 'invalid_params', __( 'Invalid parameters.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		if ( ! $this->db->is_space_member( $id, $user_id ) ) {
			return new WP_Error( 'not_member', __( 'You must be a member to post.', 'zeko-qa' ), array( 'status' => 403 ) );
		}

		$post_id = $this->db->post_to_space( $id, $item_id, $item_type, $user_id );
		return rest_ensure_response(
			array(
				'posted'  => true,
				'post_id' => $post_id,
			),
			201
		);
	}

	/**
	 * Create bounty.
	 *
	 * @param mixed $request Request.
	 */
	public function create_bounty( $request ) {
		$question_id = absint( $request->get_param( 'question_id' ) );
		$amount      = absint( $request->get_param( 'amount' ) );
		$user_id     = get_current_user_id();

		if ( ! $question_id || $amount < 5 ) {
			return new WP_Error( 'invalid_params', __( 'Minimum bounty is 5 reputation.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		$question = $this->db->get_question( $question_id );
		if ( ! $question ) {
			return new WP_Error( 'not_found', __( 'Question not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}

		if ( absint( $question->user_id ) !== $user_id ) {
			return new WP_Error( 'forbidden', __( 'Only the question author can set a bounty.', 'zeko-qa' ), array( 'status' => 403 ) );
		}

		$existing = $this->db->get_open_bounty_for_question( $question_id );
		if ( $existing ) {
			return new WP_Error( 'bounty_exists', __( 'This question already has an open bounty.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		$reputation = $this->db->get_user_reputation( $user_id );
		if ( $reputation < $amount ) {
			return new WP_Error( 'insufficient_reputation', __( 'Not enough reputation.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		$bounty_id = $this->db->create_bounty(
			array(
				'question_id' => $question_id,
				'user_id'     => $user_id,
				'amount'      => $amount,
			)
		);

		$this->db->log_reputation( $user_id, -$amount, 'bounty_posted', $bounty_id, 'bounty' );

		return rest_ensure_response(
			array(
				'bounty_id' => $bounty_id,
				'amount'    => $amount,
			),
			201
		);
	}

	/**
	 * Award bounty.
	 *
	 * @param mixed $request Request.
	 */
	public function award_bounty( $request ) {
		$bounty_id = absint( $request['id'] );
		$winner_id = absint( $request->get_param( 'winner_id' ) );
		$user_id   = get_current_user_id();

		if ( ! $winner_id ) {
			return new WP_Error( 'invalid_params', __( 'Winner user ID is required.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		$bounty = $this->db->get_bounty( $bounty_id );
		if ( ! $bounty ) {
			return new WP_Error( 'not_found', __( 'Bounty not found.', 'zeko-qa' ), array( 'status' => 404 ) );
		}
		if ( absint( $bounty->user_id ) !== $user_id ) {
			return new WP_Error( 'forbidden', __( 'Only the bounty owner can award it.', 'zeko-qa' ), array( 'status' => 403 ) );
		}
		if ( 'open' !== $bounty->status ) {
			return new WP_Error( 'not_open', __( 'This bounty is no longer open.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		$answered = false;
		foreach ( $this->db->get_answers( absint( $bounty->question_id ) ) as $answer ) {
			if ( absint( $answer->user_id ) === $winner_id ) {
				$answered = true;
				break;
			}
		}
		if ( ! $answered ) {
			return new WP_Error( 'not_answerer', __( 'Winner must have answered the question.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		if ( ! $this->db->award_bounty( $bounty_id, $winner_id ) ) {
			return new WP_Error( 'award_failed', __( 'Bounty could not be awarded.', 'zeko-qa' ), array( 'status' => 400 ) );
		}

		return rest_ensure_response(
			array(
				'awarded'   => true,
				'bounty_id' => $bounty_id,
				'winner_id' => $winner_id,
			)
		);
	}
}
