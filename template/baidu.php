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
    <?php foreach ($results as $key => $location) : ?>
        var point = new BMap.Point(<?php echo $location->lon; ?> , <?php echo $location->lat; ?>);
        var marker = new BMap.Marker(point, {icon:myIcon});
        var html = `<div class="marker-content">
            <h3><a href="<?php echo esc_url(site_url('t2s-store/' . $location->id) . '/'); ?>"><?php echo esc_attr($location->name); ?></a></h3>
            <p><em><?php echo esc_html( $location->address ); ?></em></p>
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
                        var point = new BMap.Point(locations[i]['lon'], locations[i]['lat']);
                        var marker = new BMap.Marker(point, {icon:myIcon});
                        var html = `<div class="marker-content">
                            <h3><a href="${locations[i]['link']}">${locations[i]['name']}</a></h3>
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
