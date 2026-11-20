<?php if ( have_posts() ): ?>
<main class="wrap" id="content">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'post' ); ?>>
			<?php get_template_part( 'parts/page-header', null, array( 'byline' => true ) ); ?>

			<?php the_content() ?>
		</article>
	<?php endwhile ?>
	<div class="widgets">
		<?php dynamic_sidebar( 'slms_single_post' ); ?>
	</div>
</main>
<?php endif;
