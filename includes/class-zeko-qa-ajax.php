<?php
/**
 * AJAX handler class for Zeko QA
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_QA_AJAX. */
class Zeko_QA_AJAX {

	/**
	 * Db.
	 *
	 * @var mixed Db.
	 */
	private $db;

	/**
	 * Construct.
	 *
	 * @param mixed $db Db.
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->define_hooks();
	}

	/**
	 * Define hooks.
	 */
	private function define_hooks() {
		add_action( 'wp_ajax_zeko_qa_vote', array( $this, 'handle_vote' ) );
		add_action( 'wp_ajax_nopriv_zeko_qa_vote', array( $this, 'handle_vote_login_required' ) );
		add_action( 'wp_ajax_zeko_qa_submit_answer', array( $this, 'handle_submit_answer' ) );
		add_action( 'wp_ajax_nopriv_zeko_qa_submit_answer', array( $this, 'handle_answer_login_required' ) );
		add_action( 'wp_ajax_zeko_qa_submit_question', array( $this, 'handle_submit_question' ) );
		add_action( 'wp_ajax_nopriv_zeko_qa_submit_question', array( $this, 'handle_question_login_required' ) );
		add_action( 'admin_post_nopriv_zeko_qa_submit_question', array( $this, 'handle_submit_question' ) );
		add_action( 'admin_post_zeko_qa_submit_question', array( $this, 'handle_submit_question' ) );
		add_action( 'wp_ajax_zeko_qa_report', array( $this, 'handle_report' ) );
		add_action( 'wp_ajax_nopriv_zeko_qa_report', array( $this, 'handle_report_login_required' ) );
		add_action( 'wp_ajax_zeko_qa_accept_answer', array( $this, 'handle_accept_answer' ) );
		add_action( 'wp_ajax_zeko_qa_search_suggestions', array( $this, 'handle_search_suggestions' ) );
		add_action( 'wp_ajax_nopriv_zeko_qa_search_suggestions', array( $this, 'handle_search_suggestions' ) );
		add_action( 'wp_ajax_zeko_qa_toggle_topic_follow', array( $this, 'handle_toggle_topic_follow' ) );
		add_action( 'wp_ajax_zeko_qa_toggle_bookmark', array( $this, 'handle_toggle_bookmark' ) );
		add_action( 'wp_ajax_zeko_qa_toggle_question_follow', array( $this, 'handle_toggle_question_follow' ) );
		add_action( 'wp_ajax_zeko_qa_add_comment', array( $this, 'handle_add_comment' ) );
		add_action( 'wp_ajax_nopriv_zeko_qa_add_comment', array( $this, 'handle_comment_login_required' ) );
		add_action( 'wp_ajax_zeko_qa_delete_comment', array( $this, 'handle_delete_comment' ) );
		add_action( 'wp_ajax_zeko_qa_get_notifications', array( $this, 'handle_get_notifications' ) );
		add_action( 'wp_ajax_zeko_qa_mark_notifications_read', array( $this, 'handle_mark_notifications_read' ) );
		add_action( 'wp_ajax_zeko_qa_join_space', array( $this, 'handle_join_space' ) );
		add_action( 'wp_ajax_zeko_qa_leave_space', array( $this, 'handle_leave_space' ) );
	}

