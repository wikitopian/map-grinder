jQuery(document).ready(function($) {
    $("#map-grinder-button")
    .click(function(event) {

        $.post(
            ajaxurl,
            {
                action: 'fetch_geo'
            },
            function(response) {
                response = response.getElementsByTagName('response_data')[0].textContent;
                response = $.parseJSON(response);

                var latlon  = geocode(response.label, response.address);
            }
        );

    });
});

function geocode(label, address) {

    geocoder.geocode( { 'address': address}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
            results[0].label = label;
            results[0].geometry.location.latitude = results[0].geometry.location.lat();
            results[0].geometry.location.longitude = results[0].geometry.location.lng();
            results[0].geometry.viewport.northeast_latitude = results[0].geometry.viewport.getNorthEast().lat();
            results[0].geometry.viewport.northeast_longitude = results[0].geometry.viewport.getNorthEast().lng();
            results[0].geometry.viewport.southwest_latitude = results[0].geometry.viewport.getSouthWest().lat();
            results[0].geometry.viewport.southwest_longitude = results[0].geometry.viewport.getSouthWest().lng();

            var geodata = JSON.stringify(results);
            jQuery.post(
                ajaxurl,
                {
                    action: 'put_geo',
                    data: geodata
                },
                function(response) {
                    console.log('Geocoded: ' + label);
                }
            );
        } else {
            alert("Geocode was not successful for the following reason: " + status);
        }
    });
}
