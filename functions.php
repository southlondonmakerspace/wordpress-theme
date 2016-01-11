<?php

	define( 'SLMS_THEME_DIR', get_stylesheet_directory() );

	require SLMS_THEME_DIR . '/inc/template.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-blog.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-events.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-forum.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-newsletter.php';
	require SLMS_THEME_DIR . '/inc/widget-slms-quick-links.php';

	if ( ! class_exists( 'SLMS_Theme' ) ) {
		class SLMS_Theme {
			
			function __construct() {
				add_theme_support( 'post-thumbnails' ); 

				add_action( 'init', array( &$this, 'register_sidebars' ) );
				add_action( 'init', array( &$this, 'register_nav_menus' ) );
				add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
				add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
				add_action( 'widgets_init', array( &$this, 'register_widgets' ) );
				add_filter( 'excerpt_more', array( &$this, 'excerpt_more' ) );
			}

			function register_sidebars() {
				register_sidebar( array(
					'name'	=> 'Homepage',
					'id'	=> 'slms_home_sidebar',
					'before_widget' => '<section id="%1$s" class="widget %2$s">',
					'after_widget' => '</section>',
					'before_title' => '<h1 class="widgettitle">',
					'after_title' => '</h1>'
				) );
			}

			function register_nav_menus() {
				register_nav_menus( array(
					'slms_home_links'	=>	'Homepage Links',
					'slms_quick_links'	=>	'Quick Links'
				) );
			}

			function enqueue_styles() {
				wp_enqueue_style( 'mapbox', 'https://api.tiles.mapbox.com/mapbox.js/v2.1.5/mapbox.css' );
				wp_enqueue_style( 'slms_main', get_stylesheet_directory_uri() . '/static/css/main.css' );
			}

			function enqueue_scripts() {
				wp_register_script( 'mapbox', 'https://api.tiles.mapbox.com/mapbox.js/v2.1.5/mapbox.js' );
				wp_enqueue_script( 'slms_main', get_stylesheet_directory_uri() . '/js/main.js', array( 'jquery' ) );

				wp_enqueue_script( 'slms_map', get_stylesheet_directory_uri() . '/js/map.js', array( 'jquery', 'mapbox' ) );
			}

			function register_widgets() {
				register_widget( 'slms_newsletter' );
				register_widget( 'slms_quick_links' );
				register_widget( 'slms_forum' );
				register_widget( 'slms_blog' );
				register_widget( 'slms_events' );
			}

			function excerpt_more( $more ) {
				return '…';
			}
		}

		$SLMS_Theme = new SLMS_Theme();
	}
