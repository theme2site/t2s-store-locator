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
                            <h4 class="t2s-stores-search-title"><a href="<?php echo esc_url(site_url('t2s-store/' . $result->id) . '/'); ?>"><?php echo $value->name; ?></a></h4>
                            <div class="t2s-stores-search-address" data-lat="<?php echo $value->lat; ?>" data-lng="<?php echo $value->lon; ?>"><?php echo $value->address; ?></div>
                        </div>
                        <a class="t2s-stores-search-right" href="<?php echo esc_url(site_url('t2s-store/' . $result->id) . '/'); ?>" style="background-image: url(<?php echo $value->image_url; ?>);"></a>
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
<script type="text/javascript" src="https://webapi.amap.com/maps?v=1.4.15&key=<?php echo get_option('T2S_StoreLocator_amap_api'); ?>"></script>
<script type="text/javascript">
    var map = new AMap.Map("T2SStoreLocatorMap", {
        resizeEnable: true, //是否监控地图容器尺寸变化
        zoom: 16, //初始化地图层级
        // center: [116.397428, 39.90923] //初始化地图中心点
    });

    var infoWindow = new AMap.InfoWindow({
        anchor: "top-left",
        offset: new AMap.Pixel(0, -30)
    });

    <?php foreach ($results as $key => $location) : ?>
        // 自动适应显示想显示的范围区域
        var marker<?php echo $key; ?> = new AMap.Marker({
            map: map,
            position: [<?php echo $location->lon; ?> , <?php echo $location->lat; ?>],
            offset: new AMap.Pixel(0,-20),
            clickable : true
        });
        map.setFitView();
        var content<?php echo $key; ?> = `<div class="marker-content">
            <h3><a href="<?php echo esc_url(site_url('t2s-store/' . $result->id) . '/'); ?>"><?php echo esc_attr($location->name); ?></a></h3>
            <p><em><?php echo esc_html( $location->address ); ?></em></p>
        </div>`
        marker<?php echo $key; ?>.content = content<?php echo $key; ?>;
        marker<?php echo $key; ?>.on("click", markerClick);
        marker<?php echo $key; ?>.emit('click', { target: marker<?php echo $key; ?> });// 此处是设置默认出现信息窗体
        map.add(marker<?php echo $key; ?>);
    <?php endforeach; ?>

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
                    var bottom = data['bottom'];
                    jQuery("#storeList").html(top);
                    jQuery("#storeList").change();
                    // 清空地图上的marker
                    map.clearMap();
                    var locations = data['locations'];
                    var markers = [];
                    for (var i = 0; i < locations.length; i++) {
                        var marker = new AMap.Marker({
                            map: map,
                            position: [locations[i]['lon'], locations[i]['lat']],
                            offset: new AMap.Pixel(0,-20),
                            clickable : true
                        });
                        var content = `<div class="marker-content">
                            <h3><a href="${locations[i]['link']}">${locations[i]['name']}</a></h3>
                            <p><em>${locations[i]['address']}</em></p>
                        </div>`
                        marker.content = content;
                        marker.on("click", markerClick);
                        marker.emit('click', { target: marker });// 此处是设置默认出现信息窗体
                        markers.push(marker);
                    }
                    // 地图重新定位到新的中心点
                    map.setFitView();
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
