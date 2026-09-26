<?php
/**
 * Database class for Zeko QA
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_QA_DB. */
class Zeko_QA_DB {

	/**
	 * Table questions.
	 *
	 * @var mixed Table questions.
	 */
	private $table_questions;
	/**
	 * Table answers.
	 *
	 * @var mixed Table answers.
	 */
	private $table_answers;
	/**
	 * Table votes.
	 *
	 * @var mixed Table votes.
	 */
	private $table_votes;
	/**
	 * Table topics.
	 *
	 * @var mixed Table topics.
	 */
	private $table_topics;
	/**
	 * Table question topics.
	 *
	 * @var mixed Table question topics.
	 */
	private $table_question_topics;
	/**
	 * Table tags.
	 *
	 * @var mixed Table tags.
	 */
	private $table_tags;
	/**
	 * Table question tags.
	 *
	 * @var mixed Table question tags.
	 */
	private $table_question_tags;
	/**
	 * Table notifications.
	 *
	 * @var mixed Table notifications.
	 */
	private $table_notifications;
	/**
	 * Table reports.
	 *
	 * @var mixed Table reports.
	 */
	private $table_reports;
	/**
	 * Table revisions.
	 *
	 * @var mixed Table revisions.
	 */
	private $table_revisions;
	/**
	 * Table reputation log.
	 *
	 * @var mixed Table reputation log.
	 */
	private $table_reputation_log;
	/**
	 * Table badges.
	 *
	 * @var mixed Table badges.
	 */
	private $table_badges;
	/**
	 * Table user badges.
	 *
	 * @var mixed Table user badges.
	 */
	private $table_user_badges;
	/**
	 * Table topic followers.
	 *
	 * @var mixed Table topic followers.
	 */
	private $table_topic_followers;
	/**
	 * Table bookmarks.
	 *
	 * @var mixed Table bookmarks.
	 */
	private $table_bookmarks;
	/**
	 * Table comments.
	 *
	 * @var mixed Table comments.
	 */
	private $table_comments;
	/**
	 * Table question followers.
	 *
	 * @var mixed Table question followers.
	 */
	private $table_question_followers;
	/**
	 * Table spaces.
	 *
	 * @var mixed Table spaces.
	 */
	private $table_spaces;
	/**
	 * Table space members.
	 *
	 * @var mixed Table space members.
	 */
	private $table_space_members;
	/**
	 * Table space posts.
	 *
	 * @var mixed Table space posts.
	 */
	private $table_space_posts;
	/**
	 * Table bounties.
	 *
	 * @var mixed Table bounties.
	 */
	private $table_bounties;

	/**
	 * Construct.
	 */
	public function __construct() {
		global $wpdb;

		$this->table_questions          = $wpdb->prefix . 'zeko_questions';
		$this->table_answers            = $wpdb->prefix . 'zeko_answers';
		$this->table_votes              = $wpdb->prefix . 'zeko_qa_votes';
		$this->table_topics             = $wpdb->prefix . 'zeko_qa_topics';
		$this->table_question_topics    = $wpdb->prefix . 'zeko_qa_question_topics';
		$this->table_tags               = $wpdb->prefix . 'zeko_qa_tags';
		$this->table_question_tags      = $wpdb->prefix . 'zeko_qa_question_tags';
		$this->table_notifications      = $wpdb->prefix . 'zeko_qa_notifications';
		$this->table_reports            = $wpdb->prefix . 'zeko_qa_reports';
		$this->table_revisions          = $wpdb->prefix . 'zeko_qa_revisions';
		$this->table_reputation_log     = $wpdb->prefix . 'zeko_qa_reputation_log';
		$this->table_badges             = $wpdb->prefix . 'zeko_qa_badges';
		$this->table_user_badges        = $wpdb->prefix . 'zeko_qa_user_badges';
		$this->table_topic_followers    = $wpdb->prefix . 'zeko_qa_topic_followers';
		$this->table_bookmarks          = $wpdb->prefix . 'zeko_qa_bookmarks';
		$this->table_comments           = $wpdb->prefix . 'zeko_qa_comments';
		$this->table_question_followers = $wpdb->prefix . 'zeko_qa_question_followers';
		$this->table_spaces             = $wpdb->prefix . 'zeko_qa_spaces';
		$this->table_space_members      = $wpdb->prefix . 'zeko_qa_space_members';
		$this->table_space_posts        = $wpdb->prefix . 'zeko_qa_space_posts';
		$this->table_bounties           = $wpdb->prefix . 'zeko_qa_bounties';
	}

	/**
	 * Create tables.
	 */
	public function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $this->get_charset_collate();

