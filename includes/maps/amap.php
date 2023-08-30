<input
    id="hintPut"
    class="map-search-controls"
    type="text"
    placeholder="搜索"
    style="
        width: 100%;
        height: 40px;
        border: 0;
        background: none padding-box rgb(255, 255, 255);
        box-shadow: rgba(0, 0, 0, 0.3) 0px 1px 4px -1px;
        border-radius: 2px 2px 0 0;
    "
/>
<div id="T2SStoreLocatorMap" style="height: 500px;width: 100%;"></div>
<script type="text/javascript" src="https://webapi.amap.com/maps?v=1.4.15&key=<?php echo $amap_api; ?>&plugin=AMap.Autocomplete"></script>
<script type="text/javascript" src="https://cache.amap.com/lbs/static/addToolbar.js"></script>
<script>
    function displayCoordinates(marker, address) {
        document.getElementById("T2SStoreLocator_meta_latitude").value = marker.lat.toFixed(6);
        document.getElementById("T2SStoreLocator_meta_longitude").value = marker.lng.toFixed(6);
        document.getElementById("T2SStoreLocator_meta_address").value = address ? address : '';
    }
    var map = new AMap.Map("T2SStoreLocatorMap", {
        resizeEnable: true,
        zoom: 16,
        center: [<?php echo $center_longitude; ?>, <?php echo $center_latitude; ?>]
    });

    //输入提示
    var autoOptions = {
        input: "hintPut"
    };

    AMap.plugin(['AMap.PlaceSearch', 'AMap.Autocomplete'], function() {
        var auto = new AMap.Autocomplete(autoOptions);
        var placeSearch = new AMap.PlaceSearch({
            map: map
        }); //构造地点查询类
        auto.on("select", select); //注册监听，当选中某条记录时会触发
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
