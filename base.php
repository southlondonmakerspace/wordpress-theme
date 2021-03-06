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

	Email us: trustees@southlondonmakerspace.org

-->
<html <?php language_attributes()?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ) ?>" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

		<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ) ?> RSS2 Feed" href="<?php bloginfo( 'rss2_url' ) ?>" />
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ) ?>" />

		<title><?php wp_title( '&mdash;', true, 'right' ) ?><?php echo bloginfo( 'name' ) ?></title>

		<?php wp_head() ?>
	</head>
	<body <?php body_class() ?>>
		<!--[if lt IE 8]>
			<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
			<div class="browserupgrade"></div>
		<![endif]-->
		<header>
			<a href="<?php bloginfo( 'url' ); ?>" class="logo"><?php bloginfo( 'name' ) ?></a>
			<?php if ( is_front_page() ) : ?>
				<div class="hero" id="map"></div>
			<?php endif; ?>
		</header>
		<?php include slms_template_path(); ?>
		<section class="ruled">
			<div class="wrap">
				<div class="widgets">
					<div class="widget widget__large">
						<h1 class="widgettitle">Supporters</h1>
						<?php

							$sponsors = get_post_by_slug('sponsors');

							echo '<div class="sponsors">' . apply_filters('the_content', $sponsors->post_content ) . '</div>';

							$children = get_children([
								'post_parent' => $sponsors->ID,
								'post_status' => 'publish'
							] );
						?>
					</div>
				</div>
			</div>
		</section>
		<section class="wrap ruled">
			<p>&copy;2013&mdash;<?php echo date( 'Y' ); ?> South London Makerspace</p>
			<p>Proud to be part of the London <a href="http://openworkshopnetwork.com/" target="_blank">Open Workshop Network</a></p>
			<p>
				Found an issue? <a href="https://github.com/southlondonmakerspace/wordpress-theme/issues" target="_blank">Let us know</a> or
				<a href="https://github.com/southlondonmakerspace/wordpress-theme" target="_blank">fork on Github</a>
			</p>
		</section>
		<?php wp_footer() ?>
	</body>
</html>
