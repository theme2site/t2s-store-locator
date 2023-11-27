<?php

require_once T2S_STORE_LOCATOR_PLUGIN_DIR . 'vendor/autoload.php';
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

/**
 * PART 1. Defining Custom Database Table
 * ============================================================================
 *
 * In this part you are going to define custom database table,
 * create it, update, and fill with some dummy data
 *
 * http://codex.wordpress.org/Creating_Tables_with_Plugins
 *
 * In case your are developing and want to check plugin use:
 *
 * DROP TABLE IF EXISTS wp_t2s_stores;
 * DELETE FROM wp_options WHERE option_name = 't2s_store_locator_db_version';
 *
 * to drop table and option
 */

/**
 * $t2s_store_locator_db_version - holds current database version
 * and used on plugin update to sync database tables
 */
global $t2s_store_locator_db_version;
$t2s_store_locator_db_version = '1.0'; // version changed from 1.0 to 1.1

/**
 * register_activation_hook implementation
 *
 * will be called when user activates plugin first time
 * must create needed database tables
 */
function tsl_install()
{
    global $wpdb;
    global $t2s_store_locator_db_version;

    $table_name = $wpdb->prefix . 't2s_stores'; // do not forget about tables prefix

    // sql to create your table
    // NOTICE that:
    // 1. each field MUST be in separate line
    // 2. There must be two spaces between PRIMARY KEY and its name
    //    Like this: PRIMARY KEY[space][space](id)
    // otherwise dbDelta will not work
    $sql = "CREATE TABLE " . $table_name . " (
        id int(11) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        image_url VARCHAR(255) NULL,
        address tinytext NULL,
        city tinytext NULL,
        state tinytext NULL,
        country tinytext NOT NULL,
        postal_code tinytext NULL,
        lon float(10,6) NULL,
        lat float(10,6) NULL,
        overview text NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY  (id)
    );";

    // we do not execute sql directly
    // we are calling dbDelta which cant migrate database
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // save current database version for later use (on upgrade)
    add_option('t2s_store_locator_db_version', $t2s_store_locator_db_version);

    /**
     * [OPTIONAL] Example of updating to 1.1 version
     *
     * If you develop new version of plugin
     * just increment $t2s_store_locator_db_version variable
     * and add following block of code
     *
     * must be repeated for each new version
     * in version 1.1 we change email field
     * to contain 200 chars rather 100 in version 1.0
     * and again we are not executing sql
     * we are using dbDelta to migrate table changes
     */
    $installed_ver = get_option('t2s_store_locator_db_version');
    if ($installed_ver != $t2s_store_locator_db_version) {
        $sql = "CREATE TABLE " . $table_name . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            image_url VARCHAR(255) NULL,
            address tinytext NULL,
            city tinytext NULL,
            state tinytext NULL,
            country tinytext NOT NULL,
            postal_code tinytext NULL,
            lon float(10,6) NULL,
            lat float(10,6) NULL,
            overview text NULL,
            created_at timestamp NULL DEFAULT NULL,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY  (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // notice that we are updating option, rather than adding it
        update_option('t2s_store_locator_db_version', $t2s_store_locator_db_version);
    }

    $store_detail_page = get_page_by_path('t2s-store');
    if (!$store_detail_page) {
        $store_detail_page = array(
            'post_title' => __('T2s store', 't2s_store_locator'),
            'post_name' => 't2s-store',
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => '1',
        );
        wp_insert_post($store_detail_page);
    }
}

register_activation_hook(__FILE__, 'tsl_install');

/**
 * Trick to update plugin database, see docs
 */
function tsl_update_db_check()
{
    global $t2s_store_locator_db_version;
    if (get_site_option('t2s_store_locator_db_version') != $t2s_store_locator_db_version) {
        tsl_install();
    }
}

add_action('plugins_loaded', 'tsl_update_db_check');

