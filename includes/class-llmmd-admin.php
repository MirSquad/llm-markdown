<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LLMMD_Admin {

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}

	public static function add_menu() {
		add_options_page(
			__( 'LLM Markdown', 'llm-markdown' ),
			__( 'LLM Markdown', 'llm-markdown' ),
			'manage_options',
			'llm-markdown',
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function register_settings() {
		register_setting( 'llmmd_settings_group', 'llmmd_settings', [
			'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
		] );

		add_settings_section(
			'llmmd_main',
			'',
			'__return_false',
			'llm-markdown'
		);

		add_settings_field(
			'llmmd_post_types',
			__( 'Enabled Post Types', 'llm-markdown' ),
			[ __CLASS__, 'render_post_types_field' ],
			'llm-markdown',
			'llmmd_main'
		);

		add_settings_field(
			'llmmd_root_selector',
			__( 'Content Root Selector', 'llm-markdown' ),
			[ __CLASS__, 'render_root_selector_field' ],
			'llm-markdown',
			'llmmd_main'
		);
	}

	public static function sanitize_settings( $input ) {
		$sanitized = [];

		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			$sanitized['post_types'] = array_map( 'sanitize_key', $input['post_types'] );
		} else {
			$sanitized['post_types'] = [];
		}

		if ( isset( $input['root_selector'] ) ) {
			$sanitized['root_selector'] = mb_substr( sanitize_text_field( $input['root_selector'] ), 0, 500 );
		} else {
			$sanitized['root_selector'] = '';
		}

		delete_transient( 'llmmd_llms_txt' );

		return $sanitized;
	}

	public static function render_post_types_field() {
		$settings   = get_option( 'llmmd_settings', [] );
		$enabled    = isset( $settings['post_types'] ) ? $settings['post_types'] : [ 'post', 'page' ];
		$post_types = get_post_types( [ 'public' => true ], 'objects' );

		foreach ( $post_types as $pt ) {
			if ( 'attachment' === $pt->name ) {
				continue;
			}
			$checked = in_array( $pt->name, $enabled, true ) ? 'checked' : '';
			echo '<label style="display:block;margin-bottom:6px;">';
			echo '<input type="checkbox" name="llmmd_settings[post_types][]" value="' . esc_attr( $pt->name ) . '" ' . $checked . '> ';
			echo esc_html( $pt->labels->name ) . ' <code>' . esc_html( $pt->name ) . '</code>';
			echo '</label>';
		}
	}

	public static function render_root_selector_field() {
		$settings = get_option( 'llmmd_settings', [] );
		$value    = isset( $settings['root_selector'] ) ? $settings['root_selector'] : '';
		echo '<input type="text" name="llmmd_settings[root_selector]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="main, article, .entry-content">';
		echo '<p class="description">' . esc_html__( 'CSS selector(s) to extract content from. Leave empty to use the full post content. Comma-separated for multiple selectors.', 'llm-markdown' ) . '</p>';
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$llms_txt_url = home_url( '/llms.txt' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'LLM Markdown Settings', 'llm-markdown' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'llmmd_settings_group' );
				do_settings_sections( 'llm-markdown' );
				submit_button();
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Quick Links', 'llm-markdown' ); ?></h2>
			<p>
				<strong><?php esc_html_e( 'llms.txt:', 'llm-markdown' ); ?></strong>
				<a href="<?php echo esc_url( $llms_txt_url ); ?>" target="_blank"><?php echo esc_html( $llms_txt_url ); ?></a>
			</p>

			<hr>

			<h2><?php esc_html_e( 'Regenerate Markdown', 'llm-markdown' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Regenerate cached markdown for all published content. This happens automatically when posts are saved.', 'llm-markdown' ); ?></p>
			<?php
			if ( isset( $_GET['llmmd_regenerated'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'llmmd_regenerate' ) ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'All markdown content has been regenerated.', 'llm-markdown' ) . '</p></div>';
			}
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="llmmd_regenerate">
				<?php wp_nonce_field( 'llmmd_regenerate', 'llmmd_nonce' ); ?>
				<?php submit_button( __( 'Regenerate All', 'llm-markdown' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}
}

add_action( 'admin_post_llmmd_regenerate', 'llmmd_handle_regenerate' );
function llmmd_handle_regenerate() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'llm-markdown' ) );
	}
	if ( ! isset( $_POST['llmmd_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['llmmd_nonce'] ) ), 'llmmd_regenerate' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'llm-markdown' ) );
	}

	llmmd_bulk_generate();
	delete_transient( 'llmmd_llms_txt' );

	wp_safe_redirect( add_query_arg(
		[
			'page'              => 'llm-markdown',
			'llmmd_regenerated' => '1',
			'_wpnonce'          => wp_create_nonce( 'llmmd_regenerate' ),
		],
		admin_url( 'options-general.php' )
	) );
	exit;
}
