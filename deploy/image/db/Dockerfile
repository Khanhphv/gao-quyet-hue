FROM mariadb:11.4

# Database của site (build-image.sh chép vào). Tự nhập ở lần chạy đầu, khi volume dữ liệu còn trống.
COPY db.sql.gz /docker-entrypoint-initdb.d/
