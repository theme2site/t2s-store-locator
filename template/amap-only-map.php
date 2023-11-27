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
        <div class="t2s-stores-map-wrap">
            <div id="T2SStoreLocatorMap" style="height: 500px;width: 100%;"></div>
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
            <h3><?php echo esc_attr($location->name); ?></h3>
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
</script>
<?php wp_reset_query(); ?>
