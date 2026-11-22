<?php

/**
 * The space's directors, past and present, or only the current ones, with or
 * without their dates (see inc/directors.php)
 */
class slms_directors extends WP_Widget {
	public function __construct() {
		parent::__construct( 'slms_directors', 'Directors', array(
			'description' => 'Directors past and present, from Companies House.',
		) );
	}

	public function widget( $args, $instance ) {
		$html = SLMS_Company_Directors::render( array(
			'show'  => $instance['show'] ?? 'all',
			'dates' => $instance['dates'] ?? 'yes',
		) );

		if ( '' === $html ) {
			return;
		}

		$title = apply_filters( 'widget_title', $instance['title'] ?? '', $instance, $this->id_base );

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}
		echo $html;
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = $instance['title'] ?? 'Directors past and present';
		$show  = $instance['show'] ?? 'all';
		$dates = $instance['dates'] ?? 'yes';
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'show' ); ?>">Show:</label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'show' ); ?>" name="<?php echo $this->get_field_name( 'show' ); ?>">
					<?php foreach ( array( 'all' => 'Past and present', 'current' => 'Current only', 'past' => 'Past only' ) as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $show, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach ?>
				</select>
			</p>
			<p>
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'dates' ); ?>" name="<?php echo $this->get_field_name( 'dates' ); ?>" value="yes" <?php checked( $dates, 'yes' ); ?>>
				<label for="<?php echo $this->get_field_id( 'dates' ); ?>">Show dates</label>
			</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
			'show'  => in_array( $new_instance['show'] ?? '', array( 'all', 'current', 'past' ), true ) ? $new_instance['show'] : 'all',
			'dates' => 'yes' === ( $new_instance['dates'] ?? '' ) ? 'yes' : 'no',
		);
	}
}
