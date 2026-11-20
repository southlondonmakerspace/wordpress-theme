<?php
// A post in a list of them, as in the blog and the Blog widget: its title,
// date, excerpt and a Read more link. $args['heading'] is the title's
// heading level: h2 under the page's title, h3 under a widget's. No
// whitespace around the article, as the lists put their posts side by side as
// inline blocks.
$heading = ( $args['heading'] ?? '' ) === 'h3' ? 'h3' : 'h2';
?><article <?php post_class(); ?>>
	<header>
		<<?php echo $heading; ?> class="h2"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo $heading; ?>>
		<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
	</header>
	<?php the_excerpt(); ?>
	<?php slms_read_more(); ?>
</article>