#!/usr/bin/env bash
# Xuất database + wp-content của site đang chạy thành 1 file ban-sao/quyethue-<ngày giờ>.tar.
#   Trên máy local (đứng ở thư mục dự án):  deploy/xuat-du-lieu.sh .
#   Trên VPS (sao lưu):                      ./xuat-du-lieu.sh
# Đặt GIU_LAI_NGAY=14 để tự xoá các bản sao cũ hơn 14 ngày trong ban-sao/.
set -euo pipefail

cd "${1:-$(dirname "$0")}"
for s in db wordpress; do
	docker compose ps --status running --services | grep -qx "$s" \
		|| { echo "Site chưa chạy (thiếu $s). Bật bằng: docker compose up -d" >&2; exit 1; }
done

sql() { docker compose exec -T db sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -N -uroot "$MARIADB_DATABASE"'; }

TEN="quyethue-$(date +%Y%m%d-%H%M%S)"
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

echo "SELECT option_value FROM wp_options WHERE option_name = 'siteurl';" | sql > "$TMP/siteurl.txt"
[ -s "$TMP/siteurl.txt" ] || { echo "Không đọc được siteurl từ database." >&2; exit 1; }

docker compose exec -T db sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb-dump -uroot --single-transaction --default-character-set=utf8mb4 "$MARIADB_DATABASE"' \
	| gzip > "$TMP/db.sql.gz"

docker compose exec -T wordpress tar -C /var/www/html \
	--exclude=wp-content/cache --exclude=wp-content/upgrade --exclude=wp-content/ai1wm-backups \
	-czf - wp-content > "$TMP/wp-content.tar.gz"

mkdir -p ban-sao
umask 077
tar -C "$TMP" -cf "ban-sao/$TEN.tar" siteurl.txt db.sql.gz wp-content.tar.gz

if [ -n "${GIU_LAI_NGAY:-}" ]; then
	find ban-sao -name 'quyethue-*.tar' -mtime "+$GIU_LAI_NGAY" -delete
fi

echo "Đã xuất: $(pwd)/ban-sao/$TEN.tar ($(du -h "ban-sao/$TEN.tar" | awk '{print $1}'), từ $(cat "$TMP/siteurl.txt"))"
