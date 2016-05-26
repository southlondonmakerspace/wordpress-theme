<?php

class slms_newsletter extends WP_Widget {
	public function __construct() {
		parent::__construct( 'slms_newsletter', 'Newsletter' );
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		echo $args['before_title'] . apply_filters( 'widget_title', 'Newsletter' ). $args['after_title'];

		if ( ! empty( $instance['text'] ) ) {
			echo '<p>' . nl2br( $instance['text'] ) . '</p>';
		}

		?>
			<div id="mc_embed_signup">
				<form action="//southlondonmakerspace.us3.list-manage.com/subscribe/post?u=851fda98e1d7951586754129f&amp;id=38bef3c0a3" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
					<div id="mc_embed_signup_scroll">
						<div class="mc-field-group">
							<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
						</div>
						<div id="mce-responses" class="clear">
							<div class="response" id="mce-error-response" style="display:none"></div>
							<div class="response" id="mce-success-response" style="display:none"></div>
						</div>
						<div style="position: absolute; left: -5000px;"><input type="text" name="b_851fda98e1d7951586754129f_c0f84d3985" tabindex="-1" value=""></div>
						<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="btn">
				    </div>
				</form>
			</div>
			<script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script><script type='text/javascript'>(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';fnames[3]='MMERGE3';ftypes[3]='text';fnames[4]='MMERGE4';ftypes[4]='text';}(jQuery));var $mcj = jQuery.noConflict(true);</script>
		<?php
		echo $args['after_widget'];
	}

	public function form( $instance ) {

		$text = isset( $instance['text'] ) ? esc_attr( $instance['text'] ) : '';
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'text' ) ?>">Text:</label><br />
				<textarea id="<?php echo $this->get_field_id( 'text' ) ?>" name="<?php echo $this->get_field_name( 'text' ) ?>"><?php echo $text; ?></textarea>
			</p>
		<?php
	}
	
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['text'] = ( ! empty( $new_instance['text'] ) ) ? strip_tags( $new_instance['text'] ) : '';
		return $instance;
	}
}
