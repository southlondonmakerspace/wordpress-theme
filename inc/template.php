<?php

function slms_template_path() {
	return SLMS_Wrapping::$main_template;
}

class SLMS_Wrapping {
	/**
	 * Stores the full path to the main template file
	 */
	static $main_template;

	// Every page is base.php, which includes the template WordPress chose
	static function wrap( $template ) {
		self::$main_template = $template;

		return locate_template( 'base.php' );
	}
}
add_filter( 'template_include', array( 'SLMS_Wrapping', 'wrap' ), 99 );
