<?php

	define( 'SLMS_THEME_DIR', get_stylesheet_directory() );
	define( 'SLMS_ENV', preg_match('/southlondonmakerspace\.org/', get_bloginfo('wpurl') ) ? 'production' : 'development' );

	require SLMS_THEME_DIR . '/inc/template.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-blog.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-events.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-forum.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-newsletter.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-quick-links.php';

	if ( ! class_exists( 'SLMS_Theme' ) ) {
		class SLMS_Theme {

			public function __construct() {
				add_theme_support( 'post-thumbnails' );

				add_action( 'init', array( &$this, 'register_sidebars' ) );
				add_action( 'init', array( &$this, 'register_nav_menus' ) );
				add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
				add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
				add_action( 'widgets_init', array( &$this, 'register_widgets' ) );
				add_filter( 'excerpt_more', array( &$this, 'excerpt_more' ) );
				add_filter( 'script_loader_src', array( &$this, 'remove_query_string_versioning' ) );
				add_filter( 'style_loader_src', array( &$this, 'remove_query_string_versioning' ) );
			}

			function remove_query_string_versioning( $src ) {
				if ( preg_match('/maps\.google/', $src ) )
					return $src;

				$parts = explode( '?', $src );

				return array_shift( $parts );
			}


			public function register_sidebars() {
				register_sidebar( array(
					'name'	=> 'Homepage',
					'id'	=> 'slms_home_sidebar',
					'before_widget' => '<section id="%1$s" class="widget %2$s">',
					'after_widget' => '</section>',
					'before_title' => '<h1 class="widgettitle">',
					'after_title' => '</h1>'
				) );

				register_sidebar( array(
					'name'	=> 'Single Post',
					'id'	=> 'slms_single_post',
					'before_widget' => '<section id="%1$s" class="widget %2$s">',
					'after_widget' => '</section>',
					'before_title' => '<h1 class="widgettitle">',
					'after_title' => '</h1>'
				) );
			}

			public function register_nav_menus() {
				register_nav_menus( array(
					'slms_home_links'	=>	'Homepage Links',
					'slms_quick_links'	=>	'Quick Links'
				) );
			}

			public function enqueue_styles() {
				wp_enqueue_style( 'mapbox', 'https://api.tiles.mapbox.com/mapbox.js/v2.1.5/mapbox.css' );

				if ( SLMS_ENV === 'production' ) {

					foreach ( glob( get_stylesheet_directory() . '/static/dist/*.css') as $css_file ) {
						wp_enqueue_style( 'slms-' . basename( $css_file ), get_stylesheet_directory_uri() . '/static/dist/' . basename( $css_file ) );
					}
					return;
				}

				wp_enqueue_style( 'slms_main', get_stylesheet_directory_uri() . '/static/css/main.css' );
			}

			public function enqueue_scripts() {
				wp_register_script( 'mapbox', 'https://api.tiles.mapbox.com/mapbox.js/v2.1.5/mapbox.js' );
				wp_enqueue_script( 'slms_main', get_stylesheet_directory_uri() . '/static/js/main.js', array( 'jquery', 'mapbox' ) );
			}

			public function register_widgets() {
				register_widget( 'slms_newsletter' );
				register_widget( 'slms_quick_links' );
				register_widget( 'slms_forum' );
				register_widget( 'slms_blog' );
				register_widget( 'slms_events' );
			}

			public function excerpt_more() {
				return '&hellip;';
			}
		}

		$SLMS_Theme = new SLMS_Theme();
	}



/**
 * https://gist.github.com/keithics/5398349
 *
 * @param  string $post_name
 * @return WP_Post
 */
function get_post_by_slug($post_name) {
	global $wpdb;
	$post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s", $post_name));

	return $post ? get_post($post) : NULL;
}
