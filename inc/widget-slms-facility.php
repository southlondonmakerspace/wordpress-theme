<?php

/**
 * One facility (woodwork, electronics…) for the Facilities area on the front
 * page: a Font Awesome icon, a name and a short description. Add one widget
 * per facility; the front page shows them in a grid under one heading.
 */
class slms_facility extends WP_Widget {
	public function __construct() {
		parent::__construct( 'slms_facility', 'Facility', array(
			'description' => 'A facility with an icon, for the Facilities area on the front page.',
		) );
	}

	/**
	 * Font Awesome classes for an icon name such as "hammer", or a style and a
	 * name such as "brands codepen".
	 */
	private function icon_classes( $icon ) {
		$words = preg_split( '/\s+/', trim( $icon ) );
		$name  = array_pop( $words );
		$style = $words ? array_pop( $words ) : 'solid';

		return 'fa-' . sanitize_html_class( $style ) . ' fa-' . sanitize_html_class( $name );
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] ?? '', $instance, $this->id_base );

		echo $args['before_widget'];

		if ( ! empty( $instance['icon'] ) ) {
			echo '<i class="facility__icon ' . esc_attr( $this->icon_classes( $instance['icon'] ) ) . '" aria-hidden="true"></i>';
		}

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		if ( ! empty( $instance['text'] ) ) {
			echo wpautop( esc_html( $instance['text'] ) );
		}

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$fields = array(
			'title' => 'Name:',
			'icon'  => 'Icon:',
		);

		foreach ( $fields as $key => $label ) :
			?>
			<p>
				<label for="<?php echo $this->get_field_id( $key ); ?>"><?php echo $label; ?></label>
				<input class="widefat" type="text" id="<?php echo $this->get_field_id( $key ); ?>" name="<?php echo $this->get_field_name( $key ); ?>" value="<?php echo esc_attr( $instance[ $key ] ?? '' ); ?>" />
				<?php if ( $key === 'icon' ) : ?>
					<small>A <a href="https://fontawesome.com/search?ic=free" target="_blank">Font Awesome</a> icon name, such as <code>hammer</code>. For a brand icon, start with <code>brands</code>, such as <code>brands codepen</code>.</small>
				<?php endif; ?>
			</p>
			<?php
		endforeach;
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'text' ); ?>">Description:</label>
			<textarea class="widefat" rows="6" id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name( 'text' ); ?>"><?php echo esc_textarea( $instance['text'] ?? '' ); ?></textarea>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
			'icon'  => sanitize_text_field( $new_instance['icon'] ?? '' ),
			'text'  => sanitize_textarea_field( $new_instance['text'] ?? '' ),
		);
	}
}

/**
 * Places the facilities in the Homepage area: drag it among the other widgets
 * to choose where they show. The facilities themselves are the Facility
 * widgets in the Facilities area, in the order they are there.
 */
class slms_facilities extends WP_Widget {
	public function __construct() {
		parent::__construct( 'slms_facilities', 'Facilities', array(
			'classname'   => 'widget_slms_facilities widget__large',
			'description' => 'Shows the Facility widgets from the Facilities area, in a grid.',
		) );
	}

	public function widget( $args, $instance ) {
		if ( ! is_active_sidebar( 'slms_facilities' ) ) {
			return;
		}

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? 'Facilities' : $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];
		echo '<header>';
		echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

		if ( ! empty( $instance['text'] ) ) {
			echo '<p>' . nl2br( esc_html( $instance['text'] ) ) . '</p>';
		}

		echo '</header>';
		echo '<div class="facilities__grid">';
		dynamic_sidebar( 'slms_facilities' );
		echo '</div>';

		// A link to the Facilities page, if there is one and this isn't it
		$page = slms_page( 'facilities' );
		if ( $page && ! is_page( $page->ID ) ) {
			echo slms_more_link( get_permalink( $page ), 'Find out more', 'facilities__more' );
		}

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ?? '' ); ?>" placeholder="Facilities" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'text' ); ?>">Text:</label>
			<textarea class="widefat" rows="3" id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name( 'text' ); ?>"><?php echo esc_textarea( $instance['text'] ?? '' ); ?></textarea>
		</p>
		<p>Add, edit and reorder the facilities themselves in the Facilities widget area.</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
			'text'  => sanitize_textarea_field( $new_instance['text'] ?? '' ),
		);
	}
}
