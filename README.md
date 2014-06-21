# WOVIE

Keep control over your movie collection.

## Requires
- NGINX (or another webserver)
- PHP5 (+ apc)
- MySQL
- jpegoptim
- RabbitMQ
- redis
- supervisor
- External services
    - Google API (Freebase API)
    - Stripe

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

### RabbitMQ
vhost: /wovie user: guest:guest

## TODO
- [ ] Advanced search (Actor, Length, …)
- [ ] Rating (Favorite, Rating(Points/Like+Dislike)…)
- [ ] Chrome Extension using oAuth21
    -> https://github.com/FriendsOfSymfony/FOSOAuthServerBundle
    -> https://github.com/FriendsOfSymfony/FOSRestBundle
    -> http://smus.com/oauth2-chrome-extensions/
