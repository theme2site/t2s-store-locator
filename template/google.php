<div class="global-content">
    <div class="container">
        <?php
            $query_args = array(
                'post_type' => 't2s_stores',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'post_parent' => 0
            );
            $the_query = new WP_Query($query_args);
        ?>
        <div class="row small-row t2s-stores-map-wrap">
            <div class="col-12 col-lg-4">
                <div class="t2s-stores-search-form">
                    <input class="t2s-stores-search-input" id="storesSearchInput" type="text" value="" name="storesSearchInput" placeholder="Search for stores" aria-required="true" />
                    <button class="t2s-stores-search-btn" type="search" aria-label="" onclick="buttonSubmit()"><i class="fa fa-search" aria-hidden="true"></i></button>
                </div>
                <div class="t2s-stores-search-list" id="storeList">
                <?php $locations = []; $store_names = [];
                    if ($the_query->have_posts()) : ?>
                    <?php while ($the_query->have_posts()) : $the_query->the_post(); ?>
                        <?php
                            global $post;
                            $address = get_post_meta($post->ID, 'T2SStoreLocator_meta_address') ? get_post_meta($post->ID, 'T2SStoreLocator_meta_address')[0] : '';
                            $lng = get_post_meta($post->ID, 'T2SStoreLocator_meta_longitude') ? get_post_meta($post->ID, 'T2SStoreLocator_meta_longitude')[0] : '';
                            $lat = get_post_meta($post->ID, 'T2SStoreLocator_meta_latitude') ? get_post_meta($post->ID, 'T2SStoreLocator_meta_latitude')[0] : '';
                            $locations[]  =  [
                                'title'   => get_the_title(),
                                'link'    => get_the_permalink(),
                                'address' => $address,
                                'lng'     => $lng,
                                'lat'     => $lat
                            ];
                            $store_names[]['value'] = get_the_title();
                        ?>
                        <div class="t2s-stores-search-item">
                            <div class="t2s-stores-search-left">
                                <h4 class="t2s-stores-search-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                <div class="t2s-stores-search-address" data-lat="<?php echo $lat; ?>" data-lng="<?php echo $lng; ?>"><?php echo $address; ?></div>
                            </div>
                            <a class="t2s-stores-search-right" href="<?php the_permalink(); ?>" style="background-image: url(<?php echo get_the_post_thumbnail_url(); ?>);"></a>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="acf-map" data-zoom="7" id="storeMap">
                    <?php foreach ($locations as $location) : ?>
                        <div class="marker" data-lat="<?php echo esc_attr($location['lat']); ?>" data-lng="<?php echo esc_attr($location['lng']); ?>">
                            <h3><a class="marker-title-link" href="<?php echo $location['link']; ?>"><?php echo esc_attr($location['title']); ?></a></h3>
                            <p><em><?php echo esc_html( $location['address'] ); ?></em></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php wp_reset_query(); ?>
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
