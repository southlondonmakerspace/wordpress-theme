<?php 

	class slms_blog extends WP_Widget {
		public function __construct() {
			parent::__construct( 'slms_blog', 'Blog', array( 'classname' => 'multi' ) );
			$this->widget_options['before_widget'] = 'test';
		}

		public function widget( $args, $instance ) {
			$query = new WP_Query( 'posts_per_page=' . ( is_numeric( intval( $instance['posts'] ) ) ? intval( $instance['posts'] ) : 3 ) );
			?>
				<section class="widget widget__large">
					<header>
						<?php echo $args['before_title'] . apply_filters( 'widget_title', 'Blog' ) . $args['after_title'] ?>
						<?php if ( ! empty( $instance['text'] ) ): ?>
							<p><?php echo nl2br( $instance['text'] ) ?></p>
						<?php endif ?>
					</header>
					<div class="posts">
					<?php while( $query->have_posts() ): $query->the_post() ?><!-- 
					 --><article <?php post_class(); ?>>
							<h1><?php the_title() ?></h1>
							<time datetime="<?php echo get_the_date('c') ?>"><?php the_date() ?></time>
							<?php the_excerpt() ?>
							<a href="<?php the_permalink() ?>">Read more...</a>
						</article><!-- 
				 --><?php endwhile ?>
					</div>
				</section>
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
