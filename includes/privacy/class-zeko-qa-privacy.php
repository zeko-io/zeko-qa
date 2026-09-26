<?php
/**
 * Zeko Q&A — WordPress personal-data exporter, eraser and retention.
 *
 * Registers with Tools > Export Personal Data / Erase Personal Data so site
 * owners can fulfil data-protection requests for Q&A content and participation
 * rows, and contributes its age-based retention policy to the shared Zeko Core
 * retention registry (filter zeko_core_privacy_retention_tables).
 *
 * Table schemas byte-verified 2026-09-23 against class-zeko-qa-db.php DDL:
 *   {prefix}zeko_questions           user_id / created_at + updated_at / title + content LONGTEXT
 *   {prefix}zeko_answers             user_id / created_at + updated_at / content LONGTEXT
 *   {prefix}zeko_qa_comments         user_id / created_at / content TEXT
 *   {prefix}zeko_qa_reports          user_id / status + created_at / reason TEXT
 *   {prefix}zeko_qa_revisions        user_id / created_at / content LONGTEXT
 *   {prefix}zeko_qa_reputation_log   user_id / created_at / reason
 *   {prefix}zeko_qa_user_badges      user_id / awarded_at / (none)
 *   {prefix}zeko_qa_bookmarks        user_id / created_at / (none)
 *   {prefix}zeko_qa_topic_followers  user_id / created_at / (none)
 *   {prefix}zeko_qa_question_followers user_id / created_at / (none)
 *   {prefix}zeko_qa_space_members    user_id / created_at / role
 *   {prefix}zeko_qa_space_posts      posted_by / created_at / (none)
 *   {prefix}zeko_qa_notifications    user_id / created_at / (none)
 *   {prefix}zeko_qa_bounties         user_id + winner_id / created_at + expires_at / amount
 *
 * Community content OUTLIVES the user: the author link is scrubbed to 0 while
 * the body and provenance stay visible (questions, answers, comments,
 * revisions, space posts, bounties, and report records with their reason
 * text masked). Pure participation rows (reputation log, badges, bookmarks,
 * topic and question follows, space memberships, notifications) are deleted.
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the exporter, eraser and retention-table callbacks.
 */
function zeko_qa_privacy_register(): void {
	add_filter( 'wp_privacy_personal_data_exporters', 'zeko_qa_privacy_register_exporter' );
	add_filter( 'wp_privacy_personal_data_erasers', 'zeko_qa_privacy_register_eraser' );
	add_filter( 'zeko_core_privacy_retention_tables', 'zeko_qa_privacy_retention_tables' );
}
add_action( 'init', 'zeko_qa_privacy_register', 11 );

/**
 * Register the personal-data exporter.
 *
 * @param array $exporters Exporters.
 */
function zeko_qa_privacy_register_exporter( array $exporters ): array {
	$exporters['zeko-qa'] = array(
		'exporter_friendly_name' => __( 'Zeko Q&A data', 'zeko-qa' ),
		'callback'               => 'zeko_qa_privacy_export',
	);
	return $exporters;
}

/**
 * Register the personal-data eraser.
 *
 * @param array $erasers Erasers.
 */
function zeko_qa_privacy_register_eraser( array $erasers ): array {
	$erasers['zeko-qa'] = array(
		'eraser_friendly_name' => __( 'Zeko Q&A data', 'zeko-qa' ),
		'callback'             => 'zeko_qa_privacy_erase',
	);
	return $erasers;
}

/**
 * Get a prepared DB instance (null when the plugin is not active).
 */
function zeko_qa_privacy_db(): ?Zeko_QA_DB {
	if ( ! class_exists( 'Zeko_QA_DB' ) ) {
		return null;
	}
	return new Zeko_QA_DB();
}

/**
 * Whether one of the Q&A tables exists (guards every touch of a table).
 *
 * @param string $table Table.
 */
function zeko_qa_privacy_table_exists( string $table ): bool {
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
}

/**
 * Export specs for the community-content group.
 *
 * @return array[]
 * @param Zeko_QA_DB $db Live DB instance.
 */