/**
 * PART 2. Defining Custom Table List
 * ============================================================================
 *
 * In this part you are going to define custom table list class,
 * that will display your database records in nice looking table
 *
 * https://developer.wordpress.org/reference/classes/wp_list_table/
 */

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class STORE_List_Table extends WP_List_Table
{
    /**
     * [REQUIRED] You must declare constructor and give some basic params
     */
    public function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 't2s_store',
            'plural' => 't2s_stores',
        ));
    }

    /**
     * [REQUIRED] this is a default column renderer
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     * @return HTML
     */
    public function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    /**
     * [OPTIONAL] this is example, how to render specific column
     *
     * method name must be like this: "column_[column_name]"
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_age($item)
    {
        return '<em>' . $item['age'] . '</em>';
    }

    /**
     * [OPTIONAL] this is example, how to render column with actions,
     * when you hover row "Edit | Delete" links showed
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_name($item)
    {
        // links going to /admin.php?page=[your_plugin_page][&other_params]
        // notice how we used $_REQUEST['page'], so action will be done on curren page
        // also notice how we use $this->_args['singular'] so in this example it will
        // be something like &person=2
        $actions = array(
            'edit' => sprintf('<a href="?page=t2s_stores_form&id=%s">%s</a>', $item['id'], __('Edit', 't2s-store-locator')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 't2s-store-locator')),
        );

        return sprintf(
            '%s %s',
            $item['name'],
            $this->row_actions($actions)
        );
    }

    /**
     * [REQUIRED] this is how checkbox column renders
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    /**
     * [REQUIRED] This method return columns to display in table
     * you can skip columns that you do not want to show
     * like content, or description
     *
     * @return array
     */
    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'id' => __('ID#', 't2s-store-locator'),
            'name' => __('Name', 't2s-store-locator'),
            'address' => __('Address', 't2s-store-locator'),
            'city' => __('City', 't2s-store-locator'),
            'state' => __('State', 't2s-store-locator'),
            'country' => __('Country', 't2s-store-locator'),
            'postal_code' => __('Postal Code', 't2s-store-locator'),
        );
        return $columns;
    }

    /**
     * [OPTIONAL] This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'id' => array('id',),
            'name' => array('name', true),
            'address' => array('address', true),
            'city' => array('city', true),
            'state' => array('state', true),
            'country' => array('country', true),
            'postal_code' => array('postal_code', true),

        );
        return $sortable_columns;
    }

    /**
     * [OPTIONAL] Return array of bult actions if has any
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Del'
        );
        return $actions;
    }

    /**
     * [OPTIONAL] This method processes bulk actions
     * it can be outside of class
     * it can not use wp_redirect coz there is output already
     * in this example we are processing delete action
     * message about successful deletion will be shown on page in next part
     */
    public function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 't2s_stores'; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) {
                $ids = implode(',', $ids);
            }

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    /**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     */
    public function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 't2s_stores'; // do not forget about tables prefix

        $per_page = 20; // constant, how much records will be shown per page

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);

        // [OPTIONAL] process bulk action if any
        $this->process_bulk_action();

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // prepare query params, as usual current page, order by and order direction
        $paged = $this->get_pagenum();

        // Set the offset criteria
        $offset = ($paged - 1) * $per_page;

        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);

        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }
}

/**
 * PART 3. Admin page
 * ============================================================================
 *
 * In this part you are going to add admin page for custom table
 *
 * http://codex.wordpress.org/Administration_Menus
 */

/**
 * admin_menu hook implementation, will add pages to list t2s_stores and to add new one
 */
