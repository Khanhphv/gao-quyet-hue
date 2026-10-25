#!/bin/sh
# Chạy lệnh WP-CLI trong Docker. Ví dụ: ./wp.sh plugin list
cd "$(dirname "$0")" && exec docker compose run --rm -T --quiet-pull wpcli wp "$@"
