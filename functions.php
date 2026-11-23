<?php

	define( 'SLMS_THEME_DIR', get_stylesheet_directory() );
	define( 'SLMS_STATIC_URI', get_stylesheet_directory_uri() . '/static' );
	// The space's Discourse forum: its feeds, events and links
	define( 'SLMS_FORUM_URL', 'https://discourse.southlondonmakerspace.org/' );

	require SLMS_THEME_DIR . '/inc/template.php';
	require SLMS_THEME_DIR . '/inc/hero.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-blog.php';
	require SLMS_THEME_DIR . '/inc/event-text.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-events.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-facility.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-forum.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-newsletter.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-supporter.php';
	require SLMS_THEME_DIR . '/inc/directors.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-directors.php';

	if ( ! class_exists( 'SLMS_Theme' ) ) {
		class SLMS_Theme {

			// Versions of the copies in static/vendor
			const MAPLIBRE_VERSION = '5.24.1';
			const PMTILES_VERSION = '4.5.0';
			const FONTAWESOME_VERSION = '7.3.1';

			public function __construct() {
				add_theme_support( 'post-thumbnails' );
				// WordPress writes the <title> and the feed links in the head
				add_theme_support( 'title-tag' );
				add_theme_support( 'automatic-feed-links' );
				// The block editor shows content in the theme's font and colours
				add_theme_support( 'editor-styles' );
				add_editor_style( array( 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600', 'static/css/editor.css' ) );
				add_filter( 'document_title_separator', array( $this, 'title_separator' ) );
				add_filter( 'document_title_parts', array( $this, 'title_parts' ) );

				add_action( 'init', array( $this, 'register_sidebars' ) );
				add_action( 'init', array( $this, 'register_nav_menus' ) );
				add_action( 'init', array( $this, 'register_block_styles' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'widgets_init', array( $this, 'register_widgets' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_widget_scripts' ) );
				add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_widget_scripts' ) );
				add_filter( 'excerpt_more', array( $this, 'excerpt_more' ) );
				add_filter( 'body_class', array( $this, 'body_class' ) );
				add_action( 'after_setup_theme', array( $this, 'image_sizes' ) );
				add_action( 'after_setup_theme', array( $this, 'woocommerce_setup' ) );
				add_filter( 'script_loader_src', array( $this, 'remove_query_string_versioning' ) );
				add_filter( 'style_loader_src', array( $this, 'remove_query_string_versioning' ) );
			}

			// Titles as "Join — South London Makerspace"
			public function title_separator() {
				return '&mdash;';
			}

			// The front page's title is the site's name alone, without its tagline
			public function title_parts( $parts ) {
				if ( is_front_page() ) {
					unset( $parts['tagline'] );
				}

				return $parts;
			}

			function remove_query_string_versioning( $src ) {
				// Keep the version on the theme's own files, so browsers fetch them again when they change
				if ( strpos( $src, SLMS_STATIC_URI . '/' ) === 0 )
					return $src;

				$parts = explode( '?', $src );

				return array_shift( $parts );
			}

			// Version a theme file by when it last changed
			function asset_version( $path ) {
				$file = SLMS_THEME_DIR . '/' . $path;

				return file_exists( $file ) ? filemtime( $file ) : null;
			}


			public function register_sidebars() {
				// The widget areas in the page, each widget a section
				$widgets = array(
					'before_widget' => '<section id="%1$s" class="widget %2$s">',
					'after_widget'  => '</section>',
					'before_title'  => '<h2 class="widgettitle">',
					'after_title'   => '</h2>',
				);

				register_sidebar( array( 'name' => 'Homepage', 'id' => 'slms_home_sidebar' ) + $widgets );

				// One Facility widget per facility, shown in a grid wherever the
				// Facilities widget is in the Homepage area
				register_sidebar( array(
					'name'	=> 'Facilities',
					'id'	=> 'slms_facilities',
					'description' => 'Add a Facility widget for each facility, in the order to show them. They show wherever the Facilities widget is in the Homepage area.',
					'before_widget' => '<section id="%1$s" class="facility %2$s">',
					'after_widget' => '</section>',
					'before_title' => '<h3 class="facility__title">',
					'after_title' => '</h3>'
				) );

				register_sidebar( array( 'name' => 'Single Post', 'id' => 'slms_single_post' ) + $widgets );

				// One Supporter widget per supporter, their logos shown in the
				// front page's footer (see base.php)
				register_sidebar( array(
					'name'          => 'Supporters',
					'id'            => 'slms_supporters',
					'description'   => 'Add a Supporter widget for each supporter, in the order to show them. Their logos show in the footer of the front page, before the copyright.',
					'before_widget' => '<li id="%1$s" class="supporter">',
					'after_widget'  => '</li>',
				) );
			}

			// The Supporter widget's Choose image button, on the Widgets screen
			// and in the Customizer: the media library and the script that opens it
			public function enqueue_widget_scripts( $hook = '' ) {
				if ( 'widgets.php' !== $hook && ! doing_action( 'customize_controls_enqueue_scripts' ) ) {
					return;
				}
				wp_enqueue_media();
				wp_enqueue_script( 'slms_supporter_widget', SLMS_STATIC_URI . '/js/supporter-widget.js', array(), $this->asset_version( 'static/js/supporter-widget.js' ), true );
			}

			public function register_nav_menus() {
				register_nav_menus( array(
					'slms_home_links'	=>	'Homepage Links',
					// The footer's last column, headed with the menu's name
					'slms_footer_links'	=>	'Footer Links'
				) );
			}

			// Steps: a numbered list with each number in a red circle (see
			// steps.less), for how-to-join style instructions
			public function register_block_styles() {
				wp_register_style( 'slms_steps', SLMS_STATIC_URI . '/css/steps.css', array(), $this->asset_version( 'static/css/steps.css' ) );

				register_block_style( 'core/list', array(
					'name'         => 'steps',
					'label'        => 'Steps',
					'style_handle' => 'slms_steps',
				) );
			}

			public function enqueue_styles() {
				// Only on pages with the map in their hero (see base.php)
				if ( 'map' === slms_hero() ) {
					wp_enqueue_style( 'maplibre-gl', SLMS_STATIC_URI . '/vendor/maplibre-gl/maplibre-gl.css', array(), self::MAPLIBRE_VERSION );
				}

				// For the facilities' icons
				if ( is_front_page() && is_active_widget( false, false, 'slms_facilities' ) ) {
					wp_enqueue_style( 'fontawesome', SLMS_STATIC_URI . '/vendor/fontawesome/css/all.min.css', array(), self::FONTAWESOME_VERSION );
				}

				wp_enqueue_style( 'slms_main', SLMS_STATIC_URI . '/css/main.css', array(), $this->asset_version( 'static/css/main.css' ) );
			}

			public function enqueue_scripts() {
				// Deferred, so they don't hold up the page, and run in this order
				$defer = array( 'strategy' => 'defer' );

				// The menu button's menu, on every page with the menu
				if ( has_nav_menu( 'slms_home_links' ) ) {
					wp_enqueue_script( 'slms_menu', SLMS_STATIC_URI . '/js/menu.js', array(), $this->asset_version( 'static/js/menu.js' ), $defer );
					// The icon on the buttons that open sub-menus
					wp_localize_script( 'slms_menu', 'slmsMenu', array( 'chevron' => slms_icon( 'chevron-down', '.75em' ) ) );
				}

				// The hero's gallery: its arrows, dots and turning (see base.php)
				if ( 'gallery' === slms_hero() ) {
					wp_enqueue_script( 'slms_gallery', SLMS_STATIC_URI . '/js/gallery.js', array(), $this->asset_version( 'static/js/gallery.js' ), $defer );
					wp_localize_script( 'slms_gallery', 'slmsGallery', array( 'icons' => array( 'previous' => slms_icon( 'chevron-left' ), 'next' => slms_icon( 'chevron-right' ), 'pause' => slms_icon( 'pause' ), 'play' => slms_icon( 'play' ) ) ) );
				}

				// main.js is the hero's map, so only on pages with it (see base.php)
				if ( 'map' !== slms_hero() ) {
					return;
				}

				wp_register_script( 'maplibre-gl', SLMS_STATIC_URI . '/vendor/maplibre-gl/maplibre-gl.js', array(), self::MAPLIBRE_VERSION, $defer );
				wp_register_script( 'pmtiles', SLMS_STATIC_URI . '/vendor/pmtiles/pmtiles.js', array(), self::PMTILES_VERSION, $defer );
				wp_register_script( 'slms_map_style', SLMS_STATIC_URI . '/js/map-style.js', array(), $this->asset_version( 'static/js/map-style.js' ), $defer );
				wp_enqueue_script( 'slms_main', SLMS_STATIC_URI . '/js/main.js', array( 'maplibre-gl', 'pmtiles', 'slms_map_style' ), $this->asset_version( 'static/js/main.js' ), $defer );
				// The map's files, the Find us page its pin links to, if published,
				// and its zoom buttons' icons
				wp_localize_script( 'slms_main', 'slmsMap', array(
					'base'   => SLMS_STATIC_URI . '/map',
					'findUs' => slms_page_url( 'find-us' ),
					'icons'  => array( 'plus' => slms_icon( 'plus' ), 'minus' => slms_icon( 'minus' ) ),
				) );
			}

			public function register_widgets() {
				register_widget( 'slms_newsletter' );
				register_widget( 'slms_forum' );
				register_widget( 'slms_blog' );
				register_widget( 'slms_events' );
				register_widget( 'slms_facility' );
				register_widget( 'slms_facilities' );
				register_widget( 'slms_directors' );
				register_widget( 'slms_supporter' );
			}

			// The featured image in the hero at the top of the page (see
			// base.php), cropped from the middle to the hero's shape, 2560 × 1100,
			// in smaller copies for smaller screens. See the README for what to
			// upload.
			public function image_sizes() {
				foreach ( array( 2560, 1920, 1280, 768 ) as $width ) {
					add_image_size( 'slms-band-' . $width, $width, round( $width * 1100 / 2560 ), true );
				}
			}

			// Shop pages use woocommerce.php, which shows the title in the page's
			// header, so WooCommerce doesn't show it again
			public function woocommerce_setup() {
				add_theme_support( 'woocommerce' );
				// The product gallery: zoom on hover, a full-screen view, and
				// a slider when there is more than one image
				add_theme_support( 'wc-product-gallery-zoom' );
				add_theme_support( 'wc-product-gallery-lightbox' );
				add_theme_support( 'wc-product-gallery-slider' );
				add_filter( 'woocommerce_show_page_title', '__return_false' );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );

				// No suggested products under a product
				remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
				remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
			}

			// Lets the stylesheet lay out posts and pages with a hero (see slms_hero())
			public function body_class( $classes ) {
				if ( '' !== slms_hero() ) {
					$classes[] = 'has-featured-image';
				}

				return $classes;
			}

			public function excerpt_more() {
				return '&hellip;';
			}
		}

		$SLMS_Theme = new SLMS_Theme();
	}

/**
 * A published page by its slug, such as "join", or null if there isn't one,
 * for links and sections that only show when their page exists.
 */
function slms_page( $slug ) {
	$page = get_page_by_path( $slug );

	return $page && 'publish' === $page->post_status ? $page : null;
}

/**
 * The address of a published page by its slug, or '' if there isn't one.
 */
function slms_page_url( $slug ) {
	$page = slms_page( $slug );

	return $page ? get_permalink( $page ) : '';
}

/**
 * A link onward, to the rest of something, with an arrow after its text, as
 * in "Find out more →". The one place the arrow is written, so every such
 * link looks the same.
 */
function slms_more_link( $url, $text, $class = '' ) {
	return '<a' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . ' href="' . esc_url( $url ) . '">'
		. esc_html( $text ) . ' <span aria-hidden="true">&rarr;</span></a>';
}

/**
 * An icon, inline, so no icon font on every page: Font Awesome Free 7.3.1's
 * (CC BY 4.0, https://fontawesome.com/license/free), by its name there.
 * Hidden from screen readers, so give its link or button a name.
 */
function slms_icon( $name, $size = '1.25em' ) {
	// Each icon's viewBox and path, from Font Awesome's SVGs: solid, or brands,
	// but where marked
	static $icons = array(
		'bars'            => array( '0 0 448 512', 'M0 96C0 78.3 14.3 64 32 64l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 128C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32L32 448c-17.7 0-32-14.3-32-32s14.3-32 32-32l384 0c17.7 0 32 14.3 32 32z' ),
		'basket-shopping' => array( '0 0 576 512', 'M288 0c6.6 0 12.9 2.7 17.4 7.5l144 152 .5 .5 78.1 0c17.7 0 32 14.3 32 32 0 14.5-9.6 26.7-22.8 30.7L491.1 429.9c-6.5 29.3-32.5 50.1-62.5 50.1l-281.3 0c-30 0-56-20.8-62.5-50.1l-46-207.2c-13.2-3.9-22.8-16.2-22.8-30.7 0-17.7 14.3-32 32-32l78.1 0 .5-.5 144-152C275.1 2.7 281.4 0 288 0zm0 58.9L192.2 160 383.8 160 288 58.9zM208 264c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 112c0 13.3 10.7 24 24 24s24-10.7 24-24l0-112zm80-24c-13.3 0-24 10.7-24 24l0 112c0 13.3 10.7 24 24 24s24-10.7 24-24l0-112c0-13.3-10.7-24-24-24zm128 24c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 112c0 13.3 10.7 24 24 24s24-10.7 24-24l0-112z' ),
		'chevron-left'    => array( '0 0 320 512', 'M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z' ),
		'chevron-right'   => array( '0 0 320 512', 'M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z' ),
		'chevron-down'    => array( '0 0 448 512', 'M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z' ),
		'camera'          => array( '0 0 512 512', 'M193.1 32c-18.7 0-36.2 9.4-46.6 24.9L120.5 96 64 96C28.7 96 0 124.7 0 160L0 416c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-256c0-35.3-28.7-64-64-64l-56.5 0-26-39.1C355.1 41.4 337.6 32 318.9 32L193.1 32zm-6.7 51.6c1.5-2.2 4-3.6 6.7-3.6l125.7 0c2.7 0 5.2 1.3 6.7 3.6l33.2 49.8c4.5 6.7 11.9 10.7 20 10.7l69.3 0c8.8 0 16 7.2 16 16l0 256c0 8.8-7.2 16-16 16L64 432c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l69.3 0c8 0 15.5-4 20-10.7l33.2-49.8zM256 384a112 112 0 1 0 0-224 112 112 0 1 0 0 224zM192 272a64 64 0 1 1 128 0 64 64 0 1 1 -128 0z' ), // Regular
		'copyright'       => array( '0 0 512 512', 'M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM205.1 306.9c-28.1-28.1-28.1-73.7 0-101.8s73.7-28.1 101.8 0c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9c-46.9-46.9-122.8-46.9-169.7 0s-46.9 122.8 0 169.7 122.8 46.9 169.7 0c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0c-28.1 28.1-73.7 28.1-101.8 0z' ), // Regular
		'discourse'       => array( '0 0 448 512', 'M225.9 32C103.3 32 0 130.5 0 252.1 0 256 .1 480 .1 480l225.8-.2C348.6 479.8 448 377.5 448 255.9S348.6 32 225.9 32zM224 384c-19.4 0-37.9-4.3-54.4-12.1L88.5 392 111.4 317c-9.8-18.1-15.4-38.9-15.4-61 0-70.7 57.3-128 128-128s128 57.3 128 128-57.3 128-128 128z' ),
		'facebook'        => array( '0 0 512 512', 'M512 256C512 114.6 397.4 0 256 0S0 114.6 0 256C0 376 82.7 476.8 194.2 504.5l0-170.3-52.8 0 0-78.2 52.8 0 0-33.7c0-87.1 39.4-127.5 125-127.5 16.2 0 44.2 3.2 55.7 6.4l0 70.8c-6-.6-16.5-1-29.6-1-42 0-58.2 15.9-58.2 57.2l0 27.8 83.6 0-14.4 78.2-69.3 0 0 175.9C413.8 494.8 512 386.9 512 256z' ),
		'instagram'       => array( '0 0 448 512', 'M224.3 141a115 115 0 1 0 -.6 230 115 115 0 1 0 .6-230zm-.6 40.4a74.6 74.6 0 1 1 .6 149.2 74.6 74.6 0 1 1 -.6-149.2zm93.4-45.1a26.8 26.8 0 1 1 53.6 0 26.8 26.8 0 1 1 -53.6 0zm129.7 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM399 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z' ),
		'minus'           => array( '0 0 448 512', 'M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z' ),
		'pause'           => array( '0 0 384 512', 'M48 32C21.5 32 0 53.5 0 80L0 432c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48L48 32zm224 0c-26.5 0-48 21.5-48 48l0 352c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-352c0-26.5-21.5-48-48-48l-64 0z' ),
		'play'            => array( '0 0 448 512', 'M91.2 36.9c-12.4-6.8-27.4-6.5-39.6 .7S32 57.9 32 72l0 368c0 14.1 7.5 27.2 19.6 34.4s27.2 7.5 39.6 .7l336-184c12.8-7 20.8-20.5 20.8-35.1s-8-28.1-20.8-35.1l-336-184z' ),
		'plus'            => array( '0 0 448 512', 'M256 64c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 160-160 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l160 0 0 160c0 17.7 14.3 32 32 32s32-14.3 32-32l0-160 160 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-160 0 0-160z' ),
	);

	list( $view_box, $path ) = $icons[ $name ];
	return '<svg class="icon" viewBox="' . $view_box . '" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" aria-hidden="true" focusable="false"><path fill="currentColor" d="' . $path . '"/></svg>';
}

/**
 * The space's accounts elsewhere: a line of links, each an icon and its name.
 */
function slms_social_links() {
	$accounts = array(
		array( 'Instagram', 'https://www.instagram.com/southlondonmakerspace/', 'instagram' ),
		array( 'Facebook', 'https://www.facebook.com/southlondonmakerspace', 'facebook' ),
		array( 'Forum', SLMS_FORUM_URL, 'discourse' ),
	);

	echo '<p class="social">';
	foreach ( $accounts as list( $name, $url, $icon ) ) {
		echo '<a href="' . esc_url( $url ) . '">' . slms_icon( $icon ) . esc_html( $name ) . '</a>';
	}
	echo '</p>';
}

/**
 * Prints a "Read more →" link to the current post.
 */
function slms_read_more() {
	echo slms_more_link( get_permalink(), 'Read more' );
}

/**
 * A caption for the hero at the top of the page (see header .hero in
 * post.less). With an icon, a button that shows the caption beside it when
 * pressed, so it doesn't cover the image; a <details>, so no JavaScript.
 * Without one, the caption alone, always shown.
 *
 * @param string $html  The caption, already escaped
 * @param string $label The button's name, for screen readers
 * @param string $icon  The icon's name (see slms_icon())
 * @param bool   $open  Whether it starts shown (main.js closes it after a few seconds)
 */
function slms_hero_caption( $html, $label = '', $icon = '', $open = false ) {
	$text = '<span>' . $html . '</span>';
	if ( $icon ) {
		$text = '<details' . ( $open ? ' open' : '' ) . '><summary aria-label="' . esc_attr( $label ) . '">'
			. slms_icon( $icon )
			. '</summary>' . $text . '</details>';
	}
	echo '<figcaption>' . $text . '</figcaption>';
}

/**
 * The basket icon and its item count, linking to the WooCommerce basket, for
 * logged-in users. Returned rather than printed so WooCommerce can also send
 * it as a fragment, to update the count when something is added.
 */
function slms_cart_link() {
	if ( ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}

	$count = WC()->cart->get_cart_contents_count();

	return '<a class="cart" href="' . esc_url( wc_get_cart_url() ) . '">'
		. slms_icon( 'basket-shopping', '1.5em' )
		. '<span class="cart-label">Basket</span>'
		. '<span class="cart-count">' . intval( $count ) . '</span>'
		. '</a>';
}

add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['.site-nav .cart'] = slms_cart_link();
	return $fragments;
} );

/**
 * Marks links to other sites as external: every link on the page, the
 * theme's, the content's, the widgets' and plugins', so they all look the
 * same. Shown with an icon, and read out as going to another site (see
 * main.less).
 */
add_action( 'template_redirect', function () {
	if ( ! is_feed() ) {
		ob_start( 'slms_external_links' );
	}
} );

function slms_external_links( $html ) {
	$home = wp_parse_url( home_url(), PHP_URL_HOST );
	$tags = new WP_HTML_Tag_Processor( $html );

	while ( $tags->next_tag( 'a' ) ) {
		$host = wp_parse_url( (string) $tags->get_attribute( 'href' ), PHP_URL_HOST );
		$rel  = (string) $tags->get_attribute( 'rel' );
		if ( $host && strcasecmp( $host, $home ) !== 0 && ! preg_match( '/(^|\s)external(\s|$)/i', $rel ) ) {
			$tags->set_attribute( 'rel', trim( $rel . ' external' ) );
		}
	}

	return $tags->get_updated_html();
}
