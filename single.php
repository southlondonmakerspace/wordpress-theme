<?php if ( have_posts() ): ?>
	<div class="wrap">
		<?php while ( have_posts() ) : the_post(); ?>
			<article class="article">
				<?php if ( has_post_thumbnail() ): ?>
					<figure class="post--thumbnail">
						<?php the_post_thumbnail('large'); ?>
						
					</figure>
				<?php endif ?>
				<header>
					<h1 class="page--title"><?php the_title() ?><?php edit_post_link('Edit'); ?></h1>
					<time datetime="<?php echo get_the_date('c') ?>"><?php the_date() ?></time>
				</header>
				<?php the_content() ?>
			</article>
		<?php endwhile ?>
	</div>
<?php endif ?>