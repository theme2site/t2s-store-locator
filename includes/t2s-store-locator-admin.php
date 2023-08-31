<?php

// Enqueueing scripts and styles
function T2SStoreLocator_style()
{
    wp_enqueue_style('t2s-bootstrap', plugins_url('../assets/css/bootstrap.min.css', __FILE__));
    wp_enqueue_style('font-awesome', plugins_url('../assets/css/font-awesome.min.css', __FILE__));
    wp_enqueue_style('admin-base', plugins_url('../assets/css/admin-base.css', __FILE__));
    wp_enqueue_script('t2s-bootstrap-js', plugins_url('../assets/js/bootstrap.bundle.min.js', __FILE__));
    wp_enqueue_script('admin-base-js', plugins_url('../assets/js/admin-base.js', __FILE__));
}
add_action('admin_enqueue_scripts', 'T2SStoreLocator_style');

// Add meta box
add_action('add_meta_boxes', 'T2SStoreLocator_add_meta_box');
function T2SStoreLocator_add_meta_box()
{
    add_meta_box('T2SStoreLocator_meta', __('Store Location', 't2s-store-locator'), 'T2SStoreLocator_meta_box_cb', 't2s_stores', 'normal', 'high');
}

function T2SStoreLocator_meta_box_cb()
{
    global $post;
    $values = get_post_custom($post->ID);
    $address = isset($values['T2SStoreLocator_meta_address']) ? esc_attr($values['T2SStoreLocator_meta_address'][0]) : '';
    $latitude = isset($values['T2SStoreLocator_meta_latitude']) ? esc_attr($values['T2SStoreLocator_meta_latitude'][0]) : '';
    $longitude = isset($values['T2SStoreLocator_meta_longitude']) ? esc_attr($values['T2SStoreLocator_meta_longitude'][0]) : '';
    $center_latitude = get_option('T2SStoreLocator_center_latitude');
    $center_longitude = get_option('T2SStoreLocator_center_longitude');
    $map_type = get_option('T2SStoreLocator_map_type');
    $google_api = get_option('T2S_StoreLocator_google_map_api');
    $baidu_api = get_option('T2S_StoreLocator_baidu_map_api');
    $amap_api = get_option('T2S_StoreLocator_amap_api');
    $amap_api_secret = get_option('T2S_StoreLocator_amap_api_secret');
?>
    <div class="row mt-3">
        <div class="col-12 form-group">
            <label class="form-label" for="T2SStoreLocator_meta_address"><?php _e('Address', 't2s-store-locator'); ?></label>
            <input class="form-control" type="text" name="T2SStoreLocator_meta_address" id="T2SStoreLocator_meta_address" value="<?php echo $address; ?>" />
        </div>
        <div class="col-6 form-group">
            <label class="form-label" for="T2SStoreLocator_meta_latitude"><?php _e('Latitude', 't2s-store-locator'); ?></label>
            <input class="form-control" type="text" name="T2SStoreLocator_meta_latitude" id="T2SStoreLocator_meta_latitude" value="<?php echo $latitude; ?>" />
        </div>
        <div class="col-6 form-group">
            <label class="form-label" for="T2SStoreLocator_meta_longitude"><?php _e('Longitude', 't2s-store-locator'); ?></label>
            <input class="form-control" type="text" name="T2SStoreLocator_meta_longitude" id="T2SStoreLocator_meta_longitude" value="<?php echo $longitude; ?>" />
        </div>
    </div>
    <?php if(!$map_type || $map_type=='google'){?>
        <?php if (!$center_latitude || !$center_longitude || !$google_api) { ?>
            <div class="alert alert-danger" role="alert">
                <?php _e('You need to enter a Google Maps API key and define a start point first!', 't2s-store-locator'); ?> <a href="<?php echo admin_url('options-general.php?page=T2SStoreLocator_setting'); ?>"><?php _e('Click here', 't2s-store-locator'); ?></a> <?php _e('to setup.', 't2s-store-locator'); ?>
            </div>
        <?php } ?>
    <?php require_once(T2S_STORE_LOCATOR_PLUGIN_DIR.'/includes/maps/google.php');} ?>
    <?php if($map_type && $map_type=='baidu'){?>
        <?php if (!$center_latitude || !$center_longitude || !$baidu_api) { ?>
            <div class="alert alert-danger" role="alert">
                <?php _e('You need to enter a Baidu Maps API key and define a start point first!', 't2s-store-locator'); ?> <a href="<?php echo admin_url('options-general.php?page=T2SStoreLocator_setting'); ?>"><?php _e('Click here', 't2s-store-locator'); ?></a> <?php _e('to setup.', 't2s-store-locator'); ?>
            </div>
        <?php } ?>
    <?php require_once(T2S_STORE_LOCATOR_PLUGIN_DIR.'/includes/maps/baidu.php'); } ?>
    <?php if($map_type && $map_type=='amap'){?>
        <?php if (!$center_latitude || !$center_longitude || !$amap_api || !$amap_api_secret) { ?>
            <div class="alert alert-danger" role="alert">
                <?php _e('You need to enter a AMap API key and secret, and define a start point first!', 't2s-store-locator'); ?> <a href="<?php echo admin_url('options-general.php?page=T2SStoreLocator_setting'); ?>"><?php _e('Click here', 't2s-store-locator'); ?></a> <?php _e('to setup.', 't2s-store-locator'); ?>
            </div>
        <?php } ?>
    <?php require_once(T2S_STORE_LOCATOR_PLUGIN_DIR.'/includes/maps/amap.php');} ?>
<?php
}

