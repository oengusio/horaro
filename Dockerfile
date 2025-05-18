FROM alpine:3.21 AS builder
LABEL maintainer="Sgt. Kabukiman"

# link to search for packages https://pkgs.alpinelinux.org/packages?name=php83&branch=v3.21
# install packages
RUN apk --no-cache add php84 php84-cli php84-fpm php84-mysqli php84-json php84-openssl php84-curl \
        php84-zlib php84-simplexml php84-xml php84-intl php84-xmlreader php84-xmlwriter php84-ctype php84-session \
        php84-tokenizer php84-phar php84-mbstring php84-pdo_mysql curl file git

# Copy php executable to /usr/local/bin so we can use it properly
RUN ln -s /usr/bin/php84 /usr/local/bin/php

# install Composer
ADD https://getcomposer.org/download/2.8.8/composer.phar /usr/bin/composer
RUN chmod +rx /usr/bin/composer

# add our sources
COPY . /build
WORKDIR /build

RUN echo "APP_ENV=prod" > .env && echo "APP_DEBUG=0" >> .env
# Copy over files we will later override
COPY config/parameters.dist.yml config/parameters.yml

# install PHP dependencies
RUN composer install --no-dev --no-progress --no-suggest --prefer-dist --ignore-platform-reqs --optimize-autoloader

# build assets
RUN php bin/console asset-map:compile && php bin/console cache:clear

# determine version
RUN git describe --tags --always > version

# remove temporary files to make the next copy commands easier
RUN rm -rf assets .git .gitignore tests var

###################################################################################
# second stage: final image

FROM alpine:3.21
LABEL maintainer="Sgt. Kabukiman"

# install packages
RUN apk --no-cache add php84 php84-cli php84-fpm php84-mysqli php84-json php84-openssl php84-curl \
    php84-zlib php84-simplexml php84-xml php84-intl php84-xmlreader php84-xmlwriter php84-ctype php84-session \
    php84-tokenizer php84-phar php84-mbstring php84-pdo_mysql nginx supervisor curl file

# setup user accounts
RUN adduser -D horaro
RUN adduser nginx horaro

# Copy php executable to /usr/local/bin so we can use it properly
RUN ln -s /usr/bin/php84 /usr/local/bin/php

# setup nginx
RUN rm /etc/nginx/nginx.conf
#RUN mkdir /run/nginx
COPY config/docker/nginx.conf /etc/nginx/nginx.conf

# setup PHP-FPM
RUN rm /etc/php84/php-fpm.d/www.conf
COPY config/docker/fpm-pool.conf /etc/php84/php-fpm.d/horaro.conf

# setup supervisord
COPY config/docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# add horaro
WORKDIR /var/www/horaro
COPY --from=builder /build .

# set up horaro directories
RUN mkdir -p log tmp/session tmp/upload
RUN chown -R horaro:horaro .

# finish the image up
EXPOSE 80
USER root
CMD ["sh", "entrypoint.sh"]
