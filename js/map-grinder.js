var map_grinder_ready = true;
var map_grinder_busy  = false;

jQuery(document).ready(function($) {
    $("#map-grinder-button")
    .click(function(event) {

        setInterval( function() {
            if(map_grinder_ready && !map_grinder_busy) {
                fetch_geo();
            }
        },
        3000
        );
    });
});

function fetch_geo() {
    map_grinder_busy = true;
    jQuery.post(
            ajaxurl,
            {
                action: 'fetch_geo'
            },
            function(response) {
                response = response.getElementsByTagName('response_data')[0].textContent;
                response = jQuery.parseJSON(response);

                if(response.status == 'EMPTY') {
                    map_grinder_ready = false;
                } else {
                    geocode(response.label, response.address);
                }
            }
            );
}

function geocode(label, address) {

    geocoder.geocode( { 'address': address}, function(results, status) {
        map_grinder_busy = false;

        if( results === undefined ) {
            results = new Array();
            results[0] = new Object();
        }

        results[0].label = label;
        results[0].status = status;

        if (status == google.maps.GeocoderStatus.OK) {
            results[0].geometry.location.latitude = results[0].geometry.location.lat();
            results[0].geometry.location.longitude = results[0].geometry.location.lng();
            results[0].geometry.viewport.northeast_latitude = results[0].geometry.viewport.getNorthEast().lat();
            results[0].geometry.viewport.northeast_longitude = results[0].geometry.viewport.getNorthEast().lng();
            results[0].geometry.viewport.southwest_latitude = results[0].geometry.viewport.getSouthWest().lat();
            results[0].geometry.viewport.southwest_longitude = results[0].geometry.viewport.getSouthWest().lng();

        } else if(
            status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT
            ||
            status == google.maps.GeocoderStatus.REQUEST_DENIED
            ) {
            alert('QUERY LIMIT REACHED');
            map_grinder_ready = false;
        } else {
            console.log("Geocode was not successful for the following reason: " + status);
        }

        var geodata = JSON.stringify(results);
        jQuery.post(
            ajaxurl,
            {
                action: 'put_geo',
            data: geodata
            },
            function(response) {
                console.log('Geocoded: ' + label + " (" + status + ")");
            }
            );
    });
}
