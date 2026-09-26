<?php
/**
 * Zeko QA uninstall.
 *
 * Drops all Zeko QA tables, removes options, deletes only shortcode-created
 * pages, and clears Zeko QA user meta.
 *
 * @package Zeko_QA
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	'zeko_questions',
	'zeko_answers',
	'zeko_qa_votes',
	'zeko_qa_topics',
	'zeko_qa_question_topics',
	'zeko_qa_tags',
	'zeko_qa_question_tags',
	'zeko_qa_notifications',
	'zeko_qa_reports',
	'zeko_qa_revisions',
	'zeko_qa_reputation_log',
	'zeko_qa_badges',
	'zeko_qa_user_badges',
	'zeko_qa_topic_followers',
	'zeko_qa_bookmarks',
	'zeko_qa_comments',
	'zeko_qa_question_followers',
	'zeko_qa_spaces',
	'zeko_qa_space_members',
	'zeko_qa_space_posts',
	'zeko_qa_bounties',
);

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
	// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
}

delete_option( 'zeko_qa_db_version' );
delete_option( 'zeko_qa_pages_created' );
delete_option( 'zeko_qa_badges_seeded' );

// Remove only pages this plugin created (marker-verified via Zeko Core, with.
// a legacy slug + shortcode-content check for pre-marker installs). A user's.
// unrelated page that merely embeds a Q&A shortcode is never deleted.
if ( class_exists( 'Zeko_Core_Helpers' ) ) {
	Zeko_Core_Helpers::get_instance()->delete_plugin_pages( 'qa', array( 'questions', 'ask-a-question', 'qa-dashboard' ) );
}

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
$wpdb->delete(
	$wpdb->usermeta,
	array( 'meta_key' => 'zeko_qa_expertise' ),
	array( '%s' )
); // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
