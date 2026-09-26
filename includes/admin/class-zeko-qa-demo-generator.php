<?php
/**
 * Demo data generator for Zeko QA
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_QA_Demo_Generator. */
class Zeko_QA_Demo_Generator {

	/**
	 * Db.
	 *
	 * @var mixed Db.
	 */
	private $db;
	/**
	 * Generated user ids.
	 *
	 * @var mixed Generated user ids.
	 */
	private $generated_user_ids = array();
	/**
	 * Generated topic ids.
	 *
	 * @var mixed Generated topic ids.
	 */
	private $generated_topic_ids = array();

	/**
	 * Construct.
	 *
	 * @param mixed $db Db.
	 */
	public function __construct( $db ) {
		$this->db = $db;
	}

	/**
	 * Run.
	 */
	public function run() {
		global $wpdb;

		ignore_user_abort( true );
		set_time_limit( 300 );

		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0;' );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $this->db->get_all_tables() as $table ) {
			$wpdb->query( "TRUNCATE TABLE {$table}" );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1;' );

		$this->generate_users();
		$this->generate_topics();
		$this->generate_questions();
		$this->generate_answers();
		$this->generate_votes();

		return __( 'Demo data generated successfully.', 'zeko-qa' );
	}

	/**
	 * Generate users.
	 */
	private function generate_users() {
		$names = array(
			'Alex Johnson',
			'Maria Garcia',
			'James Smith',
			'Sarah Connor',
			'David Chen',
			'Emily Davis',
			'Michael Brown',
			'Jessica Wilson',
			'Daniel Lee',
			'Laura Martinez',
			'Chris Taylor',
			'Anna Thomas',
			'Kevin White',
			'Sophia Harris',
			'Brian Clark',
		);

		foreach ( $names as $name ) {
			$email    = strtolower( sanitize_title( $name ) ) . '@example.com';
			$existing = email_exists( $email );
			if ( $existing ) {
				update_user_meta( $existing, 'zeko_demo_user', 1 );
				$this->generated_user_ids[] = $existing;
				continue;
			}
			$username = sanitize_title( $name );
			$user_id  = wp_create_user( $username, wp_generate_password(), $email );
			if ( ! is_wp_error( $user_id ) ) {
				wp_update_user(
					array(
						'ID'           => $user_id,
						'display_name' => $name,
						'role'         => 'subscriber',
					)
				);
				update_user_meta( $user_id, 'zeko_demo_user', 1 );
				$this->generated_user_ids[] = $user_id;
			}
		}

		if ( empty( $this->generated_user_ids ) ) {
			$admin_id = get_current_user_id();
			if ( $admin_id ) {
				$this->generated_user_ids[] = $admin_id;
			}
		}
	}

	/**
	 * Generate topics.
	 */
	private function generate_topics() {
		$topics = array(
			'WordPress Development',
			'JavaScript & Frontend',
			'Database Optimization',
			'API Design',
			'Career Advice',
		);

		foreach ( $topics as $topic_name ) {
			$topic_id                    = $this->db->insert_topic(
				array(
					'name'        => $topic_name,
					'slug'        => sanitize_title( $topic_name ),
					'description' => 'Questions about ' . $topic_name,
					'user_id'     => $this->generated_user_ids[0],
				)
			);
			$this->generated_topic_ids[] = $topic_id;
		}
	}

