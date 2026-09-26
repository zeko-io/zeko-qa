<?php
/**
 * Public-facing functionality of Zeko QA
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_QA_Public. */
class Zeko_QA_Public {

	/**
	 * Db.
	 *
	 * @var mixed Db.
	 */
	private $db;

	/**
	 * Construct.
	 *
	 * @param mixed $db Db.
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->set_locale();
		$this->define_public_hooks();
		$this->define_rewrite_hooks();
		$this->define_seo_hooks();
	}

	/**
	 * Locale.
	 */
	private function set_locale() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Define public hooks.
	 */
	private function define_public_hooks() {
		add_shortcode( 'zeko_qa_archive', array( $this, 'render_archive_shortcode' ) );
		add_shortcode( 'zeko_qa_ask_form', array( $this, 'render_ask_form_shortcode' ) );
		add_shortcode( 'zeko_qa_dashboard', array( $this, 'render_dashboard_shortcode' ) );
	}

	/**
	 * Define rewrite hooks.
	 */
	private function define_rewrite_hooks() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_question_template' ) );
		add_action( 'template_redirect', array( $this, 'handle_user_profile_template' ) );
		add_action( 'template_redirect', array( $this, 'handle_topic_archive_template' ) );
		add_action( 'template_redirect', array( $this, 'handle_topic_single_template' ) );
		add_action( 'template_redirect', array( $this, 'handle_tag_archive_template' ) );
		add_action( 'template_redirect', array( $this, 'handle_tag_single_template' ) );
		add_action( 'template_redirect', array( $this, 'handle_space_archive_template' ) );
		add_action( 'template_redirect', array( $this, 'handle_space_single_template' ) );
	}

	/**
	 * Define seo hooks.
	 */
	private function define_seo_hooks() {
		add_action( 'wpseo_opengraph', array( $this, 'output_opengraph_tags' ), 10 );
		add_action( 'wp_head', array( $this, 'output_json_ld' ) );
		add_action( 'wp_head', array( $this, 'output_seo_meta' ) );
		add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ) );
		// Notifications bell moved to the unified notification center in zeko-learn.
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		$should_enqueue = false;
		$queried_id     = get_queried_object_id();

		if ( get_query_var( 'zeko_question_slug' ) || get_query_var( 'zeko_user_profile' ) || get_query_var( 'zeko_topic_archive' ) || get_query_var( 'zeko_topic_slug' ) || get_query_var( 'zeko_tag_archive' ) || get_query_var( 'zeko_tag_slug' ) || get_query_var( 'zeko_space_archive' ) || get_query_var( 'zeko_space_slug' ) ) {
			$should_enqueue = true;
		} elseif ( $queried_id ) {
			$content = get_post_field( 'post_content', $queried_id );
			if ( $content && ( has_shortcode( $content, 'zeko_qa_archive' ) || has_shortcode( $content, 'zeko_qa_ask_form' ) || has_shortcode( $content, 'zeko_qa_dashboard' ) ) ) {
				$should_enqueue = true;
			}
		}

		if ( ! $should_enqueue ) {
			return;
		}

		wp_enqueue_style( 'zeko-qa-public', ZEKO_QA_PLUGIN_URL . 'assets/css/zeko-qa-public.css', array( 'zeko-core' ), ZEKO_QA_VERSION );
		wp_enqueue_script( 'zeko-qa-public', ZEKO_QA_PLUGIN_URL . 'assets/js/zeko-qa-public.js', array( 'jquery' ), ZEKO_QA_VERSION, true );
		if ( class_exists( 'Zeko_Core_Assets' ) ) {
			Zeko_Core_Assets::enqueue_quill();
		}
		wp_localize_script(
			'zeko-qa-public',
			'zekoQAPublic',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'zeko_qa_public_nonce' ),
				'userId'  => get_current_user_id(),
				'strings' => array(
					'upvote'       => __( 'Upvote', 'zeko-qa' ),
					'downvote'     => __( 'Downvote', 'zeko-qa' ),
					'submitAnswer' => __( 'Submit Answer', 'zeko-qa' ),
					'loginToVote'  => __( 'Please login to vote', 'zeko-qa' ),
				),
			)
		);
	}

	/**
	 * Render qa template.
	 *
	 * @param mixed $template_file Template file.
	 * @param array $data Data.
	 */
	private function render_qa_template( $template_file, $data = array() ) {
		$args = $data;

		wp_enqueue_style( 'zeko-qa-public', ZEKO_QA_PLUGIN_URL . 'assets/css/zeko-qa-public.css', array( 'zeko-core' ), ZEKO_QA_VERSION );
		wp_enqueue_script( 'zeko-qa-public', ZEKO_QA_PLUGIN_URL . 'assets/js/zeko-qa-public.js', array( 'jquery' ), ZEKO_QA_VERSION, true );
		if ( class_exists( 'Zeko_Core_Assets' ) ) {
			Zeko_Core_Assets::enqueue_quill();
		}
		wp_localize_script(
			'zeko-qa-public',
			'zekoQAPublic',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'zeko_qa_public_nonce' ),
				'userId'  => get_current_user_id(),
				'strings' => array(
					'upvote'       => __( 'Upvote', 'zeko-qa' ),
					'downvote'     => __( 'Downvote', 'zeko-qa' ),
					'submitAnswer' => __( 'Submit Answer', 'zeko-qa' ),
					'loginToVote'  => __( 'Please login to vote', 'zeko-qa' ),
				),
			)
		);

		get_header();
		?>
		<main id="primary" class="site-main">
			<div class="container">
				<?php require ZEKO_QA_PLUGIN_PATH . 'templates/' . $template_file . '.php'; ?>
			</div>
		</main>
		<?php
		get_footer();
		exit;
	}

	/**
	 * Add rewrite rules.
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^questions/([^/]+)/?$', 'index.php?zeko_question_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^users/([^/]+)/?$', 'index.php?zeko_user_profile=$matches[1]&zeko_user_tab=questions', 'top' );
		add_rewrite_rule( '^users/([^/]+)/answers/?$', 'index.php?zeko_user_profile=$matches[1]&zeko_user_tab=answers', 'top' );
		add_rewrite_rule( '^users/([^/]+)/questions/?$', 'index.php?zeko_user_profile=$matches[1]&zeko_user_tab=questions', 'top' );
		add_rewrite_rule( '^users/([^/]+)/bookmarks/?$', 'index.php?zeko_user_profile=$matches[1]&zeko_user_tab=bookmarks', 'top' );
		add_rewrite_rule( '^topics/?$', 'index.php?zeko_topic_archive=1', 'top' );
		add_rewrite_rule( '^topics/([^/]+)/?$', 'index.php?zeko_topic_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^qa-tags/?$', 'index.php?zeko_tag_archive=1', 'top' );
		add_rewrite_rule( '^qa-tags/([^/]+)/?$', 'index.php?zeko_tag_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^spaces/?$', 'index.php?zeko_space_archive=1', 'top' );
		add_rewrite_rule( '^spaces/([^/]+)/?$', 'index.php?zeko_space_slug=$matches[1]', 'top' );
	}

	/**
	 * Add query vars.
	 *
	 * @param mixed $vars Vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'zeko_question_slug';
		$vars[] = 'zeko_user_profile';
		$vars[] = 'zeko_user_tab';
		$vars[] = 'zeko_topic_archive';
		$vars[] = 'zeko_topic_slug';
		$vars[] = 'zeko_tag_archive';
		$vars[] = 'zeko_tag_slug';
		$vars[] = 'zeko_space_archive';
		$vars[] = 'zeko_space_slug';
		return $vars;
	}

	/**
	 * Handle question template.
	 */
	public function handle_question_template() {
		$slug = get_query_var( 'zeko_question_slug', false );
		if ( ! $slug ) {
			return;
		}

		$question = $this->db->get_question_by_slug( $slug );
		if ( ! $question || ! $this->db->can_view_question( $question ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit;
		}

		$this->db->increment_views( $question->id );

		$answers                = $this->db->get_answers( $question->id );
		$question->answer_count = count( $answers );
		$question->answers      = $answers;

		$question_topics = $this->db->get_question_topics_by_question( $question->id );
		$question_tags   = $this->db->get_question_tags( $question->id );

		$this->render_qa_template( 'single-question', compact( 'question', 'answers', 'question_topics', 'question_tags' ) );
	}

	/**
	 * Handle user profile template.
	 */
	public function handle_user_profile_template() {
		$username = get_query_var( 'zeko_user_profile', false );
		if ( ! $username ) {
			return;
		}

		$user = get_user_by( 'login', $username );

		if ( ! $user ) {
			global $wpdb;
			$nicename = sanitize_title( $username );
			$user_id  = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE user_nicename = %s", $nicename ) );
			if ( $user_id ) {
				$user = get_userdata( $user_id );
			}
		}

		if ( ! $user ) {
			$user = get_users(
				array(
					'search'         => $username,
					'search_columns' => array( 'display_name' ),
					'number'         => 1,
					'fields'         => 'ID',
				)
			);
			$user = ! empty( $user ) ? get_userdata( $user[0] ) : false;
		}

		if ( ! $user ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit;
		}

		$tab = sanitize_key( get_query_var( 'zeko_user_tab', 'questions' ) );
		if ( strtolower( $username ) !== strtolower( $user->user_nicename ) && strtolower( $username ) !== strtolower( $user->user_login ) ) {
			wp_safe_redirect( home_url( '/users/' . $user->user_nicename . '/' . $tab . '/' ), 301 );
			exit;
		}

		$user_id         = $user->ID;
		$current_user_id = get_current_user_id();
		$tab             = sanitize_key( get_query_var( 'zeko_user_tab', 'questions' ) );
		$page            = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination query var.
		$per_page        = 15;
		$offset          = ( $page - 1 ) * $per_page;

		// Bookmarks are private: only the owner (or an admin) may view them.
		if ( 'bookmarks' === $tab && (int) $current_user_id !== (int) $user_id && ! current_user_can( 'manage_options' ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit;
		}

		$stats                = $this->db->get_user_qa_stats( $user_id );
		$expertise_topics     = $this->db->get_user_answer_topics( $user_id, 10 );
		$badges               = $this->db->get_user_badges( $user_id );
		$reputation_breakdown = $this->db->get_user_reputation_breakdown( $user_id );

		if ( 'answers' === $tab ) {
			$items = $this->db->get_user_profile_answers( $user_id, $per_page, $offset );
		} elseif ( 'bookmarks' === $tab ) {
			$items = $this->db->get_user_bookmarks( $user_id, $per_page, $offset );
		} else {
			$items = $this->db->get_user_profile_questions( $user_id, $per_page, $offset );
		}

		$canonical = home_url( '/users/' . $user->user_nicename . '/' . $tab . '/' );

		$this->render_qa_template( 'user-profile', compact( 'user', 'user_id', 'current_user_id', 'tab', 'stats', 'expertise_topics', 'badges', 'reputation_breakdown', 'items', 'canonical' ) );
	}

	/**
	 * Handle topic archive template.
	 */
	public function handle_topic_archive_template() {
		if ( ! get_query_var( 'zeko_topic_archive' ) ) {
			return;
		}
		$topics    = $this->db->get_all_topics( 50, 0 );
		$canonical = home_url( '/topics/' );
		$this->render_qa_template( 'archive-topics', compact( 'topics', 'canonical' ) );
	}

	/**
	 * Handle topic single template.
	 */
	public function handle_topic_single_template() {
		$slug = get_query_var( 'zeko_topic_slug', false );
		if ( ! $slug ) {
			return;
		}
		$topic = $this->db->get_topic_by_slug( $slug );
		if ( ! $topic ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit;
		}
		$page            = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination query var.
		$per_page        = 20;
		$offset          = ( $page - 1 ) * $per_page;
		$questions       = $this->db->get_topic_questions( $topic->id, $per_page, $offset );
		$is_following    = false;
		$current_user_id = get_current_user_id();
		if ( $current_user_id ) {
			$is_following = $this->db->is_topic_followed( $current_user_id, $topic->id );
		}
		$canonical = home_url( '/topics/' . $topic->slug . '/' );
		$this->render_qa_template( 'single-topic', compact( 'topic', 'questions', 'is_following', 'canonical' ) );
	}

	/**
	 * Handle tag archive template.
	 */
	public function handle_tag_archive_template() {
		if ( ! get_query_var( 'zeko_tag_archive' ) ) {
			return;
		}
		$tags      = $this->db->get_all_tags( 100, 0 );
		$canonical = home_url( '/qa-tags/' );
		$this->render_qa_template( 'archive-tags', compact( 'tags', 'canonical' ) );
	}

	/**
	 * Handle tag single template.
	 */
	public function handle_tag_single_template() {
		$slug = get_query_var( 'zeko_tag_slug', false );
		if ( ! $slug ) {
			return;
		}
		$tag = $this->db->get_tag_by_slug( $slug );
		if ( ! $tag ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit;
		}
		$page      = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination query var.
		$per_page  = 20;
		$offset    = ( $page - 1 ) * $per_page;
		$questions = $this->db->get_tag_questions( $tag->id, $per_page, $offset );
		$canonical = home_url( '/qa-tags/' . $tag->slug . '/' );
		$this->render_qa_template( 'single-tag', compact( 'tag', 'questions', 'canonical' ) );
	}

	/**
	 * Handle space archive template.
	 */
	public function handle_space_archive_template() {
		$is_archive = get_query_var( 'zeko_space_archive', false );
		if ( ! $is_archive ) {
			return;
		}
		$spaces = $this->db->get_spaces(
			array(
				'limit'       => 50,
				'public_only' => true,
			)
		);
		$this->render_qa_template( 'archive-spaces', compact( 'spaces' ) );
	}

	/**
	 * Handle space single template.
	 */
	public function handle_space_single_template() {
		$slug = get_query_var( 'zeko_space_slug', false );
		if ( ! $slug ) {
			return;
		}
		$space = $this->db->get_space_by_slug( $slug );
		if ( ! $space ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit;
		}
		// Private/invite-only spaces are only visible to members and admins.
		if ( ! $space->is_public ) {
			$current_user_id = get_current_user_id();
			$is_visible      = (int) $space->creator_id === (int) $current_user_id
				|| current_user_can( 'manage_options' )
				|| ( $current_user_id && $this->db->is_space_member( $space->id, $current_user_id ) );
			if ( ! $is_visible ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				get_template_part( 404 );
				exit;
			}
		}
		$posts = $this->db->get_space_posts( $space->id, 20, 0 );
		$this->render_qa_template( 'single-space', compact( 'space', 'posts' ) );
	}

	/**
	 * Filter document title.
	 *
	 * @param mixed $title Title.
	 */
	public function filter_document_title( $title ) {
		$slug = get_query_var( 'zeko_question_slug', false );
		if ( $slug ) {
			$question = $this->db->get_question_by_slug( $slug );
			if ( $question && $this->db->can_view_question( $question ) ) {
				return $question->title . ' - ' . get_bloginfo( 'name' );
			}
		}
		return $title;
	}

	/**
	 * Output opengraph tags.
	 */
	public function output_opengraph_tags() {
		$slug = get_query_var( 'zeko_question_slug', false );
		if ( ! $slug ) {
			return;
		}
		$question = $this->db->get_question_by_slug( $slug );
		if ( ! $question || ! $this->db->can_view_question( $question ) ) {
			return;
		}
		$excerpt = wp_trim_words( $question->content, 55 );
		printf( '<meta property="og:title" content="%s" />', esc_attr( $question->title ) );
		printf( '<meta property="og:description" content="%s" />', esc_attr( $excerpt ) );
		printf( '<meta property="og:type" content="website" />' );
		printf( '<meta property="og:url" content="%s" />', esc_url( home_url( '/questions/' . $question->slug . '/' ) ) );

		if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail() ) {
			$thumbnail = get_the_post_thumbnail_url( null, 'full' );
			if ( $thumbnail ) {
				printf( '<meta property="og:image" content="%s" />', esc_url( $thumbnail ) );
			}
		}
	}

	/**
	 * Output json ld.
	 */
	public function output_json_ld() {
		if ( ! is_singular() || get_query_var( 'zeko_question_slug', false ) === false ) {
			return;
		}

		$slug     = get_query_var( 'zeko_question_slug', false );
		$question = $this->db->get_question_by_slug( $slug );
		if ( ! $question || ! $this->db->can_view_question( $question ) ) {
			return;
		}

		$answers   = $this->db->get_answers( $question->id );
		$accepted  = null;
		$suggested = array();

		foreach ( $answers as $answer ) {
			$answer_data = array(
				'@type'       => 'Answer',
				'text'        => wp_strip_all_tags( $answer->content ),
				'upvoteCount' => absint( $answer->upvotes ),
				'dateCreated' => mysql2date( 'c', $answer->created_at ),
			);
			if ( $answer->is_accepted ) {
				$accepted = $answer_data;
			} else {
				$suggested[] = $answer_data;
			}
		}

		$json_ld = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'QAPage',
			'mainEntity' => array(
				'@type'           => 'Question',
				'name'            => $question->title,
				'text'            => wp_strip_all_tags( $question->content ),
				'upvoteCount'     => absint( $question->upvotes ),
				'dateCreated'     => mysql2date( 'c', $question->created_at ),
				'answerCount'     => count( $answers ),
				'acceptedAnswer'  => $accepted,
				'suggestedAnswer' => $suggested,
			),
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/**
	 * Output seo meta.
	 */
	public function output_seo_meta() {
		$slug = get_query_var( 'zeko_question_slug', false );
		if ( ! $slug ) {
			return;
		}
		$question = $this->db->get_question_by_slug( $slug );
		if ( ! $question || ! $this->db->can_view_question( $question ) ) {
			return;
		}
		$canonical   = home_url( '/questions/' . $question->slug . '/' );
		$description = wp_strip_all_tags( $question->content );
		$description = mb_substr( $description, 0, 160 );
		if ( mb_strlen( wp_strip_all_tags( $question->content ) ) > 160 ) {
			$description .= '...';
		}
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
	}

	/**
	 * Render archive shortcode.
	 *
	 * @param mixed $atts Atts.
	 */
	public function render_archive_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'filter'   => 'trending',
				'limit'    => 20,
				'search'   => '',
				'topic_id' => 0,
			),
			$atts,
			'zeko_qa_archive'
		);

		if ( isset( $_GET['filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only archive display flag.
			$atts['filter'] = sanitize_key( wp_unslash( $_GET['filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only archive display flag.
		}
		if ( isset( $_GET['s'] ) && '' !== $_GET['s'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only archive search term.
			$atts['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only archive search term.
		}
		if ( isset( $_GET['topic_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only archive filter.
			$atts['topic_id'] = absint( $_GET['topic_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only archive filter.
		}

		if ( 'trending' === $atts['filter'] ) {
			$cache_key = 'zeko_qa_trending_' . absint( $atts['limit'] );
			$questions = get_transient( $cache_key );
			if ( false === $questions ) {
				$questions = $this->db->get_trending_questions( absint( $atts['limit'] ), 0 );
				set_transient( $cache_key, $questions, 5 * MINUTE_IN_SECONDS );
			}
		} elseif ( 'unanswered' === $atts['filter'] ) {
			$questions = $this->db->get_unanswered_questions( absint( $atts['limit'] ), 0 );
		} elseif ( 'feed' === $atts['filter'] && is_user_logged_in() ) {
			$user_id            = get_current_user_id();
			$followed_topics    = $this->db->get_followed_topics_questions( $user_id, absint( $atts['limit'] ), 0 );
			$followed_questions = $this->db->get_user_followed_questions( $user_id, absint( $atts['limit'] ), 0 );
			$merged             = array();
			$seen               = array();
			foreach ( array_merge( $followed_topics, $followed_questions ) as $q ) {
				if ( ! isset( $seen[ $q->id ] ) ) {
					$seen[ $q->id ] = true;
					$merged[]       = $q;
				}
			}
			usort(
				$merged,
				function ( $a, $b ) {
					return strtotime( $b->created_at ) - strtotime( $a->created_at );
				}
			);
			$questions = array_slice( $merged, 0, absint( $atts['limit'] ) );
		} else {
			$questions = $this->db->get_questions(
				array(
					'limit'    => absint( $atts['limit'] ),
					'search'   => sanitize_text_field( $atts['search'] ),
					'topic_id' => absint( $atts['topic_id'] ),
					'orderby'  => 'newest' === $atts['filter'] ? 'created_at' : 'created_at',
					'order'    => 'DESC',
				)
			);
		}

		$user_votes      = array();
		$current_user_id = get_current_user_id();
		if ( $current_user_id && ! empty( $questions ) ) {
			$question_ids = array_column( $questions, 'id' );
			$user_votes   = $this->db->get_user_votes_for_items( $current_user_id, 'question', $question_ids );
		}

		ob_start();
		require ZEKO_QA_PLUGIN_PATH . 'templates/archive-questions.php';
		return ob_get_clean();
	}

	/**
	 * Render ask form shortcode.
	 *
	 * @param mixed $atts Atts.
	 */
	public function render_ask_form_shortcode( $atts ) {
		$topics   = $this->db->get_topics( array( 'limit' => 50 ) );
		$tags     = $this->db->get_tags( array( 'limit' => 100 ) );
		$defaults = apply_filters(
			'zeko_qa_ask_defaults',
			array(
				'title'            => '',
				'content'          => '',
				'mention_business' => 0,
			),
			$atts
		);
		ob_start();
		require ZEKO_QA_PLUGIN_PATH . 'templates/ask-form.php';
		return ob_get_clean();
	}

	/**
	 * Render dashboard shortcode.
	 *
	 * @param mixed $atts Atts.
	 */
	public function render_dashboard_shortcode( $atts ) {
		unset( $atts );
		$current_user_id = get_current_user_id();
		$user_questions  = $this->db->get_user_profile_questions( $current_user_id, 10 );
		$user_answers    = $this->db->get_user_profile_answers( $current_user_id, 10 );

		$reputation    = $this->db->get_user_reputation( $current_user_id );
		$notifications = $this->db->get_user_notifications( $current_user_id, 10 );

		ob_start();
		require ZEKO_QA_PLUGIN_PATH . 'templates/qa-dashboard.php';
		return ob_get_clean();
	}

	/**
	 * Render notifications bell.
	 */
	public function render_notifications_bell() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		$unread_count = $this->db->get_user_unread_notification_count( $user_id );
		?>
		<div class="zeko-qa-notifications-wrapper" style="position:fixed;bottom:20px;right:20px;z-index:9999;">
			<button class="zeko-qa-notifications-bell" aria-label="<?php esc_attr_e( 'Notifications', 'zeko-qa' ); ?>" aria-expanded="false" aria-controls="zeko-qa-notifications-dropdown">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
				<?php if ( $unread_count > 0 ) : ?>
					<span class="zeko-qa-notifications-badge"><?php echo $unread_count > 99 ? '99+' : absint( $unread_count ); ?></span>
				<?php endif; ?>
			</button>
			<div id="zeko-qa-notifications-dropdown" class="zeko-qa-notifications-dropdown" style="display:none;">
				<div class="zeko-qa-notifications-header">
					<strong><?php esc_html_e( 'Notifications', 'zeko-qa' ); ?></strong>
					<button type="button" class="zeko-qa-notifications-mark-read"><?php esc_html_e( 'Mark all read', 'zeko-qa' ); ?></button>
				</div>
				<div class="zeko-qa-notifications-list">
					<p class="zeko-qa-notifications-loading"><?php esc_html_e( 'Loading...', 'zeko-qa' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
}
