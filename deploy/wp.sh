#!/bin/sh
# Chạy lệnh WP-CLI trên EC2. Ví dụ: ./wp.sh plugin list
cd "$(dirname "$0")" && exec docker compose exec -T -u www-data wordpress wp "$@"
