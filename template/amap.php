<div class="t2s-global-content">
    <div class="t2s-container-fluid t2s-px-0">
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
        <div class="t2s-row t2s-stores-map-wrap">
            <div class="t2s-col-12 t2s-col-lg-4">
                <div class="t2s-stores-search-form">
                    <input class="t2s-stores-search-input" id="storesSearchInput" type="text" value="" name="storesSearchInput" placeholder="Search for stores" aria-required="true" />
                    <button class="t2s-stores-search-btn" type="search" aria-label="" onclick="buttonSubmit()"><i class="fa fa-search" aria-hidden="true"></i></button>
                </div>
                <div class="t2s-stores-search-list" id="storeList">
                <?php foreach ($results as $key => $value) : ?>
                    <div class="t2s-stores-search-item">
                        <div class="t2s-stores-search-left">
                            <h4 class="t2s-stores-search-title"><a href="<?php echo esc_url(site_url('t2s-store/' . $result->id) . '/'); ?>"><?php echo $value->name; ?></a></h4>
                            <div class="t2s-stores-search-address" data-lat="<?php echo $value->lat; ?>" data-lng="<?php echo $value->lon; ?>"><?php echo $value->address; ?></div>
                        </div>
                        <a class="t2s-stores-search-right" href="<?php echo esc_url(site_url('t2s-store/' . $result->id) . '/'); ?>" style="background-image: url(<?php echo $value->image_url; ?>);"></a>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <div class="t2s-col-12 t2s-col-lg-8">
                <div id="T2SStoreLocatorMap" class="t2s-stores-map-container" style="height: <?php echo $height; ?>px;width: 100%;"></div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="https://webapi.amap.com/maps?v=1.4.15&key=<?php echo get_option('T2S_StoreLocator_amap_api'); ?>&plugin=AMap.MarkerClusterer"></script>
<script type="text/javascript">
    const initLocations = <?php echo json_encode($results, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES);?>;
    var map = new AMap.Map("T2SStoreLocatorMap", {
        resizeEnable: true, //是否监控地图容器尺寸变化
        zoom: 16, //初始化地图层级
        // center: [116.397428, 39.90923] //初始化地图中心点
    });

    var infoWindow = new AMap.InfoWindow({
        anchor: "top-left",
        offset: new AMap.Pixel(0, -30)
    });

    // Add some markers to the map.
    var locations = [];
    initLocations.map((position) => {
        if(position.lat && position.lon && typeof(position.lat) === 'number' && typeof(position.lon) === 'number'){
            locations.push(position);
        }
    });
    var preLink = "<?php echo esc_url(site_url('t2s-store/')); ?>";
    const markers = locations.map((position, i) => {
        const marker = new AMap.Marker({
            map: map,
            position: [position.lon , position.lat],
            offset: new AMap.Pixel(0,-20),
            clickable : true
        });
        map.setFitView();
        var content =
        `<div class="marker-content"><h3><a href="${preLink+position.id+'/'}" target="_blank">${position.name}</a></h3>
        <p><em>${position.address}</em></p>`;

        marker.content = content;
        marker.on("click", markerClick);
        marker.emit('click', { target: marker });// 此处是设置默认出现信息窗体
        map.add(marker);
        return marker;
    });

    /* 设置聚合
     * @param map:地图实例
     * @param markers:标点对象数组
    */
    const cluster = new AMap.MarkerClusterer(map);
    cluster.setMarkers(markers);

    function markerClick (e) {
        infoWindow.setContent(e.target.content);
        infoWindow.open(map, e.target.getPosition());
    };

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
                    jQuery("#storeList").html(top);
                    jQuery("#storeList").change();
                    // 清空地图上的marker
                    map.clearMap();
                    var locations_ = data['locations'];
                    var markers_ = [];
                    for (var i = 0; i < locations_.length; i++) {
                        var marker_ = new AMap.Marker({
                            map: map,
                            position: [locations_[i]['lon'], locations_[i]['lat']],
                            offset: new AMap.Pixel(0,-20),
                            clickable : true
                        });
                        var content_ = `<div class="marker-content">
                            <h3><a href="${locations_[i]['link']}">${locations_[i]['name']}</a></h3>
                            <p><em>${locations_[i]['address']}</em></p>
                        </div>`
                        marker_.content = content_;
                        marker_.on("click", markerClick);
                        marker_.emit('click', { target: marker_ });// 此处是设置默认出现信息窗体
                        markers_.push(marker_);
                    }
                    // 地图重新定位到新的中心点
                    map.setFitView();
                    // 重新设置聚合
                    cluster.setMarkers(markers_);
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
