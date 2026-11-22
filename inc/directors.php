<?php

/**
 * The space's directors, past and present, from Companies House, for the
 * Directors widget, the SLMS Directors block and the [slms_directors]
 * shortcode. See the README for setting it up.
 *
 * The list is fetched at most once a day, by a daily cron job, or by a page
 * if that was missed. It's kept in an option rather than a transient, so an
 * object cache (Redis) clearing it doesn't empty the list or fetch it again.
 * If a fetch fails, the last list stays and it tries again after an hour.
 */
class SLMS_Company_Directors {

	const DEFAULT_COMPANY = '08706591';
	const API_BASE        = 'https://api.company-information.service.gov.uk';

	const OPTION_CACHE    = 'slms_directors_cache';
	const OPTION_SETTINGS = 'slms_directors_settings';
	const CRON_HOOK       = 'slms_directors_refresh';
	const LOCK            = 'slms_directors_lock';

	// How long a list is kept, a little under a day so the daily cron job
	// isn't skipped by a few seconds
	const FRESH_FOR = DAY_IN_SECONDS - 15 * MINUTE_IN_SECONDS;

	// How long to wait after a failed fetch before trying again
	const RETRY_AFTER = HOUR_IN_SECONDS;

	// Companies House's roles that count as a director
	const ROLES = array( 'director', 'corporate-director', 'nominee-director', 'corporate-nominee-director' );

	// Values of the dates and bold options that mean off
	const OFF = array( false, 0, '0', 'no', 'false', 'off' );

	public static function init() {
		add_shortcode( 'slms_directors', array( __CLASS__, 'shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register_block' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ) );
		add_action( 'switch_theme', array( __CLASS__, 'unschedule' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'directors' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_slms_directors_refresh', array( __CLASS__, 'handle_refresh' ) );
	}

	// The theme has no activation hook, so the job is added whenever it's missing
	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	public static function settings() {
		$saved = get_option( self::OPTION_SETTINGS, array() );

		return wp_parse_args( is_array( $saved ) ? $saved : array(), array(
			'company'   => self::DEFAULT_COMPANY,
			'overrides' => '',
		) );
	}

	public static function company() {
		return self::settings()['company'] ?: self::DEFAULT_COMPANY;
	}

	// Set in wp-config.php, so it isn't in the database
	private static function api_key() {
		return defined( 'SLMS_CH_API_KEY' ) ? (string) SLMS_CH_API_KEY : '';
	}

	private static function cache() {
		$cache = get_option( self::OPTION_CACHE, array() );

		return is_array( $cache ) ? $cache : array();
	}

	/**
	 * The directors, oldest appointment first, each with name (as Companies
	 * House has it), appointed and resigned ('' while still a director).
	 * Fetched again first if the list is a day old.
	 */
	public static function directors() {
		$cache   = self::cache();
		$company = self::company();
		$same    = ( $cache['company'] ?? '' ) === $company;

		$fresh   = $same && ! empty( $cache['fetched'] ) && time() - (int) $cache['fetched'] < self::FRESH_FOR;
		$waiting = $same && ! empty( $cache['attempted'] ) && time() - (int) $cache['attempted'] < self::RETRY_AFTER;

		if ( ! $fresh && ! $waiting ) {
			self::refresh();
			$cache = self::cache();
			$same  = ( $cache['company'] ?? '' ) === $company;
		}

		return $same ? ( $cache['directors'] ?? array() ) : array();
	}

	/**
	 * Fetches the list and keeps it, or keeps the last one and the error.
	 * A lock stops two pages fetching at once; the Refresh now button skips it.
	 */
	public static function refresh( $force = false ) {
		if ( ! $force && get_transient( self::LOCK ) ) {
			return false;
		}
		set_transient( self::LOCK, 1, MINUTE_IN_SECONDS );

		$company = self::company();
		$cache   = self::cache();

		if ( ( $cache['company'] ?? '' ) !== $company ) {
			$cache = array( 'company' => $company );
		}

		$cache['attempted'] = time();
		$result = self::fetch( $company );

		if ( is_wp_error( $result ) ) {
			$cache['error'] = $result->get_error_message();
		} else {
			$cache['directors'] = $result;
			$cache['fetched']   = time();
			$cache['error']     = '';
		}

		update_option( self::OPTION_CACHE, $cache, false );
		delete_transient( self::LOCK );

		return ! is_wp_error( $result );
	}

	/**
	 * The company's directors from the Companies House API, a page of 100
	 * officers at a time, or a WP_Error
	 */
	private static function fetch( $company ) {
		$key = self::api_key();
		if ( '' === $key ) {
			return new WP_Error( 'slms_directors_no_key', 'SLMS_CH_API_KEY is not defined in wp-config.php.' );
		}

		$directors = array();
		$start     = 0;

		do {
			$url = add_query_arg( array(
				'items_per_page' => 100,
				'start_index'    => $start,
			), self::API_BASE . '/company/' . rawurlencode( $company ) . '/officers' );

			$response = wp_remote_get( $url, array(
				'timeout' => 10,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $key . ':' ),
					'Accept'        => 'application/json',
				),
			) );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) {
				return new WP_Error( 'slms_directors_http', sprintf( 'Companies House returned HTTP %d.', $code ) );
			}

			$body  = json_decode( wp_remote_retrieve_body( $response ), true );
			$items = isset( $body['items'] ) && is_array( $body['items'] ) ? $body['items'] : array();
			$total = (int) ( $body['total_results'] ?? 0 );

			foreach ( $items as $item ) {
				if ( empty( $item['name'] ) || ! in_array( $item['officer_role'] ?? '', self::ROLES, true ) ) {
					continue;
				}
				$directors[] = array(
					'name'      => (string) $item['name'],
					'appointed' => (string) ( $item['appointed_on'] ?? '' ),
					'resigned'  => (string) ( $item['resigned_on'] ?? '' ),
				);
			}

			$start += count( $items );
		} while ( $items && $start < $total && $start < 1000 );

