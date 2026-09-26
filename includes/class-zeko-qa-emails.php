<?php
/**
 * Email notification system for Zeko QA.
 *
 * Sends answer-posted and answer-accepted emails with opt-out support.
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_QA_Emails. */
class Zeko_QA_Emails {

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
		add_action( 'init', array( $this, 'handle_unsubscribe' ) );
	}

	/**
	 * Enabled.
	 */
	public static function is_enabled(): bool {
		return (bool) get_option( 'zeko_qa_email_notifications_enabled', 1 );
	}

	/**
	 * From email.
	 */
	private function get_from_email(): string {
		return get_option( 'admin_email' );
	}

	/**
	 * From name.
	 */
	private function get_from_name(): string {
		return get_option( 'blogname' );
	}

	/**
	 * Headers.
	 */
	private function get_headers(): array {
		return array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>',
		);
	}

	/**
	 * Wrap template.
	 * Polished, brand-consistent wrapper: accent strip, gradient-safe brand
	 * header with a module tagline, roomy content card, and a quiet footer.
	 *
	 * @param string $title Title.
	 * @param string $body Body.
	 */
	private function wrap_template( string $title, string $body ): string {
		$site_name = get_bloginfo( 'name' );
		$tagline   = __( 'Ask. Learn. Share what you know.', 'zeko-qa' );

		$brand      = '#4f46e5';
		$brand_dark = '#4338ca';
		$bg         = '#f1f5f9';
		$ink        = '#0f172a';
		$muted      = '#64748b';
		$border     = '#e2e8f0';

		return '<div style="background:' . $bg . ';padding:24px 16px;font-family:Arial,Helvetica,sans-serif;">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;">'
			. '<tr><td style="background:' . $brand . ';height:6px;line-height:6px;font-size:0;">&nbsp;</td></tr>'
			. '<tr><td style="background:' . $brand_dark . ';padding:26px 30px;text-align:center;">'
			. '<h1 style="margin:0;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:0.3px;">' . esc_html( $site_name ) . '</h1>'
			. '<p style="margin:6px 0 0;font-size:13px;color:rgba(255,255,255,0.9);">' . esc_html( $tagline ) . '</p>'
			. '</td></tr>'
			. '<tr><td style="background:#ffffff;padding:32px 30px;">'
			. '<h2 style="margin:0 0 18px;font-size:18px;font-weight:700;color:' . $ink . ';">' . esc_html( $title ) . '</h2>'
			. $body
			. '</td></tr>'
			. '<tr><td style="background:#ffffff;border-top:1px solid ' . $border . ';padding:16px 30px;text-align:center;">'
			. '<p style="margin:0;font-size:12px;color:' . $muted . ';">&copy; ' . esc_html( gmdate( 'Y' ) ) . ' ' . esc_html( $site_name ) . ' &middot; <a href="' . esc_url( home_url( '/' ) ) . '" style="color:' . $muted . ';">' . esc_html__( 'Visit site', 'zeko-qa' ) . '</a></p>'
			. '</td></tr>'
			. '</table></div>';
	}

	/**
	 * Unsubscribe url.
	 *
	 * @param int $user_id User id.
	 */
	private function get_unsubscribe_url( int $user_id ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'zeko_qa_unsubscribe' => '1',
					'user_id'             => $user_id,
				),
				home_url( '/' )
			),
			'zeko_qa_email_unsubscribe_' . $user_id
		);
	}

	/**
	 * User unsubscribed.
	 *
	 * @param int $user_id User id.
	 */
	private function is_user_unsubscribed( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, 'zeko_qa_email_unsubscribed', true );
	}

	/**
	 * Demo recipient.
	 *
	 * @param int $user_id User id.
	 */
	private function is_demo_recipient( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, 'zeko_demo_user', true );
	}

	/**
	 * Handle unsubscribe.
	 */
	public function handle_unsubscribe() {
		if ( empty( $_GET['zeko_qa_unsubscribe'] ) ) {
			return;
		}

		$user_id = absint( $_GET['user_id'] ?? 0 );
		$nonce   = sanitize_key( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

		if ( ! $user_id || ! wp_verify_nonce( $nonce, 'zeko_qa_email_unsubscribe_' . $user_id ) ) {
			wp_die( esc_html__( 'Invalid or expired link.', 'zeko-qa' ), '', array( 'response' => 403 ) );
		}

		update_user_meta( $user_id, 'zeko_qa_email_unsubscribed', 1 );

		wp_die(
			'<div style="text-align:center;padding:80px 20px;font-family:Arial,sans-serif;">'
			. '<h2>' . esc_html__( 'You have been unsubscribed.', 'zeko-qa' ) . '</h2>'
			. '<p>' . esc_html__( 'You will no longer receive email notifications from Q&A.', 'zeko-qa' ) . '</p>'
			. '<p><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Return to site', 'zeko-qa' ) . '</a></p>'
			. '</div>',
			esc_html__( 'Unsubscribed', 'zeko-qa' ),
			array( 'response' => 200 )
		);
	}

	/**
	 * Send answer notification.
	 *
	 * @param mixed $question Question.
	 * @param mixed $answer Answer.
	 */
	public function send_answer_notification( $question, $answer ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$recipient_id = absint( $question->user_id );
		if ( ! $recipient_id || $this->is_user_unsubscribed( $recipient_id ) || $this->is_demo_recipient( $recipient_id ) ) {
			return;
		}

		$recipient = get_userdata( $recipient_id );
		$author    = get_userdata( absint( $answer->user_id ) );
		if ( ! $recipient || ! $author || empty( $recipient->user_email ) ) {
			return;
		}

		$question_url = home_url( '/questions/' . $question->slug . '/#answer-' . $answer->id );
		$site_name    = get_bloginfo( 'name' );

		$title = sprintf(
			/* translators: 1: site name, 2: question title */
			__( '[%1$s] New answer on: %2$s', 'zeko-qa' ),
			$site_name,
			$question->title
		);

		$body = '<p>' . sprintf(
			/* translators: recipient name */
			__( 'Hi %s,', 'zeko-qa' ),
			esc_html( $recipient->display_name )
		) . '</p>'
		. '<p>' . sprintf(
			/* translators: 1: author name, 2: question title */
			__( '<strong>%1$s</strong> posted an answer on your question <strong>%2$s</strong>.', 'zeko-qa' ),
			esc_html( $author->display_name ),
			esc_html( $question->title )
		) . '</p>'
		. '<div style="background:#f8fafc;border-left:3px solid #4f46e5;padding:12px 16px;border-radius:0 6px 6px 0;margin:16px 0;">'
		. wp_kses_post( wp_trim_words( $answer->content, 50 ) )
		. '</div>'
		. '<p style="text-align:center;margin:32px 0;">'
		. '<a href="' . esc_url( $question_url ) . '" style="background:#4f46e5;color:#fff;padding:14px 32px;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;">'
		. esc_html__( 'View Answer', 'zeko-qa' ) . '</a></p>'
		. '<p style="font-size:12px;color:#757575;">'
		. '<a href="' . esc_url( $this->get_unsubscribe_url( $recipient_id ) ) . '">'
		. esc_html__( 'Unsubscribe from email notifications', 'zeko-qa' ) . '</a></p>';

		$headers = $this->get_headers();
		$headers = apply_filters( 'zeko_qa_email_headers', $headers, $recipient->user_email, $title );

		$body = apply_filters( 'zeko_qa_email_body', $body, $answer->id, 'answer_notification' );

		wp_mail( $recipient->user_email, $title, $this->wrap_template( $title, $body ), $headers );
	}

	/**
	 * Send answer accepted email.
	 *
	 * @param mixed $question Question.
	 * @param mixed $answer Answer.
	 */
	public function send_answer_accepted_email( $question, $answer ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$recipient_id = absint( $answer->user_id );
		if ( ! $recipient_id || $this->is_user_unsubscribed( $recipient_id ) || $this->is_demo_recipient( $recipient_id ) ) {
			return;
		}

		$recipient = get_userdata( $recipient_id );
		$asker     = get_userdata( absint( $question->user_id ) );
		if ( ! $recipient || empty( $recipient->user_email ) ) {
			return;
		}

		$question_url = home_url( '/questions/' . $question->slug . '/#answer-' . $answer->id );
		$site_name    = get_bloginfo( 'name' );

		$title = sprintf(
			/* translators: 1: site name, 2: question title */
			__( '[%1$s] Your answer was accepted: %2$s', 'zeko-qa' ),
			$site_name,
			$question->title
		);

		$body = '<p>' . sprintf(
			/* translators: recipient name */
			__( 'Hi %s,', 'zeko-qa' ),
			esc_html( $recipient->display_name )
		) . '</p>'
		. '<p>' . sprintf(
			/* translators: question title */
			__( 'Your answer on <strong>%s</strong> has been accepted!', 'zeko-qa' ),
			esc_html( $question->title )
		) . '</p>';

		if ( $asker ) {
			$body .= '<p>' . sprintf(
				/* translators: asker name */
				__( 'The question author <strong>%s</strong> marked your answer as the accepted solution.', 'zeko-qa' ),
				esc_html( $asker->display_name )
			) . '</p>';
		}

		$body .= '<p style="text-align:center;margin:32px 0;">'
		. '<a href="' . esc_url( $question_url ) . '" style="background:#2e7d32;color:#fff;padding:14px 32px;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;">'
		. esc_html__( 'View Your Answer', 'zeko-qa' ) . '</a></p>'
		. '<p style="font-size:12px;color:#757575;">'
		. '<a href="' . esc_url( $this->get_unsubscribe_url( $recipient_id ) ) . '">'
		. esc_html__( 'Unsubscribe from email notifications', 'zeko-qa' ) . '</a></p>';

		$headers = $this->get_headers();
		$headers = apply_filters( 'zeko_qa_email_headers', $headers, $recipient->user_email, $title );

		$body = apply_filters( 'zeko_qa_email_body', $body, $answer->id, 'answer_accepted' );

		wp_mail( $recipient->user_email, $title, $this->wrap_template( $title, $body ), $headers );
	}
}
