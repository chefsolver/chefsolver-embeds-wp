<?php
/**
 * Manifest fetching, validation, caching and lookup.
 *
 * @package ChefSolver_Embeds
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads the public ChefSolver embed manifest, validates it, caches it in a
 * transient, and offers lookup/search over the normalized entries.
 */
class ChefSolver_Embeds_Manifest {

	/**
	 * In-request lookup index (type|lang|slug => entry), built lazily.
	 *
	 * @var array|null
	 */
	protected static $index = null;

	/**
	 * Resolve the configured cache TTL (seconds), clamped to a sane range.
	 *
	 * @return int
	 */
	public static function ttl() {
		$options = get_option( CHEFSOLVER_EMBEDS_OPTION, array() );
		$ttl     = isset( $options['cache_ttl'] ) ? (int) $options['cache_ttl'] : CHEFSOLVER_EMBEDS_DEFAULT_TTL;
		if ( $ttl < 300 ) {
			$ttl = 300; // never hammer the origin more than every 5 minutes.
		}
		if ( $ttl > WEEK_IN_SECONDS ) {
			$ttl = WEEK_IN_SECONDS;
		}
		return $ttl;
	}

	/**
	 * Get the normalized manifest (cached). Returns null when unavailable.
	 *
	 * @param bool $force Bypass the transient cache and refetch.
	 * @return array|null {entries:array, counts:array, fetched:int}
	 */
	public static function get( $force = false ) {
		if ( ! $force ) {
			$cached = get_transient( CHEFSOLVER_EMBEDS_TRANSIENT );
			if ( is_array( $cached ) && isset( $cached['entries'] ) ) {
				return $cached;
			}
		}

		$raw = self::fetch_remote();
		if ( is_wp_error( $raw ) || ! is_array( $raw ) ) {
			return null;
		}

		$normalized = self::validate_and_normalize( $raw );
		if ( empty( $normalized['entries'] ) ) {
			return null;
		}

		set_transient( CHEFSOLVER_EMBEDS_TRANSIENT, $normalized, self::ttl() );
		self::$index = null; // reset in-request index.
		return $normalized;
	}

	/**
	 * Force a refresh: clear the transient and refetch.
	 *
	 * @return array|null
	 */
	public static function refresh() {
		delete_transient( CHEFSOLVER_EMBEDS_TRANSIENT );
		self::$index = null;
		return self::get( true );
	}

