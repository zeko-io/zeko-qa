<?php
/**
 * Ecosystem integration for Zeko QA
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_QA_Ecosystem. */
class Zeko_QA_Ecosystem {

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
		$this->define_hooks();
	}

	/**
	 * Define hooks.
	 */
	private function define_hooks() {
		add_action( 'show_user_profile', array( $this, 'render_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'render_user_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_profile_fields' ) );

		add_filter( 'zeko_dashboard_tabs', array( $this, 'add_dashboard_tab' ) );
		add_action( 'zeko_dashboard_tab_content_qa', array( $this, 'render_dashboard_tab' ) );

		add_filter( 'zeko_activity_feed_items', array( $this, 'inject_activity_feed' ) );

		// Nav items registry.
		add_filter( 'zeko_nav_items', array( $this, 'register_nav_items' ) );

		// Notification source registry.
		add_filter( 'zeko_register_notification_sources', array( $this, 'register_notification_source' ) );
	}

	/**
	 * Register Q&A as a notification source for the core bell.
	 *
	 * @param array $sources Sources.
	 */
	public function register_notification_source( array $sources ): array {
		$sources['qa'] = array(
			'table'       => $this->db->get_table_notifications(),
			'type_column' => 'action',
			'has_object'  => true,
			'icon'        => 'editor-help',
			'label'       => __( 'Q&A', 'zeko-qa' ),
		);
		return $sources;
	}

	/**
	 * Render user profile fields.
	 *
	 * @param mixed $user User.
	 */
	public function render_user_profile_fields( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}
		$reputation = $this->db->get_user_reputation( $user->ID );
		$expertise  = get_user_meta( $user->ID, 'zeko_qa_expertise', true );
		if ( ! is_array( $expertise ) ) {
			$expertise = array();
		}
		$expertise_json = wp_json_encode( $expertise );
		?>
		<h2><?php esc_html_e( 'Zeko QA Profile', 'zeko-qa' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="zeko_qa_reputation"><?php esc_html_e( 'Reputation Score', 'zeko-qa' ); ?></label></th>
				<td>
					<span id="zeko_qa_reputation"><?php echo absint( $reputation ); ?></span>
					<p class="description"><?php esc_html_e( 'Calculated dynamically from upvotes on your questions and answers.', 'zeko-qa' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="zeko_qa_expertise"><?php esc_html_e( 'Topics of Expertise', 'zeko-qa' ); ?></label></th>
				<td>
					<textarea name="zeko_qa_expertise" id="zeko_qa_expertise" rows="4" class="large-text"><?php echo esc_textarea( implode( "\n", $expertise ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter one topic per line. This helps match you with relevant questions.', 'zeko-qa' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save user profile fields.
	 *
	 * @param mixed $user_id User id.
	 */
	public function save_user_profile_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		if ( isset( $_POST['zeko_qa_expertise'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Profile update screens are nonce-verified by Core (check_admin_referer('update-user_{id}')).
			$expertise = explode( "\n", sanitize_textarea_field( wp_unslash( $_POST['zeko_qa_expertise'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Profile update screens are nonce-verified by Core (check_admin_referer('update-user_{id}')).
			$expertise = array_map( 'trim', $expertise );
			$expertise = array_filter( $expertise );
			update_user_meta( $user_id, 'zeko_qa_expertise', $expertise );
		}
	}

	/**
	 * Add dashboard tab.
	 *
	 * @param mixed $tabs Tabs.
	 */
	public function add_dashboard_tab( $tabs ) {
		$tabs['qa'] = __( 'My Q&A', 'zeko-qa' );
		return $tabs;
	}

	/**
	 * Register Q&A nav items via the core registry.
	 *
	 * @return array
	 * @param array $locations keyed by location slug.
	 */
	public function register_nav_items( array $locations ): array {
		$locations['primary'][] = array(
			'title'    => __( 'Q&A', 'zeko-qa' ),
			'url'      => home_url( '/questions/' ),
			'order'    => 4,
			'children' => array(
				array(
					'title' => __( 'Questions', 'zeko-qa' ),
					'url'   => home_url( '/questions/' ),
				),
				array(
					'title' => __( 'Ask a Question', 'zeko-qa' ),
					'url'   => home_url( '/ask-a-question/' ),
				),
				array(
					'title' => __( 'My Q&A', 'zeko-qa' ),
					'url'   => home_url( '/qa-dashboard/' ),
				),
				array(
					'title' => __( 'Topics', 'zeko-qa' ),
					'url'   => home_url( '/topics/' ),
				),
			),
		);
		$locations['footer'][]  = array(
			'title' => __( 'Q&A', 'zeko-qa' ),
			'url'   => home_url( '/questions/' ),
			'order' => 4,
		);
		return $locations;
	}

	/**
	 * Render dashboard tab.
	 */
	public function render_dashboard_tab() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			echo '<p>' . esc_html__( 'Please login.', 'zeko-qa' ) . '</p>';
			return;
		}

		$stats            = $this->db->get_user_qa_stats( $user_id );
		$badges           = $this->db->get_user_badges( $user_id );
		$recent_questions = $this->db->get_user_profile_questions( $user_id, 5, 0 );
		$recent_answers   = $this->db->get_user_profile_answers( $user_id, 5, 0 );
		$notifications    = $this->db->get_user_notifications( $user_id, 10 );
		$profile_url      = home_url( '/users/' . wp_get_current_user()->user_nicename . '/' );
		?>
		<div class="zeko-qa-dashboard-tab">
			<div class="zeko-qa-dashboard-stats">
				<a href="<?php echo esc_url( $profile_url ); ?>" class="zeko-qa-stat-card">
					<span class="zeko-qa-stat-value"><?php echo absint( $stats['reputation'] ); ?></span>
					<span class="zeko-qa-stat-label"><?php esc_html_e( 'Reputation', 'zeko-qa' ); ?></span>
				</a>
				<a href="<?php echo esc_url( $profile_url ); ?>" class="zeko-qa-stat-card">
					<span class="zeko-qa-stat-value"><?php echo absint( $stats['question_count'] ); ?></span>
					<span class="zeko-qa-stat-label"><?php esc_html_e( 'Questions', 'zeko-qa' ); ?></span>
				</a>
				<a href="<?php echo esc_url( $profile_url . 'answers/' ); ?>" class="zeko-qa-stat-card">
					<span class="zeko-qa-stat-value"><?php echo absint( $stats['answer_count'] ); ?></span>
					<span class="zeko-qa-stat-label"><?php esc_html_e( 'Answers', 'zeko-qa' ); ?></span>
				</a>
				<div class="zeko-qa-stat-card">
					<span class="zeko-qa-stat-value"><?php echo absint( $stats['accepted_count'] ); ?></span>
					<span class="zeko-qa-stat-label"><?php esc_html_e( 'Accepted', 'zeko-qa' ); ?></span>
				</div>
			</div>

			<?php if ( ! empty( $badges ) ) : ?>
				<section class="zeko-qa-dashboard-section">
					<h3 class="zeko-qa-dashboard-heading"><?php esc_html_e( 'My Badges', 'zeko-qa' ); ?></h3>
					<div class="zeko-qa-dashboard-badges">
						<?php foreach ( array_slice( $badges, 0, 5 ) as $badge ) : ?>
							<span class="zeko-qa-badge-item" title="<?php echo esc_attr( $badge->description ); ?>">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="#b92b27" stroke="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
								<?php echo esc_html( $badge->name ); ?>
							</span>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<div class="zeko-qa-dashboard-grid">
				<section class="zeko-qa-dashboard-section">
					<h3 class="zeko-qa-dashboard-heading"><?php esc_html_e( 'My Questions', 'zeko-qa' ); ?></h3>
					<?php if ( empty( $recent_questions ) ) : ?>
						<p class="zeko-qa-empty-text"><?php esc_html_e( 'You have not asked any questions yet.', 'zeko-qa' ); ?></p>
					<?php else : ?>
						<ul class="zeko-qa-dashboard-list">
							<?php foreach ( $recent_questions as $question ) : ?>
								<li class="zeko-qa-dashboard-item">
									<a href="<?php echo esc_url( home_url( '/questions/' . $question->slug . '/' ) ); ?>"><?php echo esc_html( $question->title ); ?></a>
									<span class="zeko-qa-dashboard-item-meta"><?php echo absint( $question->answer_count ); ?> <?php esc_html_e( 'answers', 'zeko-qa' ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
				<section class="zeko-qa-dashboard-section">
					<h3 class="zeko-qa-dashboard-heading"><?php esc_html_e( 'Recent Notifications', 'zeko-qa' ); ?></h3>
					<?php if ( empty( $notifications ) ) : ?>
						<p class="zeko-qa-empty-text"><?php esc_html_e( 'No new notifications.', 'zeko-qa' ); ?></p>
					<?php else : ?>
						<ul class="zeko-qa-dashboard-list">
							<?php foreach ( $notifications as $notification ) : ?>
								<li class="zeko-qa-dashboard-item <?php echo ! $notification->is_read ? 'zeko-qa-unread' : ''; ?>">
									<?php echo esc_html( $notification->action ); ?>
									<span class="zeko-qa-dashboard-item-meta"><?php echo esc_html( human_time_diff( strtotime( $notification->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Inject activity feed.
	 *
	 * @param mixed $items Items.
	 */
	public function inject_activity_feed( $items ) {
		if ( ! is_array( $items ) ) {
			$items = array();
		}

		global $wpdb;
		$questions_table = $this->db->get_table_questions();
		$answers_table   = $this->db->get_table_answers();

		$qa_items = array();

		$recent_questions = $this->db->get_questions(
			array(
				'limit'   => 5,
				'offset'  => 0,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			)
		);

		foreach ( $recent_questions as $question ) {
			$user       = get_userdata( $question->user_id );
			$user_name  = $user ? $user->display_name : __( 'Anonymous', 'zeko-qa' );
			$qa_items[] = array(
				'module'    => 'qa',
				'action'    => 'question_asked',
				/* translators: 1: user name. 2: question title */
				'message'   => sprintf( __( '%1$s asked a question about "%2$s"', 'zeko-qa' ), $user_name, $question->title ),
				'timestamp' => $question->created_at,
				'item_id'   => (int) $question->id,
				'link'      => home_url( '/questions/' . $question->slug . '/' ),
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$recent_answers = $wpdb->get_results(
			"SELECT a.*, q.title, q.slug FROM {$answers_table} a
             INNER JOIN {$questions_table} q ON a.question_id = q.id
             WHERE q.status <> 'deleted' AND NOT EXISTS (
                 SELECT 1 FROM {$this->db->get_table_space_posts()} zsp
                 INNER JOIN {$this->db->get_table_spaces()} zs ON zsp.space_id = zs.id
                 WHERE zsp.item_type = 'question' AND zsp.item_id = q.id
                   AND zs.is_public = 0
                   AND zs.id NOT IN (
                       SELECT zsm.space_id FROM {$this->db->get_table_space_members()} zsm WHERE zsm.user_id = " . (int) get_current_user_id() . '
                   )
             )
             ORDER BY a.created_at DESC LIMIT 5'
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		foreach ( $recent_answers as $answer ) {
			$user       = get_userdata( $answer->user_id );
			$user_name  = $user ? $user->display_name : __( 'Anonymous', 'zeko-qa' );
			$qa_items[] = array(
				'module'    => 'qa',
				'action'    => 'answer_given',
				/* translators: 1: user name. 2: answer title */
				'message'   => sprintf( __( '%1$s answered "%2$s"', 'zeko-qa' ), $user_name, $answer->title ),
				'timestamp' => $answer->created_at,
				'item_id'   => (int) $answer->id,
				'link'      => home_url( '/questions/' . $answer->slug . '/#answer-' . $answer->id ),
			);
		}

		usort(
			$qa_items,
			function ( $a, $b ) {
				return strtotime( $b['timestamp'] ) - strtotime( $a['timestamp'] );
			}
		);

		return array_merge( $items, $qa_items );
	}
}

/**
 * Zeko qa log activity.
 *
 * @param mixed $user_id User id.
 * @param mixed $action Action.
 * @param mixed $object_id Object id.
 * @param mixed $object_type Object type.
 */
function zeko_qa_log_activity( $user_id, $action, $object_id, $object_type ) {
	if ( ! function_exists( 'zeko_qa' ) ) {
		return false;
	}
	$db = zeko_qa()->get_db();
	if ( ! $db ) {
		return false;
	}

	$actor_id = get_current_user_id();

	$db->insert_notification(
		array(
			'user_id'     => absint( $user_id ),
			'action'      => sanitize_text_field( $action ),
			'object_id'   => absint( $object_id ),
			'object_type' => sanitize_key( $object_type ),
			'actor_id'    => absint( $actor_id ),
		)
	);

	global $wpdb;
	$activity_table = $wpdb->prefix . 'zeko_user_activity';

	$content = '';
	if ( 'asked_question' === $action || 'answered_question' === $action ) {
		$question = $db->get_question( absint( $object_id ) );
		$content  = $question ? $question->title : '';
	}

	if ( class_exists( 'Zeko_Core_Activity' ) ) {
		Zeko_Core_Activity::get_instance()->log(
			absint( $actor_id ),
			sanitize_text_field( $action ),
			$content,
			absint( $object_id ),
			array(
				'module'      => 'zeko_qa',
				'object_type' => sanitize_key( $object_type ),
				'object_id'   => absint( $object_id ),
			)
		);
	} elseif ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $activity_table ) ) === $activity_table ) {
		$wpdb->insert(
			$activity_table,
			array(
				'user_id'          => absint( $actor_id ),
				'activity_type'    => $action,
				'activity_module'  => 'zeko_qa',
				'activity_item_id' => absint( $object_id ),
				'activity_content' => $content,
				'activity_meta'    => maybe_serialize(
					array(
						'object_type' => sanitize_key( $object_type ),
						'object_id'   => absint( $object_id ),
					)
				),
				'activity_date'    => current_time( 'mysql' ),
				'activity_ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'activity_status'  => 'published',
			)
		);
	}

	do_action( 'zeko_qa_activity_logged', $actor_id, $action, $object_id, $object_type );

	return true;
}
