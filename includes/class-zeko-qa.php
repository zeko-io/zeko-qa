<?php
/**
 * Core singleton class for Zeko QA
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_QA. */
class Zeko_QA {

	/**
	 * Instance.
	 *
	 * @var mixed Instance.
	 */
	private static $instance = null;

	/**
	 * Db.
	 *
	 * @var mixed Db.
	 */
	private $db;
	/**
	 * Public.
	 *
	 * @var mixed Public.
	 */
	private $public;
	/**
	 * Admin.
	 *
	 * @var mixed Admin.
	 */
	private $admin;
	/**
	 * Ajax.
	 *
	 * @var mixed Ajax.
	 */
	private $ajax;
	/**
	 * Ecosystem.
	 *
	 * @var mixed Ecosystem.
	 */
	private $ecosystem;
	/**
	 * Rest api.
	 *
	 * @var mixed Rest api.
	 */
	private $rest_api;
	/**
	 * Emails.
	 *
	 * @var mixed Emails.
	 */
	private $emails;

	/**
	 * Instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Construct.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->init_hooks();
	}

	/**
	 * Load dependencies.
	 */
	private function load_dependencies() {
		require_once ZEKO_QA_PLUGIN_PATH . 'includes/db/class-zeko-qa-db.php';
		require_once ZEKO_QA_PLUGIN_PATH . 'includes/public/class-zeko-qa-public.php';
		require_once ZEKO_QA_PLUGIN_PATH . 'includes/admin/class-zeko-qa-admin.php';
		require_once ZEKO_QA_PLUGIN_PATH . 'includes/class-zeko-qa-ajax.php';
		require_once ZEKO_QA_PLUGIN_PATH . 'includes/class-zeko-qa-ecosystem.php';
		require_once ZEKO_QA_PLUGIN_PATH . 'includes/class-zeko-qa-rest-api.php';
		require_once ZEKO_QA_PLUGIN_PATH . 'includes/class-zeko-qa-emails.php';
		require_once ZEKO_QA_PLUGIN_PATH . 'includes/privacy/class-zeko-qa-privacy.php';

		$this->db        = new Zeko_QA_DB();
		$this->public    = new Zeko_QA_Public( $this->db );
		$this->admin     = new Zeko_QA_Admin( $this->db );
		$this->ajax      = new Zeko_QA_AJAX( $this->db );
		$this->ecosystem = new Zeko_QA_Ecosystem( $this->db );
		$this->rest_api  = new Zeko_QA_REST_API( $this->db );
		$this->emails    = new Zeko_QA_Emails( $this->db );

		// Migration support — register Q&A migrators.
		$base_file = ZEKO_QA_PLUGIN_PATH . '../zeko-core/includes/class-zeko-migrator-base.php';
		if ( file_exists( $base_file ) ) {
			require_once $base_file;
		}
		$migrator_files = array(
			'migrator/class-zeko-migrate-wpforo.php',
			'migrator/class-zeko-migrate-jetonomy.php',
			'migrator/class-zeko-migrate-anspress.php',
			'migrator/class-zeko-migrate-dwqa.php',
		);
		foreach ( $migrator_files as $mf ) {
			$mf_path = ZEKO_QA_PLUGIN_PATH . 'includes/' . $mf;
			if ( file_exists( $mf_path ) ) {
				require_once $mf_path;
			}
		}
		add_filter(
			'zbp_available_migrators',
			function ( array $migrators ): array {
				$migrators['wpforo']   = array(
					'class'   => 'Zeko_Migrate_WpForo',
					'label'   => 'wpForo',
					'package' => 'zeko-qa',
				);
				$migrators['jetonomy'] = array(
					'class'   => 'Zeko_Migrate_Jetonomy',
					'label'   => 'Jetonomy',
					'package' => 'zeko-qa',
				);
				$migrators['anspress'] = array(
					'class'   => 'Zeko_Migrate_AnsPress',
					'label'   => 'AnsPress',
					'package' => 'zeko-qa',
				);
				$migrators['dwqa']     = array(
					'class'   => 'Zeko_Migrate_DWQA',
					'label'   => 'DW Question & Answer',
					'package' => 'zeko-qa',
				);
				return $migrators;
			}
		);
	}

	/**
	 * Locale.
	 */
	private function set_locale() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'zeko-qa', false, dirname( ZEKO_QA_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Init hooks.
	 */
	private function init_hooks() {
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Deactivate.
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Db.
	 */
	public function get_db() {
		return $this->db;
	}

	/**
	 * Public.
	 */
	public function get_public() {
		return $this->public;
	}

	/**
	 * Admin.
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Ajax.
	 */
	public function get_ajax() {
		return $this->ajax;
	}

	/**
	 * Ecosystem.
	 */
	public function get_ecosystem() {
		return $this->ecosystem;
	}

	/**
	 * Emails.
	 */
	public function get_emails() {
		return $this->emails;
	}
}
