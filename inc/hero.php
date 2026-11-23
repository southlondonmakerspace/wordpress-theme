<?php
/**
 * The hero at the top of a post or page (the .hero in base.php): its featured
 * image, the map, an embed or a gallery, chosen in the editor's sidebar.
 */

// What each choice is called in the editor
const SLMS_HERO_CHOICES = array(
	'featured' => 'Featured image',
	'map'      => 'Map',
	'embed'    => 'Embed',
	'gallery'  => 'Gallery',
);

/**
 * What the post or page shown fills its hero with: 'featured', 'map', 'embed',
 * 'gallery', or '' for no hero. The front page has the map unless it chooses
 * otherwise, other pages their featured image. A choice with nothing to show,
 * such as an embed with no address, falls back to the featured image.
 */
function slms_hero() {
	// A front page that lists posts has no page to choose with
	if ( is_front_page() && ! is_singular() ) {
		return 'map';
	}

	// Not products, whose images are in WooCommerce's gallery
	if ( ! is_singular() || is_singular( 'product' ) ) {
		return '';
	}

	$id     = get_queried_object_id();
	$choice = get_post_meta( $id, '_slms_hero', true ) ?: slms_hero_default( $id );

	if ( 'map' === $choice
		|| ( 'embed' === $choice && slms_hero_embed_url( $id ) )
		|| ( 'gallery' === $choice && slms_hero_gallery( $id ) ) ) {
		return $choice;
	}

	return has_post_thumbnail( $id ) ? 'featured' : '';
}

// What a post or page has without choosing: the front page the map, others
// their featured image
function slms_hero_default( $id ) {
	return (int) get_option( 'page_on_front' ) === $id ? 'map' : 'featured';
}

// The embed's address
function slms_hero_embed_url( $id ) {
	return (string) get_post_meta( $id, '_slms_hero_embed', true );
}

// Where the map's pin links to: the address set, or the Find us page
function slms_hero_pin( $id ) {
	return (string) get_post_meta( $id, '_slms_hero_pin', true ) ?: slms_page_url( 'find-us' );
}

// The gallery's images, as attachment IDs in order
function slms_hero_gallery( $id ) {
	$ids = array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_slms_hero_gallery', true ) ) );

	return array_values( array_filter( $ids, 'wp_attachment_is_image' ) );
}

// How long the gallery shows each image before moving on, in seconds
const SLMS_HERO_SECONDS = 4;

function slms_hero_seconds( $id ) {
	return (int) get_post_meta( $id, '_slms_hero_seconds', true ) ?: SLMS_HERO_SECONDS;
}

/**
 * An image cropped to the hero, at the largest of the hero's sizes it has at
 * the hero's shape (see image_sizes() in functions.php), the others in its
 * srcset. WordPress doesn't enlarge, so from a smaller upload the larger sizes
 * come out narrower than that shape. Phones and wide screens crop its sides,
 * so it's shown at least as wide as the hero is tall at its shape
 * (2560 × 1100): 324px tall on narrow screens, 720px on wide ones.
 */
function slms_hero_image( $id, $attr = array() ) {
	$sizes = wp_get_attachment_metadata( $id )['sizes'] ?? array();
	$crop  = current( array_filter( array( 'slms-band-2560', 'slms-band-1920', 'slms-band-1280', 'slms-band-768' ), function ( $size ) use ( $sizes ) {
		return isset( $sizes[ $size ] ) && abs( $sizes[ $size ]['width'] / $sizes[ $size ]['height'] - 2560 / 1100 ) < .01;
	} ) ) ?: 'full';

	return wp_get_attachment_image( $id, $crop, false, $attr + array( 'sizes' => '(max-width: 69em) max(100vw, 755px), max(100vw, 1676px)' ) );
}

/**
 * The embed: for a site WordPress knows how to embed, such as YouTube or
 * Vimeo, its player, cached with the post; for any other, such as a
 * Matterport tour, the address in an iframe. Not by asking other sites how
 * to embed them, as WordPress sandboxes what they say, which breaks tours.
 */
function slms_hero_embed( $id ) {
	global $wp_embed;

	$url  = slms_hero_embed_url( $id );
	$html = false;

	if ( _wp_oembed_get_object()->get_provider( $url, array( 'discover' => false ) ) ) {
		$wp_embed->return_false_on_fail = true;
		$html = $wp_embed->shortcode( array(), $url );
		$wp_embed->return_false_on_fail = false;
	}

	return $html ?: '<iframe src="' . esc_url( $url ) . '" title="' . esc_attr( get_the_title( $id ) ) . '" allow="fullscreen; xr-spatial-tracking" allowfullscreen></iframe>';
}

