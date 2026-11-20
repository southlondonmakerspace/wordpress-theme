<?php
// The shop, its categories and products, in the theme's page layout: the
// title in the page's header, beside the logo, WooCommerce's own below it.
// See SLMS_Theme::woocommerce_setup for the titles this replaces.
?>
<main class="wrap" id="content">
	<article class="post">
		<?php get_template_part( 'parts/page-header', null, is_singular() ? array() : array( 'title' => woocommerce_page_title( false ) ) ); ?>
		<?php woocommerce_content(); ?>
	</article>
</main>
