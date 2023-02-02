FROM phpswoole/swoole:php8.1-alpine

WORKDIR /app

COPY composer.lock /app
COPY composer.json /app

RUN composer install

COPY . /app

EXPOSE 8080

CMD [ "php", "app/server.php" ]
