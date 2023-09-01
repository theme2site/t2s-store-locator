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
                <div id="AtStoreLocatorMap" style="height: 500px;width: 100%;"></div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="https://webapi.amap.com/maps?v=1.4.15&key=<?php echo get_option('T2S_StoreLocator_amap_api'); ?>"></script>
<script type="text/javascript">
    var map = new AMap.Map("AtStoreLocatorMap", {
        resizeEnable: true, //是否监控地图容器尺寸变化
        zoom: 16, //初始化地图层级
        // center: [116.397428, 39.90923] //初始化地图中心点
    });

    var infoWindow = new AMap.InfoWindow({
        anchor: "top-left",
        offset: new AMap.Pixel(0, -30)
    });

    <?php foreach ($locations as $key => $location) : ?>
        // 自动适应显示想显示的范围区域
        var marker<?php echo $key; ?> = new AMap.Marker({
            map: map,
            position: [<?php echo $location['lng']; ?> , <?php echo $location['lat']; ?>],
            offset: new AMap.Pixel(0,-20),
            clickable : true
        });
        map.setFitView();
        var content<?php echo $key; ?> = `<div class="marker-content">
            <h3><a href="<?php echo $location['link']; ?>"><?php echo esc_attr($location['title']); ?></a></h3>
            <p><em><?php echo esc_html( $location['address'] ); ?></em></p>
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
                    action : 'T2SStoreLocator_get_stores',
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
<?php wp_reset_query(); ?>
