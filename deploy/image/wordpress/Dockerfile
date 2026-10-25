FROM wordpress:php8.3-apache

COPY --from=wordpress:cli-php8.3 /usr/local/bin/wp /usr/local/bin/wp
COPY php.ini /usr/local/etc/php/conf.d/quyethue.ini

# wp-content của site (plugin, theme, ảnh) thay cho bản mặc định. Image gốc chép nó vào volume ở lần chạy đầu.
RUN rm -rf /usr/src/wordpress/wp-content
COPY --chown=www-data:www-data wp-content /usr/src/wordpress/wp-content

# mu-plugin giao diện: chép đè mỗi lần khởi động, nên build image mới là giao diện được cập nhật.
COPY mu-plugins /usr/src/quyethue/mu-plugins
COPY quyethue-entrypoint.sh /usr/local/bin/

ENTRYPOINT ["quyethue-entrypoint.sh"]
CMD ["apache2-foreground"]
