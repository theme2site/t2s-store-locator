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
    <div id="T2SStoreLocatorMap" style="height: <?php echo $height; ?>px;width: 100%;"></div>
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
            <h3><?php echo esc_attr($location->name); ?></h3>
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
</script>