	/**
	 * Verify nonce.
	 *
	 * @param string $action Action.
	 */
	private function verify_nonce( $action = 'zeko_qa_public_nonce' ) {
		$nonce = sanitize_key( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'zeko-qa' ) ) );
			die();
		}
	}

	/**
	 * Verify user.
	 */
	private function verify_user() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in to perform this action.', 'zeko-qa' ) ) );
			die();
		}
		return get_current_user_id();
	}

	/**
	 * Handle vote.
	 */
	public function handle_vote() {
		$this->verify_nonce();
		$user_id = $this->verify_user();

		$item_id   = absint( $_POST['item_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		$item_type = sanitize_key( $_POST['item_type'] ?? 'question' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		$vote_type = sanitize_key( $_POST['vote_type'] ?? 'up' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.

		if ( ! $item_id || ! in_array( $item_type, array( 'question', 'answer' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'zeko-qa' ) ) );
			die();
		}

		// Only visible questions (and answers on them) may be voted on.
		$item     = 'question' === $item_type
			? $this->db->get_question( $item_id )
			: $this->db->get_answer( $item_id );
		$question = 'question' === $item_type ? $item : ( $item ? $this->db->get_question( absint( $item->question_id ) ) : null );
		if ( ! $item || ! $question || ! $this->db->can_view_question( $question ) ) {
			wp_send_json_error( array( 'message' => __( 'Item not found.', 'zeko-qa' ) ) );
			die();
		}

		$existing = $this->db->get_vote( $user_id, $item_id, $item_type );

		if ( $existing ) {
			if ( $existing->vote_type === $vote_type ) {
				$this->db->delete_vote( $user_id, $item_id, $item_type );
				$message = __( 'Vote removed.', 'zeko-qa' );
			} else {
				$this->db->delete_vote( $user_id, $item_id, $item_type );
				$this->db->insert_vote(
					array(
						'user_id'   => $user_id,
						'item_id'   => $item_id,
						'item_type' => $item_type,
						'vote_type' => $vote_type,
					)
				);
				$message = __( 'Vote changed.', 'zeko-qa' );
			}
		} else {
			$this->db->insert_vote(
				array(
					'user_id'   => $user_id,
					'item_id'   => $item_id,
					'item_type' => $item_type,
					'vote_type' => $vote_type,
				)
			);
			$message = __( 'Vote recorded.', 'zeko-qa' );
		}

		if ( 'question' === $item_type ) {
			$upvotes   = $this->db->count_votes( $item_id, $item_type, 'up' );
			$downvotes = $this->db->count_votes( $item_id, $item_type, 'down' );
			$this->db->update_question(
				$item_id,
				array(
					'upvotes'   => $upvotes,
					'downvotes' => $downvotes,
				)
			);
		} else {
			$upvotes   = $this->db->count_votes( $item_id, $item_type, 'up' );
			$downvotes = $this->db->count_votes( $item_id, $item_type, 'down' );
			$this->db->update_answer(
				$item_id,
				array(
					'upvotes'   => $upvotes,
					'downvotes' => $downvotes,
				)
			);
		}

		$item_owner_id = 0;
		if ( 'question' === $item_type ) {
			$item          = $this->db->get_question( $item_id );
			$item_owner_id = $item ? absint( $item->user_id ) : 0;
		} else {
			$item          = $this->db->get_answer( $item_id );
			$item_owner_id = $item ? absint( $item->user_id ) : 0;
		}

		if ( $item_owner_id && $item_owner_id !== $user_id ) {
			if ( 'up' === $vote_type && ! ( $existing && 'up' === $existing->vote_type ) ) {
				$points = ( 'answer' === $item_type ) ? 10 : 5;
				$this->db->log_reputation( $item_owner_id, $points, 'upvote_received', $item_id, $item_type );
			}
			if ( $existing && 'up' === $existing->vote_type && 'up' !== $vote_type ) {
				$points = ( 'answer' === $item_type ) ? -10 : -5;
				$this->db->log_reputation( $item_owner_id, $points, 'upvote_removed', $item_id, $item_type );
			}
		}

		if ( $item_owner_id && $item_owner_id !== $user_id ) {
			$this->db->check_and_award_badges( $item_owner_id );
		}

		wp_send_json_success(
			array(
				'message'    => $message,
				'vote_state' => isset( $vote_type ) ? $vote_type : '',
				'net_votes'  => $upvotes - $downvotes,
			)
		);
	}

	/**
	 * Handle vote login required.
	 */
	public function handle_vote_login_required() {
		wp_send_json_error( array( 'message' => __( 'Please login to vote.', 'zeko-qa' ) ) );
	}

	/**
	 * Handle submit answer.
	 */
	public function handle_submit_answer() {
		$this->verify_nonce();
		$user_id = $this->verify_user();

		$question_id = absint( $_POST['question_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		$content     = wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.

		if ( ! $question_id || empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Question ID and content are required.', 'zeko-qa' ) ) );
			die();
		}

		if ( mb_strlen( wp_strip_all_tags( $content ) ) < 10 ) {
			wp_send_json_error( array( 'message' => __( 'Answer must be at least 10 characters.', 'zeko-qa' ) ) );
			die();
		}

		if ( mb_strlen( $content ) > 50000 ) {
			wp_send_json_error( array( 'message' => __( 'Answer is too long (max 50,000 characters).', 'zeko-qa' ) ) );
			die();
		}

		$question = $this->db->get_question( $question_id );
		if ( ! $question || ! $this->db->can_view_question( $question ) ) {
			wp_send_json_error( array( 'message' => __( 'Question not found.', 'zeko-qa' ) ) );
			die();
		}

		$answer_id = $this->db->insert_answer(
			array(
				'question_id' => $question_id,
				'user_id'     => $user_id,
				'content'     => $content,
			)
		);

		$this->db->insert_revision(
			array(
				'item_id'   => $answer_id,
				'item_type' => 'answer',
				'user_id'   => $user_id,
				'content'   => $content,
			)
		);

		if ( function_exists( 'zeko_qa_log_activity' ) ) {
			zeko_qa_log_activity( $user_id, 'answered_question', $question_id, 'question' );
		}

		$this->db->check_and_award_badges( $user_id );

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
			$answer_data = $this->db->get_answer( $answer_id );
			if ( $answer_data ) {
				zeko_qa()->get_emails()->send_answer_notification( $question, $answer_data );
			}
		}

		wp_send_json_success(
			array(
				'message'   => __( 'Answer submitted successfully.', 'zeko-qa' ),
				'answer_id' => $answer_id,
			)
		);
	}

	/**
	 * Handle answer login required.
	 */
	public function handle_answer_login_required() {
		wp_send_json_error( array( 'message' => __( 'Please login to answer.', 'zeko-qa' ) ) );
	}

	/**
	 * Handle submit question.
	 */
	public function handle_submit_question() {
		$nonce = sanitize_key( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'zeko_qa_ask_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'zeko-qa' ) ) );
			die();
		}
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Please login to ask a question.', 'zeko-qa' ) ) );
			die();
		}

		$title      = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
		$content    = wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) );
		$topic_ids  = isset( $_POST['topic_ids'] ) ? array_map( 'absint', (array) $_POST['topic_ids'] ) : array();
		$tags       = sanitize_text_field( wp_unslash( $_POST['tags'] ?? '' ) );
		$visibility = sanitize_key( $_POST['visibility'] ?? 'public' );

		if ( empty( $title ) || empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Title and content are required.', 'zeko-qa' ) ) );
			die();
		}

		if ( mb_strlen( $title ) < 10 ) {
			wp_send_json_error( array( 'message' => __( 'Title must be at least 10 characters.', 'zeko-qa' ) ) );
			die();
		}

		if ( mb_strlen( $title ) > 200 ) {
			wp_send_json_error( array( 'message' => __( 'Title is too long (max 200 characters).', 'zeko-qa' ) ) );
			die();
		}

		if ( mb_strlen( wp_strip_all_tags( $content ) ) < 10 ) {
			wp_send_json_error( array( 'message' => __( 'Content must be at least 10 characters.', 'zeko-qa' ) ) );
			die();
		}

		if ( mb_strlen( $content ) > 50000 ) {
			wp_send_json_error( array( 'message' => __( 'Content is too long (max 50,000 characters).', 'zeko-qa' ) ) );
			die();
		}

		$topic_ids = array_slice( array_unique( $topic_ids ), 0, 5 );
		$topic_ids = array_filter( $topic_ids );

		$slug     = sanitize_title( $title );
		$existing = $this->db->get_question_by_slug( $slug );
		if ( $existing ) {
			$slug .= '-' . time();
		}

		$question_user_id = ( 'anonymous' === $visibility ) ? 0 : $user_id;

		$question_id = $this->db->insert_question(
			array(
				'user_id' => $question_user_id,
				'title'   => $title,
				'slug'    => $slug,
				'content' => $content,
			)
		);

		if ( ! empty( $topic_ids ) ) {
			global $wpdb;
			foreach ( $topic_ids as $topic_id ) {
				$wpdb->insert(
					$this->db->get_table_question_topics(),
					array(
						'question_id' => $question_id,
						'topic_id'    => $topic_id,
					)
				);
			}
		}

		if ( ! empty( $tags ) ) {
			$tag_names = array_map( 'trim', explode( ',', $tags ) );
			$tag_names = array_unique( array_filter( $tag_names ) );
			$tag_ids   = array();
			foreach ( $tag_names as $tag_name ) {
				$tag_ids[] = $this->db->get_or_create_tag( $tag_name );
			}
			$this->db->attach_tags_to_question( $question_id, $tag_ids );
		}

		if ( function_exists( 'zeko_qa_log_activity' ) ) {
			zeko_qa_log_activity( $user_id, 'asked_question', $question_id, 'question' );
		}

		$this->db->check_and_award_badges( $user_id );

		wp_send_json_success(
			array(
				'message'  => __( 'Question posted successfully.', 'zeko-qa' ),
				'redirect' => home_url( '/questions/' . $slug . '/' ),
			)
		);
	}

	/**
	 * Handle question login required.
	 */
	public function handle_question_login_required() {
		wp_send_json_error( array( 'message' => __( 'Please login to ask a question.', 'zeko-qa' ) ) );
	}

	/**
	 * Handle report.
	 */
	public function handle_report() {
		$this->verify_nonce();
		$user_id = $this->verify_user();

		$item_id   = absint( $_POST['item_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		$item_type = sanitize_key( $_POST['item_type'] ?? 'question' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		$reason    = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.

		if ( ! $item_id || empty( $reason ) ) {
			wp_send_json_error( array( 'message' => __( 'Item ID and reason are required.', 'zeko-qa' ) ) );
			die();
		}

		$this->db->insert_report(
			array(
				'user_id'   => $user_id,
				'item_id'   => $item_id,
				'item_type' => $item_type,
				'reason'    => $reason,
			)
		);

		wp_send_json_success( array( 'message' => __( 'Report submitted. Thank you.', 'zeko-qa' ) ) );
	}

	/**
	 * Handle report login required.
	 */
	public function handle_report_login_required() {
		wp_send_json_error( array( 'message' => __( 'Please login to report.', 'zeko-qa' ) ) );
	}

	/**
	 * Handle accept answer.
	 */
	public function handle_accept_answer() {
		$this->verify_nonce();
		$user_id = $this->verify_user();

		$answer_id   = absint( $_POST['answer_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		$question_id = absint( $_POST['question_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.

		if ( ! $answer_id || ! $question_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'zeko-qa' ) ) );
			die();
		}

		$question = $this->db->get_question( $question_id );
		if ( ! $question || absint( $question->user_id ) !== $user_id ) {
			wp_send_json_error( array( 'message' => __( 'You cannot accept this answer.', 'zeko-qa' ) ) );
			die();
		}

		$answer = $this->db->get_answer( $answer_id );
		if ( ! $answer || absint( $answer->question_id ) !== $question_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid answer.', 'zeko-qa' ) ) );
			die();
		}

		$was_accepted = (bool) $answer->is_accepted;

		$this->db->accept_answer( $question_id, $answer_id );
		$this->db->update_question( $question_id, array( 'status' => 'resolved' ) );

		if ( ! $was_accepted && absint( $answer->user_id ) !== $user_id ) {
			$this->db->log_reputation( absint( $answer->user_id ), 15, 'answer_accepted', $answer_id, 'answer' );
			$this->db->check_and_award_badges( absint( $answer->user_id ) );
			$this->db->insert_notification(
				array(
					'user_id'     => $answer->user_id,
					'action'      => 'accepted your answer',
					'object_id'   => $question_id,
					'object_type' => 'question',
					'actor_id'    => $user_id,
				)
			);
			zeko_qa()->get_emails()->send_answer_accepted_email( $question, $answer );
			do_action( 'zeko_qa_answer_accepted', absint( $answer->user_id ), $answer_id, $question_id );
		}

		wp_send_json_success( array( 'message' => __( 'Answer accepted.', 'zeko-qa' ) ) );
	}

	/**
	 * Handle search suggestions.
	 */
	public function handle_search_suggestions() {
		if ( ! $this->rate_limit_search_suggestions() ) {
			return;
		}

		$term = sanitize_text_field( wp_unslash( $_GET['term'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only nopriv search lookup, rate limited.
		$term = trim( $term );

		if ( mb_strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'suggestions' => array() ) );
		}

		$results = $this->db->get_search_suggestions( $term, 8 );

		$suggestions = array();
		foreach ( $results as $row ) {
			$suggestions[] = array(
				'id'           => absint( $row->id ),
				'title'        => esc_html( $row->title ),
				'slug'         => esc_attr( $row->slug ),
				'answer_count' => absint( $row->answer_count ),
			);
		}

		wp_send_json_success( array( 'suggestions' => $suggestions ) );
	}

	/**
	 * Throttle anonymous search-suggestion lookups per user or IP.
	 */
	private function rate_limit_search_suggestions(): bool {
		$limit  = (int) apply_filters( 'zeko_qa_search_suggestions_rate_limit', 30 );
		$window = (int) apply_filters( 'zeko_qa_search_suggestions_rate_window', MINUTE_IN_SECONDS );

		if ( $limit <= 0 || $window <= 0 ) {
			return true; // Disabled via filter.
		}

		$identifier = is_user_logged_in() ? 'u' . get_current_user_id() : 'ip' . md5( $this->client_ip() );
		$key        = 'zeko_qa_rl_search_' . $identifier;
		$count      = (int) get_transient( $key );

		if ( $count >= $limit ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'zeko-qa' ) ) );
			return false;
		}

		set_transient( $key, $count + 1, $window );
		return true;
	}

	/**
	 * Get the client IP address for rate limiting.
	 */
	private function client_ip(): string {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return sanitize_text_field( $ip );
	}

	/**
	 * Handle toggle topic follow.
	 */
	public function handle_toggle_topic_follow() {
		$this->verify_nonce();
		$user_id = $this->verify_user();

		$topic_id = absint( $_POST['topic_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		if ( ! $topic_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid topic.', 'zeko-qa' ) ) );
			die();
		}

		$is_following = $this->db->toggle_topic_follow( $user_id, $topic_id );
		$message      = $is_following ? __( 'Now following this topic.', 'zeko-qa' ) : __( 'Unfollowed this topic.', 'zeko-qa' );

		wp_send_json_success(
			array(
				'message'   => $message,
				'following' => $is_following,
			)
		);
	}

	/**
	 * Handle toggle bookmark.
	 */
	public function handle_toggle_bookmark() {
		$this->verify_nonce();
		$user_id = $this->verify_user();

		$question_id = absint( $_POST['question_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		if ( ! $question_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid question.', 'zeko-qa' ) ) );
			die();
		}

		$question = $this->db->get_question( $question_id );
		if ( ! $question || ! $this->db->can_view_question( $question ) ) {
			wp_send_json_error( array( 'message' => __( 'Question not found.', 'zeko-qa' ) ) );
			die();
		}

		$is_bookmarked = $this->db->toggle_bookmark( $user_id, $question_id );
		$message       = $is_bookmarked ? __( 'Question bookmarked.', 'zeko-qa' ) : __( 'Bookmark removed.', 'zeko-qa' );

		wp_send_json_success(
			array(
				'message'    => $message,
				'bookmarked' => $is_bookmarked,
			)
		);
	}

	/**
	 * Handle toggle question follow.
	 */
	public function handle_toggle_question_follow() {
		$this->verify_nonce();
		$user_id = $this->verify_user();

		$question_id = absint( $_POST['question_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		if ( ! $question_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid question.', 'zeko-qa' ) ) );
			die();
		}

		$question = $this->db->get_question( $question_id );
		if ( ! $question || ! $this->db->can_view_question( $question ) ) {
			wp_send_json_error( array( 'message' => __( 'Question not found.', 'zeko-qa' ) ) );
			die();
		}

		$following = $this->db->toggle_question_follow( $question_id, $user_id );
		$message   = $following ? __( 'You are now following this question.', 'zeko-qa' ) : __( 'You stopped following this question.', 'zeko-qa' );

		wp_send_json_success(
			array(
				'message'   => $message,
				'following' => $following,
			)
		);
	}

	/**
	 * Handle add comment.
	 */
	public function handle_add_comment() {
		$this->verify_nonce();
		$user_id = $this->verify_user();

		$item_id   = absint( $_POST['item_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		$item_type = sanitize_key( $_POST['item_type'] ?? 'answer' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		$content   = sanitize_textarea_field( wp_unslash( $_POST['content'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.

		if ( ! $item_id || empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Item ID and content are required.', 'zeko-qa' ) ) );
			die();
		}

		if ( ! in_array( $item_type, array( 'answer', 'question' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item type.', 'zeko-qa' ) ) );
			die();
		}

		if ( mb_strlen( $content ) > 2000 ) {
			wp_send_json_error( array( 'message' => __( 'Comment is too long (max 2,000 characters).', 'zeko-qa' ) ) );
			die();
		}

		// Comments may only be added to content the user can see.
		if ( 'question' === $item_type ) {
			$target = $this->db->get_question( $item_id );
		} else {
			$answer = $this->db->get_answer( $item_id );
			$target = $answer ? $this->db->get_question( absint( $answer->question_id ) ) : null;
		}
		if ( ! $target || ! $this->db->can_view_question( $target ) ) {
			wp_send_json_error( array( 'message' => __( 'Item not found.', 'zeko-qa' ) ) );
			die();
		}

		$comment_id = $this->db->insert_comment(
			array(
				'user_id'   => $user_id,
				'item_id'   => $item_id,
				'item_type' => $item_type,
				'content'   => $content,
			)
		);

		$user = get_userdata( $user_id );

		wp_send_json_success(
			array(
				'message' => __( 'Comment added.', 'zeko-qa' ),
				'comment' => array(
					'id'              => $comment_id,
					'content'         => esc_html( $content ),
					'author_name'     => $user ? esc_html( $user->display_name ) : '',
					'author_id'       => $user_id,
					'author_nicename' => $user ? $user->user_nicename : '',
					'created_at'      => human_time_diff( strtotime( current_time( 'mysql' ) ), time() ),
				),
			)
		);
	}

	/**
	 * Handle comment login required.
	 */
	public function handle_comment_login_required() {
		wp_send_json_error( array( 'message' => __( 'Please login to comment.', 'zeko-qa' ) ) );
		die();
	}

	/**
	 * Handle delete comment.
	 */
	public function handle_delete_comment() {
		$this->verify_nonce();
		$user_id = $this->verify_user();

		$comment_id = absint( $_POST['comment_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.
		if ( ! $comment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid comment.', 'zeko-qa' ) ) );
			die();
		}

		$result = $this->db->delete_comment( $comment_id, $user_id );
		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Cannot delete this comment.', 'zeko-qa' ) ) );
			die();
		}

		wp_send_json_success( array( 'message' => __( 'Comment deleted.', 'zeko-qa' ) ) );
	}

	/**
	 * Handle get notifications.
	 */
	public function handle_get_notifications() {
		$this->verify_nonce();
		$user_id = $this->verify_user();

		$notifications = $this->db->get_user_notifications( $user_id, 15 );
		$unread_count  = $this->db->get_user_unread_notification_count( $user_id );

		$items = array();
		foreach ( $notifications as $n ) {
			$actor      = get_userdata( $n->actor_id );
			$actor_name = $actor ? $actor->display_name : __( 'Someone', 'zeko-qa' );
			$link       = '';
			if ( 'question' === $n->object_type && $n->object_id ) {
				$question = $this->db->get_question( absint( $n->object_id ) );
				if ( $question ) {
					$link = home_url( '/questions/' . $question->slug . '/' );
				}
			} elseif ( 'answer' === $n->object_type && $n->object_id ) {
				global $wpdb;
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
				$question_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT question_id FROM {$this->db->get_table_answers()} WHERE id = %d",
						absint( $n->object_id )
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
				if ( $question_id ) {
					$question = $this->db->get_question( absint( $question_id ) );
					if ( $question ) {
						$link = home_url( '/questions/' . $question->slug . '/#answer-' . $n->object_id );
					}
				}
			} elseif ( 'topic' === $n->object_type && $n->object_id ) {
				$topic = $this->db->get_topic( absint( $n->object_id ) );
				if ( $topic ) {
					$link = home_url( '/topics/' . $topic->slug . '/' );
				}
			}
			$items[] = array(
				'id'          => (int) $n->id,
				'action'      => $n->action,
				'actor_name'  => esc_html( $actor_name ),
				'object_type' => $n->object_type,
				'object_id'   => (int) $n->object_id,
				'link'        => $link,
				'is_read'     => (bool) $n->is_read,
				'created_at'  => $n->created_at,
			);
		}

		wp_send_json_success(
			array(
				'notifications' => $items,
				'unread_count'  => $unread_count,
			)
		);
	}

	/**
	 * Handle mark notifications read.
	 */
	public function handle_mark_notifications_read() {
		$this->verify_nonce();
		$user_id = $this->verify_user();
		$this->db->mark_notifications_read( $user_id );
		wp_send_json_success( array( 'message' => __( 'All notifications marked as read.', 'zeko-qa' ) ) );
	}

	/**
	 * Handle join space.
	 */
	public function handle_join_space() {
		$this->verify_nonce();
		$user_id  = $this->verify_user();
		$space_id = absint( $_POST['space_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.

		if ( ! $space_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid space.', 'zeko-qa' ) ) );
			die();
		}

		$space = $this->db->get_space( $space_id );
		if ( ! $space ) {
			wp_send_json_error( array( 'message' => __( 'Space not found.', 'zeko-qa' ) ) );
			die();
		}

		// Private/invite-only spaces cannot be self-joined.
		if ( ! $space->is_public ) {
			wp_send_json_error( array( 'message' => __( 'This space is by invitation only.', 'zeko-qa' ) ) );
			die();
		}

		$result = $this->db->join_space( $space_id, $user_id );
		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'You are already a member.', 'zeko-qa' ) ) );
			die();
		}

		wp_send_json_success(
			array(
				'message' => __( 'Joined space.', 'zeko-qa' ),
				'joined'  => true,
			)
		);
	}

	/**
	 * Handle leave space.
	 */
	public function handle_leave_space() {
		$this->verify_nonce();
		$user_id  = $this->verify_user();
		$space_id = absint( $_POST['space_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_nonce() at handler top.

		if ( ! $space_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid space.', 'zeko-qa' ) ) );
			die();
		}

		$result = $this->db->leave_space( $space_id, $user_id );
		wp_send_json_success(
			array(
				'message' => __( 'Left space.', 'zeko-qa' ),
				'left'    => (bool) $result,
			)
		);
	}
}
