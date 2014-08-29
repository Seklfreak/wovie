$(function() {
    // TODO: Help modal with all keyboard commands
    // logged in?
    if (window.loggedIn == true)
    {
        // not in iframe?
        if (window.location == window.parent.location)
        {
            // s => focus search
            if ($('#search').length > 0) {
                Mousetrap.bind('s', function() { $('#search').focus(); return false; });
            }
            // g l => go to library
            Mousetrap.bind('g l', function () { window.location.href = Routing.generate('slmn_wovie_user_movie_shelf') });
            // g a => go to activity
            Mousetrap.bind('g a', function () { window.location.href = Routing.generate('slmn_wovie_user_activity') });
            // d n => mark next episode as watched
            if ($('#mark-next-episode').length > 0)
            {
                Mousetrap.bind('d n', function () { $('#mark-next-episode').click(); return false; });
            }
            // d w => mark as seen
            if ($('#mark-as-seen').length > 0)
            {
                Mousetrap.bind('d w', function () { $('#mark-as-seen').click(); return false; });
            }
        }
    }
    // shift+up => scroll to top
    Mousetrap.bind('shift+up', function() { $("html, body").animate({ scrollTop: 0 }, 400); });
    // shift+down => scroll to bottom
    Mousetrap.bind('shift+down', function() { $("html, body").animate({ scrollTop: $(document).height() }, 400); });
});