	/**
	 * Fetch + JSON-decode the remote manifest via the WordPress HTTP API.
	 *
	 * @return array|WP_Error Decoded JSON array, or WP_Error on failure.
	 */
	protected static function fetch_remote() {
		$response = wp_remote_get(
			CHEFSOLVER_EMBEDS_MANIFEST_URL,
			array(
				'timeout'     => 15,
				'redirection' => 3,
				'sslverify'   => true,
				'user-agent'  => 'ChefSolver-Embeds/' . CHEFSOLVER_EMBEDS_VERSION . '; ' . home_url( '/' ),
				'headers'     => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'chefsolver_embeds_http', 'Unexpected HTTP status from manifest.' );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			return new WP_Error( 'chefsolver_embeds_empty', 'Empty manifest body.' );
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'chefsolver_embeds_json', 'Manifest is not valid JSON.' );
		}
		return $data;
	}

	/**
	 * Validate the manifest structure and normalize each usable entry.
	 *
	 * A usable entry must have a valid type, lang, title, and an embedUrl whose
	 * host is an allowed ChefSolver host. Entries missing those are skipped.
	 *
	 * @param array $raw Decoded manifest.
	 * @return array {entries:array, counts:array, fetched:int}
	 */
	public static function validate_and_normalize( $raw ) {
		$list = array();
		if ( isset( $raw['embeds'] ) && is_array( $raw['embeds'] ) ) {
			$list = $raw['embeds'];
		}

		$entries = array();
		$counts  = array(
			'byType'     => array(),
			'byLang'     => array(),
			'byTypeLang' => array(),
			'total'      => 0,
		);

		foreach ( $list as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$entry = self::normalize_entry( $item );
			if ( null === $entry ) {
				continue;
			}
			$entries[] = $entry;

			$t = $entry['type'];
			$l = $entry['lang'];
			$counts['byType'][ $t ]              = ( isset( $counts['byType'][ $t ] ) ? $counts['byType'][ $t ] : 0 ) + 1;
			$counts['byLang'][ $l ]              = ( isset( $counts['byLang'][ $l ] ) ? $counts['byLang'][ $l ] : 0 ) + 1;
			$key                                 = $t . '|' . $l;
			$counts['byTypeLang'][ $key ]        = ( isset( $counts['byTypeLang'][ $key ] ) ? $counts['byTypeLang'][ $key ] : 0 ) + 1;
			++$counts['total'];
		}

		return array(
			'entries' => $entries,
			'counts'  => $counts,
			'fetched' => time(),
		);
	}

	/**
	 * Normalize one raw manifest entry into the compact shape the plugin uses.
	 *
	 * @param array $item Raw entry.
	 * @return array|null Normalized entry or null if unusable.
	 */
	protected static function normalize_entry( $item ) {
		$type = isset( $item['type'] ) ? strtolower( (string) $item['type'] ) : '';
		if ( ! preg_match( '/^[a-z][a-z0-9-]{0,40}$/', $type ) ) {
			return null;
		}

		$lang = isset( $item['lang'] ) ? strtolower( (string) $item['lang'] ) : '';
		if ( ! preg_match( '/^[a-z]{2}$/', $lang ) ) {
			return null;
		}

		$embed_url = isset( $item['embedUrl'] ) ? (string) $item['embedUrl'] : '';
		if ( ! self::is_allowed_embed_url( $embed_url ) ) {
			return null;
		}

		$slug = self::derive_slug( $item, $lang );
		if ( '' === $slug ) {
			return null;
		}

		$title = isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '';
		if ( '' === $title ) {
			$title = $slug;
		}

		$origin_url = isset( $item['originUrl'] ) ? esc_url_raw( (string) $item['originUrl'] ) : '';

		$height = isset( $item['heightRecommended'] ) ? (int) $item['heightRecommended'] : CHEFSOLVER_EMBEDS_DEFAULT_HEIGHT;
		if ( $height < CHEFSOLVER_EMBEDS_MIN_HEIGHT || $height > CHEFSOLVER_EMBEDS_MAX_HEIGHT ) {
			$height = CHEFSOLVER_EMBEDS_DEFAULT_HEIGHT;
		}

		return array(
			'type'      => $type,
			'slug'      => $slug,
			'lang'      => $lang,
			'title'     => $title,
			'embedUrl'  => esc_url_raw( $embed_url ),
			'originUrl' => $origin_url,
			'height'    => $height,
		);
	}

	/**
	 * Derive a stable lookup slug for an entry.
	 *
	 * - explicit `slug` (generic converters);
	 * - `ingredient/conversion` compound (ingredient converters);
	 * - else the path after /embed/{lang}/ (future/unknown types).
	 *
	 * @param array  $item Raw entry.
	 * @param string $lang Two-letter language.
	 * @return string Normalized slug, or '' if none could be derived.
	 */
	protected static function derive_slug( $item, $lang ) {
		if ( ! empty( $item['slug'] ) ) {
			return self::clean_slug( (string) $item['slug'] );
		}
		if ( ! empty( $item['ingredient'] ) && ! empty( $item['conversion'] ) ) {
			return self::clean_slug( $item['ingredient'] . '/' . $item['conversion'] );
		}
		$path = (string) wp_parse_url( (string) ( isset( $item['embedUrl'] ) ? $item['embedUrl'] : '' ), PHP_URL_PATH );
		$path = preg_replace( '#^/embed/' . preg_quote( $lang, '#' ) . '/#', '', trim( $path, '/' ) . '/' );
		return self::clean_slug( trim( (string) $path, '/' ) );
	}

	/**
	 * Strict slug cleaner: lowercase; only [a-z0-9_/-]; no traversal.
	 *
	 * @param string $slug Raw slug.
	 * @return string Cleaned slug, or '' if invalid.
	 */
	public static function clean_slug( $slug ) {
		$raw = strtolower( trim( (string) $slug ) );
		// Reject path traversal up front, before dots are stripped.
		if ( '' === $raw || false !== strpos( $raw, '..' ) ) {
			return '';
		}
		$slug = preg_replace( '#[^a-z0-9_/-]#', '', $raw );
		$slug = preg_replace( '#/{2,}#', '/', (string) $slug );
		$slug = trim( (string) $slug, '/' );
		return '' === $slug ? '' : $slug;
	}

	/**
	 * Whether an embed URL is https and on an allowed ChefSolver host.
	 *
	 * @param string $url URL to check.
	 * @return bool
	 */
	public static function is_allowed_embed_url( $url ) {
		if ( '' === $url ) {
			return false;
		}
		$parts = wp_parse_url( $url );
		if ( empty( $parts['scheme'] ) || 'https' !== strtolower( $parts['scheme'] ) ) {
			return false;
		}
		if ( empty( $parts['host'] ) ) {
			return false;
		}
		return in_array( strtolower( $parts['host'] ), CHEFSOLVER_EMBEDS_ALLOWED_HOSTS, true );
	}

	/**
	 * Look up a single normalized entry by type + slug + lang.
	 *
	 * @param string $type Embed type.
	 * @param string $slug Lookup slug.
	 * @param string $lang Two-letter language.
	 * @return array|null
	 */
	public static function lookup( $type, $slug, $lang ) {
		$manifest = self::get();
		if ( null === $manifest ) {
			return null;
		}
		if ( null === self::$index ) {
			self::$index = array();
			foreach ( $manifest['entries'] as $entry ) {
				self::$index[ $entry['type'] . '|' . $entry['lang'] . '|' . $entry['slug'] ] = $entry;
			}
		}
		$key = $type . '|' . $lang . '|' . $slug;
		return isset( self::$index[ $key ] ) ? self::$index[ $key ] : null;
	}

	/**
	 * Search entries for the block editor. Filters by free text/type/lang.
	 *
	 * @param array $args {search:string, type:string, lang:string, per_page:int}.
	 * @return array List of normalized entries (capped).
	 */
	public static function search( $args ) {
		$manifest = self::get();
		if ( null === $manifest ) {
			return array();
		}
		$search   = isset( $args['search'] ) ? strtolower( trim( (string) $args['search'] ) ) : '';
		$type     = isset( $args['type'] ) ? (string) $args['type'] : '';
		$lang     = isset( $args['lang'] ) ? (string) $args['lang'] : '';
		$per_page = isset( $args['per_page'] ) ? (int) $args['per_page'] : 50;
		$per_page = max( 1, min( 100, $per_page ) );

		$out = array();
		foreach ( $manifest['entries'] as $entry ) {
			if ( '' !== $type && $entry['type'] !== $type ) {
				continue;
			}
			if ( '' !== $lang && $entry['lang'] !== $lang ) {
				continue;
			}
			if ( '' !== $search ) {
				$haystack = strtolower( $entry['title'] . ' ' . $entry['slug'] );
				if ( false === strpos( $haystack, $search ) ) {
					continue;
				}
			}
			$out[] = $entry;
			if ( count( $out ) >= $per_page ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * List the distinct types present in the manifest (for editor filters).
	 *
	 * @return array
	 */
	public static function types() {
		$manifest = self::get();
		if ( null === $manifest || empty( $manifest['counts']['byType'] ) ) {
			return array_keys( CHEFSOLVER_EMBEDS_PATH_TEMPLATES );
		}
		return array_keys( $manifest['counts']['byType'] );
	}
}