/**
 * The editor's Hero setting, in the sidebar of posts and pages: a choice of
 * what fills the hero, the embed's address and the gallery's images.
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'slms_hero', 'Hero', 'slms_hero_meta_box', array( 'post', 'page' ), 'side' );
} );

function slms_hero_meta_box( $post ) {
	$choice  = get_post_meta( $post->ID, '_slms_hero', true ) ?: slms_hero_default( $post->ID );
	$gallery = slms_hero_gallery( $post->ID );

	wp_nonce_field( 'slms_hero', 'slms_hero_nonce' );
	?>
	<?php // Each choice's own field shows only when it's chosen, without JavaScript ?>
	<style>
		#slms_hero .slms-hero-field { display: none; margin-top: 1em; }
		#slms_hero:has([value="map"]:checked) .slms-hero-map,
		#slms_hero:has([value="embed"]:checked) .slms-hero-embed,
		#slms_hero:has([value="gallery"]:checked) .slms-hero-gallery { display: block; }
		#slms_hero .slms-hero-images { display: flex; flex-wrap: wrap; gap: 4px; margin: 0 0 .5em; }
		#slms_hero .slms-hero-images img { width: 60px; height: 60px; object-fit: cover; }
	</style>
	<p>What fills the hero at the top of the page.</p>
	<?php foreach ( SLMS_HERO_CHOICES as $value => $label ) : ?>
		<label style="display: block; margin: .25em 0">
			<input type="radio" name="slms_hero" value="<?php echo esc_attr( $value ) ?>" <?php checked( $choice, $value ) ?>>
			<?php echo esc_html( $label ) ?>
		</label>
	<?php endforeach ?>
	<p class="slms-hero-field slms-hero-map">
		<label for="slms_hero_pin">Pin link</label>
		<input type="url" class="widefat" id="slms_hero_pin" name="slms_hero_pin" value="<?php echo esc_attr( get_post_meta( $post->ID, '_slms_hero_pin', true ) ) ?>" placeholder="<?php echo esc_attr( slms_page_url( 'find-us' ) ) ?>">
		<span class="description">Where clicking the pin goes. Empty for the Find us page.</span>
	</p>
	<p class="slms-hero-field slms-hero-embed">
		<label for="slms_hero_embed">Address or iframe code</label>
		<textarea class="widefat" id="slms_hero_embed" name="slms_hero_embed" rows="3"><?php echo esc_textarea( slms_hero_embed_url( $post->ID ) ) ?></textarea>
		<span class="description">A YouTube or Vimeo address, a Matterport tour, or an iframe's code.</span>
	</p>
	<div class="slms-hero-field slms-hero-gallery">
		<div class="slms-hero-images">
			<?php foreach ( $gallery as $image ) : ?>
				<?php echo wp_get_attachment_image( $image, 'thumbnail' ) ?>
			<?php endforeach ?>
		</div>
		<input type="hidden" name="slms_hero_gallery" value="<?php echo esc_attr( implode( ',', $gallery ) ) ?>">
		<button type="button" class="button slms-hero-choose">Choose images</button>
		<p>
			<label for="slms_hero_seconds">Seconds per image</label>
			<input type="number" class="small-text" id="slms_hero_seconds" name="slms_hero_seconds" min="1" max="60" value="<?php echo esc_attr( slms_hero_seconds( $post->ID ) ) ?>">
		</p>
	</div>
	<?php
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['slms_hero_nonce'] ) || ! wp_verify_nonce( $_POST['slms_hero_nonce'], 'slms_hero' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Each setting kept only where it isn't the default, so posts that don't
	// use them hold nothing
	$save = function ( $key, $value, $default = '' ) use ( $post_id ) {
		if ( $value === $default ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	};

	$choice = sanitize_key( $_POST['slms_hero'] ?? '' );
	$save( '_slms_hero', isset( SLMS_HERO_CHOICES[ $choice ] ) ? $choice : 'featured', slms_hero_default( $post_id ) );

	// From iframe code, just its address: the hero makes its own iframe
	$embed = trim( wp_unslash( $_POST['slms_hero_embed'] ?? '' ) );
	if ( false !== stripos( $embed, '<iframe' ) ) {
		$tags  = new WP_HTML_Tag_Processor( $embed );
		$embed = $tags->next_tag( 'iframe' ) ? (string) $tags->get_attribute( 'src' ) : '';
	}
	$save( '_slms_hero_embed', esc_url_raw( $embed, array( 'https', 'http' ) ) );

	$save( '_slms_hero_pin', esc_url_raw( trim( wp_unslash( $_POST['slms_hero_pin'] ?? '' ) ), array( 'https', 'http' ) ) );

	$gallery = array_filter( array_map( 'absint', explode( ',', $_POST['slms_hero_gallery'] ?? '' ) ) );
	$save( '_slms_hero_gallery', implode( ',', $gallery ) );

	// Between 1 and 60 seconds
	$seconds = min( 60, max( 1, (int) ( $_POST['slms_hero_seconds'] ?? SLMS_HERO_SECONDS ) ) );
	$save( '_slms_hero_seconds', $seconds, SLMS_HERO_SECONDS );
} );

// The gallery's Choose images button opens the media library
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && in_array( get_current_screen()->post_type, array( 'post', 'page' ), true ) ) {
		wp_enqueue_media();
		wp_enqueue_script( 'slms_hero_editor', SLMS_STATIC_URI . '/js/hero-editor.js', array(), filemtime( SLMS_THEME_DIR . '/static/js/hero-editor.js' ), true );
	}
} );