// Save the Metabox Data
add_action('save_post', 'T2SStoreLocator_meta_box_save');
function T2SStoreLocator_meta_box_save($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['T2SStoreLocator_meta_address']))
        update_post_meta($post_id, 'T2SStoreLocator_meta_address', esc_attr($_POST['T2SStoreLocator_meta_address']));

    if (isset($_POST['T2SStoreLocator_meta_longitude']))
        update_post_meta($post_id, 'T2SStoreLocator_meta_longitude', esc_attr($_POST['T2SStoreLocator_meta_longitude']));

    if (isset($_POST['T2SStoreLocator_meta_latitude']))
        update_post_meta($post_id, 'T2SStoreLocator_meta_latitude', esc_attr($_POST['T2SStoreLocator_meta_latitude']));
}

// Add menu under setting
add_action('admin_menu', 'T2SStoreLocator_add_menu');
function T2SStoreLocator_add_menu()
{
    global $my_plugin_hook;
    $my_plugin_hook = add_options_page(__('T2S Store Locator', 't2s-store-locator'), __('T2S Store Locator', 't2s-store-locator'), 'manage_options', 'T2SStoreLocator_setting', 'T2SStoreLocator_setting_form');
}

// register setting to wp options
function T2SStoreLocator_add_options()
{
    register_setting('T2SStoreLocator_options', 'T2SStoreLocator_map_type');
    register_setting('T2SStoreLocator_options', 'T2S_StoreLocator_google_map_api');
    register_setting('T2SStoreLocator_options', 'T2S_StoreLocator_baidu_map_api');
    register_setting('T2SStoreLocator_options', 'T2S_StoreLocator_amap_api');
    register_setting('T2SStoreLocator_options', 'T2S_StoreLocator_amap_api_secret');
    register_setting('T2SStoreLocator_options', 'T2SStoreLocator_center_latitude');
    register_setting('T2SStoreLocator_options', 'T2SStoreLocator_center_longitude');
}
add_filter('admin_init', 'T2SStoreLocator_add_options');

/**
 * Setting form
 *
 * @return void
 */
