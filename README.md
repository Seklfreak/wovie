# WOVIE

An website to manage your movie shelf.

Rabbit: `sudo -u http app/console rabbitmq:consumer -w -l 128 create_activity -v`

### supervisord wovie.conf
```ini
[program:wovie-rabbitmq-consumer-create-activity]
command=/usr/bin/php /path/to/wovie/app/console rabbitmq:consumer -w -l 128 create_activity
directory=/path/to/wovie
autostart=true
autorestart=true
stdout_logfile=/path/to/wovie/app/logs/rabbitmq/consumer-create-activity-%(process_num)s.log
stdout_logfile_maxbytes=1MB
stderr_logfile=/path/to/wovie/app/logs/rabbitmq/consumer-create-activity-%(process_num)s.log
stderr_logfile_maxbytes=1MB
user=www-data
numprocs=1
process_name=%(program_name)s_%(process_num)s
```

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
