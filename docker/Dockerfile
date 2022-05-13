FROM ubuntu:20.04

RUN groupadd -g 1000 ubuntu
RUN useradd -ms /bin/bash ubuntu -u 1000 -g 1000
RUN usermod -aG sudo ubuntu
COPY .my.cnf /home/ubuntu/.my.cnf

#RUN groupmod --new-name
RUN useradd -ms /bin/bash macos -u 501 -g 20
RUN usermod -aG sudo macos
COPY .my.cnf /home/macos

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update -y

RUN apt-get install sudo -y
COPY sudoers /etc/sudoers

RUN apt-get install mysql-client -y
RUN apt-get install npm -y

RUN apt-get install php -y
RUN apt-get install libapache2-mod-php -y
RUN apt-get install php-curl -y
RUN apt-get install php-mysql -y
RUN apt-get install php-zip -y
RUN apt-get install php-mbstring -y
RUN apt-get install php-imagick -y
RUN apt-get install php-intl -y
RUN apt-get install php-xml -y
RUN apt-get install php-xmlrpc -y
RUN apt-get install php-soap -y
RUN apt-get install composer -y
RUN apt-get install php-mailparse -y
RUN apt-get install php-gd -y

RUN apt-get install php-xdebug -y
RUN rm /etc/php/7.4/apache2/conf.d/20-xdebug.ini

RUN a2enmod rewrite

RUN apt-get install wget -y
RUN wget -O phive.phar "https://phar.io/releases/phive.phar" \
    && wget -O phive.phar.asc "https://phar.io/releases/phive.phar.asc" \
    && gpg --keyserver hkps://keys.openpgp.org --recv-keys 0x6AF725270AB81E04D79442549D8A98B29B2D5D79 \
    && gpg --verify phive.phar.asc phive.phar \
    && rm phive.phar.asc \
    && chmod +x phive.phar \
    && mv phive.phar /usr/local/bin/phive
\

EXPOSE 80