function T2SStoreLocator_setting_form()
{
?>
    <div class="wrap">
        <h1><?php _e('Setting', 't2s-store-locator'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('T2SStoreLocator_options'); ?>
            <?php do_settings_sections('T2SStoreLocator_options'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <?php _e('Usage:', 't2s-store-locator'); ?>
                        </th>
                        <td>
                            <p><?php _e('To use the shortcode, please add the following code to the page you want to display the map:', 't2s-store-locator'); ?></p>
                            <p><code>[t2s_store_locator]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="T2SStoreLocator_map_type"><?php _e('Map type:', 't2s-store-locator'); ?></label>
                        </th>
                        <td>
                            <select name="T2SStoreLocator_map_type" id="T2SStoreLocator_map_type">
                                <option value="google" <?php echo (!get_option('T2SStoreLocator_map_type') || get_option('T2SStoreLocator_map_type') == 'google') ? 'selected' : ''; ?>><?php _e('Google Map', 't2s-store-locator'); ?></option>
                                <option value="baidu" <?php echo get_option('T2SStoreLocator_map_type') == 'baidu' ? 'selected' : ''; ?>><?php _e('Baidu Map', 't2s-store-locator'); ?></option>
                                <option value="amap" <?php echo get_option('T2SStoreLocator_map_type') == 'amap' ? 'selected' : ''; ?>><?php _e('Amap', 't2s-store-locator'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr id="google-api-key" style="display: <?php echo (!get_option('T2SStoreLocator_map_type') || get_option('T2SStoreLocator_map_type') == 'google') ? 'table-row' : 'none'; ?>">
                        <th scope="row">
                            <label for="T2S_StoreLocator_google_map_api"><?php _e('Google Maps API key:', 't2s-store-locator'); ?> <i class="fa fa-info-circle" data-html="true" data-toggle="tooltip" data-placement="top" html=true title="<?php _e('Click', 't2s-store-locator'); ?><a href='https://www.google.com/maps/' target='_blank'> <?php _e('Me', 't2s-store-locator'); ?> </a><?php _e('to get the map center', 't2s-store-locator'); ?>"></i></label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="T2S_StoreLocator_google_map_api" id="T2S_StoreLocator_google_map_api" value="<?php echo esc_attr(get_option('T2S_StoreLocator_google_map_api')); ?>" />
                        </td>
                    </tr>
                    <tr id="baidu-api-key" style="display: <?php echo (get_option('T2SStoreLocator_map_type') && get_option('T2SStoreLocator_map_type') == 'baidu') ? 'table-row' : 'none'; ?>">
                        <th scope="row">
                            <label for="T2S_StoreLocator_baidu_map_api"><?php _e('Baidu Maps API key:', 't2s-store-locator'); ?> <i class="fa fa-info-circle" data-html="true" data-toggle="tooltip" data-placement="top" html=true title="<?php _e('Click', 't2s-store-locator'); ?><a href='https://api.map.baidu.com/lbsapi/getpoint/index.html' target='_blank'> <?php _e('Me', 't2s-store-locator'); ?> </a><?php _e('to get the map center', 't2s-store-locator'); ?>"></i></label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="T2S_StoreLocator_baidu_map_api" id="T2S_StoreLocator_baidu_map_api" value="<?php echo esc_attr(get_option('T2S_StoreLocator_baidu_map_api')); ?>" />
                        </td>
                    </tr>
                    <tr id="amap-api-key" style="display: <?php echo (get_option('T2SStoreLocator_map_type') && get_option('T2SStoreLocator_map_type') == 'amap') ? 'table-row' : 'none'; ?>">
                        <th scope="row">
                            <label for="T2S_StoreLocator_amap_api"><?php _e('Amap API key:', 't2s-store-locator'); ?> <i class="fa fa-info-circle" data-html="true" data-toggle="tooltip" data-placement="top" html=true title="<?php _e('Click', 't2s-store-locator'); ?><a href='https://lbs.amap.com/tools/picker' target='_blank'> <?php _e('Me', 't2s-store-locator'); ?> </a><?php _e('to get the map center', 't2s-store-locator'); ?>"></i></label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="T2S_StoreLocator_amap_api" id="T2S_StoreLocator_amap_api" value="<?php echo esc_attr(get_option('T2S_StoreLocator_amap_api')); ?>" />
                        </td>
                    </tr>
                    <tr id="amap-api-secret" style="display: <?php echo (get_option('T2SStoreLocator_map_type') && get_option('T2SStoreLocator_map_type') == 'amap') ? 'table-row' : 'none'; ?>">
                        <th scope="row">
                            <label for="T2S_StoreLocator_amap_api_secret"><?php _e('Amap API Secret:', 't2s-store-locator'); ?></label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="T2S_StoreLocator_amap_api_secret" id="T2S_StoreLocator_amap_api_secret" value="<?php echo esc_attr(get_option('T2S_StoreLocator_amap_api_secret')); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="T2SStoreLocator_center_latitude"><?php _e('Latitude (Map center):', 't2s-store-locator'); ?></label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="T2SStoreLocator_center_latitude" id="T2SStoreLocator_center_latitude" value="<?php echo esc_attr(get_option('T2SStoreLocator_center_latitude')); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="T2SStoreLocator_center_longitude"><?php _e('Longitude (Map center):', 't2s-store-locator'); ?></label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="T2SStoreLocator_center_longitude" id="T2SStoreLocator_center_longitude" value="<?php echo esc_attr(get_option('T2SStoreLocator_center_longitude')); ?>" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
        <script>
            jQuery(function($) {
                // 选择切换地图
                $('#T2SStoreLocator_map_type').change(function() {
                    var map_type = $(this).val();
                    if (map_type == 'google') {
                        $('#google-api-key').show();
                        $('#baidu-api-key').hide();
                        $('#amap-api-key').hide();
                        $('#amap-api-secret').hide();
                    } else if (map_type == 'baidu') {
                        $('#google-api-key').hide();
                        $('#baidu-api-key').show();
                        $('#amap-api-key').hide();
                        $('#amap-api-secret').hide();
                    } else if (map_type == 'amap') {
                        $('#google-api-key').hide();
                        $('#baidu-api-key').hide();
                        $('#amap-api-key').show();
                        $('#amap-api-secret').show();
                    }
                });
            })
        </script>
    </div>
<?php
}
