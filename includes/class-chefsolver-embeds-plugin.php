<?php
/**
 * Main plugin class: shortcodes, Gutenberg block, settings, manifest cache and
 * the shared iframe renderer.
 *
 * @package ChefSolver_Embeds
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton plugin controller.
 */
class ChefSolver_Embeds_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var ChefSolver_Embeds_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return ChefSolver_Embeds_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up hooks.
	 */
	protected function __construct() {
		// Shortcodes.
		add_shortcode( 'chefsolver_embed', array( $this, 'shortcode_embed' ) );
		add_shortcode( 'chefsolver_converter', array( $this, 'shortcode_converter' ) );

		// Block + REST.
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest' ) );

		// Admin settings.
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_chefsolver_embeds_refresh', array( $this, 'handle_refresh' ) );

		// Frontend container styles (local, tiny, inline).
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_style' ) );
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * Options
	 * ───────────────────────────────────────────────────────────────────── */

	/**
	 * Read a single option with a default.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_opt( $key, $default = '' ) {
		$options = get_option( CHEFSOLVER_EMBEDS_OPTION, array() );
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	/**
	 * Default attribute values (from settings, then hard defaults).
	 *
	 * @return array
	 */
	public function defaults() {
		return array(
			'lang'   => $this->sanitize_lang( $this->get_opt( 'default_lang', 'en' ) ),
			'theme'  => $this->sanitize_theme( $this->get_opt( 'default_theme', 'auto' ) ),
			'height' => $this->sanitize_height( $this->get_opt( 'default_height', CHEFSOLVER_EMBEDS_DEFAULT_HEIGHT ) ),
		);
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * Sanitizers (strict allowlists / patterns)
	 * ───────────────────────────────────────────────────────────────────── */

	/**
	 * @param mixed $v Raw language.
	 * @return string
	 */
	public function sanitize_lang( $v ) {
		$v = strtolower( trim( (string) $v ) );
		return in_array( $v, CHEFSOLVER_EMBEDS_LANGS, true ) ? $v : 'en';
	}

	/**
	 * @param mixed $v Raw theme.
	 * @return string
	 */
	public function sanitize_theme( $v ) {
		$v = strtolower( trim( (string) $v ) );
		return in_array( $v, CHEFSOLVER_EMBEDS_THEMES, true ) ? $v : 'auto';
	}

	/**
	 * @param mixed $v Raw type.
	 * @return string
	 */
	public function sanitize_type( $v ) {
		$v = strtolower( trim( (string) $v ) );
		return preg_match( '/^[a-z][a-z0-9-]{0,40}$/', $v ) ? $v : '';
	}

	/**
	 * @param mixed $v Raw slug.
	 * @return string
	 */
	public function sanitize_slug( $v ) {
		return ChefSolver_Embeds_Manifest::clean_slug( (string) $v );
	}

	/**
	 * Accept only a valid CSS hex color (#rgb/#rgba/#rrggbb/#rrggbbaa).
	 *
	 * @param mixed $v Raw accent.
	 * @return string Hex color or '' if invalid.
	 */
	public function sanitize_accent( $v ) {
		$v = trim( (string) $v );
		return preg_match( '/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $v ) ? $v : '';
	}

	/**
	 * @param mixed $v Raw compact flag.
	 * @return string '1' or ''.
	 */
	public function sanitize_compact( $v ) {
		if ( true === $v || '1' === (string) $v || 1 === $v || 'true' === $v || 'on' === $v || 'yes' === $v ) {
			return '1';
		}
		return '';
	}

	/**
	 * @param mixed $v Raw radius.
	 * @return string Integer string in [0, MAX] or '' when unset/invalid.
	 */
	public function sanitize_radius( $v ) {
		if ( '' === $v || null === $v ) {
			return '';
		}
		if ( ! preg_match( '/^\d{1,3}$/', (string) $v ) ) {
			return '';
		}
		$n = (int) $v;
		if ( $n < 0 || $n > CHEFSOLVER_EMBEDS_MAX_RADIUS ) {
			return '';
		}
		return (string) $n;
	}

	/**
	 * @param mixed $v Raw height.
	 * @return int Height clamped to a safe range.
	 */
	public function sanitize_height( $v ) {
		$n = (int) $v;
		if ( $n < CHEFSOLVER_EMBEDS_MIN_HEIGHT || $n > CHEFSOLVER_EMBEDS_MAX_HEIGHT ) {
			return CHEFSOLVER_EMBEDS_DEFAULT_HEIGHT;
		}
		return $n;
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * Shortcodes
	 * ───────────────────────────────────────────────────────────────────── */

	/**
	 * [chefsolver_embed] handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_embed( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();
		return $this->render( $atts );
	}

	/**
	 * [chefsolver_converter] alias — defaults type to "converter".
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_converter( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();
		if ( empty( $atts['type'] ) ) {
			$atts['type'] = 'converter';
		}
		return $this->render( $atts );
	}

	/**
	 * Block server-side render callback (shares render()).
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ) {
		$attributes = is_array( $attributes ) ? $attributes : array();
		return $this->render( $attributes );
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * Shared renderer
	 * ───────────────────────────────────────────────────────────────────── */

	/**
	 * Resolve an embed and return safe iframe markup (or an HTML comment on
	 * failure). This is the single rendering path for both shortcodes and the
	 * block, so behavior and escaping never diverge.
	 *
	 * @param array $raw Raw attributes.
	 * @return string
	 */
	public function render( $raw ) {
		$defaults = $this->defaults();

		$type    = $this->sanitize_type( isset( $raw['type'] ) ? $raw['type'] : 'converter' );
		$slug    = $this->sanitize_slug( isset( $raw['slug'] ) ? $raw['slug'] : '' );
		$lang    = isset( $raw['lang'] ) && '' !== $raw['lang'] ? $this->sanitize_lang( $raw['lang'] ) : $defaults['lang'];
		$theme   = isset( $raw['theme'] ) && '' !== $raw['theme'] ? $this->sanitize_theme( $raw['theme'] ) : $defaults['theme'];
		$accent  = $this->sanitize_accent( isset( $raw['accent'] ) ? $raw['accent'] : '' );
		$compact = $this->sanitize_compact( isset( $raw['compact'] ) ? $raw['compact'] : '' );
		$radius  = $this->sanitize_radius( isset( $raw['radius'] ) ? $raw['radius'] : '' );

		if ( '' === $type || '' === $slug ) {
			return $this->comment( 'chefsolver-embeds: missing or invalid type/slug' );
		}

		// Resolve the entry from the manifest first (source of truth).
		$entry     = ChefSolver_Embeds_Manifest::lookup( $type, $slug, $lang );
		$embed_url = '';
		$title     = '';
		$height    = isset( $raw['height'] ) && '' !== $raw['height'] ? $this->sanitize_height( $raw['height'] ) : 0;

		if ( null !== $entry ) {
			$embed_url = $entry['embedUrl'];
			$title     = $entry['title'];
			if ( 0 === $height ) {
				$height = (int) $entry['height'];
			}
		} else {
			// Safe fallback: build from a known per-type template.
			$embed_url = $this->fallback_url( $type, $slug, $lang );
			$title     = sprintf( '%s (%s)', $slug, strtoupper( $lang ) );
		}

		if ( 0 === $height ) {
			$height = $defaults['height'];
		}

		// Defense in depth: never emit an iframe to a non-ChefSolver host.
		if ( '' === $embed_url || ! ChefSolver_Embeds_Manifest::is_allowed_embed_url( $embed_url ) ) {
			return $this->comment( 'chefsolver-embeds: no valid embed for this type/slug/lang' );
		}

		// Append style query params (only the ones that are set / non-default).
		$query = array( 'theme' => $theme );
		if ( '' !== $accent ) {
			$query['accent'] = $accent;
		}
		if ( '1' === $compact ) {
			$query['compact'] = '1';
		}
		if ( '' !== $radius ) {
			$query['radius'] = $radius;
		}
		$src = add_query_arg( array_map( 'rawurlencode', $query ), $embed_url );

		$iframe_title = '' !== $title ? $title : __( 'ChefSolver embed', 'chefsolver-embeds' );

		return sprintf(
			'<div class="chefsolver-embed"><iframe class="chefsolver-embed__iframe" src="%1$s" title="%2$s" height="%3$d" width="100%%" loading="lazy" style="border:0;width:100%%;max-width:640px;display:block;" referrerpolicy="strict-origin-when-cross-origin"></iframe></div>',
			esc_url( $src ),
			esc_attr( $iframe_title ),
			(int) $height
		);
	}

	/**
	 * Build a fallback embed URL from a known per-type template.
	 *
	 * @param string $type Embed type.
	 * @param string $slug Lookup slug.
	 * @param string $lang Language.
	 * @return string Absolute URL, or '' if the type has no known template.
	 */
	protected function fallback_url( $type, $slug, $lang ) {
		$templates = CHEFSOLVER_EMBEDS_PATH_TEMPLATES;
		if ( empty( $templates[ $type ] ) ) {
			return '';
		}
		$path = str_replace(
			array( '%lang%', '%slug%' ),
			array( rawurlencode( $lang ), implode( '/', array_map( 'rawurlencode', explode( '/', $slug ) ) ) ),
			$templates[ $type ]
		);
		return 'https://chefsolver.com' . $path;
	}

	/**
	 * Render an HTML comment (used for graceful, invisible failures).
	 *
	 * @param string $msg Message.
	 * @return string
	 */
	protected function comment( $msg ) {
		return '<!-- ' . esc_html( $msg ) . ' -->';
	}

	/**
	 * Tiny frontend style for the container (local, inline — no remote asset).
	 */
	public function register_frontend_style() {
		$handle = 'chefsolver-embeds';
		wp_register_style( $handle, false, array(), CHEFSOLVER_EMBEDS_VERSION );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, '.chefsolver-embed{margin:1rem 0;}.chefsolver-embed__iframe{border:0;width:100%;max-width:640px;display:block;}' );
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * Gutenberg block (server-side rendered)
	 * ───────────────────────────────────────────────────────────────────── */

	/**
	 * Register the editor script + the server-rendered block.
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return; // Classic-only site; shortcodes still work.
		}

		wp_register_script(
			'chefsolver-embeds-editor',
			CHEFSOLVER_EMBEDS_URL . 'blocks/embed/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch', 'wp-server-side-render' ),
			CHEFSOLVER_EMBEDS_VERSION,
			true
		);

		wp_localize_script(
			'chefsolver-embeds-editor',
			'ChefSolverEmbedsData',
			array(
				'langs'    => CHEFSOLVER_EMBEDS_LANGS,
				'themes'   => CHEFSOLVER_EMBEDS_THEMES,
				'types'    => ChefSolver_Embeds_Manifest::types(),
				'defaults' => $this->defaults(),
			)
		);

		register_block_type(
			CHEFSOLVER_EMBEDS_DIR . 'blocks/embed',
			array( 'render_callback' => array( $this, 'render_block' ) )
		);
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * REST (editor search — local same-site endpoint, no remote JS)
	 * ───────────────────────────────────────────────────────────────────── */

	/**
	 * Register REST routes used by the block editor.
	 */
	public function register_rest() {
		register_rest_route(
			'chefsolver-embeds/v1',
			'/entries',
			array(
				'methods'             => 'GET',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'search'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'type'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'lang'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'per_page' => array( 'sanitize_callback' => 'absint' ),
				),
				'callback'            => array( $this, 'rest_entries' ),
			)
		);

		register_rest_route(
			'chefsolver-embeds/v1',
			'/meta',
			array(
				'methods'             => 'GET',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'callback'            => array( $this, 'rest_meta' ),
			)
		);
	}

	/**
	 * REST: search manifest entries for the editor.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function rest_entries( $request ) {
		$entries = ChefSolver_Embeds_Manifest::search(
			array(
				'search'   => (string) $request->get_param( 'search' ),
				'type'     => $this->sanitize_type( (string) $request->get_param( 'type' ) ),
				'lang'     => (string) $request->get_param( 'lang' ),
				'per_page' => (int) $request->get_param( 'per_page' ),
			)
		);
		return rest_ensure_response(
			array_map(
				function ( $e ) {
					return array(
						'type'   => $e['type'],
						'slug'   => $e['slug'],
						'lang'   => $e['lang'],
						'title'  => $e['title'],
						'height' => $e['height'],
					);
				},
				$entries
			)
		);
	}

	/**
	 * REST: manifest availability + types + counts for the editor.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_meta() {
		$manifest = ChefSolver_Embeds_Manifest::get();
		return rest_ensure_response(
			array(
				'available' => null !== $manifest,
				'types'     => ChefSolver_Embeds_Manifest::types(),
				'langs'     => CHEFSOLVER_EMBEDS_LANGS,
				'themes'    => CHEFSOLVER_EMBEDS_THEMES,
				'counts'    => null !== $manifest ? $manifest['counts'] : null,
			)
		);
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * Settings page
	 * ───────────────────────────────────────────────────────────────────── */

	/**
	 * Add the Settings > ChefSolver Embeds page.
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'ChefSolver Embeds', 'chefsolver-embeds' ),
			__( 'ChefSolver Embeds', 'chefsolver-embeds' ),
			'manage_options',
			'chefsolver-embeds',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, section and fields via the Settings API.
	 */
	public function register_settings() {
		register_setting(
			'chefsolver_embeds_group',
			CHEFSOLVER_EMBEDS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_options' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			'chefsolver_embeds_main',
			__( 'Defaults & cache', 'chefsolver-embeds' ),
			'__return_false',
			'chefsolver-embeds'
		);

		add_settings_field( 'default_lang', __( 'Default language', 'chefsolver-embeds' ), array( $this, 'field_default_lang' ), 'chefsolver-embeds', 'chefsolver_embeds_main' );
		add_settings_field( 'default_theme', __( 'Default theme', 'chefsolver-embeds' ), array( $this, 'field_default_theme' ), 'chefsolver-embeds', 'chefsolver_embeds_main' );
		add_settings_field( 'default_height', __( 'Default height (px)', 'chefsolver-embeds' ), array( $this, 'field_default_height' ), 'chefsolver-embeds', 'chefsolver_embeds_main' );
		add_settings_field( 'cache_ttl', __( 'Manifest cache TTL (seconds)', 'chefsolver-embeds' ), array( $this, 'field_cache_ttl' ), 'chefsolver-embeds', 'chefsolver_embeds_main' );
	}

	/**
	 * Sanitize the options array.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$input = is_array( $input ) ? $input : array();
		$ttl   = isset( $input['cache_ttl'] ) ? (int) $input['cache_ttl'] : CHEFSOLVER_EMBEDS_DEFAULT_TTL;
		if ( $ttl < 300 ) {
			$ttl = 300;
		}
		if ( $ttl > WEEK_IN_SECONDS ) {
			$ttl = WEEK_IN_SECONDS;
		}
		return array(
			'default_lang'   => $this->sanitize_lang( isset( $input['default_lang'] ) ? $input['default_lang'] : 'en' ),
			'default_theme'  => $this->sanitize_theme( isset( $input['default_theme'] ) ? $input['default_theme'] : 'auto' ),
			'default_height' => $this->sanitize_height( isset( $input['default_height'] ) ? $input['default_height'] : CHEFSOLVER_EMBEDS_DEFAULT_HEIGHT ),
			'cache_ttl'      => $ttl,
		);
	}

	/**
	 * Field: default language.
	 */
	public function field_default_lang() {
		$current = $this->sanitize_lang( $this->get_opt( 'default_lang', 'en' ) );
		echo '<select name="' . esc_attr( CHEFSOLVER_EMBEDS_OPTION ) . '[default_lang]">';
		foreach ( CHEFSOLVER_EMBEDS_LANGS as $lang ) {
			printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $lang ), selected( $current, $lang, false ) );
		}
		echo '</select>';
	}

	/**
	 * Field: default theme.
	 */
	public function field_default_theme() {
		$current = $this->sanitize_theme( $this->get_opt( 'default_theme', 'auto' ) );
		echo '<select name="' . esc_attr( CHEFSOLVER_EMBEDS_OPTION ) . '[default_theme]">';
		foreach ( CHEFSOLVER_EMBEDS_THEMES as $theme ) {
			printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $theme ), selected( $current, $theme, false ) );
		}
		echo '</select>';
	}

	/**
	 * Field: default height.
	 */
	public function field_default_height() {
		$current = $this->sanitize_height( $this->get_opt( 'default_height', CHEFSOLVER_EMBEDS_DEFAULT_HEIGHT ) );
		printf(
			'<input type="number" min="%1$d" max="%2$d" step="1" name="%3$s[default_height]" value="%4$d" />',
			(int) CHEFSOLVER_EMBEDS_MIN_HEIGHT,
			(int) CHEFSOLVER_EMBEDS_MAX_HEIGHT,
			esc_attr( CHEFSOLVER_EMBEDS_OPTION ),
			(int) $current
		);
	}

	/**
	 * Field: cache TTL.
	 */
	public function field_cache_ttl() {
		$current = (int) $this->get_opt( 'cache_ttl', CHEFSOLVER_EMBEDS_DEFAULT_TTL );
		printf(
			'<input type="number" min="300" max="%1$d" step="1" name="%2$s[cache_ttl]" value="%3$d" /> <span class="description">%4$s</span>',
			(int) WEEK_IN_SECONDS,
			esc_attr( CHEFSOLVER_EMBEDS_OPTION ),
			(int) $current,
			esc_html__( 'Minimum 300 seconds.', 'chefsolver-embeds' )
		);
	}

	/**
	 * Render the settings page (capability-gated by add_options_page).
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$manifest  = ChefSolver_Embeds_Manifest::get();
		$available = null !== $manifest;
		$total     = $available ? (int) $manifest['counts']['total'] : 0;
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'ChefSolver Embeds', 'chefsolver-embeds' ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'chefsolver_embeds_group' );
				do_settings_sections( 'chefsolver-embeds' );
				submit_button();
				?>
			</form>

			<hr />
			<h2><?php echo esc_html__( 'Manifest', 'chefsolver-embeds' ); ?></h2>
			<p>
				<?php
				if ( $available ) {
					/* translators: %d: number of embeds available. */
					echo esc_html( sprintf( __( 'Manifest cached: %d embeds available.', 'chefsolver-embeds' ), $total ) );
				} else {
					echo esc_html__( 'Manifest not currently available (offline or not yet fetched). Shortcodes still render via safe fallback URLs.', 'chefsolver-embeds' );
				}
				?>
			</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="chefsolver_embeds_refresh" />
				<?php wp_nonce_field( 'chefsolver_embeds_refresh' ); ?>
				<?php submit_button( __( 'Refresh manifest', 'chefsolver-embeds' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the "Refresh manifest" action (capability + nonce protected).
	 */
	public function handle_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'chefsolver-embeds' ) );
		}
		check_admin_referer( 'chefsolver_embeds_refresh' );

		ChefSolver_Embeds_Manifest::refresh();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                    => 'chefsolver-embeds',
					'chefsolver_embeds_notice' => 'refreshed',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}
}
