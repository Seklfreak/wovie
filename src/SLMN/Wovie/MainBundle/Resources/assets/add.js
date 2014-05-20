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
    // Init foundation
    $(document).foundation({});
    // Shelf titles all same high
    // TODO: Calculate max high PER LINE
    boxes = $('.shelf-media-titles');
    maxHeight = Math.max.apply(
        Math, boxes.map(function() {
            return $(this).height();
        }).get());
    boxes.height(maxHeight);
    // Change add/edit form based on media type
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
    // Watched it button action
    $('.watched_it').click(function() {
        var button = $(this);
        if ($(button).data('media-id') != null)
        {
            $(button).prop('disabled', true);
            $(button).html('<i class="fa fa-spinner fa-spin fa-lg"></i><span style="margin-left: 5px;">Loading…</span>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_watchedit'),
                type: "POST",
                data: { media_id: $(button).data('media-id'), episode_id: $(button).data('episode-id') }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $(button).addClass('success');
                        $(button).html('Watched!');
                        if ($(button).data('episode-id') != null)
                        {
                            window.parent.$('#modal-frame-choose-episode').foundation('reveal', 'close');
                        }
                    }
                    else
                    {
                        $(button).addClass('alert');
                        $(button).html('Error!');
                    }
                });
        }
    });
    // Modal actions
    /* TODO: Load modal only with loading indicator -> when iframe is loaded
                Show modal with iframe. No loading screen glitches anymore!
     */
    $('#modal-frame-choose-episode > iframe').load(function() {
        if ($('#modal-frame-choose-episode > iframe').attr('src') != '')
        {
            $('#modal-frame-choose-episode > .modal-loading').addClass('hide');
        }
    });
    $(document).on('closed', '#modal-frame-choose-episode', function () {
        $('#modal-frame-choose-episode > iframe').attr('src', '');
        $('#modal-frame-choose-episode > .modal-loading').removeClass('hide');
    });
    // Search actions
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