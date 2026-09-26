<?php
/**
 * Migrator: wpForo → zeko-qa.
 *
 * Imports wpForo forums, topics, posts, votes, and user profiles
 * into the zeko-qa system.
 *
 * @package Zeko_QA
 * @since 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/** Class Zeko_Migrate_WpForo. */
class Zeko_Migrate_WpForo extends Zeko_Migrator_Base {

	/**
	 * Wf prefix.
	 *
	 * @var ?string Wf prefix.
	 */
	private ?string $wf_prefix = null;

	/**
	 * Topic map.
	 *
	 * @var array Topic map.
	 */
	private array $topic_map = array();

	/**
	 * Post map.
	 *
	 * @var array Post map.
	 */
	private array $post_map = array();

	/**
	 * User map.
	 *
	 * @var array User map.
	 */
	private array $user_map = array();

	/**
	 * Default migration options.
	 */
	protected function get_defaults(): array {
		return array(
			'migrate_forums'   => true,
			'migrate_topics'   => true,
			'migrate_posts'    => true,
			'migrate_votes'    => true,
			'migrate_profiles' => true,
			'delete_source'    => false,
		);
	}

	/**
	 * Human-readable label.
	 */
	public function get_label(): string {
		return __( 'wpForo → zeko-qa', 'zeko-qa' );
	}

	/**
	 * Detect wpForo availability.
	 */
	public function is_available(): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'wpforo_forums';
		$check = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( $check ) {
			return true;
		}

