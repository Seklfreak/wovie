# WOVIE

Keep control over your movie collection.

## Requires
- NGINX (or another webserver)
- PHP5 (+ APCu)
- MySQL
- jpegoptim
- RabbitMQ
- redis
- supervisor
- elasticsearch
- External services
    - Google API (Freebase API)
    - Stripe

## Ubuntu LTS 14.04 Dependencies
`$ apt-get install mysql-server php5-mysql php5-curl php5-apcu php5-intl jpegoptim ruby ruby-compass nodejs npm`
Check your configuration with `$ php -f app/check.php`

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
vhost: /wovie user: guest:guest (`$ rabbitmqctl add_vhost /wovie && rabbitmqctl set_permissions -p /wovie guest ".*" ".*" ".*"`)

## TODO
- [ ] Advanced search (Actor, Length, …)
- [ ] Rating (Favorite, Rating(Points/Like+Dislike)…)
- [ ] Chrome Extension using oAuth21
    -> https://github.com/FriendsOfSymfony/FOSOAuthServerBundle
    -> https://github.com/FriendsOfSymfony/FOSRestBundle
    -> http://smus.com/oauth2-chrome-extensions/
