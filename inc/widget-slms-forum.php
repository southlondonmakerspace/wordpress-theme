<?php 

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
		
		public function update( $new_instance ) {
			$instance = array();
			$instance['text'] = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';

			$instance['items'] = 3;
			if ( ! empty( $new_instance['items'] ) && is_numeric( $new_instance['items'] ) && $new_instance['items'] >= 3 && $new_instance['items'] <= 10 )
				$instance['items'] = $new_instance['items'];

			return $instance;
		}
	}