		$sql = array();

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_questions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            title varchar(255) NOT NULL DEFAULT '',
            slug varchar(255) NOT NULL DEFAULT '',
            content longtext NOT NULL,
            views bigint(20) unsigned NOT NULL DEFAULT 0,
            upvotes bigint(20) unsigned NOT NULL DEFAULT 0,
            downvotes bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'open',
            is_featured tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY slug (slug),
            KEY status (status),
            KEY is_featured (is_featured),
            KEY created_at (created_at)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_answers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            question_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            content longtext NOT NULL,
            is_accepted tinyint(1) NOT NULL DEFAULT 0,
            upvotes bigint(20) unsigned NOT NULL DEFAULT 0,
            downvotes bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY question_id (question_id),
            KEY user_id (user_id),
            KEY is_accepted (is_accepted),
            KEY created_at (created_at)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_votes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            item_id bigint(20) unsigned NOT NULL DEFAULT 0,
            item_type varchar(20) NOT NULL DEFAULT 'question',
            vote_type varchar(10) NOT NULL DEFAULT 'up',
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY user_item_type (user_id, item_id, item_type),
            KEY item_id (item_id),
            KEY item_type (item_type),
            KEY vote_type (vote_type)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_topics} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL DEFAULT '',
            slug varchar(255) NOT NULL DEFAULT '',
            description text NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            followers bigint(20) unsigned NOT NULL DEFAULT 0,
            question_count bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY user_id (user_id),
            KEY followers (followers)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_question_topics} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            question_id bigint(20) unsigned NOT NULL DEFAULT 0,
            topic_id bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY question_topic (question_id, topic_id),
            KEY topic_id (topic_id)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_tags} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL DEFAULT '',
            slug varchar(255) NOT NULL DEFAULT '',
            description text NOT NULL,
            question_count bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY name (name),
            KEY question_count (question_count)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_question_tags} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            question_id bigint(20) unsigned NOT NULL DEFAULT 0,
            tag_id bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY question_tag (question_id, tag_id),
            KEY tag_id (tag_id)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_notifications} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            action varchar(50) NOT NULL DEFAULT '',
            object_id bigint(20) unsigned NOT NULL DEFAULT 0,
            object_type varchar(20) NOT NULL DEFAULT 'question',
            actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY user_unread (user_id, is_read),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_reports} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            item_id bigint(20) unsigned NOT NULL DEFAULT 0,
            item_type varchar(20) NOT NULL DEFAULT 'question',
            reason text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY item_id (item_id),
            KEY status (status)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_revisions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            item_id bigint(20) unsigned NOT NULL DEFAULT 0,
            item_type varchar(20) NOT NULL DEFAULT 'question',
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            content longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY item_id (item_id),
            KEY item_type (item_type),
            KEY user_id (user_id)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_reputation_log} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            points int(11) NOT NULL DEFAULT 0,
            reason varchar(50) NOT NULL DEFAULT '',
            reference_id bigint(20) unsigned NOT NULL DEFAULT 0,
            reference_type varchar(20) NOT NULL DEFAULT 'question',
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY reason (reason),
            KEY created_at (created_at)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_badges} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL DEFAULT '',
            slug varchar(100) NOT NULL DEFAULT '',
            description text NOT NULL,
            icon varchar(50) NOT NULL DEFAULT 'star',
            criteria_type varchar(50) NOT NULL DEFAULT 'manual',
            criteria_value int(10) unsigned NOT NULL DEFAULT 0,
            points int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_user_badges} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            badge_id bigint(20) unsigned NOT NULL DEFAULT 0,
            awarded_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY user_badge (user_id, badge_id),
            KEY user_id (user_id),
            KEY badge_id (badge_id)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_topic_followers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            topic_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY user_topic (user_id, topic_id),
            KEY user_id (user_id),
            KEY topic_id (topic_id)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_bookmarks} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            question_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY user_question (user_id, question_id),
            KEY user_id (user_id),
            KEY question_id (question_id)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_comments} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            item_id bigint(20) unsigned NOT NULL DEFAULT 0,
            item_type varchar(20) NOT NULL DEFAULT 'answer',
            content text NOT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY item_id_item_type (item_id, item_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_question_followers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            question_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY question_user (question_id, user_id),
            KEY user_id (user_id)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_spaces} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL DEFAULT '',
            slug varchar(255) NOT NULL DEFAULT '',
            description text NOT NULL,
            creator_id bigint(20) unsigned NOT NULL DEFAULT 0,
            member_count bigint(20) unsigned NOT NULL DEFAULT 1,
            is_public tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY creator_id (creator_id),
            KEY is_public (is_public)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_space_members} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            space_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            role varchar(20) NOT NULL DEFAULT 'member',
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY space_user (space_id, user_id),
            KEY user_id (user_id)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_space_posts} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            space_id bigint(20) unsigned NOT NULL DEFAULT 0,
            item_id bigint(20) unsigned NOT NULL DEFAULT 0,
            item_type varchar(20) NOT NULL DEFAULT 'question',
            posted_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY space_id (space_id),
            KEY item_id_item_type (item_id, item_type)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_bounties} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            question_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            amount bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'open',
            winner_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            expires_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY question_id (question_id),
            KEY user_id (user_id),
            KEY status (status)
        ) {$charset_collate};";

		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'zeko_qa_db_version', ZEKO_QA_DB_VERSION );
	}

	/**
	 * Add missing hot-path indexes to existing tables.
	 */
	public function add_missing_indexes() {
		global $wpdb;

		$indexes = array(
			$this->table_notifications => array( 'user_unread', '`user_id`, `is_read`' ),
		);

		foreach ( $indexes as $table => $key ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = %s AND index_name = %s',
					$table,
					$key[0]
				)
			);

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			if ( ! $exists ) {
				$wpdb->query( "ALTER TABLE `{$table}` ADD KEY `{$key[0]}` ({$key[1]})" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}
	}

	/**
	 * Charset collate.
	 */
	private function get_charset_collate() {
		global $wpdb;
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset . ' COLLATE ' . $wpdb->collate;
		return $charset_collate;
	}

	/**
	 * Table questions.
	 */
	public function get_table_questions() {
		return $this->table_questions;
	}

	/**
	 * Table answers.
	 */
	public function get_table_answers() {
		return $this->table_answers;
	}

	/**
	 * Table votes.
	 */
	public function get_table_votes() {
		return $this->table_votes;
	}

	/**
	 * Table topics.
	 */
	public function get_table_topics() {
		return $this->table_topics;
	}

	/**
	 * Table question topics.
	 */
	public function get_table_question_topics() {
		return $this->table_question_topics;
	}

	/**
	 * Table tags.
	 */
	public function get_table_tags() {
		return $this->table_tags;
	}

	/**
	 * Table question tags.
	 */
	public function get_table_question_tags() {
		return $this->table_question_tags;
	}

	/**
	 * Every custom QA table name except the static `badges` reference data
	 * (seeded once on init via `zeko_qa_badges_seeded`; not demo content).
	 * Used by the demo generator / clear-demo routines so a reset can never
	 * leave orphaned rows behind.
	 *
	 * @return string[]
	 */
	public function get_all_tables() {
		return array(
			$this->table_questions,
			$this->table_answers,
			$this->table_votes,
			$this->table_topics,
			$this->table_question_topics,
			$this->table_tags,
			$this->table_question_tags,
			$this->table_notifications,
			$this->table_reports,
			$this->table_revisions,
			$this->table_reputation_log,
			$this->table_user_badges,
			$this->table_topic_followers,
			$this->table_bookmarks,
			$this->table_comments,
			$this->table_question_followers,
			$this->table_spaces,
			$this->table_space_members,
			$this->table_space_posts,
			$this->table_bounties,
		);
	}

	/**
	 * Table notifications.
	 */
	public function get_table_notifications() {
		return $this->table_notifications;
	}

	/**
	 * Table reports.
	 */
	public function get_table_reports() {
		return $this->table_reports;
	}

	/**
	 * Table revisions.
	 */
	public function get_table_revisions() {
		return $this->table_revisions;
	}

	/**
	 * Table bookmarks.
	 */
	public function get_table_bookmarks() {
		return $this->table_bookmarks;
	}

	/**
	 * Table comments.
	 */
	public function get_table_comments() {
		return $this->table_comments;
	}

	/**
	 * Table reputation log.
	 */
	public function get_table_reputation_log() {
		return $this->table_reputation_log;
	}

	/**
	 * Table topic followers.
	 */
	public function get_table_topic_followers() {
		return $this->table_topic_followers;
	}

	/**
	 * Table question followers.
	 */
	public function get_table_question_followers() {
		return $this->table_question_followers;
	}

	/**
	 * Question followed.
	 *
	 * @param mixed $question_id Question id.
	 * @param mixed $user_id User id.
	 */
	public function is_question_followed( $question_id, $user_id ) {
		global $wpdb;
		$question_id = absint( $question_id );
		$user_id     = absint( $user_id );
		if ( ! $question_id || ! $user_id ) {
			return false;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$sql = $wpdb->prepare(
			"SELECT id FROM {$this->table_question_followers} WHERE question_id = %d AND user_id = %d LIMIT 1",
			$question_id,
			$user_id
		);
		return (bool) $wpdb->get_var( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Toggle question follow.
	 *
	 * @param mixed $question_id Question id.
	 * @param mixed $user_id User id.
	 */
	public function toggle_question_follow( $question_id, $user_id ) {
		global $wpdb;
		$question_id = absint( $question_id );
		$user_id     = absint( $user_id );
		if ( ! $question_id || ! $user_id ) {
			return false;
		}

		$existing = $this->is_question_followed( $question_id, $user_id );
		if ( $existing ) {
			$wpdb->delete(
				$this->table_question_followers,
				array(
					'question_id' => $question_id,
					'user_id'     => $user_id,
				),
				array( '%d', '%d' )
			);
			return false;
		} else {
			$wpdb->insert(
				$this->table_question_followers,
				array(
					'question_id' => $question_id,
					'user_id'     => $user_id,
					'created_at'  => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s' )
			);
			return true;
		}
	}

	/**
	 * SQL condition that excludes questions belonging to a private space the
	 * current viewer is not a member of. Appended to every public question
	 * read query so "private/invite-only" spaces cannot leak their posts into
	 * archives, search, trending, bookmarks, feeds or profiles.
	 *
	 * @return string
	 */
	public function get_private_space_condition() {
		$user_id = (int) get_current_user_id();
		return "NOT EXISTS (
            SELECT 1 FROM {$this->table_space_posts} zsp
            INNER JOIN {$this->table_spaces} zs ON zsp.space_id = zs.id
            WHERE zsp.item_type = 'question' AND zsp.item_id = q.id
              AND zs.is_public = 0
              AND zs.id NOT IN (
                  SELECT zsm.space_id FROM {$this->table_space_members} zsm WHERE zsm.user_id = {$user_id}
              )
        )";
	}

	/**
	 * User followed questions.
	 *
	 * @param mixed     $user_id User id.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_user_followed_questions( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return array();
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$sql = $wpdb->prepare(
			"SELECT q.*, (SELECT COUNT(*) FROM {$this->table_answers} WHERE question_id = q.id) AS answer_count FROM {$this->table_questions} q
             INNER JOIN {$this->table_question_followers} qf ON q.id = qf.question_id
             WHERE qf.user_id = %d AND q.status <> 'deleted' AND {$this->get_private_space_condition()}
             ORDER BY q.created_at DESC
             LIMIT %d OFFSET %d",
			$user_id,
			absint( $limit ),
			absint( $offset )
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Followed topics questions.
	 *
	 * @param mixed     $user_id User id.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_followed_topics_questions( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return array();
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$sql = $wpdb->prepare(
			"SELECT DISTINCT q.*, (SELECT COUNT(*) FROM {$this->table_answers} WHERE question_id = q.id) AS answer_count FROM {$this->table_questions} q
             INNER JOIN {$this->table_question_topics} qt ON q.id = qt.question_id
             INNER JOIN {$this->table_topic_followers} tf ON qt.topic_id = tf.topic_id
             WHERE tf.user_id = %d AND q.status <> 'deleted' AND {$this->get_private_space_condition()}
             ORDER BY q.created_at DESC
             LIMIT %d OFFSET %d",
			$user_id,
			absint( $limit ),
			absint( $offset )
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Question.
	 *
	 * @param mixed $id Id.
	 */
	public function get_question( $id ) {
		global $wpdb;
		$id  = absint( $id );
		$sql = $wpdb->prepare( "SELECT * FROM {$this->table_questions} WHERE id = %d LIMIT 1", $id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Question by slug.
	 *
	 * @param mixed $slug Slug.
	 */
	public function get_question_by_slug( $slug ) {
		global $wpdb;
		$slug = sanitize_title( $slug );
		$sql  = $wpdb->prepare( "SELECT * FROM {$this->table_questions} WHERE slug = %s LIMIT 1", $slug ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Whether the current viewer may see a question, accounting for soft
	 * deletes and private-space membership. Returns true for admins, the
	 * question author, and anyone who is a member of every private space the
	 * question is posted to.
	 *
	 * @param mixed $question Question.
	 */
	public function can_view_question( $question ) {
		if ( ! $question || 'deleted' === $question->status ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		if ( (int) get_current_user_id() === (int) $question->user_id ) {
			return true;
		}
		global $wpdb;
		$user_id = (int) get_current_user_id();
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$private_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_space_posts} zsp
             INNER JOIN {$this->table_spaces} zs ON zsp.space_id = zs.id
             WHERE zsp.item_type = 'question' AND zsp.item_id = %d AND zs.is_public = 0
               AND zs.id NOT IN (
                   SELECT zsm.space_id FROM {$this->table_space_members} zsm WHERE zsm.user_id = %d
               )",
				(int) $question->id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return 0 === $private_count;
	}

	/**
	 * Questions.
	 *
	 * @param array $args Args.
	 */
	public function get_questions( $args = array() ) {
		global $wpdb;
		$defaults = array(
			'status'   => 'open',
			'limit'    => 20,
			'offset'   => 0,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
			'search'   => '',
			'topic_id' => 0,
		);
		$args     = wp_parse_args( $args, $defaults );

		$where          = array( 'q.status = %s' );
		$prepare_values = array( $args['status'] );
		$joins          = "LEFT JOIN {$this->table_answers} a ON q.id = a.question_id";

		$where[] = $this->get_private_space_condition();

		if ( ! empty( $args['search'] ) ) {
			$where[]          = '(q.title LIKE %s OR q.content LIKE %s)';
			$prepare_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$prepare_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		if ( ! empty( $args['topic_id'] ) ) {
			$joins           .= " INNER JOIN {$this->table_question_topics} qt ON q.id = qt.question_id";
			$where[]          = 'qt.topic_id = %d';
			$prepare_values[] = absint( $args['topic_id'] );
		}

		$where_sql = implode( ' AND ', $where );

		$valid_orderby = array(
			'created_at',
			'upvotes',
			'views',
			'answer_count',
			'trending_score',
			'title',
			'id',
		);
		$orderby_col   = in_array( $args['orderby'], $valid_orderby, true ) ? $args['orderby'] : 'created_at';
		$order_dir     = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		if ( 'trending_score' === $orderby_col ) {
			$orderby = "(q.upvotes * 10 + COUNT(DISTINCT a.id) * 5 + GREATEST(q.views, 1) / GREATEST(POW(HOUR(TIMESTAMPDIFF(HOUR, q.created_at, NOW())) + 2, 1.8), 1) * 100) {$order_dir}";
		} elseif ( 'answer_count' === $orderby_col ) {
			$orderby = "answer_count {$order_dir}";
		} else {
			$orderby = "q.{$orderby_col} {$order_dir}";
		}

		$sql = "SELECT q.*, COUNT(DISTINCT a.id) as answer_count
                FROM {$this->table_questions} q
                {$joins}
                WHERE {$where_sql}
                GROUP BY q.id
                ORDER BY {$orderby}
                LIMIT %d OFFSET %d";

		$prepare_values[] = absint( $args['limit'] );
		$prepare_values[] = absint( $args['offset'] );

		$sql = $wpdb->prepare( $sql, $prepare_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Sanitize rich-text (Quill) content with the narrow ecosystem
	 * allow-list; falls back to wp_kses_post() when zeko-core is absent.
	 *
	 * @param mixed $html Html.
	 */
	private static function sanitize_rich( $html ): string {
		if ( class_exists( 'Zeko_Core_Sanitize' ) ) {
			return Zeko_Core_Sanitize::rich_text( (string) $html );
		}
		return wp_kses_post( $html );
	}

	/**
	 * Insert question.
	 *
	 * @param mixed $data Data.
	 */
	public function insert_question( $data ) {
		global $wpdb;
		$defaults           = array(
			'user_id' => 0,
			'title'   => '',
			'slug'    => '',
			'content' => '',
			'views'   => 0,
			'status'  => 'open',
		);
		$data               = wp_parse_args( $data, $defaults );
		$data['user_id']    = absint( $data['user_id'] );
		$data['title']      = sanitize_text_field( $data['title'] );
		$data['slug']       = sanitize_title( $data['slug'] );
		$data['content']    = self::sanitize_rich( $data['content'] );
		$data['views']      = absint( $data['views'] );
		$data['status']     = sanitize_key( $data['status'] );
		$data['created_at'] = current_time( 'mysql' );
		$data['updated_at'] = current_time( 'mysql' );

		$wpdb->insert( $this->table_questions, $data );
		$insert_id = (int) $wpdb->insert_id;
		do_action( 'zeko_qa_question_created', $insert_id, (int) $data['user_id'], $data );
		return $insert_id;
	}

	/**
	 * Update question.
	 *
	 * @param mixed $id Id.
	 * @param mixed $data Data.
	 */
	public function update_question( $id, $data ) {
		global $wpdb;
		$id          = absint( $id );
		$allowed     = array( 'title', 'content', 'status', 'views', 'upvotes', 'downvotes', 'is_featured' );
		$update_data = array();
		foreach ( $allowed as $field ) {
			if ( isset( $data[ $field ] ) ) {
				if ( 'title' === $field ) {
					$update_data[ $field ] = sanitize_text_field( $data[ $field ] );
				} elseif ( 'content' === $field ) {
					$update_data[ $field ] = self::sanitize_rich( $data[ $field ] );
				} elseif ( in_array( $field, array( 'views', 'upvotes', 'downvotes', 'is_featured' ) ) ) {
					$update_data[ $field ] = absint( $data[ $field ] );
				} else {
					$update_data[ $field ] = sanitize_key( $data[ $field ] );
				}
			}
		}
		if ( empty( $update_data ) ) {
			return false;
		}
		$update_data['updated_at'] = current_time( 'mysql' );
		return $wpdb->update( $this->table_questions, $update_data, array( 'id' => $id ) );
	}

	/**
	 * Answer.
	 *
	 * @param mixed $answer_id Answer id.
	 */
	public function get_answer( $answer_id ) {
		global $wpdb;
		$answer_id = absint( $answer_id );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_answers} WHERE id = %d",
				$answer_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Answers.
	 *
	 * @param mixed $question_id Question id.
	 */
	public function get_answers( $question_id ) {
		global $wpdb;
		$question_id = absint( $question_id );
		$sql         = $wpdb->prepare( "SELECT * FROM {$this->table_answers} WHERE question_id = %d ORDER BY is_accepted DESC, upvotes DESC, created_at DESC", $question_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Insert answer.
	 *
	 * @param mixed $data Data.
	 */
	public function insert_answer( $data ) {
		global $wpdb;
		$defaults            = array(
			'question_id' => 0,
			'user_id'     => 0,
			'content'     => '',
			'is_accepted' => 0,
		);
		$data                = wp_parse_args( $data, $defaults );
		$data['question_id'] = absint( $data['question_id'] );
		$data['user_id']     = absint( $data['user_id'] );
		$data['content']     = self::sanitize_rich( $data['content'] );
		$data['is_accepted'] = absint( $data['is_accepted'] );
		$data['created_at']  = current_time( 'mysql' );
		$data['updated_at']  = current_time( 'mysql' );

		$wpdb->insert( $this->table_answers, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Update answer.
	 *
	 * @param mixed $id Id.
	 * @param mixed $data Data.
	 */
	public function update_answer( $id, $data ) {
		global $wpdb;
		$id          = absint( $id );
		$allowed     = array( 'content', 'is_accepted', 'upvotes', 'downvotes' );
		$update_data = array();
		foreach ( $allowed as $field ) {
			if ( isset( $data[ $field ] ) ) {
				if ( 'content' === $field ) {
					$update_data[ $field ] = self::sanitize_rich( $data[ $field ] );
				} elseif ( in_array( $field, array( 'upvotes', 'downvotes', 'is_accepted' ) ) ) {
					$update_data[ $field ] = absint( $data[ $field ] );
				}
			}
		}
		if ( empty( $update_data ) ) {
			return false;
		}
		$update_data['updated_at'] = current_time( 'mysql' );
		return $wpdb->update( $this->table_answers, $update_data, array( 'id' => $id ) );
	}

	/**
	 * Accept a single answer for a question, clearing any previously
	 * accepted answer so only one `is_accepted = 1` row exists per question.
	 * Uses a single conditional UPDATE so the accept/clear is atomic —
	 * concurrent accepts cannot leave multiple accepted answers.
	 *
	 * @param mixed $question_id Question id.
	 * @param mixed $answer_id Answer id.
	 */
	public function accept_answer( $question_id, $answer_id ) {
		global $wpdb;
		$question_id = absint( $question_id );
		$answer_id   = absint( $answer_id );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return (bool) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table_answers} SET is_accepted = (id = %d) WHERE question_id = %d",
				$answer_id,
				$question_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Vote.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $item_id Item id.
	 * @param mixed $item_type Item type.
	 */
	public function get_vote( $user_id, $item_id, $item_type ) {
		global $wpdb;
		$user_id   = absint( $user_id );
		$item_id   = absint( $item_id );
		$item_type = sanitize_key( $item_type );
		$sql       = $wpdb->prepare( "SELECT * FROM {$this->table_votes} WHERE user_id = %d AND item_id = %d AND item_type = %s LIMIT 1", $user_id, $item_id, $item_type ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Insert vote.
	 *
	 * @param mixed $data Data.
	 */
	public function insert_vote( $data ) {
		global $wpdb;
		$defaults           = array(
			'user_id'   => 0,
			'item_id'   => 0,
			'item_type' => 'question',
			'vote_type' => 'up',
		);
		$data               = wp_parse_args( $data, $defaults );
		$data['user_id']    = absint( $data['user_id'] );
		$data['item_id']    = absint( $data['item_id'] );
		$data['item_type']  = sanitize_key( $data['item_type'] );
		$data['vote_type']  = sanitize_key( $data['vote_type'] );
		$data['created_at'] = current_time( 'mysql' );

		$wpdb->insert( $this->table_votes, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Delete vote.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $item_id Item id.
	 * @param mixed $item_type Item type.
	 */
	public function delete_vote( $user_id, $item_id, $item_type ) {
		global $wpdb;
		$user_id   = absint( $user_id );
		$item_id   = absint( $item_id );
		$item_type = sanitize_key( $item_type );
		$sql       = $wpdb->prepare( "DELETE FROM {$this->table_votes} WHERE user_id = %d AND item_id = %d AND item_type = %s", $user_id, $item_id, $item_type ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Count votes.
	 *
	 * @param mixed $item_id Item id.
	 * @param mixed $item_type Item type.
	 * @param mixed $vote_type Vote type.
	 */
	public function count_votes( $item_id, $item_type, $vote_type ) {
		global $wpdb;
		$item_id   = absint( $item_id );
		$item_type = sanitize_key( $item_type );
		$vote_type = sanitize_key( $vote_type );
		$sql       = $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_votes} WHERE item_id = %d AND item_type = %s AND vote_type = %s", $item_id, $item_type, $vote_type ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return absint( $wpdb->get_var( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * User votes for items.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $item_type Item type.
	 * @param mixed $item_ids Item ids.
	 */
	public function get_user_votes_for_items( $user_id, $item_type, $item_ids ) {
		global $wpdb;
		$user_id   = absint( $user_id );
		$item_type = sanitize_key( $item_type );
		$item_ids  = array_map( 'absint', $item_ids );
		if ( empty( $item_ids ) ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $item_ids ), '%d' ) );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql     = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			"SELECT item_id, vote_type FROM {$this->table_votes}
            WHERE user_id = %d AND item_type = %s AND item_id IN ({$placeholders})",
			array_merge( array( $user_id, $item_type ), $item_ids )
		);
		$results = $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$votes = array();
		foreach ( $results as $row ) {
			$votes[ intval( $row->item_id ) ] = $row->vote_type;
		}
		return $votes;
	}

	/**
	 * User vote.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $item_id Item id.
	 * @param mixed $item_type Item type.
	 */
	public function get_user_vote( $user_id, $item_id, $item_type ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table_votes} WHERE user_id = %d AND item_id = %d AND item_type = %s LIMIT 1",
			absint( $user_id ),
			absint( $item_id ),
			sanitize_key( $item_type )
		);
		return $wpdb->get_row( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Net votes.
	 *
	 * @param mixed $item_id Item id.
	 * @param mixed $item_type Item type.
	 */
	public function get_net_votes( $item_id, $item_type ) {
		global $wpdb;
		$table = 'question' === $item_type ? $this->table_questions : $this->table_answers;
		$sql   = $wpdb->prepare( "SELECT upvotes, downvotes FROM {$table} WHERE id = %d LIMIT 1", absint( $item_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$row   = $wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( ! $row ) {
			return 0;
		}
		return absint( $row->upvotes ) - absint( $row->downvotes );
	}

	/**
	 * Search suggestions.
	 *
	 * @param mixed     $term Term.
	 * @param int|float $limit Limit.
	 */
	public function get_search_suggestions( $term, $limit = 8 ) {
		global $wpdb;
		$limit = absint( $limit );
		$term  = '%' . $wpdb->esc_like( $term ) . '%';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT id, title, slug, upvotes,
                (SELECT COUNT(*) FROM {$this->table_answers} WHERE question_id = q.id) as answer_count
            FROM {$this->table_questions} q
            WHERE q.status = 'open' AND q.title LIKE %s AND {$this->get_private_space_condition()}
            ORDER BY q.upvotes DESC, q.created_at DESC
            LIMIT %d",
			$term,
			$limit
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Topics.
	 *
	 * @param array $args Args.
	 */
	public function get_topics( $args = array() ) {
		global $wpdb;
		$defaults = array(
			'limit'   => 50,
			'offset'  => 0,
			'orderby' => 'followers',
			'order'   => 'DESC',
			'search'  => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		$where          = array();
		$prepare_values = array();

		if ( ! empty( $args['search'] ) ) {
			$where[]          = 'name LIKE %s';
			$prepare_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_sql = '';
		if ( ! empty( $where ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where );
		}

		$orderby          = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		$sql              = "SELECT * FROM {$this->table_topics} {$where_sql} ORDER BY {$orderby} LIMIT %d OFFSET %d";
		$prepare_values[] = absint( $args['limit'] );
		$prepare_values[] = absint( $args['offset'] );

		$sql = $wpdb->prepare( $sql, $prepare_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Insert topic.
	 *
	 * @param mixed $data Data.
	 */
	public function insert_topic( $data ) {
		global $wpdb;
		$defaults            = array(
			'name'        => '',
			'slug'        => '',
			'description' => '',
			'user_id'     => 0,
		);
		$data                = wp_parse_args( $data, $defaults );
		$data['name']        = sanitize_text_field( $data['name'] );
		$data['slug']        = sanitize_title( $data['slug'] );
		$data['description'] = sanitize_textarea_field( $data['description'] );
		$data['user_id']     = absint( $data['user_id'] );
		$data['created_at']  = current_time( 'mysql' );

		$wpdb->insert( $this->table_topics, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Topic.
	 *
	 * @param mixed $id Id.
	 */
	public function get_topic( $id ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return null;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_topics} WHERE id = %d LIMIT 1",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Topic by slug.
	 *
	 * @param mixed $slug Slug.
	 */
	public function get_topic_by_slug( $slug ) {
		global $wpdb;
		$slug = sanitize_title( $slug );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_topics} WHERE slug = %s",
				$slug
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Topic questions.
	 *
	 * @param mixed     $topic_id Topic id.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_topic_questions( $topic_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$topic_id = absint( $topic_id );
		$limit    = absint( $limit );
		$offset   = absint( $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT q.*,
                (SELECT COUNT(*) FROM {$this->table_answers} WHERE question_id = q.id) as answer_count,
                (SELECT COUNT(*) FROM {$this->table_votes} WHERE item_id = q.id AND item_type = 'question' AND vote_type = 'up') as total_upvotes
            FROM {$this->table_questions} q
            INNER JOIN {$this->table_question_topics} qt ON q.id = qt.question_id
            WHERE qt.topic_id = %d AND q.status = 'open' AND {$this->get_private_space_condition()}
            ORDER BY q.created_at DESC
            LIMIT %d OFFSET %d",
			$topic_id,
			$limit,
			$offset
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Topic followed.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $topic_id Topic id.
	 */
	public function is_topic_followed( $user_id, $topic_id ) {
		global $wpdb;
		$user_id  = absint( $user_id );
		$topic_id = absint( $topic_id );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_topic_followers} WHERE user_id = %d AND topic_id = %d",
				$user_id,
				$topic_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Follow topic.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $topic_id Topic id.
	 */
	public function follow_topic( $user_id, $topic_id ) {
		global $wpdb;
		$user_id  = absint( $user_id );
		$topic_id = absint( $topic_id );
		if ( $this->is_topic_followed( $user_id, $topic_id ) ) {
			return false;
		}
		$wpdb->insert(
			$this->table_topic_followers,
			array(
				'user_id'    => $user_id,
				'topic_id'   => $topic_id,
				'created_at' => current_time( 'mysql' ),
			)
		);
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table_topics} SET followers = followers + 1 WHERE id = %d",
				$topic_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return true;
	}

	/**
	 * Unfollow topic.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $topic_id Topic id.
	 */
	public function unfollow_topic( $user_id, $topic_id ) {
		global $wpdb;
		$user_id  = absint( $user_id );
		$topic_id = absint( $topic_id );
		$result   = $wpdb->delete(
			$this->table_topic_followers,
			array(
				'user_id'  => $user_id,
				'topic_id' => $topic_id,
			)
		);
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $result ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$this->table_topics} SET followers = GREATEST(followers - 1, 0) WHERE id = %d",
					$topic_id
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		return (bool) $result;
	}

	/**
	 * Toggle topic follow.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $topic_id Topic id.
	 */
	public function toggle_topic_follow( $user_id, $topic_id ) {
		if ( $this->is_topic_followed( $user_id, $topic_id ) ) {
			$this->unfollow_topic( $user_id, $topic_id );
			return false;
		} else {
			$this->follow_topic( $user_id, $topic_id );
			return true;
		}
	}

	/**
	 * All topics.
	 *
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_all_topics( $limit = 50, $offset = 0 ) {
		global $wpdb;
		$limit  = absint( $limit );
		$offset = absint( $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_topics} ORDER BY followers DESC, question_count DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * All tags.
	 *
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_all_tags( $limit = 50, $offset = 0 ) {
		global $wpdb;
		$limit  = absint( $limit );
		$offset = absint( $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_tags} ORDER BY question_count DESC, name ASC LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Tag by slug.
	 *
	 * @param mixed $slug Slug.
	 */
	public function get_tag_by_slug( $slug ) {
		global $wpdb;
		$slug = sanitize_title( $slug );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_tags} WHERE slug = %s",
				$slug
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Tag questions.
	 *
	 * @param mixed     $tag_id Tag id.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_tag_questions( $tag_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$tag_id = absint( $tag_id );
		$limit  = absint( $limit );
		$offset = absint( $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT q.*,
                (SELECT COUNT(*) FROM {$this->table_answers} WHERE question_id = q.id) as answer_count,
                (SELECT COUNT(*) FROM {$this->table_votes} WHERE item_id = q.id AND item_type = 'question' AND vote_type = 'up') as total_upvotes
            FROM {$this->table_questions} q
            INNER JOIN {$this->table_question_tags} qt ON q.id = qt.question_id
            WHERE qt.tag_id = %d AND q.status = 'open' AND {$this->get_private_space_condition()}
            ORDER BY q.created_at DESC
            LIMIT %d OFFSET %d",
			$tag_id,
			$limit,
			$offset
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Bookmarked.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $question_id Question id.
	 */
	public function is_bookmarked( $user_id, $question_id ) {
		global $wpdb;
		$user_id     = absint( $user_id );
		$question_id = absint( $question_id );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_bookmarks} WHERE user_id = %d AND question_id = %d",
				$user_id,
				$question_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Toggle bookmark.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $question_id Question id.
	 */
	public function toggle_bookmark( $user_id, $question_id ) {
		global $wpdb;
		$user_id     = absint( $user_id );
		$question_id = absint( $question_id );
		if ( $this->is_bookmarked( $user_id, $question_id ) ) {
			$wpdb->delete(
				$this->table_bookmarks,
				array(
					'user_id'     => $user_id,
					'question_id' => $question_id,
				)
			);
			return false;
		} else {
			$wpdb->insert(
				$this->table_bookmarks,
				array(
					'user_id'     => $user_id,
					'question_id' => $question_id,
					'created_at'  => current_time( 'mysql' ),
				)
			);
			return true;
		}
	}

	/**
	 * User bookmarks.
	 *
	 * @param mixed     $user_id User id.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_user_bookmarks( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$limit   = absint( $limit );
		$offset  = absint( $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT q.*, b.created_at as bookmarked_at,
                (SELECT COUNT(*) FROM {$this->table_answers} WHERE question_id = q.id) as answer_count,
                (SELECT COUNT(*) FROM {$this->table_votes} WHERE item_id = q.id AND item_type = 'question' AND vote_type = 'up') as total_upvotes
            FROM {$this->table_bookmarks} b
            INNER JOIN {$this->table_questions} q ON b.question_id = q.id
            WHERE b.user_id = %d AND q.status = 'open' AND {$this->get_private_space_condition()}
            ORDER BY b.created_at DESC
            LIMIT %d OFFSET %d",
			$user_id,
			$limit,
			$offset
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Comments.
	 *
	 * @param mixed $item_id Item id.
	 * @param mixed $item_type Item type.
	 */
	public function get_comments( $item_id, $item_type ) {
		global $wpdb;
		$item_id   = absint( $item_id );
		$item_type = sanitize_key( $item_type );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*, u.display_name as author_name
            FROM {$this->table_comments} c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            WHERE c.item_id = %d AND c.item_type = %s
            ORDER BY c.created_at ASC",
				$item_id,
				$item_type
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Comment counts for items.
	 *
	 * @param mixed $item_ids Item ids.
	 * @param mixed $item_type Item type.
	 */
	public function get_comment_counts_for_items( $item_ids, $item_type ) {
		global $wpdb;
		$item_type = sanitize_key( $item_type );
		$item_ids  = array_map( 'absint', $item_ids );
		if ( empty( $item_ids ) ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $item_ids ), '%d' ) );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql     = $wpdb->prepare(
			"SELECT item_id, COUNT(*) as comment_count
            FROM {$this->table_comments}
            WHERE item_type = %s AND item_id IN ({$placeholders})
            GROUP BY item_id",
			array_merge( array( $item_type ), $item_ids )
		);
		$results = $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$counts = array();
		foreach ( $results as $row ) {
			$counts[ intval( $row->item_id ) ] = absint( $row->comment_count );
		}
		return $counts;
	}

	/**
	 * Insert comment.
	 *
	 * @param mixed $data Data.
	 */
	public function insert_comment( $data ) {
		global $wpdb;
		$data               = wp_parse_args(
			$data,
			array(
				'user_id'   => 0,
				'item_id'   => 0,
				'item_type' => 'answer',
				'content'   => '',
			)
		);
		$data['user_id']    = absint( $data['user_id'] );
		$data['item_id']    = absint( $data['item_id'] );
		$data['item_type']  = sanitize_key( $data['item_type'] );
		$data['content']    = sanitize_textarea_field( $data['content'] );
		$data['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $this->table_comments, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Delete comment.
	 *
	 * @param mixed $comment_id Comment id.
	 * @param mixed $user_id User id.
	 */
	public function delete_comment( $comment_id, $user_id ) {
		global $wpdb;
		$comment_id = absint( $comment_id );
		$user_id    = absint( $user_id );
		return $wpdb->delete(
			$this->table_comments,
			array(
				'id'      => $comment_id,
				'user_id' => $user_id,
			)
		);
	}

	/**
	 * Reports.
	 *
	 * @param string    $status Status.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_reports( $status = 'pending', $limit = 50, $offset = 0 ) {
		global $wpdb;
		$status = sanitize_key( $status );
		$limit  = absint( $limit );
		$offset = absint( $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT r.*, u.display_name as reporter_name
            FROM {$this->table_reports} r
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            WHERE r.status = %s
            ORDER BY r.created_at DESC
            LIMIT %d OFFSET %d",
			$status,
			$limit,
			$offset
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Update report status.
	 *
	 * @param mixed $report_id Report id.
	 * @param mixed $status Status.
	 */
	public function update_report_status( $report_id, $status ) {
		global $wpdb;
		$report_id = absint( $report_id );
		$status    = sanitize_key( $status );
		return $wpdb->update( $this->table_reports, array( 'status' => $status ), array( 'id' => $report_id ) );
	}

	/**
	 * Delete question admin.
	 *
	 * @param mixed $question_id Question id.
	 */
	public function delete_question_admin( $question_id ) {
		global $wpdb;
		$question_id = absint( $question_id );
		$wpdb->delete( $this->table_answers, array( 'question_id' => $question_id ) );
		$wpdb->delete(
			$this->table_votes,
			array(
				'item_id'   => $question_id,
				'item_type' => 'question',
			)
		);
		$wpdb->delete( $this->table_question_topics, array( 'question_id' => $question_id ) );
		$wpdb->delete( $this->table_question_tags, array( 'question_id' => $question_id ) );
		$wpdb->delete( $this->table_bookmarks, array( 'question_id' => $question_id ) );
		return $wpdb->delete( $this->table_questions, array( 'id' => $question_id ) );
	}

	/**
	 * Delete answer admin.
	 *
	 * @param mixed $answer_id Answer id.
	 */
	public function delete_answer_admin( $answer_id ) {
		global $wpdb;
		$answer_id = absint( $answer_id );
		$wpdb->delete(
			$this->table_votes,
			array(
				'item_id'   => $answer_id,
				'item_type' => 'answer',
			)
		);
		$wpdb->delete(
			$this->table_comments,
			array(
				'item_id'   => $answer_id,
				'item_type' => 'answer',
			)
		);
		return $wpdb->delete( $this->table_answers, array( 'id' => $answer_id ) );
	}

	/**
	 * Tags.
	 *
	 * @param array $args Args.
	 */
	public function get_tags( $args = array() ) {
		global $wpdb;
		$defaults = array(
			'limit'   => 50,
			'offset'  => 0,
			'orderby' => 'question_count',
			'order'   => 'DESC',
			'search'  => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		$where          = array();
		$prepare_values = array();

		if ( ! empty( $args['search'] ) ) {
			$where[]          = 'name LIKE %s';
			$prepare_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_sql = '';
		if ( ! empty( $where ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where );
		}

		$orderby          = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		$sql              = "SELECT * FROM {$this->table_tags} {$where_sql} ORDER BY {$orderby} LIMIT %d OFFSET %d";
		$prepare_values[] = absint( $args['limit'] );
		$prepare_values[] = absint( $args['offset'] );

		$sql = $wpdb->prepare( $sql, $prepare_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Insert tag.
	 *
	 * @param mixed $data Data.
	 */
	public function insert_tag( $data ) {
		global $wpdb;
		$defaults            = array(
			'name'        => '',
			'slug'        => '',
			'description' => '',
		);
		$data                = wp_parse_args( $data, $defaults );
		$data['name']        = sanitize_text_field( $data['name'] );
		$data['slug']        = sanitize_title( $data['slug'] );
		$data['description'] = sanitize_textarea_field( $data['description'] );
		$data['created_at']  = current_time( 'mysql' );

		$wpdb->insert( $this->table_tags, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Question tags.
	 *
	 * @param mixed $question_id Question id.
	 */
	public function get_question_tags( $question_id ) {
		global $wpdb;
		$question_id = absint( $question_id );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT t.* FROM {$this->table_tags} t
                INNER JOIN {$this->table_question_tags} qt ON t.id = qt.tag_id
                WHERE qt.question_id = %d
                ORDER BY t.name ASC",
			$question_id
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Attach tags to question.
	 *
	 * @param mixed $question_id Question id.
	 * @param mixed $tag_ids Tag ids.
	 */
	public function attach_tags_to_question( $question_id, $tag_ids ) {
		global $wpdb;
		$question_id = absint( $question_id );
		$tag_ids     = array_map( 'absint', (array) $tag_ids );
		$tag_ids     = array_unique( array_filter( $tag_ids ) );

		foreach ( $tag_ids as $tag_id ) {
			$wpdb->insert(
				$this->table_question_tags,
				array(
					'question_id' => $question_id,
					'tag_id'      => $tag_id,
				)
			);
			$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_tags} SET question_count = question_count + 1 WHERE id = %d", $tag_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
	}

	/**
	 * Detach tags from question.
	 *
	 * @param mixed $question_id Question id.
	 */
	public function detach_tags_from_question( $question_id ) {
		global $wpdb;
		$question_id = absint( $question_id );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_question_tags} WHERE question_id = %d", $question_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Or create tag.
	 *
	 * @param mixed $name Name.
	 */
	public function get_or_create_tag( $name ) {
		global $wpdb;
		$name = sanitize_text_field( $name );
		$slug = sanitize_title( $name );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_tags} WHERE name = %s LIMIT 1",
				$name
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $existing_id ) {
			return absint( $existing_id );
		}
		return $this->insert_tag(
			array(
				'name'        => $name,
				'slug'        => $slug,
				'description' => '',
			)
		);
	}

	/**
	 * User reputation.
	 *
	 * @param mixed $user_id User id.
	 */
	public function get_user_reputation( $user_id ) {
		global $wpdb;
		$user_id          = absint( $user_id );
		$question_upvotes = $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(upvotes), 0) FROM {$this->table_questions} WHERE user_id = %d", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$answer_upvotes = $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(upvotes), 0) FROM {$this->table_answers} WHERE user_id = %d", $user_id ) );
		$committed      = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(amount), 0) FROM {$this->table_bounties} WHERE user_id = %d AND status IN ('open', 'awarded')",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return max( 0, absint( $question_upvotes ) + absint( $answer_upvotes ) - absint( $committed ) );
	}

	/**
	 * Trending questions.
	 *
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_trending_questions( $limit = 10, $offset = 0 ) {
		global $wpdb;
		$limit  = absint( $limit );
		$offset = absint( $offset );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT q.*,
                COUNT(DISTINCT a.id) as answer_count,
                (q.upvotes * 10 + COUNT(DISTINCT a.id) * 5 + GREATEST(q.views, 1) / GREATEST(POW(HOUR(TIMESTAMPDIFF(HOUR, q.created_at, NOW())) + 2, 1.8), 1) * 100) as trending_score
            FROM {$this->table_questions} q
            LEFT JOIN {$this->table_answers} a ON q.id = a.question_id
            WHERE q.status = 'open'
                AND q.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND {$this->get_private_space_condition()}
            GROUP BY q.id
            ORDER BY trending_score DESC
            LIMIT %d OFFSET %d",
			$limit,
			$offset
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Question topics by question.
	 *
	 * @param mixed $question_id Question id.
	 */
	public function get_question_topics_by_question( $question_id ) {
		global $wpdb;
		$question_id = absint( $question_id );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT t.* FROM {$this->table_topics} t
                INNER JOIN {$this->table_question_topics} qt ON t.id = qt.topic_id
                WHERE qt.question_id = %d
                ORDER BY t.name ASC",
			$question_id
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Unanswered questions.
	 *
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_unanswered_questions( $limit = 20, $offset = 0 ) {
		global $wpdb;
		$limit  = absint( $limit );
		$offset = absint( $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT q.*, COUNT(a.id) as answer_count
                FROM {$this->table_questions} q
                LEFT JOIN {$this->table_answers} a ON q.id = a.question_id
                WHERE q.status = 'open' AND {$this->get_private_space_condition()}
                GROUP BY q.id
                HAVING answer_count = 0
                ORDER BY q.created_at DESC
                LIMIT %d OFFSET %d",
			$limit,
			$offset
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Increment views.
	 *
	 * @param mixed $question_id Question id.
	 */
	public function increment_views( $question_id ) {
		global $wpdb;
		$question_id = absint( $question_id );
		$sql         = $wpdb->prepare( "UPDATE {$this->table_questions} SET views = views + 1 WHERE id = %d", $question_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Insert notification.
	 *
	 * @param mixed $data Data.
	 */
	public function insert_notification( $data ) {
		global $wpdb;
		$defaults            = array(
			'user_id'     => 0,
			'action'      => '',
			'object_id'   => 0,
			'object_type' => 'question',
			'actor_id'    => 0,
			'is_read'     => 0,
		);
		$data                = wp_parse_args( $data, $defaults );
		$data['user_id']     = absint( $data['user_id'] );
		$data['action']      = sanitize_text_field( $data['action'] );
		$data['object_id']   = absint( $data['object_id'] );
		$data['object_type'] = sanitize_key( $data['object_type'] );
		$data['actor_id']    = absint( $data['actor_id'] );
		$data['is_read']     = absint( $data['is_read'] );
		$data['created_at']  = current_time( 'mysql' );

		$wpdb->insert( $this->table_notifications, $data );
		$insert_id = $wpdb->insert_id;
		do_action( 'zeko_qa_notification_created', $insert_id, $data );
		return $insert_id;
	}

	/**
	 * User notifications.
	 *
	 * @param mixed     $user_id User id.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_user_notifications( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$user_id       = absint( $user_id );
		$sql           = $wpdb->prepare( "SELECT * FROM {$this->table_notifications} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d", $user_id, $limit, $offset ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$notifications = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return apply_filters( 'zeko_qa_user_notifications', $notifications, $user_id );
	}

	/**
	 * Mark notifications read.
	 *
	 * @param mixed $user_id User id.
	 */
	public function mark_notifications_read( $user_id ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$sql     = $wpdb->prepare( "UPDATE {$this->table_notifications} SET is_read = 1 WHERE user_id = %d AND is_read = 0", $user_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * User unread notification count.
	 *
	 * @param mixed $user_id User id.
	 */
	public function get_user_unread_notification_count( $user_id ) {
		global $wpdb;
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return 0;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$sql   = $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_notifications} WHERE user_id = %d AND is_read = 0", $user_id );
		$count = (int) $wpdb->get_var( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return apply_filters( 'zeko_qa_unread_notification_count', $count, $user_id );
	}

	/**
	 * Insert report.
	 *
	 * @param mixed $data Data.
	 */
	public function insert_report( $data ) {
		global $wpdb;
		$defaults           = array(
			'user_id'   => 0,
			'item_id'   => 0,
			'item_type' => 'question',
			'reason'    => '',
			'status'    => 'pending',
		);
		$data               = wp_parse_args( $data, $defaults );
		$data['user_id']    = absint( $data['user_id'] );
		$data['item_id']    = absint( $data['item_id'] );
		$data['item_type']  = sanitize_key( $data['item_type'] );
		$data['reason']     = sanitize_textarea_field( $data['reason'] );
		$data['status']     = sanitize_key( $data['status'] );
		$data['created_at'] = current_time( 'mysql' );

		$wpdb->insert( $this->table_reports, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Insert revision.
	 *
	 * @param mixed $data Data.
	 */
	public function insert_revision( $data ) {
		global $wpdb;
		$defaults           = array(
			'item_id'   => 0,
			'item_type' => 'question',
			'user_id'   => 0,
			'content'   => '',
		);
		$data               = wp_parse_args( $data, $defaults );
		$data['item_id']    = absint( $data['item_id'] );
		$data['item_type']  = sanitize_key( $data['item_type'] );
		$data['user_id']    = absint( $data['user_id'] );
		$data['content']    = self::sanitize_rich( $data['content'] );
		$data['created_at'] = current_time( 'mysql' );

		$wpdb->insert( $this->table_revisions, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Revisions.
	 *
	 * @param mixed $item_id Item id.
	 * @param mixed $item_type Item type.
	 */
	public function get_revisions( $item_id, $item_type ) {
		global $wpdb;
		$item_id   = absint( $item_id );
		$item_type = sanitize_key( $item_type );
		$sql       = $wpdb->prepare( "SELECT * FROM {$this->table_revisions} WHERE item_id = %d AND item_type = %s ORDER BY created_at DESC", $item_id, $item_type ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * User qa stats.
	 *
	 * @param mixed $user_id User id.
	 */
	public function get_user_qa_stats( $user_id ) {
		global $wpdb;
		$user_id = absint( $user_id );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$question_count = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_questions} WHERE user_id = %d AND status <> 'deleted'",
					$user_id
				)
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$answer_count = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_answers} WHERE user_id = %d",
					$user_id
				)
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$question_upvotes = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(upvotes), 0) FROM {$this->table_questions} WHERE user_id = %d",
					$user_id
				)
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$answer_upvotes = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(upvotes), 0) FROM {$this->table_answers} WHERE user_id = %d",
					$user_id
				)
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$accepted_count = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_answers} WHERE user_id = %d AND is_accepted = 1",
					$user_id
				)
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$total_views = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(views), 0) FROM {$this->table_questions} WHERE user_id = %d",
					$user_id
				)
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		$reputation = ( $question_upvotes * 5 ) + ( $answer_upvotes * 10 ) + ( $accepted_count * 15 );

		return array(
			'question_count'   => $question_count,
			'answer_count'     => $answer_count,
			'question_upvotes' => $question_upvotes,
			'answer_upvotes'   => $answer_upvotes,
			'accepted_count'   => $accepted_count,
			'total_views'      => $total_views,
			'reputation'       => $reputation,
		);
	}

	/**
	 * User profile questions.
	 *
	 * @param mixed     $user_id User id.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_user_profile_questions( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$limit   = absint( $limit );
		$offset  = absint( $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT q.*,
                (SELECT COUNT(*) FROM {$this->table_answers} WHERE question_id = q.id) as answer_count,
                (SELECT COUNT(*) FROM {$this->table_votes} WHERE item_id = q.id AND item_type = 'question' AND vote_type = 'up') as total_upvotes
            FROM {$this->table_questions} q
            WHERE q.user_id = %d AND q.status = 'open' AND {$this->get_private_space_condition()}
            ORDER BY q.created_at DESC
            LIMIT %d OFFSET %d",
			$user_id,
			$limit,
			$offset
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * User profile answers.
	 *
	 * @param mixed     $user_id User id.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_user_profile_answers( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$limit   = absint( $limit );
		$offset  = absint( $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT a.*, q.title as question_title, q.slug as question_slug,
                (SELECT COUNT(*) FROM {$this->table_votes} WHERE item_id = a.id AND item_type = 'answer' AND vote_type = 'up') as total_upvotes
            FROM {$this->table_answers} a
            INNER JOIN {$this->table_questions} q ON a.question_id = q.id
            WHERE a.user_id = %d AND q.status <> 'deleted' AND {$this->get_private_space_condition()}
            ORDER BY a.upvotes DESC, a.created_at DESC
            LIMIT %d OFFSET %d",
			$user_id,
			$limit,
			$offset
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * User answer topics.
	 *
	 * @param mixed     $user_id User id.
	 * @param int|float $limit Limit.
	 */
	public function get_user_answer_topics( $user_id, $limit = 10 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$limit   = absint( $limit );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT t.id, t.name, t.slug, COUNT(qt.question_id) as answer_count
            FROM {$this->table_answers} a
            INNER JOIN {$this->table_questions} q ON a.question_id = q.id
            INNER JOIN {$this->table_question_topics} qt ON q.id = qt.question_id
            INNER JOIN {$this->table_topics} t ON qt.topic_id = t.id
            WHERE a.user_id = %d
            GROUP BY t.id
            ORDER BY answer_count DESC
            LIMIT %d",
			$user_id,
			$limit
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Log reputation.
	 *
	 * @param mixed     $user_id User id.
	 * @param mixed     $points Points.
	 * @param mixed     $reason Reason.
	 * @param int|float $reference_id Reference id.
	 * @param string    $reference_type Reference type.
	 */
	public function log_reputation( $user_id, $points, $reason, $reference_id = 0, $reference_type = 'question' ) {
		global $wpdb;
		$data = array(
			'user_id'        => absint( $user_id ),
			'points'         => intval( $points ),
			'reason'         => sanitize_key( $reason ),
			'reference_id'   => absint( $reference_id ),
			'reference_type' => sanitize_key( $reference_type ),
			'created_at'     => current_time( 'mysql' ),
		);
		$wpdb->insert( $this->table_reputation_log, $data );
		return $wpdb->insert_id;
	}

	/**
	 * User reputation log.
	 *
	 * @param mixed     $user_id User id.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_user_reputation_log( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$limit   = absint( $limit );
		$offset  = absint( $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table_reputation_log}
            WHERE user_id = %d
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
			$user_id,
			$limit,
			$offset
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * User reputation breakdown.
	 *
	 * @param mixed $user_id User id.
	 */
	public function get_user_reputation_breakdown( $user_id ) {
		global $wpdb;
		$user_id = absint( $user_id );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT reason, SUM(points) as total_points, COUNT(*) as count
            FROM {$this->table_reputation_log}
            WHERE user_id = %d
            GROUP BY reason
            ORDER BY total_points DESC",
			$user_id
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Badges.
	 */
	public function get_badges() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$this->table_badges} ORDER BY name ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * User badges.
	 *
	 * @param mixed $user_id User id.
	 */
	public function get_user_badges( $user_id ) {
		global $wpdb;
		$user_id = absint( $user_id );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT b.*, ub.awarded_at
            FROM {$this->table_user_badges} ub
            INNER JOIN {$this->table_badges} b ON ub.badge_id = b.id
            WHERE ub.user_id = %d
            ORDER BY ub.awarded_at DESC",
			$user_id
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Award badge.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $badge_id Badge id.
	 */
	public function award_badge( $user_id, $badge_id ) {
		global $wpdb;
		$user_id  = absint( $user_id );
		$badge_id = absint( $badge_id );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_user_badges} WHERE user_id = %d AND badge_id = %d",
				$user_id,
				$badge_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $existing ) {
			return false;
		}
		$wpdb->insert(
			$this->table_user_badges,
			array(
				'user_id'    => $user_id,
				'badge_id'   => $badge_id,
				'awarded_at' => current_time( 'mysql' ),
			)
		);
		return $wpdb->insert_id;
	}

	/**
	 * Insert badge.
	 *
	 * @param mixed $data Data.
	 */
	public function insert_badge( $data ) {
		global $wpdb;
		$data                   = wp_parse_args(
			$data,
			array(
				'name'           => '',
				'slug'           => '',
				'description'    => '',
				'icon'           => 'star',
				'criteria_type'  => 'manual',
				'criteria_value' => 0,
				'points'         => 0,
			)
		);
		$data['name']           = sanitize_text_field( $data['name'] );
		$data['slug']           = sanitize_title( $data['slug'] );
		$data['description']    = sanitize_textarea_field( $data['description'] );
		$data['icon']           = sanitize_key( $data['icon'] );
		$data['criteria_type']  = sanitize_key( $data['criteria_type'] );
		$data['criteria_value'] = absint( $data['criteria_value'] );
		$data['points']         = absint( $data['points'] );
		$data['created_at']     = current_time( 'mysql' );
		$wpdb->insert( $this->table_badges, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Check and award badges.
	 *
	 * @param mixed $user_id User id.
	 */
	public function check_and_award_badges( $user_id ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$stats   = $this->get_user_qa_stats( $user_id );
		$awarded = array();

		$checks = array(
			array( 'first-question', $stats['question_count'] >= 1 ),
			array( 'first-answer', $stats['answer_count'] >= 1 ),
			array( 'first-upvote', ( $stats['question_upvotes'] + $stats['answer_upvotes'] ) >= 1 ),
			array( 'ten-answers', $stats['answer_count'] >= 10 ),
			array( 'fifty-answers', $stats['answer_count'] >= 50 ),
			array( 'hundred-answers', $stats['answer_count'] >= 100 ),
			array( 'accepted-author', $stats['accepted_count'] >= 1 ),
			array( 'ten-accepted', $stats['accepted_count'] >= 10 ),
			array( 'popular-asker', $stats['question_upvotes'] >= 50 ),
			array( 'top-writer', $stats['answer_upvotes'] >= 100 ),
		);

		foreach ( $checks as $check ) {
			list($slug, $condition) = $check;
			if ( ! $condition ) {
				continue;
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			}
			$badge_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$this->table_badges} WHERE slug = %s",
					$slug
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			if ( $badge_id ) {
				$result = $this->award_badge( $user_id, $badge_id );
				if ( $result ) {
					$awarded[] = $slug;
				}
			}
		}

		return $awarded;
	}

	/**
	 * Table spaces.
	 */
	public function get_table_spaces() {
		return $this->table_spaces;
	}

	/**
	 * Table space members.
	 */
	public function get_table_space_members() {
		return $this->table_space_members;
	}

	/**
	 * Table space posts.
	 */
	public function get_table_space_posts() {
		return $this->table_space_posts;
	}

	/**
	 * Table bounties.
	 */
	public function get_table_bounties() {
		return $this->table_bounties;
	}

	/**
	 * Space.
	 *
	 * @param mixed $id Id.
	 */
	public function get_space( $id ) {
		global $wpdb;
		$id = absint( $id );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_spaces} WHERE id = %d LIMIT 1", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Space by slug.
	 *
	 * @param mixed $slug Slug.
	 */
	public function get_space_by_slug( $slug ) {
		global $wpdb;
		$slug = sanitize_title( $slug );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_spaces} WHERE slug = %s LIMIT 1", $slug ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Spaces.
	 *
	 * @param array $args Args.
	 */
	public function get_spaces( $args = array() ) {
		global $wpdb;
		$defaults = array(
			'limit'       => 20,
			'offset'      => 0,
			'public_only' => false,
		);
		$args     = wp_parse_args( $args, $defaults );
		$where    = $args['public_only'] ? 'WHERE is_public = 1' : '';
		$sql      = "SELECT * FROM {$this->table_spaces} {$where} ORDER BY member_count DESC LIMIT %d OFFSET %d";
		return $wpdb->get_results( $wpdb->prepare( $sql, absint( $args['limit'] ), absint( $args['offset'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Create space.
	 *
	 * @param mixed $data Data.
	 */
	public function create_space( $data ) {
		global $wpdb;
		$data['name']         = sanitize_text_field( $data['name'] );
		$data['slug']         = sanitize_title( $data['slug'] );
		$data['description']  = sanitize_textarea_field( $data['description'] );
		$data['creator_id']   = absint( $data['creator_id'] );
		$data['is_public']    = absint( $data['is_public'] ?? 1 );
		$data['member_count'] = 1;
		$data['created_at']   = current_time( 'mysql' );
		$wpdb->insert( $this->table_spaces, $data );
		$space_id = $wpdb->insert_id;
		if ( $space_id ) {
			$wpdb->insert(
				$this->table_space_members,
				array(
					'space_id'   => $space_id,
					'user_id'    => $data['creator_id'],
					'role'       => 'admin',
					'created_at' => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s', '%s' )
			);
		}
		return $space_id;
	}

	/**
	 * Space member.
	 *
	 * @param mixed $space_id Space id.
	 * @param mixed $user_id User id.
	 */
	public function is_space_member( $space_id, $user_id ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT id FROM {$this->table_space_members} WHERE space_id = %d AND user_id = %d LIMIT 1",
			absint( $space_id ),
			absint( $user_id )
		);
		return (bool) $wpdb->get_var( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Join space.
	 *
	 * @param mixed $space_id Space id.
	 * @param mixed $user_id User id.
	 */
	public function join_space( $space_id, $user_id ) {
		global $wpdb;
		if ( $this->is_space_member( $space_id, $user_id ) ) {
			return false;
		}
		$wpdb->insert(
			$this->table_space_members,
			array(
				'space_id'   => absint( $space_id ),
				'user_id'    => absint( $user_id ),
				'role'       => 'member',
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
		$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_spaces} SET member_count = member_count + 1 WHERE id = %d", absint( $space_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return true;
	}

	/**
	 * Leave space.
	 *
	 * @param mixed $space_id Space id.
	 * @param mixed $user_id User id.
	 */
	public function leave_space( $space_id, $user_id ) {
		global $wpdb;
		$result = $wpdb->delete(
			$this->table_space_members,
			array(
				'space_id' => absint( $space_id ),
				'user_id'  => absint( $user_id ),
			),
			array( '%d', '%d' )
		);
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $result ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$this->table_spaces} SET member_count = GREATEST(member_count - 1, 0) WHERE id = %d", absint( $space_id ) ) );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		return (bool) $result;
	}

	/**
	 * Post to space.
	 *
	 * @param mixed $space_id Space id.
	 * @param mixed $item_id Item id.
	 * @param mixed $item_type Item type.
	 * @param mixed $posted_by Posted by.
	 */
	public function post_to_space( $space_id, $item_id, $item_type, $posted_by ) {
		global $wpdb;
		$wpdb->insert(
			$this->table_space_posts,
			array(
				'space_id'   => absint( $space_id ),
				'item_id'    => absint( $item_id ),
				'item_type'  => sanitize_key( $item_type ),
				'posted_by'  => absint( $posted_by ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%s' )
		);
		return $wpdb->insert_id;
	}

	/**
	 * Space posts.
	 *
	 * @param mixed     $space_id Space id.
	 * @param int|float $limit Limit.
	 * @param int|float $offset Offset.
	 */
	public function get_space_posts( $space_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT sp.*, q.title, q.slug, q.content, q.upvotes, q.created_at AS question_created,
                (SELECT COUNT(*) FROM {$this->table_answers} WHERE question_id = q.id) AS answer_count
             FROM {$this->table_space_posts} sp
             INNER JOIN {$this->table_questions} q ON sp.item_id = q.id AND sp.item_type = 'question'
             WHERE sp.space_id = %d AND q.status <> 'deleted'
             ORDER BY sp.created_at DESC
             LIMIT %d OFFSET %d",
			absint( $space_id ),
			absint( $limit ),
			absint( $offset )
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Bounty.
	 *
	 * @param mixed $id Id.
	 */
	public function get_bounty( $id ) {
		global $wpdb;
		$this->expire_expired_bounties();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_bounties} WHERE id = %d LIMIT 1", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Open bounty for question.
	 *
	 * @param mixed $question_id Question id.
	 */
	public function get_open_bounty_for_question( $question_id ) {
		global $wpdb;
		$this->expire_expired_bounties();
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_bounties} WHERE question_id = %d AND status = 'open' LIMIT 1",
				absint( $question_id )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Create bounty.
	 *
	 * @param mixed $data Data.
	 */
	public function create_bounty( $data ) {
		global $wpdb;
		$wpdb->insert(
			$this->table_bounties,
			array(
				'question_id' => absint( $data['question_id'] ),
				'user_id'     => absint( $data['user_id'] ),
				'amount'      => absint( $data['amount'] ),
				'status'      => 'open',
				'created_at'  => current_time( 'mysql' ),
				'expires_at'  => gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s' )
		);
		return $wpdb->insert_id;
	}

	/**
	 * Expire expired bounties.
	 */
	public function expire_expired_bounties() {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$expired = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, user_id, amount FROM {$this->table_bounties}
            WHERE status = 'open' AND expires_at <> '0000-00-00 00:00:00' AND expires_at <= %s",
				current_time( 'mysql' )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $expired as $bounty ) {
			$wpdb->update( $this->table_bounties, array( 'status' => 'expired' ), array( 'id' => absint( $bounty->id ) ), array( '%s' ), array( '%d' ) );
			$this->log_reputation( absint( $bounty->user_id ), absint( $bounty->amount ), 'bounty_refunded', absint( $bounty->id ), 'bounty' );
		}
		return count( $expired );
	}

	/**
	 * Award bounty.
	 *
	 * @param mixed $bounty_id Bounty id.
	 * @param mixed $winner_id Winner id.
	 */
	public function award_bounty( $bounty_id, $winner_id ) {
		global $wpdb;
		$bounty = $this->get_bounty( $bounty_id );
		if ( ! $bounty || 'open' !== $bounty->status ) {
			return false;
		}
		$wpdb->update(
			$this->table_bounties,
			array(
				'status'    => 'awarded',
				'winner_id' => absint( $winner_id ),
			),
			array( 'id' => absint( $bounty_id ) ),
			array( '%s', '%d' ),
			array( '%d' )
		);
		$this->log_reputation( absint( $winner_id ), absint( $bounty->amount ), 'bounty_awarded', $bounty_id, 'bounty' );
		$this->insert_notification(
			array(
				'user_id'     => absint( $winner_id ),
				'action'      => 'bounty_awarded',
				'object_id'   => absint( $bounty_id ),
				'object_type' => 'bounty',
				'actor_id'    => absint( $bounty->user_id ),
			)
		);
		return true;
	}

	/**
	 * User spaces.
	 *
	 * @param mixed     $user_id User id.
	 * @param int|float $limit Limit.
	 */
	public function get_user_spaces( $user_id, $limit = 20 ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$sql = $wpdb->prepare(
			"SELECT s.* FROM {$this->table_spaces} s
             INNER JOIN {$this->table_space_members} sm ON s.id = sm.space_id
             WHERE sm.user_id = %d
             ORDER BY s.member_count DESC
             LIMIT %d",
			absint( $user_id ),
			absint( $limit )
		);
		return $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}
}
