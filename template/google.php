<div class="global-content">
    <div class="container">
        <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 't2s_stores';

            // 构建 SQL 查询语句
            $query = $wpdb->prepare(
                "SELECT * FROM {$table_name}"
            );
            // 执行查询
            $results = $wpdb->get_results($query);
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
                <div class="acf-map" data-zoom="7" id="storeMap">
                    <?php foreach ($results as $location) : ?>
                        <div class="marker" data-lat="<?php echo esc_attr($location->lat); ?>" data-lng="<?php echo esc_attr($location->lon); ?>">
                            <h3><a class="marker-title-link" href="<?php echo esc_url(site_url('t2s-store/' . $result->id) . '/'); ?>"><?php echo esc_attr($location->name); ?></a></h3>
                            <p><em><?php echo esc_html( $location->address ); ?></em></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
var outerTesetMap = null;
var outerClickAddress = null;
(function( $ ) {
var map_;
/**
 * initMap
 *
 * Renders a Google Map onto the selected jQuery element
 *
 * @date    22/10/19
 * @since   5.8.6
 *
 * @param   jQuery $el The jQuery element.
 * @return  object The map instance.
 */
function initMap( $el ) {

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
    map.markers = [];
    $markers.each(function(){
        initMarker( $(this), map );
    });

    // Center map based on markers.
    centerMap( map );
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
function initMarker( $marker, map ) {

    // Get position from marker.
    var lat = $marker.data('lat');
    var lng = $marker.data('lng');
    var latLng = {
        lat: parseFloat( lat ),
        lng: parseFloat( lng )
    };

    // Create marker instance.
    var marker = new google.maps.Marker({
        position : latLng,
        map: map
    });

    // Append to reference for later use.
    map.markers.push( marker );

    // If marker contains HTML, add it to an infoWindow.
    if( $marker.html() ){

        // Create info window.
        var infowindow = new google.maps.InfoWindow({
            content: $marker.html()
        });

        // Show info window when marker is clicked.
        google.maps.event.addListener(marker, 'click', function() {
            infowindow.open( map, marker );
            // map.setZoom(7);
            map.setCenter(marker.getPosition());
        });
    }
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
    innerTesetMap();
});

// init and reset map
function innerTesetMap(){
    $('.acf-map').each(function(){
        var map = initMap( $(this) );
        map_ = map;
    });
}
function innerClickAddress(lat, lng){
    map_.setZoom(10);
    map_.setCenter(new google.maps.LatLng(lat, lng));
}

outerTesetMap = innerTesetMap;
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
                var bottom = data['bottom'];
                jQuery("#storeList").html(top);
                jQuery("#storeList").change();
                jQuery("#storeMap").html(bottom);
                jQuery("#storeMap").change();
                outerTesetMap();
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
</script>
