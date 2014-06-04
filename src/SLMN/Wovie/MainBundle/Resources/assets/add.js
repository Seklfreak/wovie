function getUrlParameter(sParam)
{
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            return sParameterName[1];
        }
    }
}

function chooseEpisodeSelectCount()
{
    var len = $('.choose-episode-checkbox-input:checkbox:checked').length;
    $('.choose-episode-count').text(len);
}

function init()
{
    $(document).foundation({});
    ZeroClipboard.config( {
        cacheBust: false,
        swfPath: Routing.generate('slmn_wovie_js_zeroClipboardSwf').replace('app_dev.php/', '')
    } );
    var client = new ZeroClipboard($('.clipboard'));
}

$(function() {
    // Init
    init();
    $(document).foundation('joyride', 'start');
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
    $('.watched_it, .watched_it_cover').click(function() {
        var button = $(this);
        if ($(button).data('media-id') != null)
        {
            $(button).prop('disabled', true);
            $(button).html('<i class="fa fa-spinner fa-spin fa-lg"></i><span style="margin-left: 5px;">Loading…</span>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_watchedit'),
                type: "POST",
                data: { media_id: $(button).data('media-id'), episode_ids: [$(button).data('episode-id')] }
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
    $('.watched_it_not').click(function() {
        var button = $(this);
        if ($(button).data('media-id') != null)
        {
            $(button).prop('disabled', true);
            $(button).html('<i class="fa fa-spinner fa-spin fa-lg"></i><span style="margin-left: 5px;">Loading…</span>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_watcheditnot'),
                type: "POST",
                data: { media_id: $(button).data('media-id'), episode_ids: [$(button).data('episode-id')] }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $(button).html('Removed');
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
                init();
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
    // Choose episode
    $('.choose-episode-checkbox-input:checkbox').click(function() {
        chooseEpisodeSelectCount();
    });
    $('.choose-episode-select-all').click(function() {
        $('.choose-episode-checkbox-input:checkbox').prop('checked', true);
        chooseEpisodeSelectCount();
    });
    $('.choose-episode-select-none').click(function() {
        $('.choose-episode-checkbox-input:checkbox').prop('checked', false);
        chooseEpisodeSelectCount();
    });
    $('.choose-episode-select-invert').click(function() {
        $('.choose-episode-checkbox-input:checkbox').each(function() {
            if ($(this).prop('checked'))
            {
                $(this).prop('checked', false);
            }
            else
            {
                $(this).prop('checked', true);
            }
        });
        chooseEpisodeSelectCount();
    });
    $('.choose-episode-select-season').click(function() {
        var season = $(this).data('season');
        $('.choose-episode-checkbox-input:checkbox').each(function() {
            if ($(this).data('season') == season)
            {
                $(this).prop('checked', true);
            }
        });
        chooseEpisodeSelectCount();
    });
    $('.choose-episode-unselect-season').click(function() {
        var season = $(this).data('season');
        $('.choose-episode-checkbox-input:checkbox').each(function() {
            if ($(this).data('season') == season)
            {
                $(this).prop('checked', false);
            }
        });
        chooseEpisodeSelectCount();
    });
    $('.choose-episode-select-submit-watched').click(function() {
        var button = $(this);
        var mediaId = $(this).data('media-id');
        var selected = [];
        $('.choose-episode-checkbox-input:checkbox:checked').each(function() {
            selected.push($(this).data('episode-id'));
        });
        if (mediaId != null && selected.length > 0)
        {
            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin fa-lg"></i><span style="margin-left: 5px;">Loading…</span>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_watchedit'),
                type: "POST",
                data: { media_id: mediaId, episode_ids: selected }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $(button).addClass('success');
                        $(button).html('Watched!');
                        window.parent.$('#modal-frame-choose-episode').foundation('reveal', 'close');
                    }
                    else
                    {
                        $(button).addClass('alert');
                        $(button).html('Error!');
                    }
                });
        }
    });
    $('.choose-episode-select-submit-notwatched').click(function() {
        var button = $(this);
        var mediaId = $(this).data('media-id');
        var selected = [];
        $('.choose-episode-checkbox-input:checkbox:checked').each(function() {
            selected.push($(this).data('episode-id'));
        });
        if (mediaId != null && selected.length > 0)
        {
            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin fa-lg"></i><span style="margin-left: 5px;">Loading…</span>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_watcheditnot'),
                type: "POST",
                data: { media_id: mediaId, episode_ids: selected }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $(button).addClass('success');
                        $(button).html('Removed');
                        window.parent.$('#modal-frame-choose-episode').foundation('reveal', 'close');
                    }
                    else
                    {
                        $(button).addClass('alert');
                        $(button).html('Error!');
                    }
                });
        }
    });
    // Edit Media form
    $('.reset-freebase-data').click(function() {
        var input = $(this).attr('for');
        var value = $(this).data('value');
        if (input != null && value != null)
        {
            $('#' + input).val(value);
        }
    });
    $('.action-media-delete').click(function() {
        var button = $(this);
        var mediaId = $(this).data('media-id');
        if (mediaId != null)
        {
            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin fa-lg"></i><span style="margin-left: 5px;">Loading…</span>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_media_delete'),
                type: "POST",
                data: { media_id: mediaId }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $(button).addClass('alert');
                        $(button).html('Deleted');
                        window.location.replace(Routing.generate('slmn_wovie_user_movie_shelf'));
                    }
                    else
                    {
                        $(button).addClass('alert');
                        $(button).html('Error!');
                    }
                });
        }
    });
});