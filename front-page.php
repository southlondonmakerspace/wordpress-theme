<main class="wrap" id="content">
	<?php if ( have_posts() ) : the_post(); ?>
		<section id="intro">
			<div>
				<h1 class="page--title"><?php the_title() ?></h1>
				<?php the_content() ?>
			</div>
			<?php if ( $join = slms_page_url( 'join' ) ) : ?>
				<a class="btn" href="<?php echo esc_url( $join ) ?>">Join</a>
			<?php endif ?>
		</section>
	<?php endif ?>
	<div class="widgets">
		<?php dynamic_sidebar( 'slms_home_sidebar' ); ?>
	</div>
</main>