function zeko_qa_privacy_content_specs( Zeko_QA_DB $db ): array {
	return array(
		array(
			'table'       => $db->get_table_questions(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Questions', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-question-',
			'type_label'  => __( 'Question', 'zeko-qa' ),
			'labels'      => array(
				'title'      => __( 'Title', 'zeko-qa' ),
				'content'    => __( 'Content', 'zeko-qa' ),
				'views'      => __( 'Views', 'zeko-qa' ),
				'upvotes'    => __( 'Upvotes', 'zeko-qa' ),
				'status'     => __( 'Status', 'zeko-qa' ),
				'created_at' => __( 'Created at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_answers(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Answers', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-answer-',
			'type_label'  => __( 'Answer', 'zeko-qa' ),
			'labels'      => array(
				'question_id' => __( 'Question ID', 'zeko-qa' ),
				'content'     => __( 'Content', 'zeko-qa' ),
				'is_accepted' => __( 'Accepted', 'zeko-qa' ),
				'upvotes'     => __( 'Upvotes', 'zeko-qa' ),
				'created_at'  => __( 'Created at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_comments(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Comments', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-comment-',
			'type_label'  => __( 'Comment', 'zeko-qa' ),
			'labels'      => array(
				'item_id'    => __( 'Item ID', 'zeko-qa' ),
				'item_type'  => __( 'Item type', 'zeko-qa' ),
				'content'    => __( 'Comment', 'zeko-qa' ),
				'created_at' => __( 'Created at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_revisions(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Revisions', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-revision-',
			'type_label'  => __( 'Revision', 'zeko-qa' ),
			'labels'      => array(
				'item_id'    => __( 'Item ID', 'zeko-qa' ),
				'item_type'  => __( 'Item type', 'zeko-qa' ),
				'content'    => __( 'Content', 'zeko-qa' ),
				'created_at' => __( 'Created at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_space_posts(),
			'user_cols'   => array( 'posted_by' ),
			'group_label' => __( 'Zeko Q&A — Space posts', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-space-post-',
			'type_label'  => __( 'Space post', 'zeko-qa' ),
			'labels'      => array(
				'space_id'   => __( 'Space ID', 'zeko-qa' ),
				'item_id'    => __( 'Item ID', 'zeko-qa' ),
				'item_type'  => __( 'Item type', 'zeko-qa' ),
				'created_at' => __( 'Created at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_bounties(),
			'user_cols'   => array( 'user_id', 'winner_id' ),
			'group_label' => __( 'Zeko Q&A — Bounties', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-bounty-',
			'type_label'  => __( 'Bounty', 'zeko-qa' ),
			'labels'      => array(
				'question_id' => __( 'Question ID', 'zeko-qa' ),
				'amount'      => __( 'Amount', 'zeko-qa' ),
				'status'      => __( 'Status', 'zeko-qa' ),
				'winner_id'   => __( 'Winner ID', 'zeko-qa' ),
				'created_at'  => __( 'Created at', 'zeko-qa' ),
				'expires_at'  => __( 'Expires at', 'zeko-qa' ),
			),
		),
	);
}

/**
 * Export specs for the participation group (user rows that are erased).
 *
 * @return array[]
 * @param Zeko_QA_DB $db Live DB instance.
 */
function zeko_qa_privacy_participation_specs( Zeko_QA_DB $db ): array {
	global $wpdb;

	return array(
		array(
			'table'       => $wpdb->prefix . 'zeko_qa_user_badges',
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Badges', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-user-badge-',
			'type_label'  => __( 'User badge', 'zeko-qa' ),
			'labels'      => array(
				'badge_id'   => __( 'Badge ID', 'zeko-qa' ),
				'awarded_at' => __( 'Awarded at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_bookmarks(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Bookmarks', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-bookmark-',
			'type_label'  => __( 'Bookmark', 'zeko-qa' ),
			'labels'      => array(
				'question_id' => __( 'Question ID', 'zeko-qa' ),
				'created_at'  => __( 'Created at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_topic_followers(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Topic follows', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-topic-follower-',
			'type_label'  => __( 'Topic follower', 'zeko-qa' ),
			'labels'      => array(
				'topic_id'   => __( 'Topic ID', 'zeko-qa' ),
				'created_at' => __( 'Created at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_question_followers(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Question follows', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-question-follower-',
			'type_label'  => __( 'Question follower', 'zeko-qa' ),
			'labels'      => array(
				'question_id' => __( 'Question ID', 'zeko-qa' ),
				'created_at'  => __( 'Created at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_space_members(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Space memberships', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-space-member-',
			'type_label'  => __( 'Space member', 'zeko-qa' ),
			'labels'      => array(
				'space_id'   => __( 'Space ID', 'zeko-qa' ),
				'role'       => __( 'Role', 'zeko-qa' ),
				'created_at' => __( 'Created at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_reputation_log(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Reputation log', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-reputation-',
			'type_label'  => __( 'Reputation entry', 'zeko-qa' ),
			'labels'      => array(
				'points'         => __( 'Points', 'zeko-qa' ),
				'reason'         => __( 'Reason', 'zeko-qa' ),
				'reference_id'   => __( 'Reference ID', 'zeko-qa' ),
				'reference_type' => __( 'Reference type', 'zeko-qa' ),
				'created_at'     => __( 'Created at', 'zeko-qa' ),
			),
		),
		array(
			'table'       => $db->get_table_notifications(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Notifications', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-notification-',
			'type_label'  => __( 'Notification', 'zeko-qa' ),
			'labels'      => array(
				'action'      => __( 'Action', 'zeko-qa' ),
				'object_id'   => __( 'Object ID', 'zeko-qa' ),
				'object_type' => __( 'Object type', 'zeko-qa' ),
				'actor_id'    => __( 'Actor ID', 'zeko-qa' ),
				'is_read'     => __( 'Read', 'zeko-qa' ),
				'created_at'  => __( 'Created at', 'zeko-qa' ),
			),
		),
	);
}

/**
 * Export specs for the reports group (reports the user authored).
 *
 * @return array[]
 * @param Zeko_QA_DB $db Live DB instance.
 */
function zeko_qa_privacy_report_specs( Zeko_QA_DB $db ): array {
	return array(
		array(
			'table'       => $db->get_table_reports(),
			'user_cols'   => array( 'user_id' ),
			'group_label' => __( 'Zeko Q&A — Reports', 'zeko-qa' ),
			'item_prefix' => 'zeko-qa-report-',
			'type_label'  => __( 'Report', 'zeko-qa' ),
			'labels'      => array(
				'item_id'    => __( 'Item ID', 'zeko-qa' ),
				'item_type'  => __( 'Item type', 'zeko-qa' ),
				'reason'     => __( 'Reason', 'zeko-qa' ),
				'status'     => __( 'Status', 'zeko-qa' ),
				'created_at' => __( 'Created at', 'zeko-qa' ),
			),
		),
	);
}

/**
 * Anonymize specs for the eraser — community content outlives the user, so
 * author links are scrubbed (and report reason text masked) instead of the
 * rows being deleted.
 *
 * @return array[]
 * @param Zeko_QA_DB $db Live DB instance.
 */
function zeko_qa_privacy_anonymize_specs( Zeko_QA_DB $db ): array {
	return array(
		array(
			'table'     => $db->get_table_questions(),
			'user_cols' => array( 'user_id' ),
			'set'       => 'user_id = 0',
		),
		array(
			'table'     => $db->get_table_answers(),
			'user_cols' => array( 'user_id' ),
			'set'       => 'user_id = 0',
		),
		array(
			'table'     => $db->get_table_comments(),
			'user_cols' => array( 'user_id' ),
			'set'       => 'user_id = 0',
		),
		array(
			'table'     => $db->get_table_revisions(),
			'user_cols' => array( 'user_id' ),
			'set'       => 'user_id = 0',
		),
		array(
			'table'     => $db->get_table_space_posts(),
			'user_cols' => array( 'posted_by' ),
			'set'       => 'posted_by = 0',
		),
		array(
			'table'     => $db->get_table_bounties(),
			'user_cols' => array( 'user_id' ),
			'set'       => 'user_id = 0',
		),
		array(
			'table'     => $db->get_table_bounties(),
			'user_cols' => array( 'winner_id' ),
			'set'       => 'winner_id = 0',
		),
		array(
			'table'     => $db->get_table_reports(),
			'user_cols' => array( 'user_id' ),
			'set'       => 'user_id = 0, reason = %s',
			'set_args'  => array( '[redacted]' ),
		),
	);
}

/**
 * Delete specs for the eraser — user participation rows are erased outright.
 *
 * @return array[]
 * @param Zeko_QA_DB $db Live DB instance.
 */
function zeko_qa_privacy_delete_specs( Zeko_QA_DB $db ): array {
	global $wpdb;

	return array(
		array(
			'table'     => $db->get_table_reputation_log(),
			'user_cols' => array( 'user_id' ),
		),
		array(
			'table'     => $wpdb->prefix . 'zeko_qa_user_badges',
			'user_cols' => array( 'user_id' ),
		),
		array(
			'table'     => $db->get_table_bookmarks(),
			'user_cols' => array( 'user_id' ),
		),
		array(
			'table'     => $db->get_table_topic_followers(),
			'user_cols' => array( 'user_id' ),
		),
		array(
			'table'     => $db->get_table_question_followers(),
			'user_cols' => array( 'user_id' ),
		),
		array(
			'table'     => $db->get_table_space_members(),
			'user_cols' => array( 'user_id' ),
		),
		array(
			'table'     => $db->get_table_notifications(),
			'user_cols' => array( 'user_id' ),
		),
	);
}

/**
 * Export a user's Zeko Q&A data, 20 rows per table per page.
 *
 * @return array{data: array, done: bool}
 * @param string $email_address User who requested the export.
 * @param int    $page Export page (batching).
 */
function zeko_qa_privacy_export( string $email_address, int $page = 1 ): array {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$db = zeko_qa_privacy_db();
	if ( ! $db ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	global $wpdb;

	$user_id   = (int) $user->ID;
	$per_page  = 20;
	$offset    = ( max( 1, (int) $page ) - 1 ) * $per_page;
	$data      = array();
	$tables    = 0;
	$exhausted = 0;

	$sections = array(
		'content'       => zeko_qa_privacy_content_specs( $db ),
		'participation' => zeko_qa_privacy_participation_specs( $db ),
		'reports'       => zeko_qa_privacy_report_specs( $db ),
	);

	foreach ( $sections as $group_slug => $specs ) {
		foreach ( $specs as $spec ) {
			$spec['group_slug'] = $group_slug;

			if ( ! zeko_qa_privacy_table_exists( $spec['table'] ) ) {
				continue;
			}
			++$tables;

			$where_parts = array();
			$where_args  = array();
			foreach ( $spec['user_cols'] as $column ) {
				$where_parts[] = "{$column} = %d";
				$where_args[]  = $user_id;
			}
			$where_sql = implode( ' OR ', $where_parts );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			$rows = $wpdb->get_results(
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					'SELECT id, ' . implode( ', ', array_keys( $spec['labels'] ) ) . " FROM {$spec['table']} WHERE {$where_sql} ORDER BY id ASC LIMIT %d OFFSET %d",
					array_merge( $where_args, array( $per_page, $offset ) )
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

			foreach ( (array) $rows as $row ) {
				$pairs = array(
					array(
						'name'  => __( 'Type', 'zeko-qa' ),
						'value' => $spec['type_label'],
					),
				);
				foreach ( $spec['labels'] as $column => $label ) {
					$pairs[] = array(
						'name'  => $label,
						'value' => isset( $row->$column ) ? (string) $row->$column : '',
					);
				}
				$data[] = array(
					'group_id'    => 'zeko-qa-' . $spec['group_slug'],
					'group_label' => $spec['group_label'],
					'item_id'     => $spec['item_prefix'] . (int) $row->id,
					'data'        => $pairs,
				);
			}

			if ( count( $rows ) < $per_page ) {
				++$exhausted;
			}
		}
	}

	return array(
		'data' => $data,
		'done' => $exhausted === $tables,
	);
}

/**
 * Erase a user's Zeko Q&A data.
 * Community content is anonymized in batches (author link scrubbed to 0 /
 * report reason masked) and participation rows are deleted in batches. The
 * eraser is called repeatedly with an incremental page until done is true.
 *
 * @return array{items_removed: int, items_retained: int, messages: array, done: bool}
 * @param string $email_address User who requested erasure.
 * @param int    $_page page.
 */
function zeko_qa_privacy_erase( string $email_address, int $_page = 1 ): array {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'items_removed'  => 0,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}

	$db = zeko_qa_privacy_db();
	if ( ! $db ) {
		return array(
			'items_removed'  => 0,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}

	global $wpdb;

	$user_id   = (int) $user->ID;
	$per_page  = 20;
	$removed   = 0;
	$remaining = 0;

	foreach ( zeko_qa_privacy_anonymize_specs( $db ) as $spec ) {
		if ( ! zeko_qa_privacy_table_exists( $spec['table'] ) ) {
			continue;
		}

		$where_parts = array();
		$where_args  = array();
		foreach ( $spec['user_cols'] as $column ) {
			$where_parts[] = "{$column} = %d";
			$where_args[]  = $user_id;
		}
		$where_sql = implode( ' OR ', $where_parts );

		$set_args = isset( $spec['set_args'] ) ? $spec['set_args'] : array();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$removed   += (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$spec['table']} SET {$spec['set']} WHERE {$where_sql} LIMIT %d",
				array_merge( $set_args, $where_args, array( $per_page ) )
			)
		);
		$remaining += (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$spec['table']} WHERE {$where_sql}", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$where_args
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	foreach ( zeko_qa_privacy_delete_specs( $db ) as $spec ) {
		if ( ! zeko_qa_privacy_table_exists( $spec['table'] ) ) {
			continue;
		}

		$where_parts = array();
		$where_args  = array();
		foreach ( $spec['user_cols'] as $column ) {
			$where_parts[] = "{$column} = %d";
			$where_args[]  = $user_id;
		}
		$where_sql = implode( ' OR ', $where_parts );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$removed   += (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$spec['table']} WHERE {$where_sql} LIMIT %d",
				array_merge( $where_args, array( $per_page ) )
			)
		);
		$remaining += (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$spec['table']} WHERE {$where_sql}", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$where_args
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	return array(
		'items_removed'  => $removed,
		'items_retained' => 0,
		'messages'       => array(
			__( 'Zeko Q&A participation rows (reputation log, badges, bookmarks, topic and question follows, space memberships and notifications) were erased.', 'zeko-qa' ),
			__( 'Your questions, answers, comments and revisions remain visible as community content with their author attribution removed.', 'zeko-qa' ),
		),
		'done'           => 0 === $remaining,
	);
}

/**
 * Reputation reasons the plugin writes (byte-verified against the
 * log_reputation() call sites in class-zeko-qa-ajax.php, class-zeko-qa-rest-api.php
 * and class-zeko-qa-db.php). Only these rows are eligible for age-based purge.
 *
 * @return string[]
 */
function zeko_qa_privacy_retention_reasons(): array {
	return array(
		'upvote_received',
		'upvote_removed',
		'answer_accepted',
		'bounty_posted',
		'bounty_refunded',
		'bounty_awarded',
	);
}

/**
 * Age-based retention configs for the shared Zeko Core retention registry.
 * The filter is fired by Zeko Core (if/when present); registering this
 * callback is harmless when the filter never fires. Array shape matches the
 * core contract: table / user_col / type_col / date_col / types / days and
 * the optional scrub_col / scrub_value pair.
 *
 * @param array $tables Tables.
 */
function zeko_qa_privacy_retention_tables( array $tables ): array {
	global $wpdb;
	$prefix = $wpdb->prefix;

	// Reputation log rows age out after a year (participatory scorekeeping).
	$tables[] = array(
		'table'    => $prefix . 'zeko_qa_reputation_log',
		'user_col' => 'user_id',
		'type_col' => 'reason',
		'date_col' => 'created_at',
		'types'    => zeko_qa_privacy_retention_reasons(),
		'days'     => 365,
	);

	// Resolved reports are kept on record but their reason text is scrubbed.
	// once they are more than a year old.
	$tables[] = array(
		'table'       => $prefix . 'zeko_qa_reports',
		'user_col'    => 'user_id',
		'type_col'    => 'status',
		'date_col'    => 'created_at',
		'types'       => array( 'resolved' ),
		'days'        => 365,
		'scrub_col'   => 'reason',
		'scrub_value' => '[redacted]',
	);

	return $tables;
}
