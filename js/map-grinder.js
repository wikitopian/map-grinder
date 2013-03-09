jQuery(document).ready(function($) {
    $("#map-grinder-button")
    .click(function(event) {

        $.post(
            ajaxurl,
            {
                action: 'fetch_geo',
            },
            function(response) {
                var address = $.parseJSON(response.childNodes[0].textContent);
                var latlon  = geocode(address);
            }
        );

    });
});

function geocode(address_object) {
    var address =
          address_object.street
        + ', '
        + address_object.city
        + ', '
        + address_object.state
        + ' '
        + address_object.zip;

    geocoder.geocode( { 'address': address}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
            console.dir(results);
        } else {
            alert("Geocode was not successful for the following reason: " + status);
        }
    });
}
