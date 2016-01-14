<?php

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
				<label for="<?php echo $this->get_field_id( 'text' ); ?>">Text:</label><br />
				<textarea id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name( 'text' ); ?>"><?php echo esc_attr( $instance['text'] ); ?></textarea>
			</p>
		<?php
	}
	
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['text'] = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';
		return $instance;
	}
}