		// By appointment, and those appointed the same day by surname
		usort( $directors, function ( $a, $b ) {
			return array( $a['appointed'], self::normalise( $a['name'] ) ) <=> array( $b['appointed'], self::normalise( $b['name'] ) );
		} );

		return $directors;
	}

	/**
	 * A Companies House name for matching: "ATKIN, Jo Nicol, Mr." as
	 * "ATKIN, JO NICOL", without the title
	 */
	public static function normalise( $name ) {
		$parts = array_map( 'trim', explode( ',', $name ) );
		$key   = implode( ', ', array_filter( array_slice( $parts, 0, 2 ), 'strlen' ) );

		return strtoupper( preg_replace( '/\s+/', ' ', $key ) );
	}

	/**
	 * The display names set in Settings → Directors, one per line, as
	 * "LYNCH, Thomas Kevin = Tom Lynch", by normalised name
	 */
	private static function overrides() {
		$names = array();

		foreach ( preg_split( '/\R/', self::settings()['overrides'] ) as $line ) {
			if ( false === strpos( $line, '=' ) ) {
				continue;
			}
			list( $from, $to ) = array_map( 'trim', explode( '=', $line, 2 ) );
			if ( '' !== $from && '' !== $to ) {
				$names[ self::normalise( $from ) ] = $to;
			}
		}

		return $names;
	}

	/**
	 * Capitals for names Companies House writes in capitals: "O'NEIL" as
	 * "O'Neil", "MCDONALD" as "McDonald", "SMITH-JONES" as "Smith-Jones"
	 */
	public static function title_case( $s ) {
		$s = mb_convert_case( mb_strtolower( trim( preg_replace( '/\s+/u', ' ', $s ) ) ), MB_CASE_TITLE, 'UTF-8' );
		$s = preg_replace_callback( "/\\b([OD]['’])(\\p{Ll})/u", function ( $m ) {
			return $m[1] . mb_strtoupper( $m[2] );
		}, $s );

		return preg_replace_callback( '/\bMc(\p{Ll})/u', function ( $m ) {
			return 'Mc' . mb_strtoupper( $m[1] );
		}, $s );
	}

	/**
	 * A name as shown: its display name if one is set, or else the forenames
	 * then the surname, so "LYNCH, Thomas Kevin" is Thomas Kevin Lynch
	 */
	public static function display_name( $raw, $overrides = null ) {
		if ( null === $overrides ) {
			$overrides = self::overrides();
		}

		$key = self::normalise( $raw );
		if ( isset( $overrides[ $key ] ) ) {
			return $overrides[ $key ];
		}

		$parts     = array_map( 'trim', explode( ',', $raw ) );
		$surname   = $parts[0] ?? '';
		$forenames = $parts[1] ?? '';

		// A company that's a director has no forenames
		if ( '' === $forenames ) {
			return self::title_case( $surname );
		}

		return self::title_case( $forenames . ' ' . $surname );
	}

	// "2013-09-26" as "September 2013"
	private static function month( $date ) {
		$time = $date ? strtotime( $date . ' 12:00:00 UTC' ) : false;

		return $time ? wp_date( 'F Y', $time, new DateTimeZone( 'UTC' ) ) : '';
	}

	/**
	 * The list, as a <ul>, "Name (September 2013 – July 2016)" with current
	 * directors in bold and "Present", or with dates off, names alone.
	 *
	 * @param array $args show: all, current or past. order: asc (oldest
	 *                    first) or desc. dates and bold: yes or no.
	 */
	public static function render( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'show'  => 'all',
			'order' => 'asc',
			'dates' => 'yes',
			'bold'  => 'yes',
		) );

		$dates     = ! in_array( $args['dates'], self::OFF, true );
		$bold      = ! in_array( $args['bold'], self::OFF, true );
		$directors = self::directors();

		// Nothing for visitors, but why for admins
		if ( ! $directors ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<p class="slms-directors-error">' . esc_html( trim( 'Directors: no list yet. ' . ( self::cache()['error'] ?? '' ) ) ) . '</p>';
			}
			return '';
		}

		if ( 'current' === $args['show'] ) {
			$directors = array_filter( $directors, function ( $d ) { return '' === $d['resigned']; } );
		} elseif ( 'past' === $args['show'] ) {
			$directors = array_filter( $directors, function ( $d ) { return '' !== $d['resigned']; } );
		}

		if ( 'desc' === $args['order'] ) {
			$directors = array_reverse( $directors );
		}

		$overrides = self::overrides();
		$items     = '';

		foreach ( $directors as $d ) {
			$current = '' === $d['resigned'];
			$class   = 'slms-director' . ( $current ? ' slms-director--current' : '' );
			$name    = esc_html( self::display_name( $d['name'], $overrides ) );

			if ( ! $dates ) {
				$items .= '<li class="' . $class . '">' . $name . '</li>';
				continue;
			}

			if ( $current && $bold ) {
				$name = '<strong>' . $name . '</strong>';
			}
			$until  = $current ? 'Present' : esc_html( self::month( $d['resigned'] ) );
			$items .= '<li class="' . $class . '">' . $name . ' (' . esc_html( self::month( $d['appointed'] ) ) . ' &ndash; ' . $until . ')</li>';
		}

		return '<ul class="slms-directors">' . $items . '</ul>';
	}

	public static function shortcode( $atts ) {
		return self::render( shortcode_atts( array(
			'show'  => 'all',
			'order' => 'asc',
			'dates' => 'yes',
			'bold'  => 'yes',
		), $atts, 'slms_directors' ) );
	}

	// The block's editor script is registered here, by handle, as block.json's
	// file: paths don't resolve to a theme's address on every WordPress version
	public static function register_block() {
		wp_register_script(
			'slms-directors-editor',
			SLMS_STATIC_URI . '/blocks/directors/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
			filemtime( SLMS_THEME_DIR . '/static/blocks/directors/editor.js' )
		);

		register_block_type( SLMS_THEME_DIR . '/static/blocks/directors', array(
			'render_callback' => function ( $attributes ) {
				return '<div ' . get_block_wrapper_attributes() . '>' . self::render( (array) $attributes ) . '</div>';
			},
		) );
	}

	public static function admin_menu() {
		add_options_page( 'Directors', 'Directors', 'manage_options', 'slms-directors', array( __CLASS__, 'settings_page' ) );
	}

	public static function register_settings() {
		register_setting( 'slms_directors', self::OPTION_SETTINGS, array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => function ( $value ) {
				$value   = is_array( $value ) ? $value : array();
				$company = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) ( $value['company'] ?? '' ) ) );

				return array(
					'company'   => $company ?: self::DEFAULT_COMPANY,
					'overrides' => sanitize_textarea_field( (string) ( $value['overrides'] ?? '' ) ),
				);
			},
		) );
	}

	// The Refresh now button: fetches straight away, whenever the last fetch was
	public static function handle_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'slms_directors_refresh' );

		$ok = self::refresh( true );
		wp_safe_redirect( add_query_arg( 'refreshed', $ok ? '1' : '0', admin_url( 'options-general.php?page=slms-directors' ) ) );
		exit;
	}

	// Settings → Directors: the company, display names, how the last fetch went and a preview
	public static function settings_page() {
		$settings  = self::settings();
		$cache     = self::cache();
		$format    = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$refreshed = isset( $_GET['refreshed'] ) ? sanitize_key( $_GET['refreshed'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		$next      = wp_next_scheduled( self::CRON_HOOK );
		$name      = esc_attr( self::OPTION_SETTINGS );
		?>
		<div class="wrap">
			<h1>Directors</h1>

			<?php if ( null !== $refreshed ) : ?>
				<div class="notice notice-<?php echo '1' === $refreshed ? 'success' : 'error'; ?>"><p>
					<?php echo '1' === $refreshed ? 'Directors refreshed from Companies House.' : esc_html( 'Refresh failed: ' . ( $cache['error'] ?? '' ) ); ?>
				</p></div>
			<?php endif ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'slms_directors' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="slms-directors-company">Company number</label></th>
						<td><input id="slms-directors-company" class="regular-text" name="<?php echo $name; ?>[company]" value="<?php echo esc_attr( $settings['company'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="slms-directors-overrides">Display names</label></th>
						<td>
							<textarea id="slms-directors-overrides" class="large-text code" rows="10" name="<?php echo $name; ?>[overrides]"><?php echo esc_textarea( $settings['overrides'] ); ?></textarea>
							<p class="description">Optional, one per line, the Companies House name first: <code>LYNCH, Thomas Kevin = Tom Lynch</code>. Without one, the full name is shown, forenames first: Thomas Kevin Lynch.</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2>Status</h2>
			<table class="widefat striped" style="max-width: 48em">
				<tr><td>API key (SLMS_CH_API_KEY)</td><td><?php echo self::api_key() ? 'Set' : '<strong>Missing</strong>'; ?></td></tr>
				<tr><td>Last fetched</td><td><?php echo ! empty( $cache['fetched'] ) ? esc_html( wp_date( $format, (int) $cache['fetched'] ) ) : 'Never'; ?></td></tr>
				<tr><td>Last tried</td><td><?php echo ! empty( $cache['attempted'] ) ? esc_html( wp_date( $format, (int) $cache['attempted'] ) ) : 'Never'; ?></td></tr>
				<tr><td>Last error</td><td><?php echo esc_html( ( $cache['error'] ?? '' ) ?: 'None' ); ?></td></tr>
				<tr><td>Directors</td><td><?php echo count( $cache['directors'] ?? array() ); ?></td></tr>
				<tr><td>Next fetch</td><td><?php echo $next ? esc_html( wp_date( $format, $next ) ) : 'Not scheduled'; ?></td></tr>
			</table>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 1em">
				<input type="hidden" name="action" value="slms_directors_refresh">
				<?php wp_nonce_field( 'slms_directors_refresh' ); ?>
				<?php submit_button( 'Refresh now', 'secondary', 'submit', false ); ?>
			</form>

			<?php if ( ! empty( $cache['directors'] ) ) : ?>
				<h2>Preview</h2>
				<?php echo self::render(); ?>
				<h2>Companies House names</h2>
				<p class="description">To copy into Display names.</p>
				<pre><?php echo esc_html( implode( "\n", array_unique( array_map( array( __CLASS__, 'normalise' ), wp_list_pluck( $cache['directors'], 'name' ) ) ) ) ); ?></pre>
			<?php endif ?>
		</div>
		<?php
	}
}

SLMS_Company_Directors::init();
