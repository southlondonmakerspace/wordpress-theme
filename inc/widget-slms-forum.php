<?php

class slms_forum extends WP_Widget {

	public function __construct() {
		parent::__construct( 'slms_forum', 'Forum' );
	}

	public function widget( $args, $instance ) {

		echo $args['before_widget'];

		echo '<header>';

		echo $args['before_title'] . apply_filters( 'widget_title', 'Forum' ). $args['after_title'];

		if ( isset( $instance['text'] ) )	{
			echo '<p>' . nl2br( esc_html( $instance['text'] ) ) . '</p>';
		}

		// WordPress caches the feed, so the forum isn't fetched on every page load.
		// Not events, such as Public Events, which the Events widget lists.
		$rss   = fetch_feed( SLMS_FORUM_URL . 'latest.rss' );
		$items = is_wp_error( $rss ) ? array() : array_filter( $rss->get_items(), function ( $item ) {
			$category = $item->get_category();

			return ! ( $category && preg_match( '/\bevents$/i', trim( $category->get_label() ) ) );
		} );
		$items = array_slice( $items, 0, (int) ( $instance['items'] ?? 3 ) );

			?>
			<?php echo slms_more_link( SLMS_FORUM_URL, 'Visit our forums' ) ?>
		<?php echo '</header>'; ?>
			<div>
				<h3>Latest posts</h3>
				<ul class="links">
					<?php foreach ( $items as $item ) : ?>
						<li><a href="<?php echo esc_url( $item->get_permalink() ) ?>"><?php echo esc_html( $item->get_title() ); ?></a></li>
					<?php endforeach ?>
				</ul>
			</div>
		<?php

		echo $args['after_widget'];
	}

	public function form( $instance ) {

		$text = isset( $instance['text'] ) ? $instance['text'] : '';

		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'items' ) ?>">Items:</label>
				<input type="number" min="3" max="10" id="<?php echo $this->get_field_id( 'items' ) ?>" name="<?php echo $this->get_field_name( 'items' ) ?>" value="<?php echo esc_attr( $instance['items'] ?? 3 ) ?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'text' ) ?>">Text:</label><br />
				<textarea id="<?php echo $this->get_field_id( 'text' ) ?>" name="<?php echo $this->get_field_name( 'text' ) ?>"><?php echo esc_textarea( $text ); ?></textarea>
			</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['text'] = isset( $new_instance['text'] ) ? strip_tags( $new_instance['text'] ) : '';

		$instance['items'] = 3;
		if ( ! empty( $new_instance['items'] ) && is_numeric( $new_instance['items'] ) && $new_instance['items'] >= 3 && $new_instance['items'] <= 10 )
			$instance['items'] = $new_instance['items'];

		return $instance;
	}
}
