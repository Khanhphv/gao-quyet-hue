#!/usr/bin/env bash
# Build 2 image chứa sẵn dữ liệu site:
#   quyethue-db          MariaDB + database (tự nhập ở lần chạy đầu)
#   quyethue-wordpress   WordPress + wp-content (plugin, theme, ảnh) + mu-plugin giao diện trong setup/
# Cách dùng (site local đang chạy):
#   deploy/build-image.sh                     xuất dữ liệu mới nhất từ site local rồi build
#   deploy/build-image.sh ban-sao/<file>.tar  build từ một bản sao có sẵn
#   thêm --save                               ghi 2 image ra ban-sao/quyethue-images-<TAG>.tar.gz để chép lên EC2
# Biến môi trường:
#   TAG       mặc định ngày giờ (luôn gắn thêm tag latest)
#   PLATFORM  mặc định linux/amd64 (EC2 t3, t2, m5...). EC2 Graviton (t4g, m7g...) thì đặt linux/arm64
#   REGISTRY  tiền tố khi push lên ECR, vd 123456789012.dkr.ecr.ap-southeast-1.amazonaws.com/
set -euo pipefail

DEPLOY=$(cd "$(dirname "$0")" && pwd)
ROOT=$(dirname "$DEPLOY")
cd "$ROOT"

BUNDLE=
SAVE=
for a in "$@"; do
	case "$a" in
		--save) SAVE=1 ;;
		*) BUNDLE=$a ;;
	esac
done
TAG=${TAG:-$(date +%Y%m%d-%H%M)}
PLATFORM=${PLATFORM:-linux/amd64}
REGISTRY=${REGISTRY:-}

if [ -z "$BUNDLE" ]; then
	"$DEPLOY/xuat-du-lieu.sh" "$ROOT"
	BUNDLE=$(ls -t ban-sao/quyethue-*.tar | head -1)
fi
[ -f "$BUNDLE" ] || { echo "Không thấy file $BUNDLE" >&2; exit 1; }

CTX=$(mktemp -d)
trap 'rm -rf "$CTX"' EXIT
tar -C "$CTX" -xf "$BUNDLE"
cp -R "$DEPLOY/image/db" "$DEPLOY/image/wordpress" "$CTX/"
mv "$CTX/db.sql.gz" "$CTX/db/"
tar -C "$CTX/wordpress" -xzf "$CTX/wp-content.tar.gz"
cp -R setup/mu-plugins "$CTX/wordpress/mu-plugins"

for s in db wordpress; do
	echo "Build ${REGISTRY}quyethue-$s:$TAG ($PLATFORM)..."
	docker buildx build --builder default --platform "$PLATFORM" --load \
		-t "${REGISTRY}quyethue-$s:$TAG" -t "${REGISTRY}quyethue-$s:latest" "$CTX/$s"
done

if [ -n "$SAVE" ]; then
	OUT="ban-sao/quyethue-images-$TAG.tar.gz"
	echo "Ghi image ra $OUT..."
	umask 077
	docker save "${REGISTRY}quyethue-db:$TAG" "${REGISTRY}quyethue-db:latest" \
		"${REGISTRY}quyethue-wordpress:$TAG" "${REGISTRY}quyethue-wordpress:latest" | gzip > "$OUT"
	echo "Đã ghi: $ROOT/$OUT ($(du -h "$OUT" | awk '{print $1}'))"
fi

echo "Xong: ${REGISTRY}quyethue-db:$TAG, ${REGISTRY}quyethue-wordpress:$TAG (dữ liệu từ $BUNDLE)"
