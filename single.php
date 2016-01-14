<?php if ( have_posts() ): ?>
	<div class="wrap">
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'article' ); ?>>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="post--thumbnail">
						<?php the_post_thumbnail( 'large' ); $background_image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' ); ?>
						<style>
							@media screen and (max-width: 48em) {
								body {
									background: url(<?php echo array_shift( $background_image ); ?>) no-repeat;
									background-size: contain;
								}
							}
						</style>
						<?php
							$attachment = get_post( get_post_thumbnail_id() );
							echo apply_filters( 'the_content', $attachment->post_excerpt );
						 ?>
					</figure>
				<?php endif ?>
				<header>
					<h1 class="page--title"><?php the_title(); ?><?php edit_post_link( 'Edit' ); ?></h1>
					<time datetime="<?php echo get_the_date( 'c' ); ?>"><?php the_date(); ?></time>
				</header>
				<?php the_content() ?>
			</article>
		<?php endwhile ?>
		<div class="widgets">
			<?php dynamic_sidebar( 'slms_single_post' ); ?>
		</div>
	</div>
<?php endif; ?>
