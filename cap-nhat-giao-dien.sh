#!/bin/sh
# Chép setup/mu-plugins (màu, font, CSS, chế độ catalog) vào WordPress đang chạy. Không đụng tới nội dung trang.
cd "$(dirname "$0")" || exit 1
for f in quyethue.php quyethue.css; do
	docker compose exec -T wordpress sh -c "cat > /var/www/html/wp-content/mu-plugins/$f && chown www-data:www-data /var/www/html/wp-content/mu-plugins/$f" < "setup/mu-plugins/$f" || exit 1
done
echo "Đã cập nhật giao diện."
