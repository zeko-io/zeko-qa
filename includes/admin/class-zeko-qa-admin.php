<?php
/**
 * Admin-facing functionality of Zeko QA
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_QA_Admin. */
class Zeko_QA_Admin {

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
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Locale.
	 */
	private function set_locale() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param mixed $hook Hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'toplevel_page_zeko-qa' === $hook || 'zeko-qa_page_zeko-qa-demo' === $hook ) {
			wp_enqueue_style( 'zeko-qa-admin', ZEKO_QA_PLUGIN_URL . 'assets/css/zeko-qa-admin.css', array(), ZEKO_QA_VERSION );
			wp_enqueue_script( 'zeko-qa-admin', ZEKO_QA_PLUGIN_URL . 'assets/js/zeko-qa-admin.js', array( 'jquery' ), ZEKO_QA_VERSION, true );
			wp_localize_script(
				'zeko-qa-admin',
				'zekoQAAdmin',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'zeko_qa_admin_nonce' ),
					'strings' => array(
						'confirmGenerate' => __( 'Are you sure? This will seed demo data.', 'zeko-qa' ),
						'generating'      => __( 'Generating...', 'zeko-qa' ),
						'done'            => __( 'Done!', 'zeko-qa' ),
					),
				)
			);
		}
	}

	/**
	 * Define admin hooks.
	 */
	private function define_admin_hooks() {
		add_action( 'admin_post_zeko_qa_generate_demo', array( $this, 'generate_demo_data' ) );
		add_action( 'wp_ajax_zeko_qa_generate_demo', array( $this, 'generate_demo_data' ) );
		add_action( 'wp_ajax_zeko_qa_clear_demo', array( $this, 'clear_demo_data' ) );
		add_action( 'wp_ajax_zeko_qa_flush_rewrites', array( $this, 'flush_rewrites' ) );
		add_action( 'wp_ajax_zeko_qa_moderate_action', array( $this, 'handle_moderate' ) );
		add_action( 'init', array( $this, 'maybe_seed_badges' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade_db' ), 20 );
	}

	/**
	 * Upgrade db.
	 */
	public function maybe_upgrade_db() {
		$current = get_option( 'zeko_qa_db_version', '0' );
		if ( ZEKO_QA_DB_VERSION !== $current ) {
			$this->db->create_tables();
			$this->db->add_missing_indexes();
		}
	}

	/**
	 * Seed badges.
	 */
	public function maybe_seed_badges() {
		// Only short-circuit when the seed flag is set AND badges actually.
		// exist. A live DB can end up flag-set/table-empty (e.g. a manual.
		// truncate), which otherwise permanently disables badge awards.
		if ( get_option( 'zeko_qa_badges_seeded' ) && ! empty( $this->db->get_badges() ) ) {
			return;
		}
		$this->seed_default_badges();
		update_option( 'zeko_qa_badges_seeded', true );
	}

	/**
	 * Seed default badges.
	 */
	private function seed_default_badges() {
		$badges = array(
			array(
				'name'           => 'First Question',
				'slug'           => 'first-question',
				'description'    => 'Asked your first question.',
				'icon'           => 'help-circle',
				'criteria_type'  => 'question_count',
				'criteria_value' => 1,
				'points'         => 5,
			),
			array(
				'name'           => 'First Answer',
				'slug'           => 'first-answer',
				'description'    => 'Posted your first answer.',
				'icon'           => 'message-square',
				'criteria_type'  => 'answer_count',
				'criteria_value' => 1,
				'points'         => 5,
			),
			array(
				'name'           => 'First Upvote',
				'slug'           => 'first-upvote',
				'description'    => 'Received your first upvote.',
				'icon'           => 'thumbs-up',
				'criteria_type'  => 'upvotes',
				'criteria_value' => 1,
				'points'         => 10,
			),
			array(
				'name'           => 'Helpful',
				'slug'           => 'helpful',
				'description'    => 'Answer was accepted for the first time.',
				'icon'           => 'check-circle',
				'criteria_type'  => 'accepted_count',
				'criteria_value' => 1,
				'points'         => 15,
			),
			array(
				'name'           => '10 Answer Club',
				'slug'           => 'ten-answers',
				'description'    => 'Posted 10 answers.',
				'icon'           => 'award',
				'criteria_type'  => 'answer_count',
				'criteria_value' => 10,
				'points'         => 20,
			),
			array(
				'name'           => '50 Answer Club',
				'slug'           => 'fifty-answers',
				'description'    => 'Posted 50 answers.',
				'icon'           => 'award',
				'criteria_type'  => 'answer_count',
				'criteria_value' => 50,
				'points'         => 50,
			),
			array(
				'name'           => '100 Answer Club',
				'slug'           => 'hundred-answers',
				'description'    => 'Posted 100 answers.',
				'icon'           => 'award',
				'criteria_type'  => 'answer_count',
				'criteria_value' => 100,
				'points'         => 100,
			),
			array(
				'name'           => 'Accepted Author',
				'slug'           => 'accepted-author',
				'description'    => 'Had an answer accepted.',
				'icon'           => 'check-circle',
				'criteria_type'  => 'accepted_count',
				'criteria_value' => 1,
				'points'         => 15,
			),
			array(
				'name'           => '10 Accepted',
				'slug'           => 'ten-accepted',
				'description'    => 'Had 10 answers accepted.',
				'icon'           => 'check-circle',
				'criteria_type'  => 'accepted_count',
				'criteria_value' => 10,
				'points'         => 50,
			),
			array(
				'name'           => 'Popular Asker',
				'slug'           => 'popular-asker',
				'description'    => 'Questions received 50+ upvotes total.',
				'icon'           => 'trending-up',
				'criteria_type'  => 'question_upvotes',
				'criteria_value' => 50,
				'points'         => 30,
			),
			array(
				'name'           => 'Top Writer',
				'slug'           => 'top-writer',
				'description'    => 'Answers received 100+ upvotes total.',
				'icon'           => 'zap',
				'criteria_type'  => 'answer_upvotes',
				'criteria_value' => 100,
				'points'         => 100,
			),
		);

		foreach ( $badges as $badge ) {
			$this->db->insert_badge( $badge );
		}
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Zeko QA', 'zeko-qa' ),
			__( 'Zeko QA', 'zeko-qa' ),
			'manage_options',
			'zeko-qa',
			array( $this, 'render_settings_page' ),
			'dashicons-welcome-learn-new',
			30
		);
		add_submenu_page(
			'zeko-qa',
			__( 'Demo Data', 'zeko-qa' ),
			__( 'Demo Data', 'zeko-qa' ),
			'manage_options',
			'zeko-qa-demo',
			array( $this, 'render_demo_page' )
		);
		add_submenu_page(
			'zeko-qa',
			__( 'Moderation', 'zeko-qa' ),
			__( 'Moderation', 'zeko-qa' ),
			'manage_options',
			'zeko-qa-moderation',
			array( $this, 'render_moderation_page' )
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		require ZEKO_QA_PLUGIN_PATH . 'templates/admin/settings.php';
	}

	/**
	 * Render demo page.
	 */
	public function render_demo_page() {
		require ZEKO_QA_PLUGIN_PATH . 'templates/admin/demo-data.php';
	}

	/**
	 * Render moderation page.
	 */
	public function render_moderation_page() {
		$reports = $this->db->get_reports( 'pending', 50, 0 );
		require ZEKO_QA_PLUGIN_PATH . 'templates/admin/moderation.php';
	}

	/**
	 * Handle moderate.
	 */
	public function handle_moderate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'zeko-qa' ) ) );
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ?? '' ) ), 'zeko_qa_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'zeko-qa' ) ) );
		}

		$action    = sanitize_key( $_POST['mod_action'] ?? '' );
		$report_id = absint( $_POST['report_id'] ?? 0 );
		$item_id   = absint( $_POST['item_id'] ?? 0 );
		$item_type = sanitize_key( $_POST['item_type'] ?? '' );

		if ( ! $action || ! $report_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'zeko-qa' ) ) );
		}

		switch ( $action ) {
			case 'dismiss':
				$this->db->update_report_status( $report_id, 'dismissed' );
				break;
			case 'resolve':
				$this->db->update_report_status( $report_id, 'resolved' );
				break;
			case 'delete_question':
				if ( 'question' === $item_type && $item_id ) {
					$this->db->delete_question_admin( $item_id );
					$this->db->update_report_status( $report_id, 'resolved' );
				}
				break;
			case 'delete_answer':
				if ( 'answer' === $item_type && $item_id ) {
					$this->db->delete_answer_admin( $item_id );
					$this->db->update_report_status( $report_id, 'resolved' );
				}
				break;
			default:
				wp_send_json_error( array( 'message' => __( 'Unknown action.', 'zeko-qa' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Action completed.', 'zeko-qa' ) ) );
	}

	/**
	 * Generate demo data.
	 */
	public function generate_demo_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'zeko-qa' ) ) );
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ?? '' ) ), 'zeko_qa_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'zeko-qa' ) ) );
		}

		$this->db->create_tables();

		require_once ZEKO_QA_PLUGIN_PATH . 'includes/admin/class-zeko-qa-demo-generator.php';
		$generator = new Zeko_QA_Demo_Generator( $this->db );

		try {
			$result = $generator->run();
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
			return;
		}
		wp_send_json_success( array( 'message' => $result ) );
	}

	/**
	 * Clear demo data.
	 */
	public function clear_demo_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'zeko-qa' ) ) );
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ?? '' ) ), 'zeko_qa_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'zeko-qa' ) ) );
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $this->db->get_all_tables() as $table ) {
			$wpdb->query( "TRUNCATE TABLE {$table}" );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$demo_users = get_users(
			array(
				'meta_key'   => 'zeko_demo_user',
				'meta_value' => '1',
				'number'     => 500,
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$admin_id = get_current_user_id();
		foreach ( $demo_users as $demo_user ) {
			if ( $demo_user->ID === $admin_id ) {
				continue;
			}
			wp_delete_user( $demo_user->ID );
		}

		wp_send_json_success( array( 'message' => __( 'Demo data cleared.', 'zeko-qa' ) ) );
	}

	/**
	 * Flush rewrites.
	 */
	public function flush_rewrites() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'zeko-qa' ) ) );
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ?? '' ) ), 'zeko_qa_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'zeko-qa' ) ) );
		}
		flush_rewrite_rules();
		wp_send_json_success( array( 'message' => __( 'Rewrite rules flushed.', 'zeko-qa' ) ) );
	}
}