function tsl_admin_menu()
{
    add_menu_page(__('T2S Stores', 't2s-store-locator'), __('T2S Stores', 't2s-store-locator'), 'activate_plugins', 't2s_stores', 'tsl_t2s_stores_page_handler');
    add_submenu_page('t2s_stores', __('Stores List', 't2s-store-locator'), __('Store List', 't2s-store-locator'), 'activate_plugins', 't2s_stores', 'tsl_t2s_stores_page_handler');
    add_submenu_page('t2s_stores', __('Add New', 't2s-store-locator'), __('Add New', 't2s-store-locator'), 'activate_plugins', 't2s_stores_form', 'tsl_t2s_stores_form_page_handler');
    add_submenu_page('t2s_stores', __('Import', 't2s-store-locator'), __('Import', 't2s-store-locator'), 'activate_plugins', 't2s_stores_import', 'tsl_t2s_stores_import_page_handler');
    add_submenu_page('t2s_stores', __('Setting', 't2s-store-locator'), __('Setting', 't2s-store-locator'), 'activate_plugins', 't2s_stores_setting', 'tsl_t2s_stores_setting_page_handler');
}

add_action('admin_menu', 'tsl_admin_menu');

/**
 * List page handler
 *
 * This function renders our custom table
 * Notice how we display message about successfull deletion
 * Actualy this is very easy, and you can add as many features
 * as you want.
 *
 * Look into /wp-admin/includes/class-wp-*-list-table.php for examples
 */
function tsl_t2s_stores_page_handler()
{
    global $wpdb;

    $table = new STORE_List_Table();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>Successfully deleted.</p></div>';
    }
?>
    <div class="wrap">

        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('T2S Stores', 't2s-store-locator') ?>
            <a class="add-new-h2" href="<?php echo admin_url('admin.php?page=t2s_stores_import'); ?>">Import</a>
            <a class="add-new-h2" href="<?php echo admin_url('admin-ajax.php?action=t2s_stores_export'); ?>">Export</a>
            <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=t2s_stores_form'); ?>"><?php _e('Add New', 't2s-store-locator') ?></a>
            <!-- <a class="add-new-h2" href="<?php echo admin_url('admin-ajax.php?action=t2s_stores_getlatlon'); ?>">Sync Lat&Lon</a> -->
        </h2>
        <?php echo $message; ?>

        <form id="t2s_stores-table" method="GET">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <?php $table->display() ?>
        </form>

    </div>
<?php
}

/**
 * PART 4. Form for adding andor editing row
 * ============================================================================
 *
 * In this part you are going to add admin page for adding andor editing items
 * You cant put all form into this function, but in this example form will
 * be placed into meta box, and if you want you can split your form into
 * as many meta boxes as you want
 *
 * http://codex.wordpress.org/Data_Validation
 * http://codex.wordpress.org/Function_Reference/selected
 */

/**
 * Form page handler checks is there some data posted and tries to save it
 * Also it renders basic wrapper in which we are callin meta box render
 */
