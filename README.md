# WOVIE

An website to manage your movie shelf.

Rabbit: `sudo -u http app/console rabbitmq:consumer -w -l 128 create_activity -v`

## TODO
- [ ] EMails expiration without cronjob, check link opening
- [ ] Add movie/series to the shelf
- [ ] How often did you watch the movie/series
- [ ] Progress on series
      - [ ] Many progresses on a series (watch with others and other things like that)
- [ ] Public shelf to show others
- [ ] RSS Feeds mit updates
- [ ] History
      - [ ] Activity View
      - [ ] Calendar View?
- [ ] Advanced search (Actor, Length, …)
- [ ] Rating
- [ ] Chrome Extension
    - [ ] Uses oAuth2 to login
        -> https://github.com/FriendsOfSymfony/FOSOAuthServerBundle
    - [ ] API oAuth2:
        -> https://github.com/FriendsOfSymfony/FOSRestBundle
    -> http://smus.com/oauth2-chrome-extensions/