	/**
	 * Generate questions.
	 */
	private function generate_questions() {
		$questions = array(
			'How do I optimize custom database tables in WordPress for high-traffic sites?',
			'What is the best way to handle asynchronous API calls in a WordPress plugin?',
			'Should I use Redis or Memcached for object caching in WordPress?',
			'How can I implement a reputation system similar to Stack Overflow?',
			'What are the security implications of allowing HTML in user-generated content?',
			'How do I structure a REST API for a Q&A platform?',
			'What is the most efficient way to implement full-text search in MySQL?',
			'How can I prevent SQL injection in custom WordPress queries?',
			'What are the pros and cons of using custom tables vs post meta?',
			'How do I implement real-time notifications in WordPress?',
			'What is the best approach for rate limiting API endpoints?',
			'How can I create a scalable multi-tenant WordPress installation?',
			'What design patterns work best for WordPress plugin development?',
			'How do I handle user authentication securely in a headless WordPress setup?',
			'What are the latest performance optimization techniques for WordPress 6.0+?',
		);

		if ( empty( $this->generated_user_ids ) || empty( $this->generated_topic_ids ) ) {
			return;
		}

		foreach ( $questions as $index => $title ) {
			$content     = $this->generate_question_content( $title );
			$user_id     = $this->generated_user_ids[ $index % count( $this->generated_user_ids ) ];
			$question_id = $this->db->insert_question(
				array(
					'user_id'   => $user_id,
					'title'     => $title,
					'slug'      => sanitize_title( $title ) . '-' . ( $index + 1 ),
					'content'   => $content,
					'views'     => wp_rand( 50, 5000 ),
					'upvotes'   => wp_rand( 0, 100 ),
					'downvotes' => wp_rand( 0, 20 ),
				)
			);

			$topic_id = $this->generated_topic_ids[ $index % count( $this->generated_topic_ids ) ];
			global $wpdb;
			$wpdb->insert(
				$this->db->get_table_question_topics(),
				array(
					'question_id' => $question_id,
					'topic_id'    => $topic_id,
				)
			);
		}
	}

