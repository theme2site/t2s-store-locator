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
            include_once('includes/admin-install.php');
            include_once('includes/module-detail-install.php');
        }

        /**
         * Init T2S_StoreLocator when WordPress Initialises.
         */
        public function init()
        {
            // Add settings link
            add_filter('plugin_action_links_' . T2S_STORE_LOCATOR_PLUGIN_BASENAME, array($this, 'T2S_StoreLocator_settings_link'));

            // Add shortcode
            add_shortcode('T2S_StoreLocator', array($this, 'T2S_StoreLocator_shortcode'));

            // Add shortcode only amap
            add_shortcode('T2S_StoreLocator_Only_Map', array($this, 'T2S_StoreLocator_Only_Map_shortcode'));

            add_action('wp_enqueue_scripts', array($this, 'T2S_StoreLocator_frontend_enqueue'));

            // register ajax action
            add_action('wp_ajax_T2S_StoreLocator_get_stores', array($this, 'T2S_StoreLocator_get_stores'));
            add_action('wp_ajax_nopriv_T2S_StoreLocator_get_stores', array($this, 'T2S_StoreLocator_get_stores'));
        }

        /**
         * Add frontend scripts and styles
         */
        function T2S_StoreLocator_frontend_enqueue()
        {
            wp_enqueue_script("jquery");
            wp_register_script('autocomplete', T2S_STORE_LOCATOR_PLUGIN_URL . '/assets/js/jquery.autocomplete.min.js', array('jquery'));
            wp_enqueue_script('autocomplete');

            wp_enqueue_style('enqueue-bootstrap', T2S_STORE_LOCATOR_PLUGIN_URL . '/assets/css/bootstrap.min.css', array(), false);
            wp_enqueue_style('font-awesome', T2S_STORE_LOCATOR_PLUGIN_URL . '/assets/css/font-awesome.min.css', array(), false);
            wp_enqueue_style('t2s-store-locator-base', T2S_STORE_LOCATOR_PLUGIN_URL . '/assets/css/base.css', array(), false);
        }

        /**
         * Plugin setting link
         */
        public function T2S_StoreLocator_settings_link($links)
        {
            $settings_link = '<a href="admin.php?page=t2s_stores_setting">' . __('Settings') . '</a>';
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

        function T2S_StoreLocator_Only_Map_shortcode()
        {
            ob_start();
            //判断哪个地图
            $map_type = get_option('T2SStoreLocator_map_type');
            if($map_type == 'google'){
                include(T2S_STORE_LOCATOR_PLUGIN_DIR . '/template/google-only-map.php');
            }elseif ($map_type == 'baidu') {
                include(T2S_STORE_LOCATOR_PLUGIN_DIR . '/template/baidu-only-map.php');
            }elseif ($map_type == 'amap') {
                include(T2S_STORE_LOCATOR_PLUGIN_DIR . '/template/amap-only-map.php');
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
                global $wpdb;
                $table_name = $wpdb->prefix . 't2s_stores';

                // 构建 SQL 查询语句
                $query = $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE name LIKE '%%%s%%'",
                    '%' . $wpdb->esc_like($storesSearchInput) . '%',
                );
                // 执行查询
                $results = $wpdb->get_results($query);
                $data1 = '';
                $locations = [];
                if(count($results) > 0){
                    foreach ($results as $key => $value) {
                        $data1 .= '<div class="t2s-stores-search-item">';
                        $data1 .= '<div class="t2s-stores-search-left">';
                        $data1 .= '<h4 class="t2s-stores-search-title"><a href="' . esc_url(site_url('t2s-store/' . $value->id) . '/') . '">' . $value->name . '</a></h4>';
                        $data1 .= '<div class="t2s-stores-search-address" data-lat="' . $value->lat . '" data-lng="' . $value->lon . '">' . $value->address . '</div>';
                        $data1 .= '</div>';
                        $data1 .= '<a class="t2s-stores-search-right" href="' . esc_url(site_url('t2s-store/' . $value->id) . '/') . '" style="background-image: url(' .$value->image_url. ');"></a>';
                        $data1 .= '</div>';
                        $result = array('top' => $data1);
                        $locations[] = [
                            'id' => $value->id,
                            'name' => $value->name,
                            'address' => $value->address,
                            'lat' => $value->lat,
                            'lon' => $value->lon,
                            'lng' => $value->lon,
                            'city' => $value->city,
                            'state' => $value->state,
                            'country' => $value->country,
                            'postal_code' => $value->postal_code,
                            'overview' => $value->overview,
                            'image_url' => $value->image_url,
                            'link' => esc_url(site_url('t2s-store/' . $value->id) . '/'),
                        ];
                    }
                } else {
                    $result = array('top' => '<p>No Result</p>', 'locations' => []);
                }
            } else {
                $result = array('top' => '<p>No Result</p>', 'locations' => []);
            }
            $result['locations'] = $locations;
            // $result = json_encode($result);
            $result = json_encode($result, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES);
            echo $result;
            wp_reset_query();
            die();
        }
    }
}
new T2S_Store_Locator();
