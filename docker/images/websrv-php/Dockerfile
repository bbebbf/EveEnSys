FROM php:8.2-apache

ENV MSMTP_CONF_FILE 'msmtp.conf'

ENV APACHE_DOCUMENT_ROOT '/var/www/html'
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN apt-get update
RUN apt-get install -y --no-install-recommends \
    build-essential \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    libwebp-dev \
    curl \
    libcurl4 \
    libcurl4-openssl-dev \
    zlib1g-dev \
    libicu-dev \
    libmemcached-dev \
    memcached \
    libmagickwand-dev \
    unzip \
    libzip-dev \
    zip \
    msmtp msmtp-mta mailutils \
    locales locales-all \
    ;

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN usermod -u 1000 www-data && \
    usermod -G staff www-data

RUN docker-php-ext-install mysqli bcmath gd

RUN a2enmod rewrite && \
    sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

RUN echo "set sendmail=\"/usr/bin/msmtp -t\"" > /etc/mail.rc && \
    echo "sendmail_path=\"/usr/bin/msmtp -t\"" >> /usr/local/etc/php/conf.d/php-sendmail.ini

COPY ${MSMTP_CONF_FILE} /etc/msmtprc
RUN chmod a=r /etc/msmtprc