function tsl_t2s_stores_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 't2s_stores'; // do not forget about tables prefix

    $message = '';
    $notice = '';

    // this is default $item which will be used for new records
    $default = array(
        'id' => 0,
        'name' => '',
        'image_url' => null,
        'address' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'postal_code' => '',
        'lon' => '',
        'lat' => '',
        'overview' => '',
    );

    // here we are verifying does this request is post back and have correct nonce
    if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        // combine our default item with request params
        $item = shortcode_atts($default, $_REQUEST);
        // validate data, and if all ok save item to database
        // if id is zero insert otherwise update
        $item_valid = tsl_validate_data($item);
        if ($item_valid === true) {

            if (isset($item['overview'])) {
                $item['overview'] = stripslashes($item['overview']);
            }

            if ($item['id'] == 0) {
                $result = $wpdb->insert($table_name, $item);
                $item['id'] = $wpdb->insert_id;
                if ($result) {
                    $message = __('Item was successfully saved', 't2s-store-locator');
                } else {
                    $notice = __('There was an error while saving item', 't2s-store-locator');
                }
            } else {
                $result = $wpdb->update($table_name, $item, array('id' => $item['id']));
                if ($result) {
                    $message = __('Item was successfully updated', 't2s-store-locator');
                } else {
                    $notice = __('There was an error while updating item', 't2s-store-locator');
                }
            }
        } else {
            // if $item_valid not true it contains error message(s)
            $notice = $item_valid;
        }
    } else {
        // if this is not post back we load item to edit or give new one to create
        $item = $default;
        if (isset($_REQUEST['id'])) {
            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
            if (!$item) {
                $item = $default;
                $notice = __('Item not found', 't2s-store-locator');
            }
        }
    }

    // here we adding our custom meta box
    add_meta_box('t2s_stores_form_meta_box', 'Edit Store', 'tsl_t2s_stores_form_meta_box_handler', 'person', 'normal', 'default'); ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Store', 't2s-store-locator') ?>
            <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=t2s_stores'); ?>"><?php _e('Back', 't2s-store-locator') ?></a>
        </h2>

        <?php if (!empty($notice)) : ?>
            <div id="notice" class="error">
                <p><?php echo $notice ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($message)) : ?>
            <div id="message" class="updated">
                <p><?php echo $message ?></p>
            </div>
        <?php endif; ?>

        <form id="form" method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__)) ?>" />
            <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
            <input type="hidden" name="id" value="<?php echo $item['id'] ?>" />

            <div class="metabox-holder" id="poststuff">
                <div id="post-body">
                    <div id="post-body-content">
                        <?php /* And here we call our custom meta box */ ?>
                        <?php do_meta_boxes('person', 'normal', $item); ?>
                        <input type="submit" value="<?php _e('Submit', 't2s-store-locator') ?>" id="submit" class="button-primary" name="submit">
                    </div>
                </div>
            </div>
        </form>
    </div>
<?php
}

/**
 * This function renders our custom meta box
 * $item is row
 *
 * @param $item
 */
