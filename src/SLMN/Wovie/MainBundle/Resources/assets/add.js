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
    $(document).foundation({
    });
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
    // Disable dropzone autodiscover
    Dropzone.autoDiscover = false;
    // Shelf titles all same high
    // TODO: Calculate max high PER LINE
    boxes = $('.shelf-media-titles');
    maxHeight = Math.max.apply(
        Math, boxes.map(function() {
            return $(this).height();
        }).get());
    boxes.height(maxHeight);
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
    $('.do-switch-favorite-media').unbind().click(function() {
        var $button = $(this);
        var mediaId = $button.data('media-id');
        if (mediaId != null)
        {
            $button.removeClass('fa-heart-o fa-heart');
            $button.addClass('fa-spinner fa-spin');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_toggle_favorite'),
                type: "POST",
                data: { media_id: mediaId }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $button.removeClass('fa-spinner fa-spin');
                        $button.addClass('fa-check');
                        refreshMediaContainers(mediaId);
                    }
                    else
                    {
                        $button.removeClass('fa-spinner fa-spin');
                        $button.addClass('fa-warning');
                    }
                });
        }
    });
    $('.do-rate-media').unbind().click(function() {
        var $button = $(this);
        var mediaId = $button.data('media-id');
        var rating = $button.data('rating');
        if (mediaId != null && rating != null)
        {
            $button.parent().html('<i class="fa fa-spinner fa-spin" style="font-size: 1.75em;"></i>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_rate'),
                type: "POST",
                data: { media_id: mediaId, rating: rating }
            })
                // TODO: Error handling
                .success(function(data) {
                    if (data.status == 'success')
                    {
                        $button.parent().html('<i class="fa fa-check" style="font-size: 1.75em;"></i>');
                        refreshMediaContainers(mediaId);
                    }
                    else
                    {
                        $button.parent().html('<i class="fa fa-warning" style="font-size: 1.75em;"></i>');
                    }
                });
        }
    });
    // Cover image upload
    if ($('.uploadCustomCoverBox').length > 0) {
        var mediaId = $('.uploadCustomCoverBox').data('media-id');
        var imgError = false;
        var thumbnailImg = '';
        if (mediaId) {
            var customCoverDropzone = new Dropzone(".uploadCustomCoverBox .coverImg", {
                maxFilesize: 1, // MB
                url: Routing.generate('slmn_wovie_actions_ajax_uploadCoverImage', {mediaId: mediaId}),
                previewsContainer: ".uploadCustomCoverBox .coverImg",
                maxThumbnailFilesize: 0,
                acceptedFiles: "image/jpeg,image/png"
            });
            customCoverDropzone.on('addedfile', function(file) {
                imgError = false;
                $('.uploadCustomCoverBox .message').html('<i class="fa fa-spinner fa-spin"></i> Uploading…');
                $('.uploadCustomCoverBox .message').attr('style', '');
            });
            customCoverDropzone.on('success', function(file, response) {
                if (response.error != undefined) {
                    customCoverDropzone.removeFile(file);
                    imgError = true;
                    $('.uploadCustomCoverBox .message').html('Error: ' + response.error);
                    $('.uploadCustomCoverBox .message').attr('style', '');
                } else if (imgError == false) {
                    if (response.status == 'success')
                    {
                        $('.uploadCustomCoverBox .message').html('');
                        $('.uploadCustomCoverBox .message').attr('style', 'display: none;');
                        $('.uploadCustomCoverBox .coverImg').attr('src', response.newPoster);
                    }
                    else
                    {
                        customCoverDropzone.removeFile(file);
                        imgError = true;
                        $('.uploadCustomCoverBox .message').html('Internal error!');
                        $('.uploadCustomCoverBox .message').attr('style', '');
                    }
                }
            });
            customCoverDropzone.on('error', function(file, errorMessage) {
                customCoverDropzone.removeFile(file);
                imgError = true;
                $('.uploadCustomCoverBox .message').html('Internal error!');
                $('.uploadCustomCoverBox .message').attr('style', '');
            });
            customCoverDropzone.on('thumbnail', function(file, dataUrl) {
                thumbnailImg = dataUrl;
            });
        }
        $('.uploadCustomCoverBox .delete').unbind().click(function() {
            var $button = $(this);
            var mediaId = $button.data('media-id');
            if (mediaId != null)
            {
                $button.html('<i class="fa fa-spinner fa-spin fa-2x"></i>');
                $.ajax({
                    url: Routing.generate('slmn_wovie_actions_ajax_deleteCoverImage'),
                    type: "POST",
                    data: { media_id: mediaId }
                })
                    // TODO: Error handling
                    .success(function(data) {
                        if (data.status == 'success')
                        {
                            $button.html('<i class="fa fa-check fa-2x"></i>');
                            $('.uploadCustomCoverBox .coverImg').attr('src', data.newPoster);
                        }
                        else
                        {
                            $button.html('<i class="fa fa-warning fa-2x"></i>');
                        }
                    });
            }
        });
    }
}

function resetFilter()
{
    $('#media-entries').html($('#media-entries').data('html'));
    $('.filter-nav > dd').removeClass('active');
}

$(function() {
    // Init
    init();
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
    $(document).foundation('joyride', 'start');
    // Replace vars
    $('.ajax-replace-mediatime').each(function() {
        var $span = $(this);
        var userId = $span.data('user-id');
        var prefix = $span.data('prefix');
        if (userId != null)
        {
            $span.html('<i class="fa fa-lg fa-spinner fa-spin"></i>');
            $.ajax({
                url: Routing.generate('slmn_wovie_actions_ajax_user_mediatime'),
                type: "POST",
                data: { user_id: userId }
            })
                // TODO: Error handling
                .success(function(data) {
                    $span.html(prefix + data);
                });
        }
    });
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
    $('a[id=filter-favorite]').click(function()
    {
        resetFilter();
        $(this).parent().addClass('active');
        $('.media-entry').filter('[data-favorite!=1]').remove();
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
                        $(button).html('<i class="fa fa-check fa-lg"></i>');
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
                        $(button).html('<i class="fa fa-check fa-lg"></i>');
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
                        $(button).html('<i class="fa fa-check fa-lg"></i>');
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