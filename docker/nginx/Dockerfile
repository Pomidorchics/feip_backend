FROM nginx:alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

RUN mkdir -p /var/log/nginx

WORKDIR /var/www