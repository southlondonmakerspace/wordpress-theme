<?php if ( have_posts() ): ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<section id="intro">
			<h1><?php the_title() ?></h1>
			<?php the_content() ?>
			<?php wp_nav_menu( array(
					'theme_location'	=> 'slms_home_links',
					'container'			=> false,
				) );
			?>
		</section>
	<?php endwhile ?>
<?php endif ?>
<div class="wrap">
	<div class="widgets">
		<?php dynamic_sidebar( 'slms_home_sidebar' ) ?>
	</div>
</div>