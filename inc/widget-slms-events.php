<?php

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
				<section class="widget widget__large">
					<header>
						<?php echo $args['before_title'] . apply_filters( 'widget_title', 'Events' ) . $args['after_title'] ?>
						<?php if ( ! empty( $instance['text'] ) ): ?>
							<p><?php echo nl2br( $instance['text'] ) ?></p>
						<?php endif ?>
					</header>
					<div class="posts">
					<?php foreach( $calendar->items as $item ) :

						if ( ! in_array( $item->etag, $UIDs ) && property_exists( $item->start, 'dateTime' ) ) :

							array_push( $UIDs, $item->etag );

							 ?><!-- 
						 --><article class="post post__event">
								<h1><?php echo $item->summary ?></h1>
								<?php echo $this->get_timestamp_for( $item ); ?>
								<?php if ( strpos( $item->description, ':' ) ) : ?>
									<?php if ( preg_match( '/^(\w+\ ?\w*):\ (.*)$/sm', $item->description, $results ) ) :
										$tag = $results[1];
										$description = $results[2];
									?>
									<span><?php echo $tag ?></span>
									<?php endif; ?>
								<?php endif ?>
								<p><?php echo nl2br( empty( $description ) ? $item->description : $description ); ?></p>
							</article><!-- 
						 --><?php endif;
					endforeach ?>
					</div><!-- .posts -->
				</section>
			<?php
			wp_reset_postdata();
		}

		public function get_timestamp_for( $item ) {

			$date = date_create_from_format( 'Y-m-d\TH:i:sP', $item->start->dateTime );

			if ( property_exists( $item, 'originalStartTime' ) ) {
				return sprintf('<time datetime="%s">Every %s</time>',
					$date->format( DateTime::W3C ),
					$date->format( 'l \f\r\o\m H:i' )
				);
			}

			return sprintf('<time datetime="%s">%s</time>',
				$date->format( DateTime::W3C ),
				$date->format( 'l, dS F \f\r\o\m H:i')
			);
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
