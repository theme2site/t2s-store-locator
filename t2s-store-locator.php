<?php

/**
 * Plugin Name: T2S Store Locator
 * Description: T2S Store Locator plugin for WordPress allows users to easily display a map of their store locations on their website.
 * Author: Theme2Site
 * Author URI: http://www.theme2site.com/plugins/t2s-store-locator/
 * Version: 1.0.0
 * Text Domain: t2s-store-locator
 * Domain Path: languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define the class.
if (!class_exists('T2S_Store_Locator')) {

    class T2S_Store_Locator
    {
        public function __construct()
        {
            $this->define_constants();
            $this->includes();
            $this->init();
        }

        /**
         * define constants
         */
        private function define_constants()
        {
            define('T2S_STORE_LOCATOR_PLUGIN_BASENAME', plugin_basename(__FILE__));
            define('T2S_STORE_LOCATOR_PLUGIN_NAME', trim(dirname(T2S_STORE_LOCATOR_PLUGIN_BASENAME), '/'));
            define('T2S_STORE_LOCATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
            define('T2S_STORE_LOCATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
            define('T2S_STORE_LOCATOR_OPTIONS_PREFIX', 'T2S_StoreLocator_');
            define('T2S_STORE_LOCATOR_PLUGIN_VERSION', '1.0.0');
        }

        /**
         * Include required core files used in admin and on the frontend.
         */
        public function includes()
        {
            include_once('includes/t2s-store-locator-admin.php');
        }

        /**
         * Init T2S_StoreLocator when WordPress Initialises.
         */
        public function init()
        {
            // Register custom post type
            add_action('init', array($this, 'T2S_StoreLocator_post_type'));

            // Add settings link
            add_filter('plugin_action_links_' . T2S_STORE_LOCATOR_PLUGIN_BASENAME, array($this, 'T2S_StoreLocator_settings_link'));

            // Add shortcode
            add_shortcode('T2S_StoreLocator', array($this, 'T2S_StoreLocator_shortcode'));

            add_action('wp_enqueue_scripts', array($this, 'T2S_StoreLocator_frontend_enqueue'));

            // register ajax action
            add_action('wp_ajax_T2S_StoreLocator_get_stores', array($this, 'T2S_StoreLocator_get_stores'));
            add_action('wp_ajax_nopriv_T2S_StoreLocator_get_stores', array($this, 'T2S_StoreLocator_get_stores'));
        }

        public function T2S_StoreLocator_post_type()
        {
            // Register custom post type
            $labels = array(
                'name' => __('T2S Stores', 't2s-store-locator'),
                'singular_name' => __('Store', 't2s-store-locator'),
                'name_admin_bar' => 'Store',
                'add_new' => __('Add Store', 't2s-store-locator'),
                'add_new_item' => __('Add Store', 't2s-store-locator'),
            );
            $args = array(
                'labels' => apply_filters('t2s_stores_labels', $labels),
                'description' => '',
                'public' => true,
                'publicly_queryable' => true,
                'exclude_from_search' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => true,
                'query_var' => true,
                'can_export' => true,
                'rewrite' => '',
                'capability_type' => 'post',
                'has_archive' => true,
                'hierarchical' => true,
                'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'),
                'menu_position' => 20,
                'menu_icon' => 'dashicons-location',
            );
            register_post_type('t2s_stores', apply_filters('t2s_stores_register_args', $args, 't2s_stores'));

            // // Register taxonomy
            // $labels = array(
            //     'name' => 'Store Categories',
            //     'singular_name' => 'Store Category',
            //     'menu_name' => 'Store Categories',
            // );
            // $args = array(
            //     'label' => 'Store Categories',
            //     'labels' => apply_filters('t2s_store_categories_labels', $labels),
            //     'hierarchical' => true,
            //     'public' => true,
            //     'show_ui' => true,
            //     'show_in_nav_menus' => true,
            //     'show_tagcloud' => true,
            //     'meta_box_cb' => null,
            //     'show_admin_column' => true,
            //     'update_count_callback' => '',
            //     'query_var' => 't2s_store_categories',
            //     'rewrite' => true,
            //     'sort' => '',
            // );
            // register_taxonomy('t2s_store_categories', 't2s_stores', apply_filters('t2s_store_categories_register_args', $args, 't2s_store_categories', 't2s_stores'));

            // Clear the permalinks after the post type has been registered.
            flush_rewrite_rules();
        }

        /**
         * Add frontend scripts and styles
         */
        function T2S_StoreLocator_frontend_enqueue()
        {
            wp_enqueue_script("jquery");
            wp_register_script('autocomplete', T2S_STORE_LOCATOR_PLUGIN_URL . '/assets/js/jquery.autocomplete.min.js', array('jquery'));
            wp_enqueue_script('autocomplete');
            wp_register_script('googleapis', 'https://maps.googleapis.com/maps/api/js?key=' . get_option('T2S_StoreLocator_google_map_api') . '&callback=Function.prototype', array('jquery'));
            wp_enqueue_script('googleapis');

            wp_enqueue_style('enqueue-bootstrap', T2S_STORE_LOCATOR_PLUGIN_URL . '/assets/css/bootstrap.min.css', array(), false);
            wp_enqueue_style('font-awesome', T2S_STORE_LOCATOR_PLUGIN_URL . '/assets/css/font-awesome.min.css', array(), false);
            wp_enqueue_style('t2s-store-locator-base', T2S_STORE_LOCATOR_PLUGIN_URL . '/assets/css/base.css', array(), false);
        }

        /**
         * Plugin setting link
         */
        public function T2S_StoreLocator_settings_link($links)
        {
            $settings_link = '<a href="options-general.php?page=T2SStoreLocator_setting">' . __('Settings') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        /**
         * Register Shortcode
         */
        function T2S_StoreLocator_shortcode()
        {
            ob_start();
            //判断哪个地图
            $map_type = get_option('T2SStoreLocator_map_type');
            if($map_type == 'google'){
                include(T2S_STORE_LOCATOR_PLUGIN_DIR . '/template/google.php');
            }elseif ($map_type == 'baidu') {
                include(T2S_STORE_LOCATOR_PLUGIN_DIR . '/template/baidu.php');
            }elseif ($map_type == 'amap') {
                include(T2S_STORE_LOCATOR_PLUGIN_DIR . '/template/amap.php');
            }
            $output = ob_get_clean();

            return $output;
        }

        /**
         * Store map
         *
         * @return void
         */
        function T2S_StoreLocator_get_stores()
        {
            if (isset($_POST['action']) && $_POST['action'] == 'T2S_StoreLocator_get_stores') {
                $storesSearchInput = $_POST['storesSearchInput'];
                $query_args = array(
                    'post_type' => 't2s_stores',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'post_parent' => 0,
                    's' => $storesSearchInput
                );
                $the_query = new WP_Query($query_args);
                $data1 = '';
                $data2 = '';
                $locations = [];
                if ($the_query->have_posts()) {
                    while ($the_query->have_posts()) : $the_query->the_post();
                        global $post;
                        $address = get_post_meta($post->ID, 'T2SStoreLocator_meta_address') ? get_post_meta($post->ID, 'T2SStoreLocator_meta_address')[0] : '';
                        $lng = get_post_meta($post->ID, 'T2SStoreLocator_meta_longitude') ? get_post_meta($post->ID, 'T2SStoreLocator_meta_longitude')[0] : '';
                        $lat = get_post_meta($post->ID, 'T2SStoreLocator_meta_latitude') ? get_post_meta($post->ID, 'T2SStoreLocator_meta_latitude')[0] : '';
                        $location  =  [
                            'title'   => get_the_title(),
                            'link'    => get_the_permalink(),
                            'address' => $address,
                            'lng'     => $lng,
                            'lat'     => $lat
                        ];
                        $data1 .= '<div class="t2s-stores-search-item">';
                        $data1 .= '<div class="t2s-stores-search-left">';
                        $data1 .= '<h4 class="t2s-stores-search-title"><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h4>';
                        $data1 .= '<div class="t2s-stores-search-address" data-lat="' . $location['lat'] . '" data-lng="' . $location['lng'] . '">' . $location['address'] . '</div>';
                        $data1 .= '</div>';
                        $data1 .= '<a class="t2s-stores-search-right" href="' . get_the_permalink() . '" style="background-image: url(' . get_the_post_thumbnail_url() . ');"></a>';
                        $data1 .= '</div>';
                        $data2 .= '<div class="marker" data-lat="' . esc_attr($location['lat']) . '" data-lng="' . esc_attr($location['lng']) . '">';
                        $data2 .= '<h3><a class="marker-title-link" href="' . get_the_permalink() . '">' . get_the_title() . '</a></h3>';
                        $data2 .= '<p><em>' . esc_html($location['address']) . '</em></p>';
                        $data2 .= '</div>';
                        $locations[] = $location;
                        //Composite array
                        $result = array('top' => $data1, 'bottom' => $data2);
                    //Output
                    endwhile;
                } else {
                    $result = array('top' => '<p>No Result</p>', 'bottom' => '', 'locations' => []);
                }
            } else {
                $result = array('top' => '<p>No Result</p>', 'bottom' => '', 'locations' => []);
            }
            $result['locations'] = $locations;
            $result = json_encode($result);
            echo $result;
            wp_reset_query();
            die();
        }
    }
}
new T2S_Store_Locator();
