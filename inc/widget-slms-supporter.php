<?php

/**
 * One supporter's logo, linking to their site, for the Supporters area shown
 * in the front page's footer (see base.php). Add one widget per
 * supporter. The image is a URL: upload or choose one with the Choose image
 * button, or paste one.
 */
class slms_supporter extends WP_Widget {
	public function __construct() {
		parent::__construct( 'slms_supporter', 'Supporter', array(
			'description' => 'A supporter\'s logo and link, for the Supporters area.',
		) );
	}

	public function widget( $args, $instance ) {
		$name  = $instance['title'] ?? '';
		$image = $instance['image'] ?? '';
		$link  = $instance['link'] ?? '';

		if ( '' === $image && '' === $name ) {
			return;
		}

		// The logo, or the name where there's no logo yet
		$content = $image ? '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $name ) . '" loading="lazy">' : esc_html( $name );

		echo $args['before_widget'];
		echo $link ? '<a href="' . esc_url( $link ) . '">' . $content . '</a>' : $content;
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$fields = array(
			'title' => array( 'Name:', 'text', 'Read out in place of the logo.' ),
			'image' => array( 'Image URL:', 'url', '' ),
			'link'  => array( 'Link:', 'url', 'Their website.' ),
		);

		foreach ( $fields as $key => list( $label, $type, $help ) ) :
			?>
			<p>
				<label for="<?php echo $this->get_field_id( $key ); ?>"><?php echo $label; ?></label>
				<input class="widefat" type="<?php echo $type; ?>" id="<?php echo $this->get_field_id( $key ); ?>" name="<?php echo $this->get_field_name( $key ); ?>" value="<?php echo esc_attr( $instance[ $key ] ?? '' ); ?>" />
				<?php if ( 'image' === $key ) : ?>
					<?php // Opens the media library, to upload or pick an image (see static/js/supporter-widget.js) ?>
					<button type="button" class="button slms-supporter-choose" data-target="<?php echo $this->get_field_id( 'image' ); ?>" style="margin-top: .5em">Choose image</button>
				<?php endif; ?>
				<?php if ( $help ) : ?>
					<small><?php echo $help; ?></small>
				<?php endif; ?>
			</p>
			<?php
		endforeach;
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
			'image' => esc_url_raw( $new_instance['image'] ?? '' ),
			'link'  => esc_url_raw( $new_instance['link'] ?? '' ),
		);
	}
}