function tsl_t2s_stores_form_meta_box_handler($item)
{
    $center_latitude = get_option('T2SStoreLocator_center_latitude');
    $center_longitude = get_option('T2SStoreLocator_center_longitude');
    $map_type = get_option('T2SStoreLocator_map_type');
    $google_api = get_option('T2S_StoreLocator_google_map_api');
    $baidu_api = get_option('T2S_StoreLocator_baidu_map_api');
    $amap_api = get_option('T2S_StoreLocator_amap_api');
    $amap_api_secret = get_option('T2S_StoreLocator_amap_api_secret');
?>
    <div class="row">
        <div class="col-6">
            <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                <tbody>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <label><?php _e('Name', 't2s-store-locator') ?></label>
                        </th>
                        <td>
                            <input id="name" name="name" type="text" style="width: 95%" value="<?php echo esc_attr($item['name']) ?>" size="50" class="code" placeholder="<?php _e('Name', 't2s-store-locator') ?>" required>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <label><?php _e('Upload image', 't2s_module') ?></label>
                        </th>
                        <td>
                            <input type="button" class="button upload-image-or-file-button upload-image-button" value="<?php _e('Upload image', 't2s_module') ?>" />
                            <input type="button" class="button remove-image-or-file-button remove-image-button" value="<?php _e('Remove image', 't2s_module') ?>" />
                            <div class="upload-image-or-file-wrap" style="margin-top:10px">
                                <img id="image-url" src="<?php echo esc_attr($item['image_url']) ?>" alt="" style="max-width: 210px; height: auto;">
                                <input type="hidden" name="image_url" value="<?php echo esc_attr($item['image_url']) ?>" />
                            </div>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <label><?php _e('Address', 't2s-store-locator') ?></label>
                        </th>
                        <td>
                            <input id="address" name="address" type="text" style="width: 95%" value="<?php echo esc_attr($item['address']) ?>" size="50" class="code" placeholder="<?php _e('Address', 't2s-store-locator') ?>" required>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <label><?php _e('City', 't2s-store-locator') ?></label>
                        </th>
                        <td>
                            <input id="city" name="city" type="text" style="width: 95%" value="<?php echo esc_attr($item['city']) ?>" size="50" class="code" placeholder="<?php _e('City', 't2s-store-locator') ?>" required>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <label><?php _e('State', 't2s-store-locator') ?></label>
                        </th>
                        <td>
                            <input id="state" name="state" type="text" style="width: 95%" value="<?php echo esc_attr($item['state']) ?>" size="50" class="code" placeholder="<?php _e('State', 't2s-store-locator') ?>">
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <label><?php _e('Country', 't2s-store-locator') ?></label>
                        </th>
                        <td>
                            <input id="country" name="country" type="text" style="width: 95%" value="<?php echo esc_attr($item['country']) ?>" size="50" class="code" placeholder="<?php _e('Country', 't2s-store-locator') ?>">
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <label><?php _e('Postal Code', 't2s-store-locator') ?></label>
                        </th>
                        <td>
                            <input id="postal_code" name="postal_code" type="text" style="width: 95%" value="<?php echo esc_attr($item['postal_code']) ?>" size="50" class="code" placeholder="<?php _e('Postal Code', 't2s-store-locator') ?>">
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <label><?php _e('Longitude', 't2s-store-locator') ?></label>
                        </th>
                        <td>
                            <input id="T2SStoreLocator_meta_longitude" name="lon" type="text" style="width: 95%" value="<?php echo esc_attr($item['lon']) ?>" size="50" class="code" placeholder="<?php _e('Longitude', 't2s-store-locator') ?>" required>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <label><?php _e('Latitude', 't2s-store-locator') ?></label>
                        </th>
                        <td>
                            <input id="T2SStoreLocator_meta_latitude" name="lat" type="text" style="width: 95%" value="<?php echo esc_attr($item['lat']) ?>" size="50" class="code" placeholder="<?php _e('Latitude', 't2s-store-locator') ?>" required>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <label><?php _e('Overview', 't2s-store-locator') ?></label>
                        </th>
                        <td>
                            <textarea id="overview" name="overview" style="width: 95%" rows="5" cols="50" class="code" placeholder="<?php _e('Overview', 't2s-store-locator') ?>"><?php echo esc_attr($item['overview']) ?></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="col-6">
            <?php if (!$map_type || $map_type == 'google') { ?>
                <?php if (!$center_latitude || !$center_longitude || !$google_api) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php _e('You need to enter a Google Maps API key and define a start point first!', 't2s-store-locator'); ?> <a href="<?php echo admin_url('options-general.php?page=T2SStoreLocator_setting'); ?>"><?php _e('Click here', 't2s-store-locator'); ?></a> <?php _e('to setup.', 't2s-store-locator'); ?>
                    </div>
                <?php } ?>
            <?php require_once(T2S_STORE_LOCATOR_PLUGIN_DIR . '/includes/maps/google.php');
            } ?>
            <?php if ($map_type && $map_type == 'baidu') { ?>
                <?php if (!$center_latitude || !$center_longitude || !$baidu_api) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php _e('You need to enter a Baidu Maps API key and define a start point first!', 't2s-store-locator'); ?> <a href="<?php echo admin_url('options-general.php?page=T2SStoreLocator_setting'); ?>"><?php _e('Click here', 't2s-store-locator'); ?></a> <?php _e('to setup.', 't2s-store-locator'); ?>
                    </div>
                <?php } ?>
            <?php require_once(T2S_STORE_LOCATOR_PLUGIN_DIR . '/includes/maps/baidu.php');
            } ?>
            <?php if ($map_type && $map_type == 'amap') { ?>
                <?php if (!$center_latitude || !$center_longitude || !$amap_api || !$amap_api_secret) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php _e('You need to enter a AMap API key and secret, and define a start point first!', 't2s-store-locator'); ?> <a href="<?php echo admin_url('options-general.php?page=T2SStoreLocator_setting'); ?>"><?php _e('Click here', 't2s-store-locator'); ?></a> <?php _e('to setup.', 't2s-store-locator'); ?>
                    </div>
                <?php } ?>
            <?php require_once(T2S_STORE_LOCATOR_PLUGIN_DIR . '/includes/maps/amap.php');
            } ?>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            $('.upload-image-button').click(function(e) {
                e.preventDefault();
                // If the uploader object has already been created, reopen the dialog
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                // Extend the wp.media object
                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: '<?php _e('Choose Image', 't2s-store-locator') ?>',
                    button: {
                        text: '<?php _e('Choose Image', 't2s-store-locator') ?>'
                    },
                    multiple: false
                });
                // When a file is selected, grab the URL and set it as the text field's value
                mediaUploader.on('select', function() {
                    attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#image-url').attr('src', attachment.url);
                    $('input[name=image_url]').val(attachment.url);
                });
                // Open the uploader dialog
                mediaUploader.open();
            });
            $('.remove-image-or-file-button').click(function(e) {
                // e.preventDefault();
                var answer = confirm("<?php _e('Are you sure?', 't2s-store-locator') ?>");
                if (answer == true) {
                    $show_wrap = $(this).siblings('.upload-image-or-file-wrap');
                    $show_wrap.children('#image-url').attr('src', '');
                    $show_wrap.children('input').val('');
                }
                return;
            });

        });
    </script>
