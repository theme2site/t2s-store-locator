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
                <div id="T2SStoreLocatorMap" style="height: 500px;width: 100%;"></div>
            </div>
        </div>
    </div>
</div>
<script src="http://api.map.baidu.com/api?v=2.0&ak=<?php echo get_option('T2S_StoreLocator_baidu_map_api'); ?>"></script>
<script type="text/javascript">
    // 百度地图API功能
    var map = new BMap.Map("T2SStoreLocatorMap");
    var point = new BMap.Point(<?php echo get_option('T2SStoreLocator_center_longitude');?>, <?php echo get_option('T2SStoreLocator_center_latitude');?>);
    map.centerAndZoom(point, 15);
    map.enableScrollWheelZoom();
    map.enableContinuousZoom();
    var myIcon = new BMap.Icon("<?php echo T2S_STORE_LOCATOR_PLUGIN_URL.'/assets/imgs/marker.png';?>", new BMap.Size(29,29));
    myIcon.setImageSize(new BMap.Size(29, 29));
    <?php foreach ($locations as $key => $location) : ?>
        var point = new BMap.Point(<?php echo $location['lng']; ?> , <?php echo $location['lat']; ?>);
        var marker = new BMap.Marker(point, {icon:myIcon});
        var html = `<div class="marker-content">
            <h3><a href="<?php echo $location['link']; ?>"><?php echo esc_attr($location['title']); ?></a></h3>
            <p><em><?php echo esc_html( $location['address'] ); ?></em></p>
        </div>`;
        //设置infoWindow的大小
        var infoWindow = new BMap.InfoWindow(html);
        marker.infoWindow=infoWindow;
        marker.addEventListener("click", function(e){
            this.openInfoWindow(e.target.infoWindow);
        });
        map.addOverlay(marker);
    <?php endforeach; ?>


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
                    // 清空地图上的marker
                    map.clearOverlays();
                    var locations = data['locations'];
                    var markers = [];
                    // 循环渲染marker
                    for (var i = 0; i < locations.length; i++) {
                        var point = new BMap.Point(locations[i]['lng'], locations[i]['lat']);
                        var marker = new BMap.Marker(point, {icon:myIcon});
                        var html = `<div class="marker-content">
                            <h3><a href="${locations[i]['link']}">${locations[i]['title']}</a></h3>
                            <p><em>${locations[i]['address']}</em></p>
                        </div>`;
                        //设置infoWindow的大小
                        var infoWindow = new BMap.InfoWindow(html);
                        marker.infoWindow=infoWindow;
                        marker.addEventListener("click", function(e){
                            this.openInfoWindow(e.target.infoWindow);
                        });
                        map.addOverlay(marker);
                        markers.push(marker);
                    }
                    // 重新设置地图中心点
                    map.centerAndZoom(point, 15);
                }
            });
        }
    };

    var autocompleterData = <?php echo json_encode($store_names, JSON_UNESCAPED_UNICODE); ?>;
    jQuery('#storesSearchInput').autocomplete({
        lookup: autocompleterData,
        triggerSelectOnValidInput: false,
        onSelect: function (suggestion) {
            submitForm(suggestion.value);
        }
    });
</script>
<?php wp_reset_query(); ?>
