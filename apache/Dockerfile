#ARG APACHE_VERSION=""
#FROM httpd:${APACHE_VERSION:+${APACHE_VERSION}-}alpine


FROM httpd:latest

# Копируйте вашу конфигурацию Apache в контейнер
COPY demo.apache.conf /usr/local/apache2/conf/httpd.conf

# Копируйте содержимое вашего проекта в директорию /usr/local/apache2/htdocs внутри контейнера
COPY . /usr/local/apache2/htdocs/

RUN apk update; \
    apk upgrade;