	/**
	 * Generate answers.
	 */
	private function generate_answers() {
		global $wpdb;

		if ( empty( $this->generated_user_ids ) ) {
			return;
		}

		$questions        = $wpdb->get_col( "SELECT id FROM {$this->db->get_table_questions()} ORDER BY RAND()" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$answer_templates = array(
			'I have faced this issue before. The key is to use prepared statements and avoid direct SQL concatenation. Here is a code example that worked for me...',
			'You should definitely check out the official documentation. It explains the underlying architecture very well and provides several edge case examples.',
			'In my experience, the best approach is to combine Redis for session storage with Memcached for object caching. This gives you the best of both worlds.',
			'This is a common misconception. Actually, the overhead of custom tables is minimal compared to the benefits of proper indexing and normalization.',
			'I wrote a detailed blog post about this last year. The short version is: always sanitize, validate, and escape everything.',
			'Have you considered using the Transients API? It is built into WordPress and handles caching automatically with expiration support.',
			'The performance gain depends heavily on your specific use case. I recommend benchmarking both approaches with your actual data.',
			'Security should always be your top priority. Use nonces, capability checks, and input sanitization on every AJAX endpoint.',
			'I implemented a similar system for a client last quarter. The most important part was setting up proper database indexes from day one.',
			'You might want to look into ElasticPress or another Elasticsearch integration if you need advanced full-text search capabilities.',
		);

		foreach ( $questions as $question_id ) {
			$num_answers  = wp_rand( 1, 4 );
			$used_authors = array();

			for ( $i = 0; $i < $num_answers; $i++ ) {
				$author_id = $this->generated_user_ids[ array_rand( $this->generated_user_ids ) ];
				if ( in_array( $author_id, $used_authors, true ) ) {
					continue;
				}
				$used_authors[] = $author_id;

				$content = $answer_templates[ array_rand( $answer_templates ) ] . ' Question ID ' . $question_id . ' is interesting because it touches on several core concepts. ' . wp_generate_password( 8, false );

				$answer_id = $this->db->insert_answer(
					array(
						'question_id' => $question_id,
						'user_id'     => $author_id,
						'content'     => $content,
						'is_accepted' => 0 === $i && wp_rand( 0, 1 ) ? 1 : 0,
					)
				);

				$this->db->insert_revision(
					array(
						'item_id'   => $answer_id,
						'item_type' => 'answer',
						'user_id'   => $author_id,
						'content'   => $content,
					)
				);
			}
		}
	}

	/**
	 * Generate votes.
	 */
	private function generate_votes() {
		global $wpdb;

		if ( empty( $this->generated_user_ids ) ) {
			return;
		}

		$questions = $wpdb->get_results( "SELECT id, upvotes, downvotes FROM {$this->db->get_table_questions()}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$vote_rows = array();
		foreach ( $questions as $question ) {
			$upvotes   = absint( $question->upvotes );
			$downvotes = absint( $question->downvotes );
			for ( $i = 0; $i < $upvotes; $i++ ) {
				$user_id     = $this->generated_user_ids[ array_rand( $this->generated_user_ids ) ];
				$vote_rows[] = $wpdb->prepare( '(%d, %d, %s, %s, %s)', $user_id, $question->id, 'question', 'up', current_time( 'mysql' ) );
			}
			for ( $i = 0; $i < $downvotes; $i++ ) {
				$user_id     = $this->generated_user_ids[ array_rand( $this->generated_user_ids ) ];
				$vote_rows[] = $wpdb->prepare( '(%d, %d, %s, %s, %s)', $user_id, $question->id, 'question', 'down', current_time( 'mysql' ) );
			}
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( ! empty( $vote_rows ) ) {
			// User/item/type is a UNIQUE key; the random picker can repeat a.
			// user on the same item, which would otherwise abort the whole.
			// atomic multi-row INSERT (and no votes would ever be seeded).
			$wpdb->query( "INSERT IGNORE INTO {$this->db->get_table_votes()} (user_id, item_id, item_type, vote_type, created_at) VALUES " . implode( ', ', $vote_rows ) );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}

		$answers          = $wpdb->get_results( "SELECT id, question_id FROM {$this->db->get_table_answers()}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$answer_vote_rows = array();
		foreach ( $answers as $answer ) {
			$upvotes   = wp_rand( 0, 30 );
			$downvotes = wp_rand( 0, 5 );
			for ( $i = 0; $i < $upvotes; $i++ ) {
				$user_id            = $this->generated_user_ids[ array_rand( $this->generated_user_ids ) ];
				$answer_vote_rows[] = $wpdb->prepare( '(%d, %d, %s, %s, %s)', $user_id, $answer->id, 'answer', 'up', current_time( 'mysql' ) );
			}
			for ( $i = 0; $i < $downvotes; $i++ ) {
				$user_id            = $this->generated_user_ids[ array_rand( $this->generated_user_ids ) ];
				$answer_vote_rows[] = $wpdb->prepare( '(%d, %d, %s, %s, %s)', $user_id, $answer->id, 'answer', 'down', current_time( 'mysql' ) );
			}
			$this->db->update_answer(
				$answer->id,
				array(
					'upvotes'   => $upvotes,
					'downvotes' => $downvotes,
				)
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( ! empty( $answer_vote_rows ) ) {
			// See generate_votes(): skip UNIQUE-key collisions instead of.
			// failing the whole batch.
			$wpdb->query( "INSERT IGNORE INTO {$this->db->get_table_votes()} (user_id, item_id, item_type, vote_type, created_at) VALUES " . implode( ', ', $answer_vote_rows ) );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
	}

	/**
	 * Generate question content.
	 *
	 * @param mixed $title Title.
	 */
	private function generate_question_content( $title ) {
		return '<p>' . __( 'I am working on a project and encountered the following issue:', 'zeko-qa' ) . '</p>' .
				'<p><strong>' . esc_html( $title ) . '</strong></p>' .
				'<p>' . __( 'Could someone provide guidance or a working example? I have tried several approaches but none seem to work as expected. Any help would be greatly appreciated.', 'zeko-qa' ) . '</p>' .
				'<p>' . __( 'Here are the specifics of my setup:', 'zeko-qa' ) . '</p>' .
				'<ul>' .
				'<li>' . __( 'Environment: WordPress 6.x', 'zeko-qa' ) . '</li>' .
				'<li>' . __( 'PHP Version: 8.x', 'zeko-qa' ) . '</li>' .
				'<li>' . __( 'Database: MySQL 8.x', 'zeko-qa' ) . '</li>' .
				'</ul>';
	}
}
