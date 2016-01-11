<?php if ( have_posts() ): ?>
	<div class="wrap">
		<h1 class="page--title">Blog</h1>
		<div class="posts">
		<?php while ( have_posts() ) : the_post(); ?><!-- 
		 --><article <?php post_class(); ?>>
				<h1 class="h2"><?php the_title() ?></h1>
				<time datetime="<?php echo get_the_date('c') ?>"><?php the_date() ?></time>
				<?php the_excerpt() ?>
				<a href="<?php the_permalink() ?>">Read more...</a>
			</article><!-- 
	 --><?php endwhile ?>
		</div>
	</div>
<?php endif ?>