<?php
}

/**
 * Simple function that validates data and retrieve bool on success
 * and error message(s) on error
 *
 * @param $item
 * @return bool|string
 */
function tsl_validate_data($item)
{
    $messages = array();
    if (empty($item['name'])) {
        $messages[] = __('Name is required', 't2s-store-locator');
    }
    if (empty($item['address'])) {
        $messages[] = __('Address is required', 't2s-store-locator');
    }
    if (empty($item['city'])) {
        $messages[] = __('City is required', 't2s-store-locator');
    }
    if (empty($item['lon'])) {
        $messages[] = __('Longitude is required', 't2s-store-locator');
    }
    if (empty($item['lat'])) {
        $messages[] = __('Latitude is required', 't2s-store-locator');
    }

    if (empty($messages)) {
        return true;
    }
    return implode('<br />', $messages);
}

/**
 * Do not forget about translating your plugin, use __('english string', 'your_uniq_plugin_name') to retrieve translated string
 * and _e('english string', 'your_uniq_plugin_name') to echo it
 * in this example plugin your_uniq_plugin_name == tsl
 *
 * to create translation file, use poedit FileNew catalog...
 * Fill name of project, add "." to path (ENSURE that it was added - must be in list)
 * and on last tab add "__" and "_e"
 *
 * Name your file like this: [my_plugin]-[ru_RU].po
 *
 * http://codex.wordpress.org/Writing_a_Plugin#Internationalizing_Your_Plugin
 * http://codex.wordpress.org/I18n_for_WordPress_Developers
 */
function tsl_languages()
{
    load_plugin_textdomain('t2s-store-locator', false, dirname(plugin_basename(__FILE__)));
}
add_action('init', 'tsl_languages');


function t2s_csv_pull_export()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 't2s_stores'; // do not forget about tables prefix

    $results = $wpdb->get_results(
        "SELECT
        name, address, city, state, country, postal_code, lon, lat, overview
        FROM {$table_name};",
        ARRAY_A
    );

    if (empty($results)) {
        return;
    }

    // Create a new Spreadsheet object
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();

    // Add data to the spreadsheet
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('B1', 'Name');
    $sheet->setCellValue('C1', 'Address');
    $sheet->setCellValue('D1', 'City');
    $sheet->setCellValue('E1', 'State');
    $sheet->setCellValue('F1', 'Country');
    $sheet->setCellValue('G1', 'Postal Code');
    $sheet->setCellValue('H1', 'Longitude');
    $sheet->setCellValue('I1', 'Latitude');
    $sheet->setCellValue('J1', 'Overview');

    $i = 2;
    foreach ($results as $row) {
        $sheet->setCellValue('B' . $i, $row['name']);
        $sheet->setCellValue('C' . $i, $row['address']);
        $sheet->setCellValue('D' . $i, $row['city']);
        $sheet->setCellValue('E' . $i, $row['state']);
        $sheet->setCellValue('F' . $i, $row['country']);
        $sheet->setCellValue('G' . $i, $row['postal_code']);
        $sheet->setCellValue('H' . $i, $row['lon']);
        $sheet->setCellValue('I' . $i, $row['lat']);
        $sheet->setCellValue('J' . $i, $row['overview']);
        $i++;
    }

    // Create a writer object
    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    // Set the file headers for Excel
    $filename = "exported_" . date("Y-m-d_His", time());
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');

    // Save the Excel file to php://output
    $writer->save('php://output');
    exit;
}
add_action('wp_ajax_t2s_stores_export', 't2s_csv_pull_export');

