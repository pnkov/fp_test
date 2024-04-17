# syntax=docker/dockerfile:1

FROM composer:lts as deps
WORKDIR /app

RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --no-interaction


FROM php:8.3-alpine
WORKDIR /app

RUN docker-php-ext-install mysqli

COPY --from=deps /app/vendor/ ./vendor
COPY ./test.php ./
COPY ./src ./src

CMD ["php", "-f", "test.php"]
