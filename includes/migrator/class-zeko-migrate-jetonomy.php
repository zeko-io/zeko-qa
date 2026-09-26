<?php
/**
 * Migrate Jetonomy — Import data from Jetonomy into zeko-qa.
 *
 * @package Zeko_QA
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/** Class Zeko_Migrate_Jetonomy. */
class Zeko_Migrate_Jetonomy extends Zeko_Migrator_Base {

	private const BATCH = 50;

	/**
	 * Qa db.
	 *
	 * @var ?\Zeko_QA_DB Qa db.
	 */
	private ?\Zeko_QA_DB $qa_db = null;

	/**
	 * Jt prefix.
	 *
	 * @var string Jt prefix.
	 */
	private string $jt_prefix = '';

	/**
	 * Space map.
	 *
	 * @var array Space map.
	 */
	private array $space_map = array();

	/**
	 * Category map.
	 *
	 * @var array Category map.
	 */
	private array $category_map = array();

	/**
	 * Post map.
	 *
	 * @var array Post map.
	 */
	private array $post_map = array();

	/**
	 * Reply map.
	 *
	 * @var array Reply map.
	 */
	private array $reply_map = array();

	/**
	 * Tag map.
	 *
	 * @var array Tag map.
	 */
	private array $tag_map = array();

	// ─── Detection ──────────────────────────────────────────────────.

