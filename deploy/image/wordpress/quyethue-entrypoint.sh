#!/usr/bin/env bash
# Khởi động image quyethue-wordpress:
#   1. Phần chuẩn bị của image gốc: lần đầu chép WordPress vào volume, tạo wp-config.php.
#   2. Chép đè mu-plugin giao diện từ image.
#   3. Đợi database. Nếu đã có dữ liệu thì đổi đường dẫn site sang SITE_URL khi đang khác
#      (vd http://localhost:8080 -> https://quyethue.vn). Database còn trống thì bỏ qua, chờ nhập bằng nhap-du-lieu.sh.
#   4. Chạy Apache.
set -euo pipefail
cd /var/www/html

docker-ensure-installed.sh

mkdir -p wp-content/mu-plugins
cp /usr/src/quyethue/mu-plugins/* wp-content/mu-plugins/
chown -R www-data:www-data wp-content/mu-plugins

wp() { command wp --allow-root "$@"; }

db_san_sang() {
	php -r 'mysqli_report(MYSQLI_REPORT_OFF);
		$m = @new mysqli(getenv("WORDPRESS_DB_HOST"), getenv("WORDPRESS_DB_USER"), getenv("WORDPRESS_DB_PASSWORD"), getenv("WORDPRESS_DB_NAME"));
		exit($m->connect_errno ? 1 : 0);'
}

for _ in $(seq 90); do
	db_san_sang && break
	sleep 2
done
db_san_sang || { echo "quyethue: không kết nối được database" >&2; exit 1; }

if ! wp core is-installed --skip-plugins --skip-themes 2>/dev/null; then
	echo "quyethue: database còn trống, nhập dữ liệu bằng: ./nhap-du-lieu.sh <file bản sao .tar>"
elif [ -n "${SITE_URL:-}" ]; then
	moi=${SITE_URL%/}
	cu=$(wp option get siteurl --skip-plugins --skip-themes)
	if [ "$cu" != "$moi" ]; then
		echo "quyethue: đổi đường dẫn $cu -> $moi"
		wp search-replace "$cu" "$moi" --all-tables-with-prefix --skip-columns=guid --report-changed-only
		# Dạng có dấu \/ (trong dữ liệu JSON)
		wp search-replace "${cu//\//\\/}" "${moi//\//\\/}" --all-tables-with-prefix --skip-columns=guid --report-changed-only
		wp cache flush
	fi
fi

exec docker-entrypoint.sh "$@"
