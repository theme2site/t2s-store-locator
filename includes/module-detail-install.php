<?php

// Register module detail route
function t2s_store_detail_route() {
    add_rewrite_rule('^t2s-store/([^/]+)/?', 'index.php?pagename=t2s-store&store_id=$matches[1]', 'top');
}
add_action('init', 't2s_store_detail_route');

function t2s_store_detail_query_vars($vars) {
    $vars[] = 'store_id';
    return $vars;
}
add_filter('query_vars', 't2s_store_detail_query_vars');

function t2s_store_detail_template($template)
{
    if (get_query_var('pagename') == 't2s-store') {
        return T2S_STORE_LOCATOR_PLUGIN_DIR . 'template/store-detail.php';
    }
    return $template;
}
add_filter('template_include', 't2s_store_detail_template');

// flush rewrite rules
// function t2s_store_flush_rewrite_rules() {
//     global $wp_rewrite;
//     $wp_rewrite->flush_rules();
// }
// add_action( 'init', 't2s_store_flush_rewrite_rules');
