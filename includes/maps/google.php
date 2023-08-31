<input id="pac-input"
    class="map-search-controls search-form-input"
    type="text"
    placeholder="<?php _e('Search', 't2s-store-locator'); ?>"
    style="
        margin: 10px 0 0;
        width: calc(100% - 256px);
        height: 40px;
        border: 0;
        background: none padding-box rgb(255, 255, 255);
        box-shadow: rgba(0, 0, 0, 0.3) 0px 1px 4px -1px;
        border-radius: 2px;
    "
/>
<div id="map" style="height: 500px;width: 100%;"></div>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $google_api; ?>&callback=initAutocomplete&libraries=places&v=weekly" async defer></script>
<script>
    function displayCoordinates(latLng, address) {
        document.getElementById("T2SStoreLocator_meta_latitude").value = latLng.lat().toFixed(6);
        document.getElementById("T2SStoreLocator_meta_longitude").value = latLng.lng().toFixed(6);
        document.getElementById("T2SStoreLocator_meta_address").value = address ? address : '';
    }

    function initAutocomplete() {
        const mapCenter = {
            lat: <?php echo $center_latitude; ?>,
            lng: <?php echo $center_longitude; ?>
        };
        <?php if ($latitude && $longitude) { ?>
            mapCenter.lat = <?php echo $latitude; ?>;
            mapCenter.lng = <?php echo $longitude; ?>;
        <?php } ?>
        const map = new google.maps.Map(document.getElementById("map"), {
            center: mapCenter,
            zoom: 13,
            mapTypeId: "roadmap",
        });
        const marker = new google.maps.Marker({
            position: mapCenter,
            map: map,
            draggable: true,
        });
        google.maps.event.addListener(map, "click", (event) => {
            const latLng = event.latLng;
            marker.setPosition(latLng);
            displayCoordinates(latLng);
        });
        google.maps.event.addListener(marker, "dragend", (event) => {
            const latLng = event.latLng;
            displayCoordinates(latLng);
        });

        // Create the search box and link it to the UI element.
        const input = document.getElementById("pac-input");
        const searchBox = new google.maps.places.SearchBox(input);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
        // Bias the SearchBox results towards current map's viewport.
        map.addListener("bounds_changed", () => {
            searchBox.setBounds(map.getBounds());
        });
        let markers = [];

        // Listen for the event fired when the user selects a prediction and retrieve
        // more details for that place.
        searchBox.addListener("places_changed", () => {
            const places = searchBox.getPlaces();
            if (places.length == 0) {
                return;
            }
            console.log(places)
            // Clear out the old markers.
            markers.forEach((marker) => {
                marker.setMap(null);
            });
            markers = [];

            // For each place, get the icon, name and location.
            const bounds = new google.maps.LatLngBounds();

            marker.setPosition(places[0].geometry.location, places[0].formatted_address);
            displayCoordinates(places[0].geometry.location, places[0].formatted_address);

            places.forEach((place) => {
                if (!place.geometry || !place.geometry.location) {
                    console.log("Returned place contains no geometry");
                    return;
                }

                const icon = {
                    url: place.icon,
                    size: new google.maps.Size(71, 71),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(17, 34),
                    scaledSize: new google.maps.Size(25, 25),
                };

                // Create a marker for each place.
                markers.push(
                    new google.maps.Marker({
                        map,
                        icon,
                        title: place.name,
                        position: place.geometry.location,
                    })
                );
                if (place.geometry.viewport) {
                    // Only geocodes have viewport.
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
            });
            map.fitBounds(bounds);
        });
    }
    window.initAutocomplete = initAutocomplete;
</script>
