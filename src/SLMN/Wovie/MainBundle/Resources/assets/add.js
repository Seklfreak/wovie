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

function refreshMediaContainers(mediaId)
{
    $('.refresh-media').each(function() {
        var $container = $(this);
        if ($container.data('src') && (!mediaId || $container.data('media-id') == mediaId)) {
            var src = $container.data('src');
            $.ajax({
                url: src
            })
                // TODO: Error handling
                .success(function (data) {
                    $container.html(data);
                    init();
                });
        }
    })
}

function init()
{
    // Foundation
    $(document).foundation({});
    // Zero Clipboard
    ZeroClipboard.config( {
        cacheBust: false,
        swfPath: Routing.generate('slmn_wovie_js_zeroClipboardSwf').replace('app_dev.php/', '')
    } );
    var client = new ZeroClipboard($('.clipboard'));
    // Scroll to
    if ($('#scroll-to').length > 0) {
        $('html, body').animate({
            scrollTop: $('#scroll-to').offset().top
        }, 2000);
    }
    // Lazy load
    $('img.lazy').show().lazyload({
        effect : "fadeIn"
    });
    // Watched it buttons
    $('.watched_it, .watched_it_cover').unbind().click(function() {
        var button = $(this);
        if ($(button).data('media-id') != null)
        {
            $(button).prop('disabled', true);
            $(button).html('<i class="fa fa-spinner fa-spin fa-lg"></i>');
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
                        $(button).html('<i class="fa fa-check fa-lg"></i>');
                        if ($(button).data('episode-id') != null)
                        {
                            window.parent.$('#modal-frame-choose-episode').foundation('reveal', 'close');
                            window.parent.refreshMediaContainers($(button).data('media-id'));
                        }
                        refreshMediaContainers($(button).data('media-id'));
                    }
                    else
                    {
                        $(button).addClass('alert');
                        $(button).html('Error!');
                    }
                });
        }
    });
    $('.watched_it_not').unbind().click(function() {
        var button = $(this);
        if ($(button).data('media-id') != null)
        {
            $(button).prop('disabled', true);
            $(button).html('<i class="fa fa-spinner fa-spin fa-lg"></i>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_watcheditnot'),
                type: "POST",
                data: { media_id: $(button).data('media-id'), episode_ids: [$(button).data('episode-id')] }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $(button).html('<i class="fa fa-check fa-lg"></i>');
                        if ($(button).data('episode-id') != null)
                        {
                            window.parent.$('#modal-frame-choose-episode').foundation('reveal', 'close');
                            window.parent.refreshMediaContainers($(button).data('media-id'));
                        }
                        refreshMediaContainers($(button).data('media-id'));
                    }
                    else
                    {
                        $(button).addClass('alert');
                        $(button).html('Error!');
                    }
                });
        }
    });
    // Activity infinite container
    $('#activity-infinite-container').waypoint('infinite', {
        onBeforePageLoad: function() {
            $('#activity-infinite-spinner').removeClass('hide');
        },
        onAfterPageLoad: function() {
            $('#activity-infinite-spinner').addClass('hide');
            init();
        }
    });
}

function resetFilter()
{
    $('#media-entries').html($('#media-entries').data('html'));
    $('.filter-nav > dd').removeClass('active');
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
    // Filter
    $('#media-entries').data('html', $('#media-entries').html());
    $('a[id=filter-reset]').click(function()
    {
        resetFilter();
        $(this).parent().addClass('active');
        init();

    });
    $('a[id=filter-movies]').click(function()
    {
        resetFilter();
        $(this).parent().addClass('active');
        $('.media-entry').filter('[data-media-type=2]').remove();
        init();

    });
    $('a[id=filter-series]').click(function()
    {
        resetFilter();
        $(this).parent().addClass('active');
        $('.media-entry').filter('[data-media-type=1]').remove();
        init();

    });
    $('a[id=filter-unseen]').click(function()
    {
        resetFilter();
        $(this).parent().addClass('active');
        $('.media-entry').filter('[data-views!=0]').remove();
        init();

    });
    $('a[id=filter-in-progress]').click(function()
    {
        resetFilter();
        $(this).parent().addClass('active');
        $('.media-entry').filter('[data-media-type=1]').remove();
        $('.media-entry').filter('[data-views=0]').remove();
        $('.media-entry').each(function()
        {
            var views = $(this).data('views');
            var numberOfEpisodes = $(this).data('numberofepisodes');
            if (views && numberOfEpisodes)
            {
                if (views >= numberOfEpisodes)
                {
                    $(this).remove();
                }
            }
        });
        init();
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
    $('.choose-episode-select-submit-watched').unbind().click(function() {
        var button = $(this);
        var mediaId = $(this).data('media-id');
        var selected = [];
        $('.choose-episode-checkbox-input:checkbox:checked').each(function() {
            selected.push($(this).data('episode-id'));
        });
        if (mediaId != null && selected.length > 0)
        {
            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin fa-lg"></i>');
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
                        $(button).html('<i class="fa fa-check fa-lg"></i>');
                        window.parent.$('#modal-frame-choose-episode').foundation('reveal', 'close');
                        window.parent.refreshMediaContainers(mediaId);
                    }
                    else
                    {
                        $(button).addClass('alert');
                        $(button).html('Error!');
                    }
                });
        }
    });
    $('.choose-episode-select-submit-notwatched').unbind().click(function() {
        var button = $(this);
        var mediaId = $(this).data('media-id');
        var selected = [];
        $('.choose-episode-checkbox-input:checkbox:checked').each(function() {
            selected.push($(this).data('episode-id'));
        });
        if (mediaId != null && selected.length > 0)
        {
            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin fa-lg"></i>');
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
                        $(button).html('<i class="fa fa-check fa-lg"></i>');
                        window.parent.$('#modal-frame-choose-episode').foundation('reveal', 'close');
                        window.parent.refreshMediaContainers(mediaId);
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
    $('.action-media-delete').unbind().click(function() {
        var button = $(this);
        var mediaId = $(this).data('media-id');
        if (mediaId != null)
        {
            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin fa-lg"></i>');
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
    /* FOLLOW AND DEFOLLOW */
    $('.follow-button').unbind().click(function() {
        var button = $(this);
        var userId = $(this).data('user-id');
        if (userId)
        {
            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin fa-lg"></i>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_user_follow'),
                type: "POST",
                data: { userId: userId }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $(button).addClass('success');
                        $(button).html('Followed');
                    }
                    else
                    {
                        $(button).addClass('alert');
                        $(button).html('Error!');
                    }
                });
        }
    });
    $('.defollow-button').unbind().click(function() {
        var button = $(this);
        var userId = $(this).data('user-id');
        if (userId)
        {
            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin fa-lg"></i>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_user_defollow'),
                type: "POST",
                data: { userId: userId }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $(button).addClass('alert');
                        $(button).html('Defollowed');
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