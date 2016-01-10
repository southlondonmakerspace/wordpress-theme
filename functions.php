<?php

	require get_stylesheet_directory() . '/inc/template.php';

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

	class slms_newsletter extends WP_Widget {
		public function __construct() {
			parent::__construct( 'slms_newsletter', 'Newsletter' );
		}

		public function widget( $args, $instance ) {
			echo $args['before_widget'];
			echo $args['before_title'] . apply_filters( 'widget_title', 'Newsletter' ). $args['after_title'];
			if ( ! empty( $instance['text'] ) ) echo '<p>' . nl2br( $instance['text'] ) . '</p>';
			?>
				<div id="mc_embed_signup">
					<form action="//southlondonmakerspace.us3.list-manage.com/subscribe/post?u=851fda98e1d7951586754129f&amp;id=c0f84d3985" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
						<div id="mc_embed_signup_scroll">
							<div class="mc-field-group">
								<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
							</div>
							<div id="mce-responses" class="clear">
								<div class="response" id="mce-error-response" style="display:none"></div>
								<div class="response" id="mce-success-response" style="display:none"></div>
							</div>
							<div style="position: absolute; left: -5000px;"><input type="text" name="b_851fda98e1d7951586754129f_c0f84d3985" tabindex="-1" value=""></div>
							<div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
					    </div>
					</form>
				</div>
				<script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script><script type='text/javascript'>(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';fnames[3]='MMERGE3';ftypes[3]='text';fnames[4]='MMERGE4';ftypes[4]='text';}(jQuery));var $mcj = jQuery.noConflict(true);</script>
			<?php
			echo $args['after_widget'];
		}

		public function form( $instance ) {
			?>
				<p>
					<label for="<?php echo $this->get_field_id( 'text' ) ?>">Text:</label><br />
					<textarea id="<?php echo $this->get_field_id( 'text' ) ?>" name="<?php echo $this->get_field_name( 'text' ) ?>"><?php echo esc_attr( $instance['text'] ) ?></textarea>
				</p>
			<?php
		}
		
		public function update( $new_instance, $old_instance ) {
			$instance = array();
			$instance['text'] = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';
			return $instance;
		}
	}

	class slms_quick_links extends WP_Widget {
		public function __construct() {
			parent::__construct( 'slms_quicklinks', 'Quick Links' );
		}

		public function widget( $args, $instance ) {
			echo $args['before_widget'];
			echo $args['before_title'] . apply_filters( 'widget_title', 'Quick Links' ). $args['after_title'];
			if ( ! empty( $instance['text'] ) ) echo '<p>' . nl2br( $instance['text'] ) . '</p>';
			wp_nav_menu( array(
				'theme_location'	=> 'slms_quick_links',
				'container'			=> false,
				'menu_class'	=> 'links'
			) );
			echo $args['after_widget'];
		}

		public function form( $instance ) {
			?>
				<p>
					<label for="<?php echo $this->get_field_id( 'text' ) ?>">Text:</label><br />
					<textarea id="<?php echo $this->get_field_id( 'text' ) ?>" name="<?php echo $this->get_field_name( 'text' ) ?>"><?php echo esc_attr( $instance['text'] ) ?></textarea>
				</p>
			<?php
		}
		
		public function update( $new_instance, $old_instance ) {
			$instance = array();
			$instance['text'] = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';
			return $instance;
		}
	}

	class slms_forum extends WP_Widget {
		public function __construct() {
			parent::__construct( 'slms_forum', 'Forum' );
		}

		public function widget( $args, $instance ) {
			echo $args['before_widget'];
			echo $args['before_title'] . apply_filters( 'widget_title', 'Forum' ). $args['after_title'];
			if ( ! empty( $instance['text'] ) ) echo '<p>' . nl2br( $instance['text'] ) . '</p>';
			include_once( ABSPATH . WPINC . '/rss.php' );
			$rss = fetch_rss( 'https://discourse.southlondonmakerspace.org/latest.rss' );
			$items = array_slice( $rss->items, 0, $instance['items'] );
			?>
				<a href="http://discourse.southlondonmakerspace.org/">Visit our forum</a>
				<h2>Latest posts</h2>
				<ul class="links">
					<?php foreach( $items as $item ): ?>
						<li><a href="<?php echo $item['link'] ?>"><?php echo $item['title'] ?></a></li>
					<?php endforeach ?>
				</ul>
			<?php
			echo $args['after_widget'];
		}

		public function form( $instance ) {
			?>
				<p>
					<label for="<?php echo $this->get_field_id( 'items' ) ?>">Items:</label>
					<input type="number" min="3" max="10" id="<?php echo $this->get_field_id( 'items' ) ?>" name="<?php echo $this->get_field_name( 'items' ) ?>" value="<?php echo $instance['items'] ?>">
				</p>
				<p>
					<label for="<?php echo $this->get_field_id( 'text' ) ?>">Text:</label><br />
					<textarea id="<?php echo $this->get_field_id( 'text' ) ?>" name="<?php echo $this->get_field_name( 'text' ) ?>"><?php echo esc_attr( $instance['text'] ) ?></textarea>
				</p>
			<?php
		}
		
		public function update( $new_instance, $old_instance ) {
			$instance = array();
			$instance['text'] = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';

			$instance['items'] = 3;
			if ( ! empty( $new_instance['items'] ) && is_numeric( $new_instance['items'] ) && $new_instance['items'] >= 3 && $new_instance['items'] <= 10 )
				$instance['items'] = $new_instance['items'];

			return $instance;
		}
	}

	class slms_blog extends WP_Widget {
		public function __construct() {
			parent::__construct( 'slms_blog', 'Blog', array( 'classname' => 'multi' ) );
			$this->widget_options['before_widget'] = 'test';
		}

		public function widget( $args, $instance ) {
			$query = new WP_Query( 'posts_per_page=' . ( is_numeric( intval( $instance['posts'] ) ) ? intval( $instance['posts'] ) : 3 ) );
			?>
				<div class="multi">
					<section>
						<?php echo $args['before_title'] . apply_filters( 'widget_title', 'Blog' ) . $args['after_title'] ?>
						<?php if ( ! empty( $instance['text'] ) ): ?>
							<p><?php echo nl2br( $instance['text'] ) ?></p>
						<?php endif ?>
					</section>
					<?php while( $query->have_posts() ): $query->the_post() ?>
						<article>
							<h1><?php the_title() ?></h1>
							<time datetime="<?php echo get_the_date('c') ?>"><?php the_date() ?></time>
							<?php the_excerpt() ?>
							<a href="<?php the_permalink() ?>">Read more...</a>
						</article>
					<?php endwhile ?>
				</div>
			<?php
			wp_reset_postdata();
		}

		public function form( $instance ) {
			?>
				<p>
					<label for="<?php echo $this->get_field_id( 'posts' ) ?>">Posts:</label>
					<input type="number" min="1" max="12" id="<?php echo $this->get_field_id( 'posts' ) ?>" name="<?php echo $this->get_field_name( 'posts' ) ?>" value="<?php echo $instance['posts'] ?>">
				</p>
				<p>
					<label for="<?php echo $this->get_field_id( 'text' ) ?>">Text:</label><br />
					<textarea id="<?php echo $this->get_field_id( 'text' ) ?>" name="<?php echo $this->get_field_name( 'text' ) ?>"><?php echo esc_attr( $instance['text'] ) ?></textarea>
				</p>
			<?php
		}
		
		public function update( $new_instance, $old_instance ) {
			$instance = array();
			$instance['text'] = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';

			$instance['posts'] = 3;
			
			if ( ! empty( $new_instance['posts'] ) && is_numeric( $new_instance['posts'] ) && $new_instance['posts'] >= 1 && $new_instance['posts'] <= 12 )
				$instance['posts'] = $new_instance['posts'];

			return $instance;
		}
	}

	class slms_events extends WP_Widget {
		public function __construct() {
			parent::__construct( 'slms_events', 'Events', array( 'classname' => 'multi' ) );
			$this->widget_options['before_widget'] = 'test';
		}

		public function widget( $args, $instance ) {
			$cache_file = 'google-calendar.cache';
			if ( ! file_exists( $cache_file ) || time() - filemtime( $cache_file ) > 1 ) {
					$url = 'https://www.googleapis.com/calendar/v3/calendars/6hnjp743rq7omi2qfr3fa873ug%40group.calendar.google.com/events?singleEvents=true&orderBy=startTime&timeMin=' . date( 'Y-m-d\TH:i:s' ) . 'Z&key=AIzaSyDpWNCjMO3l8vHOuRQQMiLRdZo3jWpkaNU';
					$cache = file_get_contents( $url );
					file_put_contents( $cache_file, $cache );
			}

			$cache = file_get_contents( $cache_file );
			$calendar = json_decode( $cache );
			$UIDs = array();
			date_default_timezone_set( get_option( 'timezone_string' ) );
			?>
				<div class="multi">
					<section>
						<?php echo $args['before_title'] . apply_filters( 'widget_title', 'Events' ) . $args['after_title'] ?>
						<?php if ( ! empty( $instance['text'] ) ): ?>
							<p><?php echo nl2br( $instance['text'] ) ?></p>
						<?php endif ?>
					</section>
					<?php foreach( $calendar->items as $item ): ?>
						<?php if ( ! in_array( $item->etag, $UIDs ) && property_exists( $item->start, 'dateTime' ) ): array_push( $UIDs, $item->etag ) ?>
							<article>
								<h1><?php echo $item->summary ?></h1>
								<?php
									$date = DateTime::createFromFormat( 'Y-m-d\TH:i:sP', $item->start->dateTime );
								?>
								<?php if ( property_exists( $item, 'originalStartTime' ) ): ?>
									<time datetime="<?php echo $date->format( DateTime::W3C ) ?>">Every <?php echo $date->format( 'l \f\r\o\m H:i' ) ?></time>
								<?php else: ?>
									<time datetime="<?php echo $date->format( DateTime::W3C ) ?>"><?php echo $date->format( 'l, dS F \f\r\o\m H:i' ) ?></time>
								<?php endif ?>
								<?php if ( strpos( $item->description, ':') ): ?>
									<?php 
										preg_match( '/^(\w+\ ?\w*):\ (.*)$/sm', $item->description, $results );
										$tag = $results[1];
										$description = $results[2];
									?>
									<span><?php echo $tag ?></span>
								<?php endif ?>
								<p><?php echo nl2br( empty( $description ) ? $item->description : $description ) ?></p>
							</article>
						<?php endif ?>
					<?php endforeach ?>
				</div>
			<?php
			wp_reset_postdata();
		}

		public function form( $instance ) {
			?>
				<p>
					<label for="<?php echo $this->get_field_id( 'events' ) ?>">Events:</label>
					<input type="number" min="1" max="12" id="<?php echo $this->get_field_id( 'events' ) ?>" name="<?php echo $this->get_field_name( 'events' ) ?>" value="<?php echo $instance['events'] ?>">
				</p>
				<p>
					<label for="<?php echo $this->get_field_id( 'text' ) ?>">Text:</label><br />
					<textarea id="<?php echo $this->get_field_id( 'text' ) ?>" name="<?php echo $this->get_field_name( 'text' ) ?>"><?php echo esc_attr( $instance['text'] ) ?></textarea>
				</p>
			<?php
		}
		
		public function update( $new_instance, $old_instance ) {
			$instance = array();
			$instance['text'] = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';

			$instance['events'] = 3;
			
			if ( ! empty( $new_instance['events'] ) && is_numeric( $new_instance['events'] ) && $new_instance['events'] >= 1 && $new_instance['events'] <= 12 )
				$instance['events'] = $new_instance['events'];

			return $instance;
		}
	}