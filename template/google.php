<div class="global-content">
    <div class="container">
        <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 't2s_stores';

            // 构建 SQL 查询语句
            $query = $wpdb->prepare(
                "SELECT *, lon as lng FROM {$table_name}"
            );
            // 执行查询
            $results = $wpdb->get_results($query);
            // print_r($results);
            $store_names = [];
            foreach ($results as $key => $result) {
                $store_names[]['value'] = $result->name;
            }
        ?>
        <div class="row small-row t2s-stores-map-wrap">
            <div class="col-12 col-lg-4">
                <div class="t2s-stores-search-form">
                    <input class="t2s-stores-search-input" id="storesSearchInput" type="text" value="" name="storesSearchInput" placeholder="Search for stores" aria-required="true" />
                    <button class="t2s-stores-search-btn" type="search" aria-label="" onclick="buttonSubmit()"><i class="fa fa-search" aria-hidden="true"></i></button>
                </div>
                <div class="t2s-stores-search-list" id="storeList">
                <?php foreach ($results as $key => $value) : ?>
                    <div class="t2s-stores-search-item">
                        <div class="t2s-stores-search-left">
                            <h4 class="t2s-stores-search-title"><a href="<?php echo esc_url(site_url('t2s-store/' . $value->id) . '/'); ?>"><?php echo $value->name; ?></a></h4>
                            <div class="t2s-stores-search-address" data-lat="<?php echo $value->lat; ?>" data-lng="<?php echo $value->lon; ?>"><?php echo $value->address; ?></div>
                        </div>
                        <a class="t2s-stores-search-right" href="<?php echo esc_url(site_url('t2s-store/' . $value->id) . '/'); ?>" style="background-image: url(<?php echo $value->image_url; ?>);"></a>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="acf-map" data-zoom="7" id="storeMap"></div>
            </div>
        </div>
    </div>
</div>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo get_option('T2S_StoreLocator_google_map_api'); ?>&callback=Function.prototype"></script>
<script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
<script type="text/javascript">
var outerClickAddress = null;
var outermap = null;
(function( $ ) {
const initLocations = <?php echo json_encode($results, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES);?>;
var map_;
/**
 * initMap
 *
 * Renders a Google Map onto the selected jQuery element
 *
 * @param   jQuery $el The jQuery element.
 * @return  object The map instance.
 */
function initMap( $el ) {
    $el = $('.acf-map');
    // Find marker elements within map.
    var $markers = $el.find('.marker');
    // Create gerenic map.
    var mapArgs = {
        zoom             : $el.data('zoom') || 16,
        minZoom          : 3,
        maxZoom          : 16,
        // center           : {lat: 40.0149856, lng: -107.2705456},
        // mapTypeControl   : false,
        // scrollwheel      : false,
        // zoomControl      : false,
        // streetViewControl: false,
        // fullscreenControl: false,
        mapTypeId        : google.maps.MapTypeId.ROADMAP
    };

    var map = new google.maps.Map( $el[0], mapArgs );

    // Add markers.
    initMarker(map);

    // Return map instance.
    return map;
}

/**
 * initMarker
 *
 * Creates a marker for the given jQuery element and map.
 *
 * @date    22/10/19
 * @since   5.8.6
 *
 * @param   jQuery $el The jQuery element.
 * @param   object The map instance.
 * @return  object The marker instance.
 */
function initMarker( map ) {
    const infoWindow = new google.maps.InfoWindow({
        content: "",
        disableAutoPan: true,
    });
    // Add some markers to the map.
    map.markers = [];
    var locations = [];
    initLocations.map((position) => {
        if(position.lat && position.lon && typeof(position.lat) === 'number' && typeof(position.lon) === 'number'){
            locations.push(position);
        }
    });
    console.log(locations)
    var preLink = "<?php echo esc_url(site_url('t2s-store/')); ?>";
    const markers = locations.map((position, i) => {
        var markerContent =
        `<h4><a class="marker-title-link" href="${preLink+position.id+'/'}" target="_blank">${position.name}</a></h4>
        <p><em>${position.address}</em></p>`;
        const marker = new google.maps.Marker({
            position
        });

        // markers can only be keyboard focusable when they have click listeners
        // open info window when marker is clicked
        marker.addListener("click", () => {
            infoWindow.setContent(markerContent);
            infoWindow.open(map, marker);
            map.setCenter(marker.getPosition());
        });
        map.markers.push( marker );
        return marker;
    });

    // Add a marker clusterer to manage the markers.
    const markerCluster = new markerClusterer.MarkerClusterer({ map, markers });
    // Center map based on markers.
    centerMap( map );
}

/**
 * centerMap
 *
 * Centers the map showing all markers in view.
 *
 * @date    22/10/19
 * @since   5.8.6
 *
 * @param   object The map instance.
 * @return  void
 */
function centerMap( map ) {

    // Create map boundaries from all map markers.
    var bounds = new google.maps.LatLngBounds();
    map.markers.forEach(function( marker ){
        bounds.extend({
            lat: marker.position.lat(),
            lng: marker.position.lng()
        });
    });

    // Case: Single marker.
    if( map.markers.length == 1 ){
        map.setCenter( bounds.getCenter() );

    // Case: Multiple markers.
    } else{
        map.fitBounds( bounds );
    }
}

// Render maps on page load.
$(document).ready(function(){
    initResetMap();
});

// init and reset map
function initResetMap(){
    $('.acf-map').each(function(){
        var map = initMap( $(this));
        map_ = map;
        outermap = map;
    });
}
function innerClickAddress(lat, lng){
    map_.setZoom(10);
    map_.setCenter(new google.maps.LatLng(lat, lng));
}

outerClickAddress = innerClickAddress
})(jQuery);

