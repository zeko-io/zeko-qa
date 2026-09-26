<?php
/**
 * Migrator: AnsPress → Zeko QA.
 *
 * @package Zeko_QA
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/** Class Zeko_Migrate_AnsPress. */
class Zeko_Migrate_AnsPress extends Zeko_Migrator_Base {

	private const BATCH_SIZE = 50;

	/**
	 * Available.
	 */
	public function is_available(): bool {
		global $wpdb;

		return post_type_exists( 'question' )
			&& $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'ap_qameta' ) ) !== null;
	}

	/**
	 * Label.
	 */
	public function get_label(): string {
		return 'AnsPress';
	}

	/**
	 * Defaults.
	 */
	protected function get_defaults(): array {
		return array(
			'migrate_categories' => true,
			'migrate_tags'       => true,
			'migrate_questions'  => true,
			'migrate_answers'    => true,
			'migrate_votes'      => true,
			'delete_source'      => false,
		);
	}

	/**
	 * Item counts.
	 */
	public function get_item_counts(): array {
		global $wpdb;

		$qameta_table = $wpdb->prefix . 'ap_qameta';

		$questions  = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'question' AND post_status NOT IN ('trash','auto-draft')"
		);
		$answers    = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'answer' AND post_status NOT IN ('trash','auto-draft')"
		);
		$categories = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.taxonomy = 'question_category'"
		);
		$tags       = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.taxonomy = 'question_tag'"
		);
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$votes = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$qameta_table}" // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		return array(
			'questions'  => $questions,
			'answers'    => $answers,
			'categories' => $categories,
			'tags'       => $tags,
			'votes'      => $votes,
		);
	}

	/**
	 * Preview.
	 *
	 * @param int $limit Limit.
	 */
	public function preview( int $limit = 20 ): array {
		global $wpdb;

		$qameta_table = $wpdb->prefix . 'ap_qameta';

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$questions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, p.post_author, pm.answers, pm.votes_up, pm.votes_down
			FROM {$wpdb->posts} p
			LEFT JOIN {$qameta_table} pm ON p.ID = pm.post_id
			WHERE p.post_type = 'question' AND p.post_status NOT IN ('trash','auto-draft')
			ORDER BY p.ID DESC
			LIMIT %d",
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		$categories = $wpdb->get_col(
			"SELECT t.name FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.taxonomy = 'question_category' ORDER BY t.name ASC LIMIT 10"
		);
		$tags       = $wpdb->get_col(
			"SELECT t.name FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.taxonomy = 'question_tag' ORDER BY t.name ASC LIMIT 10"
		);

		$sample = array();
		foreach ( $questions as $q ) {
			$sample[] = array(
				'title'     => $q->post_title,
				'author'    => (int) $q->post_author,
				'answers'   => (int) $q->answers,
				'upvotes'   => (int) $q->votes_up,
				'downvotes' => (int) $q->votes_down,
			);
		}

		$counts = $this->get_item_counts();

		return array(
			'questions'         => $counts['questions'],
			'answers'           => $counts['answers'],
			'categories'        => $counts['categories'],
			'tags'              => $counts['tags'],
			'votes'             => $counts['votes'],
			'sample'            => $sample,
			'sample_categories' => $categories,
			'sample_tags'       => $tags,
		);
	}

	/**
	 * Run.
	 */
	public function run(): array {
		global $wpdb;

		$this->set_options( $this->options );
		$this->id_map = array();
		$this->log    = array();

		$qameta_table = $wpdb->prefix . 'ap_qameta';

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		$counts = array(
			'categories' => 0,
			'tags'       => 0,
			'questions'  => 0,
			'answers'    => 0,
			'votes'      => 0,
		);

		if ( $this->options['migrate_categories'] ) {
			$r                    = $this->migrate_categories();
			$counts['categories'] = $r;
		}

		if ( $this->options['migrate_tags'] ) {
			$r              = $this->migrate_tags();
			$counts['tags'] = $r;
		}

		if ( $this->options['migrate_questions'] ) {
			$r                   = $this->migrate_questions();
			$imported           += $r['imported'];
			$skipped            += $r['skipped'];
			$errors             += $r['errors'];
			$counts['questions'] = $r['imported'];
		}

		if ( $this->options['migrate_answers'] ) {
			$r                 = $this->migrate_answers();
			$imported         += $r['imported'];
			$skipped          += $r['skipped'];
			$errors           += $r['errors'];
			$counts['answers'] = $r['imported'];
		}

		if ( $this->options['migrate_votes'] ) {
			$r               = $this->migrate_votes();
			$counts['votes'] = $r;
		}

		if ( $this->options['migrate_questions'] ) {
			$this->migrate_labels();
		}

		/**
		 * Action: After AnsPress migration completes.
		 *
		 * @param array $counts Items migrated per type.
		 * @param array $options Migration options.
		 */
		do_action( 'zbp_anspress_migration_complete', $counts, $this->options );

		$this->log(
			'info',
			'migration',
			sprintf(
				'Complete: %d imported, %d skipped, %d errors.',
				$imported,
				$skipped,
				$errors
			)
		);

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/**
	 * Resolve a WP user ID from a source post_author.
	 *
	 * @param int $author_id Author id.
	 */
	private function resolve_user( int $author_id ): int {
		if ( $author_id <= 0 ) {
			return 0;
		}

		$user = get_userdata( $author_id );
		return $user ? $user->ID : 0;
	}

	// -------------------------------------------------------------------------.
	// 1. Categories → Topics.
	// -------------------------------------------------------------------------.

	/**
	 * Migrate categories.
	 */
	private function migrate_categories(): int {
		global $wpdb;

		$terms = $wpdb->get_results(
			"SELECT t.term_id, t.name, t.slug, tt.description
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			WHERE tt.taxonomy = 'question_category'
			ORDER BY t.term_id ASC"
		);

		if ( empty( $terms ) ) {
			$this->log( 'info', 'categories', 'No categories found.' );
			return 0;
		}

		$db    = \Zeko_QA_DB::get_instance();
		$count = 0;

		foreach ( $terms as $term ) {
			$existing = $db->get_topic_by_slug( $term->slug );
			if ( $existing ) {
				$this->id_map['category'][ $term->term_id ] = $existing->id;
				$this->log( 'skipped', 'category', $term->name . ' — already exists.' );
				continue;
			}

			if ( $this->dry_run ) {
				++$count;
				continue;
			}

			$topic_id = $db->insert_topic(
				array(
					'name'        => $term->name,
					'slug'        => $term->slug,
					'description' => $term->description ?: '',
					'user_id'     => 0,
				)
			);

			if ( $topic_id ) {
				$this->id_map['category'][ $term->term_id ] = $topic_id;
				++$count;
				$this->log( 'imported', 'category', $term->name . ' → topic #' . $topic_id );
			} else {
				$this->log( 'error', 'category', 'Failed to insert: ' . $term->name );
			}
		}

		return $count;
	}

	// -------------------------------------------------------------------------.
	// 2. Tags → Tags.
	// -------------------------------------------------------------------------.

	/**
	 * Migrate tags.
	 */
	private function migrate_tags(): int {
		global $wpdb;

		$terms = $wpdb->get_results(
			"SELECT t.term_id, t.name, t.slug, tt.description
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			WHERE tt.taxonomy = 'question_tag'
			ORDER BY t.term_id ASC"
		);

		if ( empty( $terms ) ) {
			$this->log( 'info', 'tags', 'No tags found.' );
			return 0;
		}

		$db    = \Zeko_QA_DB::get_instance();
		$count = 0;

		foreach ( $terms as $term ) {
			$existing = $db->get_tag_by_slug( $term->slug );
			if ( $existing ) {
				$this->id_map['tag'][ $term->term_id ] = $existing->id;
				$this->log( 'skipped', 'tag', $term->name . ' — already exists.' );
				continue;
			}

			if ( $this->dry_run ) {
				++$count;
				continue;
			}

			$tag_id = $db->insert_tag(
				array(
					'name'        => $term->name,
					'slug'        => $term->slug,
					'description' => $term->description ?: '',
				)
			);

			if ( $tag_id ) {
				$this->id_map['tag'][ $term->term_id ] = $tag_id;
				++$count;
				$this->log( 'imported', 'tag', $term->name . ' → tag #' . $tag_id );
			} else {
				$this->log( 'error', 'tag', 'Failed to insert: ' . $term->name );
			}
		}

		return $count;
	}

	// -------------------------------------------------------------------------.
	// 3. Questions.
	// -------------------------------------------------------------------------.

	/**
	 * Migrate questions.
	 */
	private function migrate_questions(): array {
		global $wpdb;

		$qameta_table   = $wpdb->prefix . 'ap_qameta';
		$tax_table      = $wpdb->term_relationships;
		$tax_term_table = $wpdb->term_taxonomy;
		$terms_table    = $wpdb->terms;

		$offset   = 0;
		$imported = 0;
		$skipped  = 0;
		$errors   = 0;
		$db       = \Zeko_QA_DB::get_instance();

		$question_topic_table = $wpdb->prefix . 'zeko_qa_question_topics';
		$question_tag_table   = $wpdb->prefix . 'zeko_qa_question_tags';

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		do {
			$posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT p.ID, p.post_author, p.post_title, p.post_name, p.post_content, p.post_status, p.post_date,
					pm.selected_id, pm.votes_up, pm.votes_down, pm.views, pm.closed, pm.featured
				FROM {$wpdb->posts} p
				LEFT JOIN {$qameta_table} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'question' AND p.post_status NOT IN ('trash','auto-draft')
				ORDER BY p.ID ASC
				LIMIT %d OFFSET %d",
					self::BATCH_SIZE,
					$offset
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

			if ( empty( $posts ) ) {
				break;
			}

			foreach ( $posts as $post ) {
				$slug = sanitize_title( $post->post_name );
				if ( empty( $slug ) ) {
					$slug = sanitize_title( $post->post_title );
				}

				$duplicate = $wpdb->get_var(
					$wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						"SELECT id FROM {$wpdb->prefix}zeko_questions WHERE title = %s LIMIT 1",
						$post->post_title
					)
				);

				if ( $duplicate ) {
					$this->id_map['question'][ $post->ID ] = (int) $duplicate;
					++$skipped;
					$this->log( 'skipped', 'question', $post->post_title . ' — duplicate.' );
					continue;
				}

				if ( $this->dry_run ) {
					++$imported;
					continue;
				}

				$status = 'open';
				if ( 'publish' !== $post->post_status ) {
					$status = 'closed';
				} elseif ( ! empty( $post->closed ) ) {
					$status = 'closed';
				}

				$user_id = $this->resolve_user( (int) $post->post_author );

				$question_id = $db->insert_question(
					array(
						'user_id' => $user_id,
						'title'   => $post->post_title,
						'slug'    => $slug,
						'content' => $post->post_content,
						'views'   => absint( $post->views ?? 0 ),
						'status'  => $status,
					)
				);

				if ( ! $question_id ) {
					++$errors;
					$this->log( 'error', 'question', 'Failed to insert: ' . $post->post_title );
					continue;
				}

				$this->id_map['question'][ $post->ID ] = $question_id;
				++$imported;

				$this->log( 'imported', 'question', $post->post_title . ' → question #' . $question_id );

				// Update optional fields that insert_question doesn't cover.
				$db->update_question(
					$question_id,
					array(
						'upvotes'     => absint( $post->votes_up ?? 0 ),
						'downvotes'   => absint( $post->votes_down ?? 0 ),
						'is_featured' => ! empty( $post->featured ) ? 1 : 0,
					)
				);

				// Link to topics (question_category).
				$this->link_question_topics( $post->ID, $question_id, $question_topic_table );

				// Link to tags (question_tag).
				$this->link_question_tags( $post->ID, $question_id, $question_tag_table );
			}

			$offset += self::BATCH_SIZE;

			$posts_count = count( $posts );
		} while ( self::BATCH_SIZE === $posts_count );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/**
	 * Link a question to its AnsPress category terms as zeko topics.
	 *
	 * @param int    $source_post_id Source post id.
	 * @param int    $zeko_question_id Zeko question id.
	 * @param string $question_topic_table Question topic table.
	 */
	private function link_question_topics( int $source_post_id, int $zeko_question_id, string $question_topic_table ): void {
		global $wpdb;

		if ( $this->dry_run ) {
			return;
		}

		$term_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT tr.term_taxonomy_id
			FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			WHERE tr.object_id = %d AND tt.taxonomy = 'question_category'",
				$source_post_id
			)
		);

		foreach ( $term_ids as $term_taxonomy_id ) {
			$topic_id = $this->id_map['category'][ $term_taxonomy_id ] ?? 0;
			if ( $topic_id > 0 ) {
				$wpdb->insert(
					$question_topic_table,
					array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					'question_id' => $zeko_question_id,
					'topic_id'    => $topic_id,
					)
				);
			}
		}
	}

	/**
	 * Link a question to its AnsPress tag terms.
	 *
	 * @param int    $source_post_id Source post id.
	 * @param int    $zeko_question_id Zeko question id.
	 * @param string $question_tag_table Question tag table.
	 */
	private function link_question_tags( int $source_post_id, int $zeko_question_id, string $question_tag_table ): void {
		global $wpdb;

		if ( $this->dry_run ) {
			return;
		}

		$term_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT tr.term_taxonomy_id
			FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			WHERE tr.object_id = %d AND tt.taxonomy = 'question_tag'",
				$source_post_id
			)
		);

		foreach ( $term_ids as $term_taxonomy_id ) {
			$tag_id = $this->id_map['tag'][ $term_taxonomy_id ] ?? 0;
			if ( $tag_id > 0 ) {
				$wpdb->insert(
					$question_tag_table,
					array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					'question_id' => $zeko_question_id,
					'tag_id'      => $tag_id,
					)
				);
			}
		}
	}

	// -------------------------------------------------------------------------.
	// 4. Answers.
	// -------------------------------------------------------------------------.

	/**
	 * Migrate answers.
	 */
	private function migrate_answers(): array {
		global $wpdb;

		$qameta_table = $wpdb->prefix . 'ap_qameta';

		$offset   = 0;
		$imported = 0;
		$skipped  = 0;
		$errors   = 0;
		$db       = \Zeko_QA_DB::get_instance();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		do {
			$posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT p.ID, p.post_author, p.post_content, p.post_parent, p.post_status,
					pm.votes_up, pm.votes_down, pm.selected_id
				FROM {$wpdb->posts} p
				LEFT JOIN {$qameta_table} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'answer' AND p.post_status NOT IN ('trash','auto-draft')
				ORDER BY p.ID ASC
				LIMIT %d OFFSET %d",
					self::BATCH_SIZE,
					$offset
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

			if ( empty( $posts ) ) {
				break;
			}

			foreach ( $posts as $post ) {
				$question_id = $this->id_map['question'][ $post->post_parent ] ?? 0;
				if ( $question_id <= 0 ) {
					++$skipped;
					$this->log( 'skipped', 'answer', 'Answer #' . $post->ID . ' — parent question not migrated.' );
					continue;
				}

				if ( $this->dry_run ) {
					++$imported;
					continue;
				}

				$user_id = $this->resolve_user( (int) $post->post_author );

				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
				// Determine if this answer is the accepted/selected one.
				$question_qameta = $wpdb->get_row(
					$wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						"SELECT selected_id FROM {$qameta_table} WHERE post_id = %d LIMIT 1",
						$post->post_parent
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

				$is_accepted = 0;
				if ( $question_qameta && absint( $question_qameta->selected_id ) === (int) $post->ID ) {
					$is_accepted = 1;
				}

				$answer_id = $db->insert_answer(
					array(
						'question_id' => $question_id,
						'user_id'     => $user_id,
						'content'     => $post->post_content,
						'is_accepted' => $is_accepted,
					)
				);

				if ( ! $answer_id ) {
					++$errors;
					$this->log( 'error', 'answer', 'Failed to insert answer #' . $post->ID );
					continue;
				}

				$this->id_map['answer'][ $post->ID ] = $answer_id;
				++$imported;

				$this->log( 'imported', 'answer', 'Answer #' . $post->ID . ' → answer #' . $answer_id );

				// Update vote counts on the answer row.
				$db->update_answer(
					$answer_id,
					array(
						'upvotes'   => absint( $post->votes_up ?? 0 ),
						'downvotes' => absint( $post->votes_down ?? 0 ),
					)
				);
			}

			$offset += self::BATCH_SIZE;

			$posts_count = count( $posts );
		} while ( self::BATCH_SIZE === $posts_count );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	// -------------------------------------------------------------------------.
	// 5. Votes.
	// -------------------------------------------------------------------------.

	/**
	 * Migrate votes.
	 */
	private function migrate_votes(): int {
		global $wpdb;

		$votes_table = $wpdb->prefix . 'ap_votes';

		// Check if the table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $votes_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( ! $table_exists ) {
			$this->log( 'info', 'votes', 'ap_votes table not found.' );
			return 0;
		}

		$offset = 0;
		$count  = 0;
		$db     = \Zeko_QA_DB::get_instance();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		do {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT vote_id, vote_post_id, vote_user_id, vote_type, vote_value
				FROM {$votes_table}
				ORDER BY vote_id ASC
				LIMIT %d OFFSET %d",
					self::BATCH_SIZE,
					$offset
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				if ( $this->dry_run ) {
					++$count;
					continue;
				}

				$item_id   = 0;
				$item_type = 'question';

				// Check if vote_post_id maps to a migrated question.
				if ( isset( $this->id_map['question'][ $row->vote_post_id ] ) ) {
					$item_id   = $this->id_map['question'][ $row->vote_post_id ];
					$item_type = 'question';
				} elseif ( isset( $this->id_map['answer'][ $row->vote_post_id ] ) ) {
					$item_id   = $this->id_map['answer'][ $row->vote_post_id ];
					$item_type = 'answer';
				}

				if ( $item_id <= 0 ) {
					continue;
				}

				$vote_value = (int) $row->vote_value;

				$wpdb->insert(
					$db->get_table_votes(),
					array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					'user_id'    => (int) $row->vote_user_id,
					'item_id'    => $item_id,
					'item_type'  => $item_type,
					'vote_type'  => $vote_value > 0 ? 'up' : 'down',
					'created_at' => current_time( 'mysql' ),
					)
				);

				if ( $wpdb->insert_id ) {
					++$count;
				}
			}

			$offset += self::BATCH_SIZE;

			$rows_count = count( $rows );
		} while ( self::BATCH_SIZE === $rows_count );

		$this->log( 'imported', 'votes', $count . ' votes migrated.' );

		return $count;
	}

	// -------------------------------------------------------------------------.
	// 6. Labels → status adjustments.
	// -------------------------------------------------------------------------.

	/**
	 * Migrate labels.
	 */
	private function migrate_labels(): void {
		global $wpdb;

		$terms = $wpdb->get_results(
			"SELECT t.term_id, t.name, t.slug
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			WHERE tt.taxonomy = 'question_label'"
		);

		if ( empty( $terms ) ) {
			return;
		}

		$solved_term_id = 0;
		foreach ( $terms as $term ) {
			if ( 'solved' === strtolower( $term->slug ) || 'solved' === strtolower( $term->name ) ) {
				$solved_term_id = (int) $term->term_id;
				break;
			}
		}

		if ( $solved_term_id <= 0 ) {
			$this->log( 'info', 'labels', 'No solved label found.' );
			return;
		}

		// Find questions that have the solved label and their accepted answers.
		$question_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT tr.object_id
			FROM {$wpdb->term_relationships} tr
			WHERE tr.term_taxonomy_id = %d
			AND tr.object_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'question')",
				$solved_term_id
			)
		);

		if ( empty( $question_ids ) ) {
			return;
		}

		$db    = \Zeko_QA_DB::get_instance();
		$count = 0;

		foreach ( $question_ids as $source_question_id ) {
			$zeko_question_id = $this->id_map['question'][ $source_question_id ] ?? 0;
			if ( $zeko_question_id <= 0 ) {
				continue;
			}

			if ( $this->dry_run ) {
				++$count;
				continue;
			}

			// Find the accepted answer if one was already marked.
			$qameta_table = $wpdb->prefix . 'ap_qameta';
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			$selected_id = (int) $wpdb->get_var(
				$wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					"SELECT selected_id FROM {$qameta_table} WHERE post_id = %d LIMIT 1",
					$source_question_id
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

			if ( $selected_id > 0 && isset( $this->id_map['answer'][ $selected_id ] ) ) {
				$zeko_answer_id = $this->id_map['answer'][ $selected_id ];
				$db->accept_answer( $zeko_question_id, $zeko_answer_id );
				++$count;
			}
		}

		$this->log( 'imported', 'labels', $count . ' questions marked as solved.' );
	}
}
