FROM laravelphp/vapor:php82

RUN apk --update add mysql-client
COPY ./php.ini /usr/local/etc/php/conf.d/overrides.ini

COPY . /var/task
