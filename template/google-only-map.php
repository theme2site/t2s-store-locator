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
    <div class="acf-map" data-zoom="7" id="storeMap"></div>
</div>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo get_option('T2S_StoreLocator_google_map_api'); ?>&callback=Function.prototype"></script>
<script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
<script type="text/javascript">
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
    });
}
})(jQuery);
</script>