function buttonSubmit(){
    inputvalue = jQuery("#storesSearchInput").val();
    submitForm(inputvalue)
}
function submitForm(inputvalue) {
    // var inputvalue = jQuery("#storesSearchInput").val();
    if(inputvalue){
        jQuery("#storeList").html('Search...');
        jQuery("#storeList").change();
        jQuery.ajax({
            url: '<?php echo admin_url("admin-ajax.php") ?>',
            datatype: "json",
            type: "post",
            data: {
                action : 'T2S_StoreLocator_get_stores',
                storesSearchInput: inputvalue
            },
            success: function (res) {
                var data = eval('(' + res + ')');
                var top = data['top'];
                var resLocations = data['locations'];
                jQuery("#storeList").html(top);
                jQuery("#storeList").change();
                outerInitMarker(outermap, resLocations);
            }
        });
    }
};

jQuery(document).on('click', '.t2s-stores-search-address', function() {
    let lat = jQuery(this).attr('data-lat');
    let lng = jQuery(this).attr('data-lng');
    outerClickAddress(lat, lng);
});

var autocompleterData = <?php echo json_encode($store_names, JSON_UNESCAPED_UNICODE); ?>;
jQuery('#storesSearchInput').autocomplete({
    lookup: autocompleterData,
    triggerSelectOnValidInput: false,
    onSelect: function (suggestion) {
        submitForm(suggestion.value);
    }
});
function outerInitMarker( map, s) {
    const infoWindow = new google.maps.InfoWindow({
        content: "",
        disableAutoPan: true,
    });
    // Add some markers to the map.
    map.markers = [];
    var locations = [];
    s.map((position) => {
        if(position.lat && position.lon && typeof(position.lat) === 'number' && typeof(position.lon) === 'number'){
            locations.push(position);
        }
    });
    // console.log(locations)
    const markers = locations.map((position, i) => {
        var markerContent =
        `<h3><a class="marker-title-link" href="${position.link}" target="_blank">${position.name}</a></h3>
        <p><em>${position.address}</em></p>`;
        const marker = new google.maps.Marker({
            position
        });

        // markers can only be keyboard focusable when they have click listeners
        // open info window when marker is clicked
        marker.addListener("click", () => {
            infoWindow.setContent(markerContent);
            infoWindow.open(map, marker);
            map.setCenter(marker.getPosition());
        });
        map.markers.push( marker );
        return marker;
    });

    // Add a marker clusterer to manage the markers.
    const markerCluster = new markerClusterer.MarkerClusterer({ map, markers });
    // Center map based on markers.
    outerCenterMap( map );
}

function outerCenterMap( map ) {
    // Create map boundaries from all map markers.
    var bounds = new google.maps.LatLngBounds();
    map.markers.forEach(function( marker ){
        bounds.extend({
            lat: marker.position.lat(),
            lng: marker.position.lng()
        });
    });

    // Case: Single marker.
    if( map.markers.length == 1 ){
        map.setCenter( bounds.getCenter() );

    // Case: Multiple markers.
    } else{
        map.fitBounds( bounds );
    }
}
</script>
