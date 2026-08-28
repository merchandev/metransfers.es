<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mt_set_blog_page_as_posts_page() {
    if ( get_option( 'mt_blog_page_set_v1' ) ) {
        return;
    }

    $blog_page = get_page_by_path( 'blog' );
    if ( $blog_page ) {
        // Set the static front page settings
        // Usually, to set a posts page, 'show_on_front' must be 'page'
        // But we don't want to mess up their front page if they haven't set one!
        // Actually, setting 'page_for_posts' works if 'show_on_front' is 'page'.
        
        $current_show_on_front = get_option( 'show_on_front' );
        if ( $current_show_on_front !== 'page' ) {
            // We shouldn't brutally change 'show_on_front' to 'page' without setting 'page_on_front'
            // Let's check if there is a Home page.
            $home_page = get_page_by_path( 'inicio' ) ?: get_page_by_path( 'home' );
            if ( $home_page ) {
                update_option( 'show_on_front', 'page' );
                update_option( 'page_on_front', $home_page->ID );
            }
        }
        
        // Assign the blog page as the posts page
        update_option( 'page_for_posts', $blog_page->ID );
        update_option( 'mt_blog_page_set_v1', true );
    }
}
add_action( 'init', 'mt_set_blog_page_as_posts_page' );
