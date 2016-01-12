<?php if ( have_posts() ): ?>
	<div class="wrap">
		<h1 class="page--title"><a href="<?php echo get_permalink( get_option('page_for_posts') ); ?>">Blog</a></h1>
		<?php if ( have_posts() ) : ?>
			<div class="posts">
			<?php while ( have_posts() ) : the_post(); ?><!-- 
			 --><article <?php post_class(); ?>>
					<header>
						<h1 class="h2"><a href="<?php the_permalink(); ?>"><?php the_title() ?></a></h1>
						<time datetime="<?php echo get_the_date('c') ?>"><?php the_date() ?></time>
					</header><!-- /header -->
					<?php the_excerpt() ?>
					<a href="<?php the_permalink() ?>">Read more...</a>
				</article><!-- 
		 --><?php endwhile ?>
			</div>
			<div class="posts"><?php the_posts_navigation(); ?></div>
		<?php else : ?>
		<?php endif; ?>
	</div>


<?php endif ?>