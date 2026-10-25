#!/usr/bin/env bash
# Nhập dữ liệu lần đầu, hoặc khôi phục, từ một bản sao (tạo bởi xuat-du-lieu.sh, ở local hoặc ở EC2).
#   ./nhap-du-lieu.sh ban-sao/quyethue-20261025-101500.tar      (thêm -y để không hỏi xác nhận)
# CẢNH BÁO: ghi đè toàn bộ database và wp-content đang có.
# Sau khi nhập, container WordPress khởi động lại và tự đổi đường dẫn sang SITE_URL trong .env.
set -euo pipefail

cd "$(dirname "$0")"
FILE=${1:?Cách dùng: ./nhap-du-lieu.sh <file .tar> [-y]}
[ -f "$FILE" ] || { echo "Không thấy file $FILE" >&2; exit 1; }
[ -f .env ] || { echo "Chưa có .env. Chạy: cp .env.example .env rồi điền." >&2; exit 1; }
SITE_URL=$(grep -E '^SITE_URL=' .env | cut -d= -f2- | tr -d '"'"'"' \r')
SITE_URL=${SITE_URL%/}

if [ "${2:-}" != "-y" ]; then
	read -r -p "Ghi đè toàn bộ dữ liệu site $SITE_URL bằng $FILE? Gõ 'co' để tiếp tục: " ok
	[ "$ok" = "co" ] || { echo "Đã huỷ."; exit 1; }
fi

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
tar -C "$TMP" -xf "$FILE"

docker compose up -d --wait
# Đợi entrypoint chuẩn bị xong (chép WordPress, mu-plugin) và Apache chạy, rồi mới thay wp-content.
for _ in $(seq 90); do
	docker compose exec -T wordpress test -f /var/run/apache2/apache2.pid 2>/dev/null && break
	sleep 2
done
docker compose exec -T wordpress test -f /var/run/apache2/apache2.pid \
	|| { echo "WordPress chưa khởi động xong. Xem: docker compose logs wordpress" >&2; exit 1; }

echo "Nhập database..."
gunzip -c "$TMP/db.sql.gz" \
	| docker compose exec -T db sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -uroot "$MARIADB_DATABASE"'

echo "Nhập wp-content..."
docker compose exec -T wordpress sh -c \
	'rm -rf /var/www/html/wp-content && tar -C /var/www/html -xzf - && chown -R www-data:www-data /var/www/html/wp-content' \
	< "$TMP/wp-content.tar.gz"

echo "Khởi động lại WordPress (đổi đường dẫn, chép mu-plugin)..."
docker compose restart wordpress
for _ in $(seq 60); do
	[ "$(./wp.sh option get siteurl --skip-plugins --skip-themes 2>/dev/null)" = "$SITE_URL" ] && break
	sleep 2
done
./wp.sh transient delete --all

echo
echo "Xong: $(./wp.sh option get siteurl --skip-plugins --skip-themes)"
echo "Tài khoản quản trị:"
./wp.sh user list --role=administrator --fields=user_login,user_email
