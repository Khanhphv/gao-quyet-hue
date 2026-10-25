#!/usr/bin/env bash
# Khởi động image quyethue-wordpress:
#   1. Phần chuẩn bị của image gốc: lần đầu chép WordPress + wp-content vào volume, tạo wp-config.php.
#   2. Chép đè mu-plugin giao diện từ image.
#   3. Đợi database, đổi đường dẫn site sang SITE_URL nếu đang khác (vd http://localhost:8080 -> https://quyethue.vn).
#   4. Chạy Apache.
set -euo pipefail
cd /var/www/html

docker-ensure-installed.sh

mkdir -p wp-content/mu-plugins
cp /usr/src/quyethue/mu-plugins/* wp-content/mu-plugins/
chown -R www-data:www-data wp-content/mu-plugins

wp() { command wp --allow-root "$@"; }

if [ -n "${SITE_URL:-}" ]; then
	moi=${SITE_URL%/}
	cu=
	for _ in $(seq 90); do
		cu=$(wp option get siteurl --skip-plugins --skip-themes 2>/dev/null) && break
		sleep 2
	done
	[ -n "$cu" ] || { echo "quyethue: không kết nối được database" >&2; exit 1; }

	if [ "$cu" != "$moi" ]; then
		echo "quyethue: đổi đường dẫn $cu -> $moi"
		wp search-replace "$cu" "$moi" --all-tables-with-prefix --skip-columns=guid --report-changed-only
		# Dạng có dấu \/ (trong dữ liệu JSON)
		wp search-replace "${cu//\//\\/}" "${moi//\//\\/}" --all-tables-with-prefix --skip-columns=guid --report-changed-only
		wp cache flush
	fi
fi

exec docker-entrypoint.sh "$@"
