<?php
/**
 * DW Question & Answer → Zeko QA migrator.
 *
 * Imports questions, answers, categories, tags, and comments from
 * DW Question & Answer into the zeko-qa custom-table ecosystem.
 *
 * @package Zeko_QA
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/** Class Zeko_Migrate_DWQA. */
class Zeko_Migrate_DWQA extends Zeko_Migrator_Base {

	private const BATCH = 50;

	/**
	 * Qa db.
	 *
	 * @var ?\Zeko_QA_DB Qa db.
	 */
	private ?\Zeko_QA_DB $qa_db = null;

	/**
	 * Category map.
	 *
	 * @var array Category map.
	 */
	private array $category_map = array();

	/**
	 * Tag map.
	 *
	 * @var array Tag map.
	 */
	private array $tag_map = array();

	/**
	 * Question map.
	 *
	 * @var array Question map.
	 */
	private array $question_map = array();

	/**
	 * Answer map.
	 *
	 * @var array Answer map.
	 */
	private array $answer_map = array();

	// ─── Detection ──────────────────────────────────────────────────.

	/**
	 * Available.
	 */
	public function is_available(): bool {
		return post_type_exists( 'dwqa-question' );
	}

	/**
	 * Label.
	 */
	public function get_label(): string {
		return __( 'DW Question & Answer', 'zeko-qa' );
	}

	// ─── Options ────────────────────────────────────────────────────.

	/**
	 * Defaults.
	 */
	protected function get_defaults(): array {
		return array(
			'migrate_categories' => true,
			'migrate_tags'       => true,
			'migrate_questions'  => true,
			'migrate_answers'    => true,
			'migrate_comments'   => true,
			'delete_source'      => false,
		);
	}

	// ─── QA DB singleton ────────────────────────────────────────────.

	/**
	 * Qa.
	 */
	private function qa(): \Zeko_QA_DB {
		if ( null === $this->qa_db ) {
			$this->qa_db = new \Zeko_QA_DB();
		}
		return $this->qa_db;
	}

	// ─── Item Counts ────────────────────────────────────────────────.

	/**
	 * Item counts.
	 */
	public function get_item_counts(): array {
		$questions_count  = 0;
		$answers_count    = 0;
		$categories_count = 0;
		$tags_count       = 0;

		if ( post_type_exists( 'dwqa-question' ) ) {
			$questions_count = (int) wp_count_posts( 'dwqa-question' )->publish ?? 0;
		}
		if ( post_type_exists( 'dwqa-answer' ) ) {
			$count         = wp_count_posts( 'dwqa-answer' );
			$answers_count = (int) ( $count->publish ?? 0 ) + (int) ( $count->draft ?? 0 ) + (int) ( $count->pending ?? 0 );
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'dwqa-question_category',
				'hide_empty' => false,
			)
		);
		if ( ! is_wp_error( $categories ) ) {
			$categories_count = count( $categories );
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'dwqa-question_tag',
				'hide_empty' => false,
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			$tags_count = count( $terms );
		}

