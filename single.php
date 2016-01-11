<?php if ( have_posts() ): ?>
	<div class="wrap">
		<?php while ( have_posts() ) : the_post(); ?>
			<article>
				<?php the_post_thumbnail(); ?>
				<h1 class="page--title"><?php the_title() ?></h1>
				<?php the_content() ?>
			</article>
		<?php endwhile ?>
	</div>
<?php endif ?>