<?php
// A post or page's header: its title, beside the logo or in the card, and,
// with $args['byline'], the date and author. $args['title'] replaces the
// post's title, as on the shop's archive pages.
?>
<header>
	<h1 class="page--title"><?php echo $args['title'] ?? get_the_title(); ?></h1>
	<?php if ( ! empty( $args['byline'] ) ) : ?>
		<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time><span class="author"><?php the_author(); ?></span>
	<?php endif ?>
</header>
