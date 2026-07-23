FROM composer/composer:2-bin AS composer

FROM dunglas/frankenphp:1-php8.3-bookworm AS base

RUN apt update && apt -y install \
    unzip \
    curl \
    ca-certificates

RUN install-php-extensions \
    opcache \
    mbstring \
    intl \
    dom \
    ctype \
    curl \
    phar \
    openssl \
    xml \
    xmlwriter \
    simplexml \
    pdo \
    pdo_pgsql \
    redis \
    pcntl

# RoadRunner binary for queue worker supervision
ARG RR_VERSION=2024.3.5
RUN curl -sL "https://github.com/roadrunner-server/roadrunner/releases/download/v${RR_VERSION}/roadrunner-${RR_VERSION}-linux-amd64.tar.gz" \
    | tar -xz -C /tmp \
 && mv /tmp/roadrunner-${RR_VERSION}-linux-amd64/rr /usr/local/bin/rr \
 && chmod +x /usr/local/bin/rr \
 && rm -rf /tmp/roadrunner-${RR_VERSION}-linux-amd64

ENV LC_ALL=C.UTF-8

#
# Development
#

FROM base AS dev
ARG USER_ID=10001
ARG GROUP_ID=10001
ARG USER_NAME=appuser
ARG GROUP_NAME=appuser

RUN install-php-extensions \
    xdebug

COPY --from=composer /composer /usr/bin/composer

# Based on https://frankenphp.dev/docs/docker/#running-as-a-non-root-user
RUN \
	groupadd --gid ${GROUP_ID} ${GROUP_NAME}; \
    useradd --gid ${GROUP_ID} --uid ${USER_ID} ${GROUP_NAME}; \
	# Add additional capability to bind to port 80 and 443 \
	setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp; \
	# Give write access to /data/caddy and /config/caddy \
	chown -R ${USER_NAME}:${GROUP_NAME} /data/caddy && chown -R ${USER_NAME}:${GROUP_NAME} /config/caddy
USER ${USER_NAME}

COPY docker/dev/Caddyfile /etc/frankenphp/Caddyfile

#
# Production
#

FROM base AS prod-builder
COPY --from=composer /composer /usr/bin/composer
COPY . /app
RUN --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --no-progress --no-interaction --classmap-authoritative && \
    rm composer.lock composer.json

FROM base AS prod
ENV APP_ENV=prod
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/prod/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY --from=prod-builder --chown=www-data:www-data /app /app
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER root
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

#
# Queue worker (RoadRunner supervisor)
#

FROM prod AS worker
ENV APP_ENV=prod
WORKDIR /app
USER www-data
ENTRYPOINT ["/usr/local/bin/rr"]
CMD ["serve", "-c", "/app/.rr.yaml"]
