<input
    id="hintPut"
    class="search-form-input"
    type="text"
    placeholder="<?php _e('Search', 't2s-store-locator'); ?>"
/>
<div id="searchResultPanel" style="border:1px solid #C0C0C0;width:100%;height:auto; display:none;"></div>
<div id="T2SStoreLocatorMap" style="height: 666px;width: 100%;"></div>
<script src="https://api.map.baidu.com/api?v=2.0&type=webgl&ak=<?php echo $baidu_api; ?>&callback=initAutocomplete&libraries=places&v=weekly"></script>
<script>
    function displayCoordinates(marker, address) {
        document.getElementById("T2SStoreLocator_meta_latitude").value = marker.latLng.lat.toFixed(6);
        document.getElementById("T2SStoreLocator_meta_longitude").value = marker.latLng.lng.toFixed(6);
        // document.getElementById("T2SStoreLocator_meta_address").value = address ? address : '';
    }

    function initAutocomplete() {
        // 创建地图实例
        let map = new BMapGL.Map("T2SStoreLocatorMap");
        // 创建点坐标
        let point = new BMapGL.Point(<?php echo $center_longitude; ?>, <?php echo $center_latitude; ?>);
        map.centerAndZoom(point, 15);
        //启用滚轮放大缩小，默认禁用。
        map.enableScrollWheelZoom(true);
        // 添加比例尺控件
        let scaleCtrl = new BMapGL.ScaleControl();
        let zoomCtrl = new BMapGL.ZoomControl();
        map.addControl(scaleCtrl);
        map.addControl(zoomCtrl);
        // 添加城市列表控件
        let cityCtrl = new BMapGL.CityListControl();
        map.addControl(cityCtrl);
        // 添加标记点
        let marker = new BMapGL.Marker(point);
        map.addOverlay(marker);
        //地图单击事件
        map.addEventListener("click", function(e){
            // 清除覆盖物
            map.clearOverlays();
            // 重设标记点
            let point = new BMapGL.Point(e.latlng.lng, e.latlng.lat);
            let marker = new BMapGL.Marker(point);
            map.addOverlay(marker);
            displayCoordinates(marker);
        });

        var ac = new BMapGL.Autocomplete({"input" : "hintPut"});

        ac.addEventListener("onhighlight", function(e) {  //鼠标放在下拉列表上的事件
        var str = "";
            var _value = e.fromitem.value;
            var value = "";
            if (e.fromitem.index > -1) {
                value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
            }
            str = "FromItem<br />index = " + e.fromitem.index + "<br />value = " + value;
            value = "";
            if (e.toitem.index > -1) {
                _value = e.toitem.value;
                value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
            }
            str += "<br />ToItem<br />index = " + e.toitem.index + "<br />value = " + value;
            document.getElementById("searchResultPanel").innerHTML = str;
        });

        var myValue;
        ac.addEventListener("onconfirm", function(e) {
            var _value = e.item.value;
            myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
            document.getElementById("searchResultPanel").innerHTML ="onconfirm<br />index = " + e.item.index + "<br />myValue = " + myValue;
            console.log(_value);
        });
    }
    window.initAutocomplete = initAutocomplete;
</script>