	/**
	 * Available.
	 */
	public function is_available(): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'jt_spaces';
		$check = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		return $check === $table;
	}

	/**
	 * Label.
	 */
	public function get_label(): string {
		return __( 'Jetonomy', 'zeko-qa' );
	}

	// ─── Options ────────────────────────────────────────────────────.

	/**
	 * Defaults.
	 */
	protected function get_defaults(): array {
		return array(
			'migrate_spaces'      => true,
			'migrate_categories'  => true,
			'migrate_posts'       => true,
			'migrate_replies'     => true,
			'migrate_votes'       => true,
			'migrate_tags'        => true,
			'migrate_bookmarks'   => true,
			'migrate_attachments' => true,
			'delete_source'       => false,
		);
	}

	// ─── QA DB singleton ────────────────────────────────────────────.

	/**
	 * Qa.
	 */
	private function qa(): \Zeko_QA_DB {
		if ( null === $this->qa_db ) {
			$this->qa_db = \Zeko_QA_DB::get_instance();
		}
		return $this->qa_db;
	}

	/**
	 * Jt.
	 *
	 * @param string $table Table.
	 */
	private function jt( string $table ): string {
		global $wpdb;
		return $wpdb->prefix . 'jt_' . $table;
	}

	// ─── Item Counts ────────────────────────────────────────────────.

	/**
	 * Item counts.
	 */
	public function get_item_counts(): array {
		global $wpdb;

		if ( ! $this->is_available() ) {
			return array(
				'spaces'  => 0,
				'posts'   => 0,
				'replies' => 0,
				'votes'   => 0,
				'tags'    => 0,
			);
		}

		$spaces  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->jt( 'spaces' )}" ); // phpcs:ignore
		$posts   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->jt( 'posts' )}" ); // phpcs:ignore
		$replies = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->jt( 'replies' )}" ); // phpcs:ignore
		$votes   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->jt( 'votes' )}" ); // phpcs:ignore
		$tags    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->jt( 'tags' )}" ); // phpcs:ignore

		return array(
			'spaces'  => $spaces,
			'posts'   => $posts,
			'replies' => $replies,
			'votes'   => $votes,
			'tags'    => $tags,
		);
	}

	// ─── Preview ────────────────────────────────────────────────────.

	/**
	 * Preview.
	 *
	 * @param int $limit Limit.
	 */
	public function preview( int $limit = 20 ): array {
		global $wpdb;

		$this->dry_run = true;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$spaces = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, slug, type FROM {$this->jt( 'spaces' )} ORDER BY id ASC LIMIT %d",
				$limit
		) ); // phpcs:ignore

		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, space_id, title, slug, status, view_count, vote_score FROM {$this->jt( 'posts' )} ORDER BY id ASC LIMIT %d",
				$limit
		) ); // phpcs:ignore

		$counts = $this->get_item_counts();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		return array(
			'spaces_sample' => $spaces,
			'posts_sample'  => $posts,
			'counts'        => $counts,
		);
	}

	// ─── Run Migration ─────────────────────────────────────────────.

	/**
	 * Run.
	 */
	public function run(): array {
		$this->dry_run      = false;
		$this->log          = array();
		$this->id_map       = array();
		$this->space_map    = array();
		$this->category_map = array();
		$this->post_map     = array();
		$this->reply_map    = array();
		$this->tag_map      = array();

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		if ( ! $this->is_available() ) {
			$this->log( 'error', 'source', 'Jetonomy tables not detected.' );
			return array(
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => 1,
			);
		}

		$this->set_options( $this->options );

		// 1. Spaces → Topics.
		if ( $this->options['migrate_spaces'] ) {
			$result    = $this->migrate_spaces();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		// 2. Categories → Topics (sub-topics).
		if ( $this->options['migrate_categories'] ) {
			$result    = $this->migrate_categories();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		// 3. Posts → Questions.
		if ( $this->options['migrate_posts'] ) {
			$result    = $this->migrate_posts();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		// 4. Replies → Answers.
		if ( $this->options['migrate_replies'] ) {
			$result    = $this->migrate_replies();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		// 5. Votes → QA Votes.
		if ( $this->options['migrate_votes'] ) {
			$result    = $this->migrate_votes();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		// 6. Tags → QA Tags.
		if ( $this->options['migrate_tags'] ) {
			$result    = $this->migrate_tags();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		// 7. Bookmarks → QA Bookmarks.
		if ( $this->options['migrate_bookmarks'] ) {
			$result    = $this->migrate_bookmarks();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		// 8. Attachments → Media.
		if ( $this->options['migrate_attachments'] ) {
			$result    = $this->migrate_attachments();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		// 9. Optionally delete source tables.
		if ( $this->options['delete_source'] ) {
			$this->delete_source_tables();
		}

		$this->log(
			'info',
			'complete',
			sprintf(
				'Jetonomy migration finished: %d imported, %d skipped, %d errors.',
				$imported,
				$skipped,
				$errors
			)
		);

		do_action( 'zbp_migration_complete', 'jetonomy', $imported, $skipped, $this->options );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/*
	=================================================================
	 * 1. Spaces → Topics
	 * ==============================================================
	 */

	/**
	 * Migrate spaces.
	 */
	private function migrate_spaces(): array {
		global $wpdb;

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;
		$offset   = 0;

		$table = $this->jt( 'spaces' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		do {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} ORDER BY id ASC LIMIT %d OFFSET %d",
					self::BATCH,
					$offset
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// phpcs:enable

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				$name = trim( $row->title );
				if ( empty( $name ) ) {
					++$skipped;
					$this->log( 'skipped', "Space #{$row->id}", 'Empty title.' );
					continue;
				}

				$slug = $this->unique_slug( sanitize_title( $row->slug ?: $name ), 'zeko_qa_topics' );

				$description = '';
				if ( ! empty( $row->settings ) ) {
					$settings = json_decode( $row->settings, true );
					if ( is_array( $settings ) && ! empty( $settings['description'] ) ) {
						$description = sanitize_textarea_field( $settings['description'] );
					}
				}

				if ( $this->dry_run ) {
					$this->log( 'dry_run', $name, 'Would create topic from space.' );
					++$imported;
					continue;
				}

				$topic_id = $this->qa()->insert_topic(
					array(
						'name'        => $name,
						'slug'        => $slug,
						'description' => $description,
						'user_id'     => 0,
					)
				);

				if ( ! $topic_id ) {
					++$errors;
					$this->log( 'error', $name, 'Failed to create topic from space.' );
					continue;
				}

				$this->space_map[ (int) $row->id ]   = $topic_id;
				$this->id_map[ 'space_' . $row->id ] = $topic_id;
				$this->log( 'imported', $name, "Topic created (ID: {$topic_id}) from space #{$row->id}." );
				++$imported;

				do_action( 'zbp_migration_item_complete', 'jetonomy', (int) $row->id, $topic_id );
			}

			$offset += self::BATCH;

			if ( $this->dry_run ) {
				break;
			}
			$rows_count = count( $rows );
		} while ( self::BATCH === $rows_count );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/*
	=================================================================
	 * 2. Categories → Topics (sub-topics)
	 * ==============================================================
	 */

	/**
	 * Migrate categories.
	 */
	private function migrate_categories(): array {
		global $wpdb;

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		$table = $this->jt( 'categories' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY parent_id ASC, id ASC" );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:enable

		if ( empty( $rows ) ) {
			return array(
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => 0,
			);
		}

		foreach ( $rows as $row ) {
			$name = trim( $row->name );
			if ( empty( $name ) ) {
				++$skipped;
				continue;
			}

			$slug = $this->unique_slug( sanitize_title( $row->slug ?: $name ), 'zeko_qa_topics' );

			if ( $this->dry_run ) {
				$this->log( 'dry_run', $name, 'Would create topic from category.' );
				++$imported;
				continue;
			}

			$topic_id = $this->qa()->insert_topic(
				array(
					'name'        => $name,
					'slug'        => $slug,
					'description' => '',
					'user_id'     => 0,
				)
			);

			if ( ! $topic_id ) {
				++$errors;
				$this->log( 'error', $name, 'Failed to create topic from category.' );
				continue;
			}

			$this->category_map[ (int) $row->id ]   = $topic_id;
			$this->id_map[ 'category_' . $row->id ] = $topic_id;
			$this->log( 'imported', $name, "Topic created (ID: {$topic_id}) from category #{$row->id}." );
			++$imported;
		}

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/*
	=================================================================
	 * 3. Posts → Questions
	 * ==============================================================
	 */

	/**
	 * Migrate posts.
	 */
	private function migrate_posts(): array {
		global $wpdb;

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;
		$offset   = 0;

		$table = $this->jt( 'posts' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		do {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} ORDER BY id ASC LIMIT %d OFFSET %d",
					self::BATCH,
					$offset
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// phpcs:enable

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				$title = trim( $row->title );
				if ( empty( $title ) ) {
					++$skipped;
					$this->log( 'skipped', "Post #{$row->id}", 'Empty title.' );
					continue;
				}

				$slug = $this->unique_slug( sanitize_title( $row->slug ?: $title ), 'zeko_questions' );

				$user_id = $this->resolve_user( (int) $row->author_id );

				$status = 'open';
				if ( 'closed' === $row->status ) {
					$status = 'closed';
				} elseif ( 'published' === $row->status ) {
					$status = 'open';
				}

				$upvotes   = max( 0, (int) $row->vote_score );
				$downvotes = max( 0, -1 * (int) $row->vote_score );

				if ( $this->dry_run ) {
					$this->log( 'dry_run', $title, 'Would create question from post.' );
					++$imported;
					continue;
				}

				$question_id = $this->qa()->insert_question(
					array(
						'user_id' => $user_id,
						'title'   => $title,
						'slug'    => $slug,
						'content' => $row->content,
						'views'   => (int) $row->view_count,
						'status'  => $status,
					)
				);

				if ( ! $question_id ) {
					++$errors;
					$this->log( 'error', $title, 'Failed to create question from post.' );
					continue;
				}

				// Update vote counts after insert.
				$this->qa()->update_question(
					$question_id,
					array(
						'upvotes'   => $upvotes,
						'downvotes' => $downvotes,
					)
				);

				$this->post_map[ (int) $row->id ]   = $question_id;
				$this->id_map[ 'post_' . $row->id ] = $question_id;
				$this->log( 'imported', $title, "Question created (ID: {$question_id}) from post #{$row->id}." );
				++$imported;

				do_action( 'zbp_migration_item_complete', 'jetonomy', (int) $row->id, $question_id );
			}

			$offset += self::BATCH;

			if ( $this->dry_run ) {
				break;
			}
			$rows_count = count( $rows );
		} while ( self::BATCH === $rows_count );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/*
	=================================================================
	 * 4. Replies → Answers
	 * ==============================================================
	 */

	/**
	 * Migrate replies.
	 */
	private function migrate_replies(): array {
		global $wpdb;

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;
		$offset   = 0;

		$table = $this->jt( 'replies' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		do {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} ORDER BY id ASC LIMIT %d OFFSET %d",
					self::BATCH,
					$offset
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// phpcs:enable

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				// Skip parent replies (only child replies become answers).
				if ( (int) $row->parent_id > 0 ) {
					++$skipped;
					continue;
				}

				$question_id = $this->post_map[ (int) $row->post_id ] ?? 0;
				if ( ! $question_id ) {
					++$skipped;
					$this->log( 'skipped', "Reply #{$row->id}", "No mapped question for post_id #{$row->post_id}." );
					continue;
				}

				$user_id = $this->resolve_user( (int) $row->author_id );

				if ( $this->dry_run ) {
					$this->log( 'dry_run', "Reply #{$row->id}", 'Would create answer.' );
					++$imported;
					continue;
				}

				$answer_id = $this->qa()->insert_answer(
					array(
						'question_id' => $question_id,
						'user_id'     => $user_id,
						'content'     => $row->content,
						'is_accepted' => 0,
					)
				);

				if ( ! $answer_id ) {
					++$errors;
					$this->log( 'error', "Reply #{$row->id}", 'Failed to create answer.' );
					continue;
				}

				$this->reply_map[ (int) $row->id ]   = $answer_id;
				$this->id_map[ 'reply_' . $row->id ] = $answer_id;
				$this->log( 'imported', "Reply #{$row->id}", "Answer created (ID: {$answer_id})." );
				++$imported;

				do_action( 'zbp_migration_item_complete', 'jetonomy', (int) $row->id, $answer_id );
			}

			$offset += self::BATCH;

			if ( $this->dry_run ) {
				break;
			}
			$rows_count = count( $rows );
		} while ( self::BATCH === $rows_count );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/*
	=================================================================
	 * 5. Votes → QA Votes
	 * ==============================================================
	 */

	/**
	 * Migrate votes.
	 */
	private function migrate_votes(): array {
		global $wpdb;

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;
		$offset   = 0;

		$table = $this->jt( 'votes' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		do {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} ORDER BY id ASC LIMIT %d OFFSET %d",
					self::BATCH,
					$offset
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// phpcs:enable

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				$object_type = sanitize_text_field( $row->object_type );
				$object_id   = (int) $row->object_id;

				// Map object_type and object_id to zeko item.
				if ( 'post' === $object_type ) {
					$item_id   = $this->post_map[ $object_id ] ?? 0;
					$item_type = 'question';
				} elseif ( 'reply' === $object_type ) {
					$item_id   = $this->reply_map[ $object_id ] ?? 0;
					$item_type = 'answer';
				} else {
					++$skipped;
					continue;
				}

				if ( ! $item_id ) {
					++$skipped;
					continue;
				}

				$value     = (int) $row->value;
				$vote_type = $value > 0 ? 'up' : 'down';

				if ( $this->dry_run ) {
					$this->log( 'dry_run', "Vote #{$row->id}", 'Would create vote.' );
					++$imported;
					continue;
				}

				$vote_id = $this->qa()->insert_vote(
					array(
						'user_id'   => (int) $row->user_id,
						'item_id'   => $item_id,
						'item_type' => $item_type,
						'vote_type' => $vote_type,
					)
				);

				if ( ! $vote_id ) {
					++$errors;
					$this->log( 'error', "Vote #{$row->id}", 'Failed to create vote.' );
					continue;
				}

				$this->log( 'imported', "Vote #{$row->id}", "Vote created (ID: {$vote_id})." );
				++$imported;
			}

			$offset += self::BATCH;

			if ( $this->dry_run ) {
				break;
			}
			$rows_count = count( $rows );
		} while ( self::BATCH === $rows_count );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/*
	=================================================================
	 * 6. Tags → QA Tags
	 * ==============================================================
	 */

	/**
	 * Migrate tags.
	 */
	private function migrate_tags(): array {
		global $wpdb;

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		// Insert tags.
		$tags_table = $this->jt( 'tags' );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tags = $wpdb->get_results( "SELECT * FROM {$tags_table} ORDER BY id ASC" );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:enable

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$name = trim( $tag->name );
				if ( empty( $name ) ) {
					++$skipped;
					continue;
				}

				$slug = $this->unique_slug( sanitize_title( $tag->slug ?: $name ), 'zeko_qa_tags' );

				if ( $this->dry_run ) {
					$this->log( 'dry_run', $name, 'Would create tag.' );
					++$imported;
					continue;
				}

				$tag_id = $this->qa()->insert_tag(
					array(
						'name'        => $name,
						'slug'        => $slug,
						'description' => '',
					)
				);

				if ( ! $tag_id ) {
					++$errors;
					$this->log( 'error', $name, 'Failed to create tag.' );
					continue;
				}

				$this->tag_map[ (int) $tag->id ] = $tag_id;
				$this->log( 'imported', $name, "Tag created (ID: {$tag_id})." );
				++$imported;
			}
		}

		// Attach tags to questions via jt_tagged.
		$tagged_table = $this->jt( 'tagged' );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tagged = $wpdb->get_results( "SELECT * FROM {$tagged_table}" );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:enable

		if ( ! empty( $tagged ) && ! $this->dry_run ) {
			foreach ( $tagged as $rel ) {
				$question_id = $this->post_map[ (int) $rel->post_id ] ?? 0;
				$tag_id      = $this->tag_map[ (int) $rel->tag_id ] ?? 0;

				if ( $question_id && $tag_id ) {
					$this->qa()->attach_tags_to_question( $question_id, array( $tag_id ) );
				}
			}
		}

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/*
	=================================================================
	 * 7. Bookmarks → QA Bookmarks
	 * ==============================================================
	 */

	/**
	 * Migrate bookmarks.
	 */
	private function migrate_bookmarks(): array {
		global $wpdb;

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		$table = $this->jt( 'bookmarks' );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC" );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:enable

		if ( empty( $rows ) ) {
			return array(
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => 0,
			);
		}

		$bookmarks_table = $this->qa()->get_table_bookmarks();

		foreach ( $rows as $row ) {
			$question_id = $this->post_map[ (int) $row->post_id ] ?? 0;
			$user_id     = (int) $row->user_id;

			if ( ! $question_id || ! $user_id ) {
				++$skipped;
				continue;
			}

			if ( $this->dry_run ) {
				$this->log( 'dry_run', "Bookmark #{$row->id}", 'Would create bookmark.' );
				++$imported;
				continue;
			}

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$bookmarks_table} WHERE user_id = %d AND question_id = %d LIMIT 1",
					$user_id,
					$question_id
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// phpcs:enable

			if ( $exists ) {
				++$skipped;
				continue;
			}

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$bookmarks_table,
				array(
					'user_id'     => $user_id,
					'question_id' => $question_id,
					'created_at'  => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s' )
			);
			// phpcs:enable

			if ( $wpdb->insert_id ) {
				$this->log( 'imported', "Bookmark #{$row->id}", "Bookmark created (ID: {$wpdb->insert_id})." );
				++$imported;
			} else {
				++$errors;
				$this->log( 'error', "Bookmark #{$row->id}", 'Failed to insert bookmark.' );
			}
		}

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/*
	=================================================================
	 * 8. Attachments → Media
	 * ==============================================================
	 */

	/**
	 * Migrate attachments.
	 */
	private function migrate_attachments(): array {
		global $wpdb;

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;
		$offset   = 0;

		$table = $this->jt( 'attachments' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		do {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} ORDER BY id ASC LIMIT %d OFFSET %d",
					self::BATCH,
					$offset
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// phpcs:enable

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				$filepath = $row->filepath;
				$filename = $row->filename;
				$mime     = $row->mime_type;

				if ( empty( $filepath ) || empty( $filename ) ) {
					++$skipped;
					continue;
				}

				// Resolve the full file path.
				$full_path = $filepath;
				if ( 0 !== strpos( $full_path, ABSPATH ) ) {
					$full_path = ABSPATH . ltrim( $full_path, '/' );
				}

				if ( ! file_exists( $full_path ) ) {
					++$skipped;
					$this->log( 'skipped', $filename, 'File not found on disk.' );
					continue;
				}

				// Determine the item (question or answer) this attachment belongs to.
				$item_type = '';
				$item_id   = 0;

				if ( (int) $row->post_id > 0 ) {
					$item_type = 'question';
					$item_id   = $this->post_map[ (int) $row->post_id ] ?? 0;
				}

				if ( $this->dry_run ) {
					$this->log( 'dry_run', $filename, 'Would import attachment.' );
					++$imported;
					continue;
				}

				// Create WP media attachment.
				$attachment_id = media_sideload_image( $full_path, 0, $filename, 'id' );

				if ( is_wp_error( $attachment_id ) ) {
					++$errors;
					$this->log( 'error', $filename, 'Media import failed: ' . $attachment_id->get_error_message() );
					continue;
				}

				$this->log( 'imported', $filename, "Attachment imported (WP media ID: {$attachment_id})." );
				++$imported;
			}

			$offset += self::BATCH;

			if ( $this->dry_run ) {
				break;
			}
			$rows_count = count( $rows );
		} while ( self::BATCH === $rows_count );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/*
	=================================================================
	 * Helpers
	 * ==============================================================
	 */

	/**
	 * Resolve a Jetonomy author_id to a WordPress user ID.
	 * Checks user profiles for reputation mapping, falls back to 0.
	 *
	 * @param int $author_id Author id.
	 */
	private function resolve_user( int $author_id ): int {
		global $wpdb;

		if ( $author_id <= 0 ) {
			return 0;
		}

		$table = $this->jt( 'user_profiles' );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$user_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$table} WHERE user_id = %d LIMIT 1",
				$author_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:enable

		if ( $user_id ) {
			return $user_id;
		}

		return 0;
	}

	/**
	 * Generate a unique slug for a given table.
	 *
	 * @param string $slug Slug.
	 * @param string $table Table.
	 */
	private function unique_slug( string $slug, string $table ): string {
		global $wpdb;

		$original   = $slug;
		$counter    = 1;
		$table_name = $wpdb->prefix . $table;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		while ( true ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE slug = %s",
					$slug
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// phpcs:enable

			if ( ! $exists ) {
				return $slug;
			}

			++$counter;
			$slug = $original . '-' . $counter;
		}
	}

	/**
	 * Delete Jetonomy source tables after migration.
	 */
	private function delete_source_tables(): void {
		global $wpdb;

		$tables = array(
			'jt_spaces',
			'jt_posts',
			'jt_replies',
			'jt_votes',
			'jt_tags',
			'jt_tagged',
			'jt_categories',
			'jt_subscriptions',
			'jt_space_members',
			'jt_bookmarks',
			'jt_user_profiles',
			'jt_attachments',
		);

		foreach ( $tables as $table ) {
			$full = $wpdb->prefix . $table;
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "DROP TABLE IF EXISTS {$full}" );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}

		$this->log( 'info', 'cleanup', 'Jetonomy source tables dropped.' );
	}
}
