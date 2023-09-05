<input
    id="hintPut"
    class="search-form-input"
    type="text"
    placeholder="<?php _e('Search', 't2s-store-locator'); ?>"
/>
<div id="result1" class="autobox" name="result1"></div>
<div id="T2SStoreLocatorMap" style="height: 500px;width: 100%;"></div>
<script type="text/javascript">
    window._AMapSecurityConfig = {
        securityJsCode: "<?php echo $amap_api_secret; ?>",
    }
</script>
<script type="text/javascript" src="https://webapi.amap.com/maps?v=2.0&key=<?php echo $amap_api; ?>&plugin=AMap.AutoComplete"></script>
<script type="text/javascript" src="https://cache.amap.com/lbs/static/addToolbar.js"></script>
<script>
    function displayCoordinates(marker, address) {
        document.getElementById("T2SStoreLocator_meta_latitude").value = marker.lat.toFixed(6);
        document.getElementById("T2SStoreLocator_meta_longitude").value = marker.lng.toFixed(6);
        // document.getElementById("T2SStoreLocator_meta_address").value = address ? address : '';
    }
    var map = new AMap.Map("T2SStoreLocatorMap", {
        resizeEnable: true,
        zoom: 16,
        center: [<?php echo $center_longitude; ?>, <?php echo $center_latitude; ?>]
    });

    var autoOptions = {
        input: "hintPut"
    };

    AMap.plugin(['AMap.PlaceSearch','AMap.AutoComplete'], function() {
        var auto = new AMap.AutoComplete(autoOptions);
        var placeSearch = new AMap.PlaceSearch({
            map: map
        });
        console.log(auto);
        auto.on("select", select);
        function select(e) {
            placeSearch.setCity(e.poi.adcode);
            placeSearch.search(e.poi.name); //关键字查询查询
            displayCoordinates(e.poi.location, e.poi.address)
        }
    });

    map.on("click", (e) => {
        displayCoordinates(e.lnglat)
    });
</script>
