<?php
/**
 * Template Name: Product Service Update
 * The template for displaying product service update form.
 */
defined("ABSPATH") || exit();
if(!is_user_logged_in() || !current_user_can( 'manage_options' )) {
    wp_die(__("Sorry, You are not allowed to access this page.", 'zen-product-service-update'));
}
get_header(); ?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <?php
            while ( have_posts() ) :
                the_post();

                do_action( 'storefront_page_before' );

                ?><article id="post-<?php the_ID(); ?>" <?php post_class(); ?>><?php
                    echo do_shortcode('[gravityform id="1" title="true"]');
                ?></article><!-- #post-## --><?php

                /**
                 * Functions hooked in to storefront_page_after action
                 *
                 * @hooked storefront_display_comments - 10
                 */
                do_action( 'storefront_page_after' );

            endwhile; // End of the loop.
            ?>

        </main><!-- #main -->
    </div><!-- #primary -->

<?php
do_action( 'storefront_sidebar' );
get_footer();