		return class_exists( 'wpForo', false );
	}

	/**
	 * Detect the correct wpForo table prefix.
	 * wpForo uses a board system. The default board prefix is
	 * `{wp_prefix}wpforo_`. Additional boards use `{wp_prefix}wpforo_{board_id}_`.
	 * We detect which tables exist and pick the first valid prefix.
	 */
	private function detect_prefix(): string {
		global $wpdb;

		if ( null !== $this->wf_prefix ) {
			return $this->wf_prefix;
		}

		$candidates = array(
			$wpdb->prefix . 'wpforo_',
		);

		// Check boards table for additional prefixes.
		$boards_table = $wpdb->prefix . 'wpforo_boards';
		$has_boards   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $boards_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $has_boards ) {
			$boards = $wpdb->get_col( "SELECT board_id FROM {$boards_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			foreach ( (array) $boards as $board_id ) {
				$board_id = absint( $board_id );
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
				if ( $board_id > 0 ) {
					$candidates[] = $wpdb->prefix . 'wpforo_' . $board_id . '_';
				}
			}
		}

		foreach ( $candidates as $prefix ) {
			$check = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $prefix . 'wpforo_forums' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( $check ) {
				$this->wf_prefix = $prefix;
				return $this->wf_prefix;
			}
		}

		// Fallback to default.
		$this->wf_prefix = $wpdb->prefix . 'wpforo_';
		return $this->wf_prefix;
	}

	/**
	 * Get a wpForo table name with the detected prefix.
	 *
	 * @param string $name Name.
	 */
	private function table( string $name ): string {
		return $this->detect_prefix() . $name;
	}

	/**
	 * Resolve wpForo userid → WP user ID.
	 *
	 * @param int $wf_user_id Wf user id.
	 */
	private function resolve_user( int $wf_user_id ): int {
		if ( isset( $this->user_map[ $wf_user_id ] ) ) {
			return $this->user_map[ $wf_user_id ];
		}

		global $wpdb;

		$profiles_table = $this->table( 'wpwpforo_profiles' );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$wp_user_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT userid FROM {$profiles_table} WHERE userid = %d LIMIT 1",
				$wf_user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// wpForo userid is the WP user ID — they match 1:1.
		if ( $wp_user_id && get_userdata( $wp_user_id ) ) {
			$this->user_map[ $wf_user_id ] = $wp_user_id;
		} else {
			$this->user_map[ $wf_user_id ] = 0;
		}

		return $this->user_map[ $wf_user_id ];
	}

	/**
	 * Ensure slug uniqueness by appending a suffix if needed.
	 *
	 * @param string $slug Slug.
	 * @param string $table Table.
	 * @param string $slug_column Slug column.
	 */
	private function unique_slug( string $slug, string $table, string $slug_column = 'slug' ): string {
		global $wpdb;

		$original = $slug;
		$suffix   = 2;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$slug_column} = %s", $slug ) ) ) {
			$slug = $original . '-' . $suffix;
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			++$suffix;
		}

		return $slug;
	}

	/**
	 * Get item counts for preview.
	 */
	public function get_item_counts(): array {
		global $wpdb;

		if ( ! $this->is_available() ) {
			return array(
				'forums' => 0,
				'topics' => 0,
				'posts'  => 0,
				'votes'  => 0,
			);
		}

		$prefix = $this->detect_prefix();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$forums = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) FROM {$prefix}wpforo_forums"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$topics = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) FROM {$prefix}wpwpforo_topics"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$posts = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) FROM {$prefix}wpwpforo_posts"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		$votes_table = $prefix . 'wpwpforo_likes';
		$has_likes   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $votes_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$votes       = 0;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $has_likes ) {
			$votes = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$votes_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		return array(
			'forums' => $forums,
			'topics' => $topics,
			'posts'  => $posts,
			'votes'  => $votes,
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Return preview data (sample of items to be migrated).
	 *
	 * @param int $limit Limit.
	 */
	public function preview( int $limit = 20 ): array {
		global $wpdb;

		$preview = array();

		if ( ! $this->is_available() ) {
			return $preview;
		}

		$prefix = $this->detect_prefix();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// Sample forums.
		$forums = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT id, title, description, forum_type FROM {$prefix}wpforo_forums ORDER BY `order` ASC LIMIT %d",
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $forums as $forum ) {
			$preview[] = array(
				'type'        => 'forum',
				'id'          => (int) $forum->id,
				'name'        => $forum->title,
				'description' => $forum->description,
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// Sample topics.
		$topics = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT id, title, forumid, views, answers, closed, pinned FROM {$prefix}wpwpforo_topics ORDER BY created DESC LIMIT %d",
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $topics as $topic ) {
			$preview[] = array(
				'type'    => 'topic',
				'id'      => (int) $topic->id,
				'title'   => $topic->title,
				'forumid' => (int) $topic->forumid,
				'views'   => (int) $topic->views,
				'answers' => (int) $topic->answers,
				'status'  => ! empty( $topic->closed ) ? 'closed' : 'open',
			);
		}

		return $preview;
	}

	/**
	 * Run the full migration.
	 */
	public function run(): array {
		$this->set_options( $this->options );
		$this->id_map    = array();
		$this->topic_map = array();
		$this->post_map  = array();
		$this->user_map  = array();

		if ( ! $this->is_available() ) {
			$this->log( 'error', 'init', __( 'wpForo not detected.', 'zeko-qa' ) );
			return array(
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => 1,
			);
		}

		global $wpdb;

		$prefix          = $this->detect_prefix();
		$imported        = 0;
		$skipped         = 0;
		$errors          = 0;
		$forum_map       = array();
		$forum_topic_ids = array();

		/**
		 * Action: Before wpForo migration starts.
		 */
		do_action( 'zbp_wpforo_migration_start', $this->options );

		// ── 1. Forums → QA Topics ──────────────────────────────────────.
		if ( $this->options['migrate_forums'] ) {
			$this->log( 'info', 'forums', __( 'Starting forum migration…', 'zeko-qa' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			$forums = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT id, parentid, title, slug, description, forum_type FROM {$prefix}wpforo_forums ORDER BY `order` ASC"
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

			$qa_db        = \Zeko_QA_DB::get_instance();
			$topics_table = $qa_db->get_table_topics();

			foreach ( $forums as $forum ) {
				$slug = sanitize_title( $forum->slug );
				if ( empty( $slug ) ) {
					$slug = sanitize_title( $forum->title );
				}
				$slug = $this->unique_slug( $slug, $topics_table );

				if ( $this->dry_run ) {
					++$imported;
					continue;
				}

				$topic_id = $qa_db->insert_topic(
					array(
						'name'        => $forum->title,
						'slug'        => $slug,
						'description' => $forum->description ?? '',
						'user_id'     => 0,
					)
				);

				if ( $topic_id ) {
					$forum_map[ (int) $forum->id ]         = (int) $topic_id;
					$this->id_map[ 'forum_' . $forum->id ] = (int) $topic_id;
					++$imported;
					$this->log( 'imported', 'forum', $forum->title . ' → topic #' . $topic_id );
				} else {
					++$errors;
					$this->log( 'error', 'forum', 'Failed to insert: ' . $forum->title );
				}
			}

			$this->log(
				'info',
				'forums',
				sprintf(
				/* translators: %d: number of forums */
					__( 'Forums migrated: %d', 'zeko-qa' ),
					$imported
				)
			);
		}

		// ── 2. Topics → Questions ───────────────────────────────────────.
		$questions_imported = 0;
		if ( $this->options['migrate_topics'] ) {
			$this->log( 'info', 'topics', __( 'Starting topic migration…', 'zeko-qa' ) );

			$qa_db         = \Zeko_QA_DB::get_instance();
			$questions_tbl = $qa_db->get_table_questions();
			$offset        = 0;
			$batch         = 50;

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			do {
				$topics = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"SELECT id, forumid, title, slug, created, posterid, views, answers, closed, pinned, `type`
						FROM {$prefix}wpwpforo_topics
						ORDER BY id ASC
						LIMIT %d OFFSET %d",
						$batch,
						$offset
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

				if ( empty( $topics ) ) {
					break;
				}

				foreach ( $topics as $topic ) {
					$slug = sanitize_title( $topic->slug );
					if ( empty( $slug ) ) {
						$slug = sanitize_title( $topic->title );
					}
					$slug = $this->unique_slug( $slug, $questions_tbl );

					// Get the first post content (topic body).
					$content = '';
					// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
					$first_post = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$wpdb->prepare(
							"SELECT content FROM {$prefix}wpwpforo_posts WHERE threadid = %d AND id = %d LIMIT 1",
							$topic->id,
							$topic->id
						)
					);
					// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
					if ( $first_post ) {
						$content = $first_post;
					}

					$user_id = $this->resolve_user( (int) $topic->posterid );

					if ( $this->dry_run ) {
						++$questions_imported;
						$this->topic_map[ (int) $topic->id ]        = 0;
						$forum_topic_ids[ (int) $topic->forumid ][] = 0;
						continue;
					}

					$question_id = $qa_db->insert_question(
						array(
							'user_id' => $user_id,
							'title'   => $topic->title,
							'slug'    => $slug,
							'content' => $content,
							'views'   => (int) $topic->views,
							'status'  => ! empty( $topic->closed ) ? 'closed' : 'open',
						)
					);

					if ( $question_id ) {
						$this->topic_map[ (int) $topic->id ]        = (int) $question_id;
						$this->id_map[ 'topic_' . $topic->id ]      = (int) $question_id;
						$forum_topic_ids[ (int) $topic->forumid ][] = (int) $question_id;
						++$questions_imported;
						$this->log( 'imported', 'topic', $topic->title . ' → question #' . $question_id );

						// Update vote counts and featured flag.
						$update = array();
						if ( ! empty( $topic->pinned ) ) {
							$update['is_featured'] = 1;
						// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
						}
						// Topic type handling is a no-op in this migration: wpForo type is not
						// mapped into zeko-qa categories.
						// Set upvote/downvote counts.
						$topic_meta = $wpdb->get_row(
							$wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
								"SELECT meta_value FROM {$prefix}wpwpforo_postmeta WHERE postid = %d AND meta_key = 'vote_net' LIMIT 1",
								$topic->id
							)
						);
						// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
						if ( ! empty( $update ) ) {
							$qa_db->update_question( $question_id, $update );
						}
					} else {
						++$errors;
						$this->log( 'error', 'topic', 'Failed to insert: ' . $topic->title );
					}
				}

				$offset += $batch;
			} while ( true );

			$this->log(
				'info',
				'topics',
				sprintf(
				/* translators: %d: number of topics */
					__( 'Topics migrated: %d', 'zeko-qa' ),
					$questions_imported
				)
			);
		}

		$imported += $questions_imported;

		// ── Link questions to forum topics ─────────────────────────────.
		if ( ! $this->dry_run && ! empty( $forum_map ) ) {
			$qa_db    = \Zeko_QA_DB::get_instance();
			$qt_table = $qa_db->get_table_question_topics();

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// Re-query topics to get their forum mapping.
			$all_topics = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT id, forumid FROM {$prefix}wpwpforo_topics"
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

			foreach ( $all_topics as $topic ) {
				$question_id = $this->topic_map[ (int) $topic->id ] ?? 0;
				$qa_topic_id = $forum_map[ (int) $topic->forumid ] ?? 0;

				if ( $question_id && $qa_topic_id ) {
					$wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$qt_table,
						array(
							'question_id' => $question_id,
							'topic_id'    => $qa_topic_id,
						),
						array( '%d', '%d' )
					);
				}
			}
		}

		// ── 3. Posts (Replies) → Answers ───────────────────────────────.
		$posts_imported = 0;
		if ( $this->options['migrate_posts'] ) {
			$this->log( 'info', 'posts', __( 'Starting post migration…', 'zeko-qa' ) );

			$qa_db  = \Zeko_QA_DB::get_instance();
			$offset = 0;
			$batch  = 50;

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			do {
				$posts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"SELECT id, threadid, forumid, title, content, userid, poster_name, created, likes, voteup, votedown
						FROM {$prefix}wpwpforo_posts
						ORDER BY id ASC
						LIMIT %d OFFSET %d",
						$batch,
						$offset
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

				if ( empty( $posts ) ) {
					break;
				}

				foreach ( $posts as $post ) {
					$wf_post_id   = (int) $post->id;
					$wf_thread_id = (int) $post->threadid;

					// A post is a reply if its threadid != its id (not the first post).
					$is_reply = ( $wf_thread_id !== $wf_post_id );

					if ( ! $is_reply ) {
						// This is the topic body post — update question content if empty.
						if ( ! $this->dry_run && ! empty( $post->content ) ) {
							$question_id = $this->topic_map[ $wf_thread_id ] ?? 0;
							if ( $question_id ) {
								$qa_db->update_question(
									$question_id,
									array(
										'content' => $post->content,
									)
								);
							}
						}
						continue;
					}

					$question_id = $this->topic_map[ $wf_thread_id ] ?? 0;

					if ( ! $question_id ) {
						++$skipped;
						$this->log( 'skipped', 'post', 'No question mapping for thread #' . $wf_thread_id );
						continue;
					}

					$user_id = $this->resolve_user( (int) $post->userid );

					if ( $this->dry_run ) {
						++$posts_imported;
						continue;
					}

					$answer_id = $qa_db->insert_answer(
						array(
							'question_id' => $question_id,
							'user_id'     => $user_id,
							'content'     => $post->content ?? '',
							'is_accepted' => 0,
						)
					);

					if ( $answer_id ) {
						$this->post_map[ $wf_post_id ]         = (int) $answer_id;
						$this->id_map[ 'post_' . $wf_post_id ] = (int) $answer_id;
						++$posts_imported;
						$this->log( 'imported', 'post', 'Post #' . $wf_post_id . ' → answer #' . $answer_id );
					} else {
						++$errors;
						$this->log( 'error', 'post', 'Failed to insert post #' . $wf_post_id );
					}
				}

				$offset += $batch;
			} while ( true );

			$this->log(
				'info',
				'posts',
				sprintf(
				/* translators: %d: number of posts */
					__( 'Posts migrated: %d', 'zeko-qa' ),
					$posts_imported
				)
			);
		}

		$imported += $posts_imported;

		// ── 4. Likes/Votes → QA Votes ──────────────────────────────────.
		$votes_imported = 0;
		if ( $this->options['migrate_votes'] ) {
			$this->log( 'info', 'votes', __( 'Starting vote migration…', 'zeko-qa' ) );

			$qa_db       = \Zeko_QA_DB::get_instance();
			$likes_table = $prefix . 'wpwpforo_likes';
			$has_likes   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $likes_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			if ( $has_likes ) {
				$offset = 0;
				$batch  = 50;

				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
				do {
					$likes = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$wpdb->prepare(
							"SELECT id, userid, postid, reaction, value FROM {$likes_table}
							ORDER BY id ASC
							LIMIT %d OFFSET %d",
							$batch,
							$offset
						)
					);
					// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

					if ( empty( $likes ) ) {
						break;
					}

					foreach ( $likes as $like ) {
						$user_id = $this->resolve_user( (int) $like->userid );

						if ( ! $user_id ) {
							++$skipped;
							continue;
						}

						$postid = (int) $like->postid;

						// Determine if this vote is for a question or answer.
						$item_type = 'question';
						$item_id   = $this->topic_map[ $postid ] ?? 0;

						if ( ! $item_id ) {
							$item_type = 'answer';
							$item_id   = $this->post_map[ $postid ] ?? 0;
						}

						if ( ! $item_id ) {
							++$skipped;
							continue;
						}

						$vote_type  = (int) $like->reaction > 0 ? 'up' : 'down';
						$vote_value = absint( $like->value ?? 1 );

						if ( $this->dry_run ) {
							++$votes_imported;
							continue;
						}

						$vote_id = $qa_db->insert_vote(
							array(
								'user_id'   => $user_id,
								'item_id'   => $item_id,
								'item_type' => $item_type,
								'vote_type' => $vote_type,
							)
						);

						if ( $vote_id ) {
							++$votes_imported;
							$this->log( 'imported', 'vote', 'Like #' . $like->id . ' → vote #' . $vote_id );
						} else {
							++$errors;
						}
					}

					$offset += $batch;
				} while ( true );
			}

			$this->log(
				'info',
				'votes',
				sprintf(
				/* translators: %d: number of votes */
					__( 'Votes migrated: %d', 'zeko-qa' ),
					$votes_imported
				)
			);
		}

		// ── 5. Profiles → User Meta ────────────────────────────────────.
		$profiles_imported = 0;
		if ( $this->options['migrate_profiles'] ) {
			$this->log( 'info', 'profiles', __( 'Starting profile migration…', 'zeko-qa' ) );

			$profiles_table = $this->table( 'wpwpforo_profiles' );
			$has_profiles   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $profiles_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			if ( $has_profiles ) {
				$offset = 0;
				$batch  = 50;

				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
				do {
					$profiles = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$wpdb->prepare(
							"SELECT userid, username, avatar, title, signature, location,
									facebook, twitter, google, instagram, linkedin, youtube, website
							FROM {$profiles_table}
							ORDER BY userid ASC
							LIMIT %d OFFSET %d",
							$batch,
							$offset
						)
					);
					// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

					if ( empty( $profiles ) ) {
						break;
					}

					foreach ( $profiles as $profile ) {
						$wp_user_id = absint( $profile->userid );

						if ( ! $wp_user_id || ! get_userdata( $wp_user_id ) ) {
							++$skipped;
							continue;
						}

						if ( $this->dry_run ) {
							++$profiles_imported;
							continue;
						}

						// Signature.
						if ( ! empty( $profile->signature ) ) {
							update_user_meta( $wp_user_id, 'zeko_qa_signature', $profile->signature );
						}

						// Location (only if not already set).
						if ( ! empty( $profile->location ) ) {
							$existing = get_user_meta( $wp_user_id, 'zeko_location', true );
							if ( empty( $existing ) ) {
								update_user_meta( $wp_user_id, 'zeko_location', $profile->location );
							}
						}

						// Social networks.
						$social_map = array(
							'facebook'  => 'zeko_social_facebook',
							'twitter'   => 'zeko_social_twitter',
							'google'    => 'zeko_social_google',
							'instagram' => 'zeko_social_instagram',
							'linkedin'  => 'zeko_social_linkedin',
							'youtube'   => 'zeko_social_youtube',
							'website'   => 'zeko_social_website',
						);

						foreach ( $social_map as $field => $meta_key ) {
							if ( ! empty( $profile->$field ) ) {
								update_user_meta( $wp_user_id, $meta_key, esc_url_raw( $profile->$field ) );
							}
						}

						++$profiles_imported;
						$this->log( 'imported', 'profile', 'User #' . $wp_user_id . ' profile updated' );
					}

					$offset += $batch;
				} while ( true );
			}

			$this->log(
				'info',
				'profiles',
				sprintf(
				/* translators: %d: number of profiles */
					__( 'Profiles migrated: %d', 'zeko-qa' ),
					$profiles_imported
				)
			);
		}

		// ── Delete source data ─────────────────────────────────────────.
		if ( $this->options['delete_source'] && ! $this->dry_run ) {
			$this->log( 'info', 'cleanup', __( 'Deleting source tables…', 'zeko-qa' ) );

			$drop_tables = array(
				$prefix . 'wpwpforo_posts',
				$prefix . 'wpwpforo_topics',
				$prefix . 'wpwpforo_profiles',
				$prefix . 'wpwpforo_likes',
				$prefix . 'wpwpforo_postmeta',
				$prefix . 'wpforo_forums',
			);

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			foreach ( $drop_tables as $table_name ) {
				$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				if ( $exists ) {
					$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$this->log( 'info', 'cleanup', 'Dropped: ' . $table_name );
					// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
				}
			}
		}

		/**
		 * Action: After wpForo migration completes.
		 *
		 * @param array $imported_stats  Stats array with counts.
		 * @param array $id_map          Source → Zeko ID mapping.
		 * @param array $options         Migration options used.
		 */
		do_action(
			'zbp_wpforo_migration_complete',
			array(
				'imported' => $imported,
				'skipped'  => $skipped,
				'errors'   => $errors,
			),
			$this->id_map,
			$this->options
		);

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}
}
