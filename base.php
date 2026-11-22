<!doctype html>
<!--

	MMMMMMMM                    MMMMMMMM
	 MMMMMMMM                  MMMM MMMM
	MMMMMMMMMM                MMMM   MMM
	   MMMMMMMM              MMMMM   MMM
	MMMMMMMMMMMM            MMMMMM   MMM
	 MMMMMMMMMMMM          MMMMMMM   MMM
	MMMMMMMMMMMMMM        MMMMMMMM   MMM
	   MMMMMMMMMMMM      MMMMMMMMM   MMM
	MMMMMMMMMMMMMMMM    MMMMMMMMMM   MMM
	 MMMMMMMM MMMMMMM  MMMMMMM MMM   MMM
	MMMMMMMMM  MMMMM  MMMMMMM  MMM   MMM
	   MMMMMM   MMM  MMMMMMM   MMM   MMM
	MMMMMMMMM       MM MMMM    MMM   MMM
	 MMMMMMMM      MM   MM     MMM   MMM
	MMMMMMMMM       MM MM      MMM   MMM
	   MMMMMM        MMM       MMM   MMM
	MMMMMMMMM         M        MMM   MMM
	 MMMMMMMM                  MMMM MMMM
	MMMMMMMMM                  MMMMMMMMM

	South London Makerspace
	=======================

	If you are reading this you obviously know your way around a web browser.
	Why not volunteer to help with the various development and maintenance projects Makerspace has.

	Email us: systems@southlondonmakerspace.org

-->
<html <?php language_attributes()?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ) ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<?php wp_head() ?>
	</head>
	<body <?php body_class() ?>>
		<?php wp_body_open() ?>
		<a class="skip-link btn" href="#content">Skip to content</a>
		<header>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo"><?php bloginfo( 'name' ) ?></a>
			<?php if ( has_nav_menu( 'slms_home_links' ) ) : ?>
				<nav class="site-nav" aria-label="Site">
					<?php // On narrow screens the summary is a menu button; on wide ones it's hidden and the links always show ?>
					<details>
						<summary aria-label="Menu"><?php echo slms_icon( 'bars', '1.5em' ) ?></summary>
						<?php
							// The basket, for logged-in users, is the menu's last item
							$cart = slms_cart_link();
							wp_nav_menu( array(
								'theme_location' => 'slms_home_links',
								'container'      => false,
								'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s' . ( $cart ? '<li class="menu-item-cart">' . $cart . '</li>' : '' ) . '</ul>',
							) );
						?>
					</details>
				</nav>
			<?php endif; ?>
			<?php if ( is_front_page() ) : ?>
				<div class="hero" id="map">
					<?php // Its credit, which OpenStreetMap's licence asks to be shown when the map loads: open, then closed after a few seconds (see main.js) ?>
					<?php slms_hero_caption( 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, map tiles by <a href="https://protomaps.com">Protomaps</a>', 'Map credits', 'copyright', true ); ?>
				</div>
			<?php elseif ( slms_has_featured_image() ) : ?>
				<figure class="hero">
					<?php
						// The largest of the band's sizes the image has at the band's
						// shape (see image_sizes() in functions.php), the others in its
						// srcset. WordPress doesn't enlarge, so from a smaller upload
						// the larger sizes come out narrower than that shape.
						// Phones and wide screens crop its sides, so it's shown at
						// least as wide as the band is tall at its shape (2560 × 1100):
						// 324px tall on narrow screens, 720px on wide ones.
						$sizes = wp_get_attachment_metadata( get_post_thumbnail_id( get_queried_object_id() ) )['sizes'] ?? array();
						$band  = current( array_filter( array( 'slms-band-2560', 'slms-band-1920', 'slms-band-1280', 'slms-band-768' ), function ( $size ) use ( $sizes ) {
							return isset( $sizes[ $size ] ) && abs( $sizes[ $size ]['width'] / $sizes[ $size ]['height'] - 2560 / 1100 ) < .01;
						} ) ) ?: 'full';
						echo get_the_post_thumbnail( get_queried_object_id(), $band, array( 'sizes' => '(max-width: 69em) max(100vw, 755px), max(100vw, 1676px)', 'loading' => false, 'fetchpriority' => 'high' ) );
					?>
					<?php if ( $caption = get_the_post_thumbnail_caption( get_queried_object_id() ) ) : ?>
						<?php slms_hero_caption( nl2br( esc_html( $caption ) ), 'Image caption', 'camera' ); ?>
					<?php endif; ?>
				</figure>
			<?php endif; ?>
		</header>
		<?php include slms_template_path(); ?>
		<?php // The Supporter widgets in the Supporters area, if there are any ?>
		<?php if ( is_active_sidebar( 'slms_supporters' ) ) : ?>
		<section class="ruled">
			<div class="wrap">
				<div class="widgets">
					<div class="widget widget__large">
						<h2 class="widgettitle">Supporters</h2>
						<ul class="sponsors"><?php dynamic_sidebar( 'slms_supporters' ); ?></ul>
					</div>
				</div>
			</div>
		</section>
		<?php endif; ?>
		<?php
			$find_us      = slms_page_url( 'find-us' );
			$open_evening = ( new slms_events() )->next_open_evening();
			$menu         = wp_get_nav_menu_object( get_nav_menu_locations()['slms_footer_links'] ?? 0 );
		?>
		<footer>
		<section class="wrap ruled">
			<div class="footer">
				<div>
					<h2>Visit</h2>
					<p>Arch 1129&ndash;1130<br>41 Norwood Road<br>London SE24 9AJ</p>
					<?php if ( $find_us ) : ?>
						<p><?php echo slms_more_link( $find_us, 'Find us' ) ?></p>
					<?php endif ?>
				</div>
				<div>
					<h2>Open evenings</h2>
					<p>The way to look round and meet members.</p>
					<?php if ( $open_evening ) : ?>
						<p>Next: <?php echo esc_html( wp_date( 'l, jS F', $open_evening['start']->getTimestamp() ) ) ?><br><?php echo esc_html( wp_date( 'H:i', $open_evening['start']->getTimestamp() ) . '–' . wp_date( 'H:i', $open_evening['end']->getTimestamp() ) ) ?></p>
						<p><?php echo slms_more_link( $open_evening['url'], 'Event details' ) ?></p>
					<?php endif ?>
				</div>
				<?php if ( $menu ) : ?>
					<div>
						<h2><?php echo esc_html( $menu->name ) ?></h2>
						<?php wp_nav_menu( array( 'theme_location' => 'slms_footer_links', 'container' => false, 'menu_class' => 'links', 'depth' => 1 ) ); ?>
					</div>
				<?php endif ?>
			</div>
		</section>
		<section class="wrap ruled">
			<?php slms_social_links() ?>
		</section>
		<section class="wrap ruled">
			<p>&copy;2013&mdash;<?php echo date( 'Y' ); ?> South London Makerspace</p>
			<p>Proud to be part of the London <a href="http://openworkshopnetwork.com/">Open Workshop Network</a></p>
		</section>
		</footer>
		<?php wp_footer() ?>
	</body>
</html>
