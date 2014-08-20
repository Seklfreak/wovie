# WOVIE

Keep control over your movie collection.

## Requires
- NGINX (or another webserver)
- PHP5 (+ GD)
- MySQL
- jpegoptim
- RabbitMQ
- redis
- supervisor (+ superlance)
- elasticsearch
- External services
    - Google API (Freebase API)
    - Stripe
- AWS S3 (stores custom covers)

## Ubuntu LTS 14.04 Dependencies
`$ apt-get install mysql-server php5-mysql php5-curl php5-apcu php5-intl jpegoptim ruby ruby-compass nodejs npm`
`$ easy_install superlance`
Check your configuration with `$ php -f app/check.php`

### php.ini
```ini
cgi.fix_pathinfo = 0
session.cookie_httponly = 1
```

### supervisord config

#### supervisord.conf
```ini
[eventlistener:crashmail]
command=/usr/local/bin/crashmail --any --email=<your@email.com>
events=PROCESS_STATE
```

#### wovie.conf
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
- [ ] API
    -> https://github.com/FriendsOfSymfony/FOSOAuthServerBundle
    -> https://github.com/FriendsOfSymfony/FOSRestBundle
- [ ] Chrome Extension using oAuth21
    -> http://smus.com/oauth2-chrome-extensions/
