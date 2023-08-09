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
    add_meta_box('T2SStoreLocator_meta', 'Store Location', 'T2SStoreLocator_meta_box_cb', 't2s_stores', 'normal', 'high');
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
    $google_api = get_option('T2S_StoreLocator_google_map_api');
?>
    <?php if (!$center_latitude || !$center_longitude || !$google_api) { ?>
        <div class="alert alert-danger" role="alert">
            You need to enter a Google Maps API key and define a start point first! <a href="<?php echo admin_url('options-general.php?page=T2SStoreLocator_setting'); ?>">Click here</a> to setup.
        </div>
    <?php } ?>
    <div class="row mt-3">
        <div class="col-12 form-group">
            <label class="form-label" for="T2SStoreLocator_meta_address">Address</label>
            <input class="form-control" type="text" name="T2SStoreLocator_meta_address" id="T2SStoreLocator_meta_address" value="<?php echo $address; ?>" />
        </div>
        <div class="col-6 form-group">
            <label class="form-label" for="T2SStoreLocator_meta_latitude">Latitude</label>
            <input class="form-control" type="text" name="T2SStoreLocator_meta_latitude" id="T2SStoreLocator_meta_latitude" value="<?php echo $latitude; ?>" />
        </div>
        <div class="col-6 form-group">
            <label class="form-label" for="T2SStoreLocator_meta_longitude">Longitude</label>
            <input class="form-control" type="text" name="T2SStoreLocator_meta_longitude" id="T2SStoreLocator_meta_longitude" value="<?php echo $longitude; ?>" />
        </div>
    </div>
    <input id="pac-input" class="map-search-controls" type="text" placeholder="Search" style="
            margin: 10px 0;
            width: calc(100% - 256px);
            height: 40px;
            border: 0;
            background: none padding-box rgb(255, 255, 255);
            box-shadow: rgba(0, 0, 0, 0.3) 0px 1px 4px -1px;
            border-radius: 2px;
        " />
    <div id="map" style="height: 500px;width: 100%;"></div>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $google_api; ?>&callback=initAutocomplete&libraries=places&v=weekly" async defer></script>
    <script>
        function displayCoordinates(latLng, address) {
            document.getElementById("T2SStoreLocator_meta_latitude").value = latLng.lat().toFixed(6);
            document.getElementById("T2SStoreLocator_meta_longitude").value = latLng.lng().toFixed(6);
            document.getElementById("T2SStoreLocator_meta_address").value = address ? address : '';
        }

        function initAutocomplete() {
            const mapCenter = {
                lat: <?php echo $center_latitude; ?>,
                lng: <?php echo $center_longitude; ?>
            };
            <?php if ($latitude && $longitude) { ?>
                mapCenter.lat = <?php echo $latitude; ?>;
                mapCenter.lng = <?php echo $longitude; ?>;
            <?php } ?>
            const map = new google.maps.Map(document.getElementById("map"), {
                center: mapCenter,
                zoom: 13,
                mapTypeId: "roadmap",
            });
            const marker = new google.maps.Marker({
                position: mapCenter,
                map: map,
                draggable: true,
            });
            google.maps.event.addListener(map, "click", (event) => {
                const latLng = event.latLng;
                marker.setPosition(latLng);
                displayCoordinates(latLng);
            });
            google.maps.event.addListener(marker, "dragend", (event) => {
                const latLng = event.latLng;
                displayCoordinates(latLng);
            });

            // Create the search box and link it to the UI element.
            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
            // Bias the SearchBox results towards current map's viewport.
            map.addListener("bounds_changed", () => {
                searchBox.setBounds(map.getBounds());
            });
            let markers = [];

            // Listen for the event fired when the user selects a prediction and retrieve
            // more details for that place.
            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();
                if (places.length == 0) {
                    return;
                }
                console.log(places)
                // Clear out the old markers.
                markers.forEach((marker) => {
                    marker.setMap(null);
                });
                markers = [];

                // For each place, get the icon, name and location.
                const bounds = new google.maps.LatLngBounds();

                marker.setPosition(places[0].geometry.location, places[0].formatted_address);
                displayCoordinates(places[0].geometry.location, places[0].formatted_address);

                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) {
                        console.log("Returned place contains no geometry");
                        return;
                    }

                    const icon = {
                        url: place.icon,
                        size: new google.maps.Size(71, 71),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25),
                    };

                    // Create a marker for each place.
                    markers.push(
                        new google.maps.Marker({
                            map,
                            icon,
                            title: place.name,
                            position: place.geometry.location,
                        })
                    );
                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });
        }
        window.initAutocomplete = initAutocomplete;
    </script>
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
    $my_plugin_hook = add_options_page('T2S Store locator', 'T2S Store locator', 'manage_options', 'T2SStoreLocator_setting', 'T2SStoreLocator_setting_form');
}

// register setting to wp options
function T2SStoreLocator_add_options()
{
    register_setting('T2SStoreLocator_options', 'T2S_StoreLocator_google_map_api');
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
        <h1>Setting</h1>
        <form method="post" action="options.php">
            <?php settings_fields('T2SStoreLocator_options'); ?>
            <?php do_settings_sections('T2SStoreLocator_options'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="blogname">Google Maps API key: </label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="T2S_StoreLocator_google_map_api" id="T2S_StoreLocator_google_map_api" value="<?php echo esc_attr(get_option('T2S_StoreLocator_google_map_api')); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="blogname">Latitude (Map center): <i class="fa fa-info-circle" data-html="true" data-toggle="tooltip" data-placement="top" html=true title="Click<a href='https://www.google.com/maps/' target='_blank'> Me </a>to get the location"></i></label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="T2SStoreLocator_center_latitude" id="T2SStoreLocator_center_latitude" value="<?php echo esc_attr(get_option('T2SStoreLocator_center_latitude')); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="blogname">Longitude (Map center): <i class="fa fa-info-circle" data-html="true" data-toggle="tooltip" data-placement="top" html=true title="Click<a href='https://www.google.com/maps/' target='_blank'> Me </a>to get the location"></i></label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="T2SStoreLocator_center_longitude" id="T2SStoreLocator_center_longitude" value="<?php echo esc_attr(get_option('T2SStoreLocator_center_longitude')); ?>" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}
