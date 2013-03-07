geocoder = new google.maps.Geocoder();

var map;
jQuery(document).ready(function($) {
    var myOptions = {
        center: new google.maps.LatLng(-34.397, 150.644),
    zoom: 8,
    mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    map = new google.maps.Map(document.getElementById("map_canvas"),
        myOptions);
});
