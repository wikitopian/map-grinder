jQuery(document).ready(function($) {
    $("#map-grinder-button")
    .click(function(event) {

        $.post(
            ajaxurl,
            {
                action: 'fetch_geo',
        data: 'Test'
            },
            function(response) {
                var geo = $.parseJSON(response.childNodes[0].textContent);
                console.dir(geo);
            }
        );

    });
});