// t2s_stores_getlatlon
function t2s_stores_getlatlon()
{

    echo "Starting to get lat and lon..." . "<br>";

    global $wpdb;
    $table_name = $wpdb->prefix . 't2s_stores';

    // lat 或 long为空的
    $results = $wpdb->get_results("SELECT * FROM {$table_name} WHERE lon is NULL OR lat is NULL;", ARRAY_A);

    if (empty($results)) {
        return;
    }

    foreach ($results as $row) {
        $address = $row['address'] . ',' . $row['city'] . ',' . $row['state'] . ',' . $row['country'] . ',' . $row['postal_code'];
        $address = urlencode($address);
        // $url = "http://maps.google.com/maps/api/geocode/json?address={$address}&sensor=false&language=zh-CN";
        $url = "https://www.mapquestapi.com/geocoding/v1/address?key=&inFormat=kvp&outFormat=json&location={$address}&thumbMaps=false";

        echo $url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($ch, CURLOPT_HEADER, 0); // 不显示header
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);

        // Google Map
        // if ($output['status'] == 'OK') {
        //     $lon = $output['results'][0]['geometry']['location']['lng'];
        //     $lat = $output['results'][0]['geometry']['location']['lat'];
        //     $wpdb->update($table_name, array(
        //         'lon' => $lon,
        //         'lat' => $lat,
        //     ), array('id' => $row['id']));
        // }

        // mapquest
        if ($output['info']['statuscode'] == 0) {
            $lon = $output['results'][0]['locations'][0]['latLng']['lng'];
            $lat = $output['results'][0]['locations'][0]['latLng']['lat'];

            echo $row['id'] . ' ' . $lon . ' ' . $lat . '<br>';

            $wpdb->update($table_name, array(
                'lon' => $lon,
                'lat' => $lat,
            ), array('id' => $row['id']));
        }
    }
    echo 'ok';
    exit;
}
add_action('wp_ajax_t2s_stores_getlatlon', 't2s_stores_getlatlon');

/**
 * Import
 *
 * @return void
 */
