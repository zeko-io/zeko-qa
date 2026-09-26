<?php
/**
 * Plugin Name: Zeko QA
 * Plugin URI: https://ozconsultz.com/zeko-qa
 * Description: A high-performance Q&A engine with custom database architecture, algorithmic sorting, and deep ecosystem integration.
 * Version: 1.0.0
 * Author: Zeko Team
 * Author URI: https://ozconsultz.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zeko-qa
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 7.1.2
 *
 * @package Zeko_ZEKO_QA
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ZEKO_QA_VERSION' ) ) {
	define( 'ZEKO_QA_VERSION', '1.0.0' );
}

if ( ! defined( 'ZEKO_QA_PLUGIN_PATH' ) ) {
	define( 'ZEKO_QA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ZEKO_QA_PLUGIN_URL' ) ) {
	define( 'ZEKO_QA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ZEKO_QA_PLUGIN_BASENAME' ) ) {
	define( 'ZEKO_QA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'ZEKO_QA_DB_VERSION' ) ) {
	define( 'ZEKO_QA_DB_VERSION', '1.2.0' );
}

require_once ZEKO_QA_PLUGIN_PATH . 'includes/class-zeko-qa.php';

/**
 * Zeko qa init.
 */
function zeko_qa_init() {
	$instance = Zeko_QA::instance();
	return $instance;
}

add_action( 'plugins_loaded', 'zeko_qa_init' );

/**
 * Zeko qa.
 */
function zeko_qa() {
	return Zeko_QA::instance();
}

/**
 * Zeko qa activate.
 */
function zeko_qa_activate() {
	require_once ZEKO_QA_PLUGIN_PATH . 'includes/db/class-zeko-qa-db.php';
	$db = new Zeko_QA_DB();
	$db->create_tables();
	zeko_qa_create_shortcode_pages();
	flush_rewrite_rules();
}

/**
 * Zeko qa deactivate.
 */
function zeko_qa_deactivate() {
	flush_rewrite_rules();
}

/**
 * Zeko qa create shortcode pages.
 */
function zeko_qa_create_shortcode_pages() {
	$pages = array(
		'questions'      => array(
			'title'   => __( 'Questions', 'zeko-qa' ),
			'content' => '[zeko_qa_archive]',
		),
		'ask-a-question' => array(
			'title'   => __( 'Ask a Question', 'zeko-qa' ),
			'content' => '[zeko_qa_ask_form]',
		),
		'qa-dashboard'   => array(
			'title'   => __( 'Q&A Dashboard', 'zeko-qa' ),
			'content' => '[zeko_qa_dashboard]',
		),
	);

	foreach ( $pages as $slug => $page ) {
		$existing = class_exists( 'Zeko_Core_Helpers' )
			? Zeko_Core_Helpers::get_instance()->get_page_by_slug( $slug )
			: get_page_by_path( $slug );
		if ( ! $existing ) {
			$result = wp_insert_post(
				array(
					'post_title'   => $page['title'],
					'post_content' => $page['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_name'    => $slug,
				)
			);
			if ( is_wp_error( $result ) ) {
				error_log( 'Zeko QA: Failed to create page "' . $slug . '": ' . $result->get_error_message() );
			} elseif ( function_exists( 'zeko_mark_plugin_page' ) ) {
					zeko_mark_plugin_page( $result, 'qa' );
			}
		}
	}
}

/**
 * Zeko qa maybe create pages.
 */
function zeko_qa_maybe_create_pages() {
	$pages_created = get_option( 'zeko_qa_pages_created', false );
	if ( ! $pages_created ) {
		zeko_qa_create_shortcode_pages();
		update_option( 'zeko_qa_pages_created', true );
	}
}

register_activation_hook( __FILE__, 'zeko_qa_activate' );
register_deactivation_hook( __FILE__, 'zeko_qa_deactivate' );
add_action( 'admin_init', 'zeko_qa_maybe_create_pages' );
