function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            return sParameterName[1];
        }
    }
}

$(function() {
    $('input[type=radio][id=media_mediaType_0]').click(function() {
        $('#media_finalYear').prop('disabled', true);
        $('#media_numberOfSeasons').prop('disabled', true);
        $('#media_numberOfEpisodes').prop('disabled', true);
    });
    $('input[type=radio][id=media_mediaType_1]').click(function() {
        $('#media_finalYear').prop('disabled', false);
        $('#media_numberOfSeasons').prop('disabled', false);
        $('#media_numberOfEpisodes').prop('disabled', false);
    });
    $('.watched_it').click(function() {
        var button = $(this);
        if ($(button).data('media-id') != null)
        {
            $(button).prop('disabled', true);
            $(button).html('<i class="fa fa-spinner fa-spin fa-lg"></i><span style="margin-left: 5px;">Loading…</span>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_watchedit'),
                type: "POST",
                data: { media_id: $(button).data('media-id') }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $(button).addClass('success');
                        $(button).html('Watched!');
                    }
                    else
                    {
                        $(button).addClass('alert');
                        $(button).html('Error!');
                    }
                    console.debug(data);
                });
        }
    });

    $(document).foundation({});

    if (typeof getUrlParameter('q') !== "undefined")
    {
        $.ajax({
            url: Routing.generate('slmn_wovie_action_ajax_search_external', { q: getUrlParameter('q') })
        })
            // TODO: Error handling
            .success(function(data) {
                $('#ajax-externalSearchContainer').html(data);
                $('.ajax-fetchTopic').each(function() { // Lazy?
                    var field = $(this);
                    $.ajax({
                        url: Routing.generate('slmn_wovie_actions_ajax_fetch_description', { id: $(this).data('id') })
                    })
                        // TODO: Error handling
                        .success(function(data) {
                            $(field).html(data);
                        });
                });
            });
    }
});