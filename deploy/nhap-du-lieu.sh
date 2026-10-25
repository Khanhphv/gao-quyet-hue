#!/usr/bin/env bash
# Nhập bản sao (tạo bởi xuat-du-lieu.sh) vào site trên VPS, rồi đổi đường dẫn sang https://<DOMAIN trong .env>.
#   ./nhap-du-lieu.sh quyethue-20261025-101500.tar      (thêm -y để không hỏi xác nhận)
# CẢNH BÁO: ghi đè toàn bộ database và wp-content đang có trên VPS.
set -euo pipefail

cd "$(dirname "$0")"
FILE=${1:?Cách dùng: ./nhap-du-lieu.sh <file .tar> [-y]}
[ -f "$FILE" ] || { echo "Không thấy file $FILE" >&2; exit 1; }
[ -f .env ] || { echo "Chưa có .env. Chạy: cp .env.example .env rồi điền." >&2; exit 1; }
DOMAIN=$(grep -E '^DOMAIN=' .env | cut -d= -f2- | tr -d '"'"'"' \r')
[ -n "$DOMAIN" ] || { echo "Chưa điền DOMAIN trong .env" >&2; exit 1; }
MOI="https://$DOMAIN"

if [ "${2:-}" != "-y" ]; then
	read -r -p "Ghi đè toàn bộ dữ liệu site $MOI bằng $FILE? Gõ 'co' để tiếp tục: " ok
	[ "$ok" = "co" ] || { echo "Đã huỷ."; exit 1; }
fi

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
tar -C "$TMP" -xf "$FILE"
CU=$(cat "$TMP/siteurl.txt")

docker compose up -d
# Lần chạy đầu, WordPress cần vài giây để chép mã nguồn vào volume và tạo wp-config.php.
for _ in $(seq 60); do
	docker compose exec -T wordpress test -f /var/www/html/wp-config.php && break
	sleep 2
done
docker compose exec -T wordpress test -f /var/www/html/wp-config.php \
	|| { echo "WordPress chưa khởi động xong. Xem: docker compose logs wordpress" >&2; exit 1; }

echo "Nhập database..."
gunzip -c "$TMP/db.sql.gz" \
	| docker compose exec -T db sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -uroot "$MARIADB_DATABASE"'

echo "Nhập wp-content..."
docker compose exec -T wordpress sh -c \
	'rm -rf /var/www/html/wp-content && tar -C /var/www/html -xzf - && chown -R www-data:www-data /var/www/html/wp-content' \
	< "$TMP/wp-content.tar.gz"

if [ "$CU" != "$MOI" ]; then
	echo "Đổi đường dẫn $CU -> $MOI..."
	./wp.sh search-replace "$CU" "$MOI" --all-tables-with-prefix --skip-columns=guid --report-changed-only
	# Dạng có dấu \/ (trong dữ liệu JSON)
	./wp.sh search-replace "${CU//\//\\/}" "${MOI//\//\\/}" --all-tables-with-prefix --skip-columns=guid --report-changed-only
fi
./wp.sh rewrite flush
./wp.sh transient delete --all
./wp.sh cache flush

echo
echo "Xong: $MOI"
echo "Tài khoản quản trị (mật khẩu vẫn giống bản local, nên đổi ngay):"
./wp.sh user list --role=administrator --fields=user_login,user_email
echo "Đổi mật khẩu:  ./wp.sh user update <user_login> --prompt=user_pass"
