FROM ubuntu:20.04


RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -yq --no-install-recommends \
    apache2 \
    libapache2-mod-php \
    php-xmlrpc \
    redis-server \
    php \
    php-cli \
    php-common \
    php-gd \
    php-curl \
    php-mysqlnd \
    php-zip \
    php-fileinfo \
    php-gmagick \
    php-opcache \
    php-xml \
    php-mbstring \
    php-gmagick \
    graphicsmagick \
    ffmpeg \
    ghostscript \
    dcraw \
    php-posix \
    php-dev \
    php-pear \
    libgraphicsmagick1-dev \
    libpoppler-dev \
    poppler-utils \
    libimage-exiftool-perl \
    libreoffice \
    mediainfo

EXPOSE 80
CMD apachectl -D FOREGROUND