		return array(
			'questions'  => $questions_count,
			'answers'    => $answers_count,
			'categories' => $categories_count,
			'tags'       => $tags_count,
		);
	}

	// ─── Preview ────────────────────────────────────────────────────.

	/**
	 * Preview.
	 *
	 * @param int $limit Limit.
	 */
	public function preview( int $limit = 20 ): array {
		$this->dry_run = true;

		$counts = $this->get_item_counts();

		$questions_sample = array();
		$posts            = get_posts(
			array(
				'post_type'      => 'dwqa-question',
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $posts as $post ) {
			$views = (int) get_post_meta( $post->ID, '_dwqa_views', true );
			$terms = wp_get_post_terms( $post->ID, 'dwqa-question_category', array( 'fields' => 'names' ) );
			$cat   = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0] : '—';

			$questions_sample[] = array(
				'title'    => $post->post_title,
				'category' => $cat,
				'views'    => $views,
				'status'   => 'publish' === $post->post_status ? 'open' : 'closed',
			);
		}

		return array(
			'items'      => $questions_sample,
			'questions'  => $counts['questions'],
			'answers'    => $counts['answers'],
			'categories' => $counts['categories'],
			'tags'       => $counts['tags'],
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
		$this->category_map = array();
		$this->tag_map      = array();
		$this->question_map = array();
		$this->answer_map   = array();

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		if ( ! $this->is_available() ) {
			$this->log( 'error', 'source', 'DW Question & Answer not detected.' );
			return array(
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => 1,
			);
		}

		$this->set_options( $this->options );

		// 1. Categories → Topics.
		if ( $this->options['migrate_categories'] ) {
			$this->migrate_categories();
		}

		// 2. Tags → QA Tags.
		if ( $this->options['migrate_tags'] ) {
			$this->migrate_tags();
		}

		// 3. Questions.
		if ( $this->options['migrate_questions'] ) {
			$result    = $this->migrate_questions();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		// 4. Answers.
		if ( $this->options['migrate_answers'] ) {
			$result    = $this->migrate_answers();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		// 5. Question Tags.
		if ( $this->options['migrate_tags'] && $this->options['migrate_questions'] ) {
			$this->migrate_question_tags();
		}

		// 6. Comments.
		if ( $this->options['migrate_comments'] ) {
			$result    = $this->migrate_comments();
			$imported += $result['imported'];
			$skipped  += $result['skipped'];
			$errors   += $result['errors'];
		}

		if ( $this->options['delete_source'] ) {
			$this->delete_source_posts();
		}

		$this->log(
			'info',
			'complete',
			sprintf(
				'Migration finished: %d imported, %d skipped, %d errors.',
				$imported,
				$skipped,
				$errors
			)
		);

		/**
		 * Action: After DW Q&A migration completes.
		 *
		 * @param string $source   Source plugin key.
		 * @param int    $imported Items imported.
		 * @param int    $skipped  Items skipped.
		 * @param array  $options  Options used.
		 */
		do_action( 'zbp_migration_complete', 'dwqa', $imported, $skipped, $this->options );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	// ─── Categories → Topics ────────────────────────────────────────.

	/**
	 * Migrate categories.
	 */
	private function migrate_categories(): void {
		$terms = get_terms(
			array(
				'taxonomy'   => 'dwqa-question_category',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			$exists = $this->qa()->get_topic_by_slug( $term->slug );
			if ( $exists ) {
				$this->category_map[ $term->term_id ] = (int) $exists->id;
				$this->log( 'skipped', $term->name, 'Topic already exists.' );
				continue;
			}

			if ( $this->dry_run ) {
				$this->log( 'dry_run', $term->name, 'Would create topic.' );
				$this->category_map[ $term->term_id ] = 0;
				continue;
			}

			$topic_id = $this->qa()->insert_topic(
				array(
					'name'        => $term->name,
					'slug'        => $term->slug,
					'description' => $term->description ?: '',
					'user_id'     => 0,
				)
			);

			if ( $topic_id ) {
				$this->category_map[ $term->term_id ]      = $topic_id;
				$this->id_map[ 'topic_' . $term->term_id ] = $topic_id;
				$this->log( 'imported', $term->name, "Topic created (ID: {$topic_id})." );
			} else {
				$this->log( 'error', $term->name, 'Failed to create topic.' );
			}
		}
	}

	// ─── Tags ───────────────────────────────────────────────────────.

	/**
	 * Migrate tags.
	 */
	private function migrate_tags(): void {
		$terms = get_terms(
			array(
				'taxonomy'   => 'dwqa-question_tag',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			$exists = $this->qa()->get_or_create_tag( $term->name );
			if ( $exists ) {
				$this->tag_map[ $term->term_id ] = (int) $exists;
				$this->log( 'skipped', $term->name, 'Tag already exists.' );
				continue;
			}

			if ( $this->dry_run ) {
				$this->log( 'dry_run', $term->name, 'Would create tag.' );
				$this->tag_map[ $term->term_id ] = 0;
				continue;
			}

			$tag_id = $this->qa()->insert_tag(
				array(
					'name'        => $term->name,
					'slug'        => $term->slug,
					'description' => '',
				)
			);

			if ( $tag_id ) {
				$this->tag_map[ $term->term_id ]         = $tag_id;
				$this->id_map[ 'tag_' . $term->term_id ] = $tag_id;
				$this->log( 'imported', $term->name, "Tag created (ID: {$tag_id})." );
			} else {
				$this->log( 'error', $term->name, 'Failed to create tag.' );
			}
		}
	}

	// ─── Questions ──────────────────────────────────────────────────.

	/**
	 * Migrate questions.
	 */
	private function migrate_questions(): array {
		$imported = 0;
		$skipped  = 0;
		$errors   = 0;
		$offset   = 0;

		do {
			$posts = get_posts(
				array(
					'post_type'      => 'dwqa-question',
					'posts_per_page' => self::BATCH,
					'offset'         => $offset,
					'post_status'    => 'publish',
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);

			if ( empty( $posts ) ) {
				break;
			}

			foreach ( $posts as $post ) {
				$result = $this->import_single_question( $post );
				switch ( $result ) {
					case 'imported':
						++$imported;
						break;
					case 'skipped':
						++$skipped;
						break;
					case 'error':
						++$errors;
						break;
				}
			}

			$offset += self::BATCH;

			if ( $this->dry_run ) {
				break;
			}
			$posts_count = count( $posts );
		} while ( self::BATCH === $posts_count );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/**
	 * Import single question.
	 *
	 * @param \WP_Post $post Post.
	 */
	private function import_single_question( \WP_Post $post ): string {
		$pid = $post->ID;

		// Duplicate check by title.
		if ( $this->question_exists( $post->post_title ) ) {
			$this->log( 'skipped', $post->post_title, 'Question already exists.' );
			if ( ! empty( $this->options['delete_source'] ) ) {
				wp_delete_post( $pid, true );
			}
			return 'skipped';
		}

		$views    = (int) get_post_meta( $pid, '_dwqa_views', true );
		$featured = (int) get_post_meta( $pid, '_dwqa_featured', true );
		$status   = ( 'publish' === $post->post_status ) ? 'open' : 'closed';

		// Ensure slug uniqueness.
		$slug = $this->unique_slug( $post->post_name, 'zeko_questions' );

		if ( $this->dry_run ) {
			$this->log( 'dry_run', $post->post_title, 'Would create question.' );
			return 'imported';
		}

		$question_id = $this->qa()->insert_question(
			array(
				'user_id' => (int) $post->post_author,
				'title'   => $post->post_title,
				'slug'    => $slug,
				'content' => $post->post_content,
				'views'   => $views,
				'status'  => $status,
			)
		);

		if ( ! $question_id ) {
			$this->log( 'error', $post->post_title, 'Failed to create question.' );
			return 'error';
		}

		// Set is_featured after insert (not in insert_question defaults).
		if ( $featured ) {
			$this->qa()->update_question( $question_id, array( 'is_featured' => 1 ) );
		}

		$this->question_map[ $pid ]         = $question_id;
		$this->id_map[ 'question_' . $pid ] = $question_id;

		$this->log( 'imported', $post->post_title, "Question created (zeko ID: {$question_id})." );

		// Link to categories/topics.
		$this->link_question_topics( $pid, $question_id );

		if ( ! empty( $this->options['delete_source'] ) ) {
			wp_delete_post( $pid, true );
		}

		/** This action is documented in class-zbp-migrator-base.php */
		do_action( 'zbp_migration_item_complete', 'dwqa', $pid, $question_id );

		return 'imported';
	}

	/**
	 * Link question topics.
	 *
	 * @param int $source_post_id Source post id.
	 * @param int $question_id Question id.
	 */
	private function link_question_topics( int $source_post_id, int $question_id ): void {
		global $wpdb;

		$relationships = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_taxonomy_id FROM {$wpdb->term_relationships}
			WHERE object_id = %d",
				$source_post_id
			)
		);

		if ( empty( $relationships ) ) {
			return;
		}

		$taxonomy = get_taxonomy( 'dwqa-question_category' );
		if ( ! $taxonomy ) {
			return;
		}

		foreach ( $relationships as $rel ) {
			$term = get_term( $rel->term_taxonomy_id, 'dwqa-question_category' );
			if ( is_wp_error( $term ) || ! isset( $this->category_map[ $term->term_id ] ) ) {
				continue;
			}

			$topic_id = $this->category_map[ $term->term_id ];
			if ( ! $topic_id ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$wpdb->prefix . 'zeko_qa_question_topics',
				array(
					'question_id' => $question_id,
					'topic_id'    => $topic_id,
				),
				array( '%d', '%d' )
			);
		}
	}

	// ─── Answers ────────────────────────────────────────────────────.

	/**
	 * Migrate answers.
	 */
	private function migrate_answers(): array {
		$imported = 0;
		$skipped  = 0;
		$errors   = 0;
		$offset   = 0;

		if ( ! post_type_exists( 'dwqa-answer' ) ) {
			return array(
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => 0,
			);
		}

		do {
			$posts = get_posts(
				array(
					'post_type'      => 'dwqa-answer',
					'posts_per_page' => self::BATCH,
					'offset'         => $offset,
					'post_status'    => array( 'publish', 'draft', 'pending' ),
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);

			if ( empty( $posts ) ) {
				break;
			}

			foreach ( $posts as $post ) {
				$result = $this->import_single_answer( $post );
				switch ( $result ) {
					case 'imported':
						++$imported;
						break;
					case 'skipped':
						++$skipped;
						break;
					case 'error':
						++$errors;
						break;
				}
			}

			$offset += self::BATCH;

			if ( $this->dry_run ) {
				break;
			}
			$posts_count = count( $posts );
		} while ( self::BATCH === $posts_count );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/**
	 * Import single answer.
	 *
	 * @param \WP_Post $post Post.
	 */
	private function import_single_answer( \WP_Post $post ): string {
		$pid = $post->ID;

		// Resolve the zeko question ID from post_parent.
		$parent_id   = (int) $post->post_parent;
		$question_id = $this->question_map[ $parent_id ] ?? 0;

		if ( ! $question_id ) {
			$this->log( 'skipped', "Answer #{$pid}", 'No matching zeko question found for parent.' );
			return 'skipped';
		}

		// Check if best answer.
		$best_answer_id = (int) get_post_meta( $parent_id, '_dwqa_best_answer', true );
		$is_accepted    = ( $best_answer_id === $pid ) ? 1 : 0;

		if ( $this->dry_run ) {
			$this->log( 'dry_run', "Answer #{$pid}", 'Would create answer.' );
			return 'imported';
		}

		$answer_id = $this->qa()->insert_answer(
			array(
				'question_id' => $question_id,
				'user_id'     => (int) $post->post_author,
				'content'     => $post->post_content,
				'is_accepted' => $is_accepted,
			)
		);

		if ( ! $answer_id ) {
			$this->log( 'error', "Answer #{$pid}", 'Failed to create answer.' );
			return 'error';
		}

		$this->answer_map[ $pid ]         = $answer_id;
		$this->id_map[ 'answer_' . $pid ] = $answer_id;

		$this->log( 'imported', "Answer #{$pid}", "Answer created (zeko ID: {$answer_id})." );

		if ( ! empty( $this->options['delete_source'] ) ) {
			wp_delete_post( $pid, true );
		}

		/** This action is documented in class-zbp-migrator-base.php */
		do_action( 'zbp_migration_item_complete', 'dwqa', $pid, $answer_id );

		return 'imported';
	}

	// ─── Question Tags ──────────────────────────────────────────────.

	/**
	 * Migrate question tags.
	 */
	private function migrate_question_tags(): void {
		global $wpdb;

		foreach ( $this->question_map as $source_post_id => $question_id ) {
			$term_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT tr.term_taxonomy_id
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE tr.object_id = %d AND tt.taxonomy = %s",
					$source_post_id,
					'dwqa-question_tag'
				)
			);

			if ( empty( $term_ids ) ) {
				continue;
			}

			$tag_ids = array();
			foreach ( $term_ids as $term_id ) {
				if ( isset( $this->tag_map[ $term_id ] ) && $this->tag_map[ $term_id ] ) {
					$tag_ids[] = $this->tag_map[ $term_id ];
				}
			}

			if ( ! empty( $tag_ids ) && ! $this->dry_run ) {
				$this->qa()->attach_tags_to_question( $question_id, $tag_ids );
			}
		}
	}

	// ─── Comments ───────────────────────────────────────────────────.

	/**
	 * Migrate comments.
	 */
	private function migrate_comments(): array {
		$imported = 0;
		$skipped  = 0;
		$errors   = 0;
		$offset   = 0;

		// Gather all source post IDs that have been mapped.
		$all_source_ids = array_merge(
			array_keys( $this->question_map ),
			array_keys( $this->answer_map )
		);

		if ( empty( $all_source_ids ) ) {
			return array(
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => 0,
			);
		}

		$placeholders = implode( ',', array_fill( 0, count( $all_source_ids ), '%d' ) );

		do {
			global $wpdb;

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$comments = $wpdb->get_results(
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					"SELECT * FROM {$wpdb->comments}
				WHERE comment_post_ID IN ({$placeholders})
				AND comment_approved = '1'
				ORDER BY comment_ID ASC
				LIMIT %d OFFSET %d",
					array_merge( $all_source_ids, array( self::BATCH, $offset ) )
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

			if ( empty( $comments ) ) {
				break;
			}

			foreach ( $comments as $comment ) {
				$result = $this->import_single_comment( $comment );
				switch ( $result ) {
					case 'imported':
						++$imported;
						break;
					case 'skipped':
						++$skipped;
						break;
					case 'error':
						++$errors;
						break;
				}
			}

			$offset += self::BATCH;

			if ( $this->dry_run ) {
				break;
			}
			$comments_count = count( $comments );
		} while ( self::BATCH === $comments_count );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/**
	 * Import single comment.
	 *
	 * @param object $comment Comment.
	 */
	private function import_single_comment( object $comment ): string {
		$post_id = (int) $comment->comment_post_ID;

		// Determine item type and mapped ID.
		if ( isset( $this->question_map[ $post_id ] ) ) {
			$item_id   = $this->question_map[ $post_id ];
			$item_type = 'question';
		} elseif ( isset( $this->answer_map[ $post_id ] ) ) {
			$item_id   = $this->answer_map[ $post_id ];
			$item_type = 'answer';
		} else {
			$this->log( 'skipped', "Comment #{$comment->comment_ID}", 'No matching zeko item.' );
			return 'skipped';
		}

		if ( $this->dry_run ) {
			$this->log( 'dry_run', "Comment #{$comment->comment_ID}", 'Would create comment.' );
			return 'imported';
		}

		$comment_id = $this->qa()->insert_comment(
			array(
				'user_id'   => (int) $comment->user_id,
				'item_id'   => $item_id,
				'item_type' => $item_type,
				'content'   => $comment->comment_content,
			)
		);

		if ( ! $comment_id ) {
			$this->log( 'error', "Comment #{$comment->comment_ID}", 'Failed to create comment.' );
			return 'error';
		}

		$this->id_map[ 'comment_' . $comment->comment_ID ] = $comment_id;

		$this->log( 'imported', "Comment #{$comment->comment_ID}", "Comment created (zeko ID: {$comment_id})." );

		return 'imported';
	}

	// ─── Helpers ────────────────────────────────────────────────────.

	/**
	 * Question exists.
	 *
	 * @param string $title Title.
	 */
	private function question_exists( string $title ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'zeko_questions';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE title = %s LIMIT 1",
				$title
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		return $count > 0;
	}

	/**
	 * Unique slug.
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
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE slug = %s",
					$slug
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

			if ( ! $exists ) {
				return $slug;
			}

			++$counter;
			$slug = $original . '-' . $counter;
		}
	}

	/**
	 * Delete source posts.
	 */
	private function delete_source_posts(): void {
		$types = array( 'dwqa-question', 'dwqa-answer' );

		foreach ( $types as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				)
			);

			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}
	}
}
