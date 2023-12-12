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
<div class="t2s-stores-map-wrap">
    <div id="T2SStoreLocatorMap" style="height: 500px;width: 100%;"></div>
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
</script>
<?php wp_reset_query(); ?>
