<?php if ( have_posts() ): ?>
	<main class="wrap" id="content">
		<h1 class="page--title"><a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) ?: 'Blog' ); ?></a></h1>
		<div class="posts">
		<?php while ( have_posts() ) : the_post(); ?><!--
		 --><?php get_template_part( 'parts/post-card' ); ?><!--
		 --><?php endwhile ?>
		</div>
		<div class="posts"><?php the_posts_navigation(); ?></div>
	</main>
<?php endif; ?>
