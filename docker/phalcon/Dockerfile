# FROM php:7.2-fpm-stretch

# ENV PHALCON_VERSION=3.4.2
# RUN curl -LO https://github.com/phalcon/cphalcon/archive/v${PHALCON_VERSION}.tar.gz \
#     && tar xzf v${PHALCON_VERSION}.tar.gz \
#     && docker-php-ext-install ${PWD}/cphalcon-${PHALCON_VERSION}/build/php7/64bits \
#     && rm -rf v${PHALCON_VERSION}.tar.gz cphalcon-${PHALCON_VERSION} 


# RUN buildDeps="libxml2-dev zlib1g-dev" \
#     && apt-get update -y \
#     && apt-get install wget \
#     && apt-get install -y $buildDeps unzip git gnupg dirmngr --no-install-recommends --no-install-suggests \
#     && pecl install redis xdebug \
#     && docker-php-ext-enable redis xdebug \
#     && docker-php-ext-install opcache pdo_mysql mysqli soap zip \
#     && rm -rf /var/lib/apt/lists/* \
#     && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false $buildDeps 
# RUN apt-get update
# RUN apt-get install -y libcurl4-openssl-dev pkg-config libssl-dev
# RUN pecl install mongodb xdebug && docker-php-ext-enable mongodb xdebug

# #RUN pecl install redis xdebug && docker-php-ext-enable redis xdebug


FROM php:7.2-fpm-stretch

RUN buildDeps="libxml2-dev zlib1g-dev" \
    && apt-get update -y \
    && apt-get install wget \
    && apt-get install -y $buildDeps unzip git gnupg dirmngr --no-install-recommends --no-install-suggests \
    && pecl install redis xdebug psr-1.1.0 \
    && docker-php-ext-enable redis xdebug psr \
    && docker-php-ext-install opcache pdo_mysql mysqli soap zip \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false $buildDeps

ENV PHALCON_VERSION=4.1.1
RUN curl -LO https://github.com/phalcon/cphalcon/archive/v${PHALCON_VERSION}.tar.gz \
    && tar xzf v${PHALCON_VERSION}.tar.gz \
    && docker-php-ext-install ${PWD}/cphalcon-${PHALCON_VERSION}/build/php7/64bits \
    && rm -rf v${PHALCON_VERSION}.tar.gz cphalcon-${PHALCON_VERSION} 



RUN echo "session.save_path=\"/app/tmp\"" >> "$PHP_INI_DIR/php.ini"
RUN apt-get update
RUN apt-get install -y libcurl4-openssl-dev ssh nano pkg-config libssl-dev
RUN pecl install mongodb xdebug && docker-php-ext-enable mongodb xdebug

#RUN pecl install redis xdebug && docker-php-ext-enable redis xdebug