function tsl_t2s_stores_import_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 't2s_stores'; // do not forget about tables prefix

    $message = '';
    $notice = '';

    // here we are verifying does this request is post back and have correct nonce
    if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        switch ($_FILES['filename']['error']) {
            case '4':
                $notice = __('Upload failed', 't2s-store-locator');
                break;
        }

        $price_excel_start = 0;
        $arUploadDir = wp_upload_dir();
        $path = $arUploadDir['basedir'] . '/import_excel/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $uploadfile = $_FILES['filename']['name'];
        $fileparse = explode(".", $uploadfile);
        $extension = end($fileparse);

        // 允许上传的后缀
        $allowedExts = array("csv", "xlsx", "xls");

        if (isset($_FILES['filename']) && $_FILES['filename']['error'] == 0) {
            if ($_FILES["filename"]["size"] > 1024 * 1024 * 10) {
                $notice = __('file size over 10Mb', 't2s-store-locator');
            } elseif (!in_array($extension, $allowedExts)) {
                $notice = __('Incorrect format', 't2s-store-locator');
            } else {

                // Create a new Spreadsheet object
                $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['filename']['tmp_name']);

                // Get the first worksheet
                $worksheet = $spreadsheet->getActiveSheet();

                // Initialize an array to map column letters to column names
                $column_map = [];

                // Iterate through the first row to build the column map
                foreach ($worksheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    foreach ($cellIterator as $cell) {
                        // Assuming that the first row contains the column names
                        $column_map[$cell->getColumn()] = $cell->getValue();
                    }
                    break; // Stop after the first row
                }

                // Iterate through the rows and import data using column names
                foreach ($worksheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $data = [];

                    foreach ($cellIterator as $cell) {
                        // Use the column map to get the column name
                        $column_name = $column_map[$cell->getColumn()];
                        $data[$column_name] = $cell->getValue();
                    }

                    if ($data['Name'] == 'Name') {
                        continue;
                    }
                    if ($data['Name'] == '') {
                        continue;
                    }

                    $name     = $data['Name'];
                    $address  = $data['Address'];
                    $city     = $data['City'];
                    $state    = $data['State'];
                    $item_data = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $table_name WHERE name = %s AND address = %s AND city = %s AND state = %s",
                        $name,
                        $address,
                        $city,
                        $state
                    ), ARRAY_A);
                    if ($item_data) {
                        $result = $wpdb->update($table_name, array(
                            'address'     => $address,
                            'city'        => $data['City'],
                            'state'       => $data['State'],
                            'country'     => $data['Country'],
                            'postal_code' => $data['Postal Code'],
                            'lon'         => $data['Longitude'],
                            'lat'         => $data['Latitude'],
                            'overview'    => $data['Overview'],
                            'updated_at'  => date('Y-m-d H:i:s'),
                        ), array('id' => $item_data['id']));
                    } else {
                        $filed = array(
                            'name'        => $data['Name'],
                            'address'     => $data['Address'],
                            'city'        => $data['City'],
                            'state'       => $data['State'],
                            'country'     => $data['Country'],
                            'postal_code' => $data['Postal Code'],
                            'lon'         => $data['Latitude'],
                            'lat'         => $data['Longitude'],
                            'overview'    => $data['Overview'],
                            'created_at'  => date('Y-m-d H:i:s'),
                        );
                        $result = $wpdb->insert($table_name, $filed);
                    }

                    if ($result) {
                        $price_excel_start += 1;
                        $message = __('Imported: ' . $price_excel_start, 't2s-store-locator');
                    } else {
                        $notice = __('There was an error while saving item', 't2s-store-locator');
                    }
                }
            }
        }
    } ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Person', 't2s-store-locator') ?>
            <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=t2s_stores'); ?>"><?php _e('Back', 't2s-store-locator') ?></a>
        </h2>

        <?php if (!empty($notice)) : ?>
            <div id="notice" class="error">
                <p><?php echo $notice ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($message)) : ?>
            <div id="message" class="updated">
                <p><?php echo $message ?></p>
            </div>
        <?php endif; ?>

        <form id="form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__)) ?>" />
            <div class="metabox-holder" id="poststuff">
                <div id="post-body">
                    <div id="post-body-content">
                        <h1>Import</h1>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="home">Choose File</label></th>
                                <td><input type="file" name="filename" class="">
                                </td>
                            </tr>
                        </table>
                        <input type="submit" value="Submit" id="submit" class="button-primary" name="submit">
                    </div>
                </div>
            </div>
        </form>
    </div>
<?php
}

/**
 * Setting
 *
 * @return void
 */
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

function tsl_t2s_stores_setting_page_handler()
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
                            <p><code>[T2S_StoreLocator]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php _e('Usage ( Show maps only ) :', 't2s-store-locator'); ?>
                        </th>
                        <td>
                            <p><?php _e('To use the shortcode, please add the following code to the page you want to display the map:', 't2s-store-locator'); ?></p>
                            <p><code>[T2S_StoreLocator_Only_Map]</code></p>
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
