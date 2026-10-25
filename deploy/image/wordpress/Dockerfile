# Build từ thư mục gốc của repo (context ..), xem deploy/docker-compose.yml.
# Image chỉ chứa code. Dữ liệu (database, plugin, theme, ảnh) nằm trong volume, nhập lần đầu bằng deploy/nhap-du-lieu.sh.
FROM wordpress:php8.3-apache

COPY --from=wordpress:cli-php8.3 /usr/local/bin/wp /usr/local/bin/wp
COPY deploy/image/wordpress/php.ini /usr/local/etc/php/conf.d/quyethue.ini

# mu-plugin giao diện: chép đè mỗi lần khởi động, nên build lại image là giao diện được cập nhật.
COPY setup/mu-plugins /usr/src/quyethue/mu-plugins
COPY deploy/image/wordpress/quyethue-entrypoint.sh /usr/local/bin/

ENTRYPOINT ["quyethue-entrypoint.sh"]
CMD ["apache2-foreground"]
