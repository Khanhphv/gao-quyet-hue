# Đưa website Quyết Huệ lên EC2 bằng Docker image

Build ở máy local ra **2 image có sẵn dữ liệu**, rồi chạy trên EC2 bằng `docker compose`:

| Image | Chứa |
|---|---|
| `quyethue-db` | MariaDB 11.4 + database của site (trang, bài viết, sản phẩm, menu, tài khoản) |
| `quyethue-wordpress` | WordPress (PHP 8.3, Apache) + `wp-content` (plugin, theme, ảnh) + mu-plugin giao diện trong `setup/mu-plugins/` |

Container WordPress mở cổng `127.0.0.1:8080` (đổi trong `.env`). Bạn tự cấu hình proxy (nginx...) trỏ vào cổng này.

## Cách hoạt động

- **Lần chạy đầu** (volume còn trống): database được nhập từ image `quyethue-db`, còn WordPress và `wp-content` được chép từ image `quyethue-wordpress` vào volume.
- **Mỗi lần container WordPress khởi động**:
  - chép đè mu-plugin giao diện từ image;
  - nếu đường dẫn trong database khác `SITE_URL` thì tự đổi (ví dụ `http://localhost:8080` thành `https://quyethue.vn`).
- **Sau lần đầu**, dữ liệu nằm trong volume `db_data` và `wp_data` trên EC2. Bài viết, sản phẩm, ảnh thêm trong trang quản trị không mất khi khởi động lại hay cập nhật image.
- Hệ quả: **build image mới chỉ cập nhật giao diện (mu-plugin)**, không đè nội dung đang có trên EC2. Muốn đưa nội dung local lên đè, xem mục 6.

## 1. Build image (máy local)

Site local phải đang chạy (`docker compose up -d`). Đứng ở thư mục dự án:

```bash
deploy/build-image.sh
```

Script xuất dữ liệu mới nhất từ site local, rồi build `quyethue-db` và `quyethue-wordpress`, gắn tag ngày giờ và `latest`.

- Mặc định build cho `linux/amd64` (EC2 t3, t2, m5...). Nếu EC2 là Graviton (t4g, m7g...): `PLATFORM=linux/arm64 deploy/build-image.sh`.
- Máy Mac M1/M2/M3 không chạy thử được bản amd64 của MariaDB (MariaDB lỗi khi chạy giả lập), nhưng trên EC2 x86 thì chạy bình thường.
- Image chứa toàn bộ dữ liệu site (cả mật khẩu đã mã hoá), **chỉ đẩy lên registry riêng tư** (ECR private), không đẩy lên Docker Hub công khai.

## 2. Đưa image lên EC2

**Cách A: chép file** (không cần registry)

```bash
deploy/build-image.sh --save
```

```bash
scp -i khoa-ec2.pem ban-sao/quyethue-images-<TAG>.tar.gz ubuntu@IP_EC2:~/
```

Trên EC2:

```bash
gunzip -c ~/quyethue-images-<TAG>.tar.gz | docker load
```

**Cách B: ECR** (tiện khi cập nhật nhiều lần)

```bash
REGISTRY=123456789012.dkr.ecr.ap-southeast-1.amazonaws.com/ deploy/build-image.sh
aws ecr get-login-password | docker login --username AWS --password-stdin 123456789012.dkr.ecr.ap-southeast-1.amazonaws.com
docker push --all-tags 123456789012.dkr.ecr.ap-southeast-1.amazonaws.com/quyethue-db
docker push --all-tags 123456789012.dkr.ecr.ap-southeast-1.amazonaws.com/quyethue-wordpress
```

Tạo trước 2 repository `quyethue-db` và `quyethue-wordpress` trong ECR. Trên EC2, đăng nhập ECR (gắn IAM role có quyền đọc ECR cho instance), rồi điền `REGISTRY` vào `.env`.

## 3. Chạy trên EC2

Chép các file chạy lên EC2 (không cần thư mục `image/` và `build-image.sh`):

```bash
ssh -i khoa-ec2.pem ubuntu@IP_EC2 mkdir -p quyethue
scp -i khoa-ec2.pem deploy/docker-compose.yml deploy/.env.example deploy/wp.sh deploy/xuat-du-lieu.sh deploy/nhap-du-lieu.sh ubuntu@IP_EC2:~/quyethue/
```

Trên EC2:

```bash
cd ~/quyethue
cp .env.example .env
nano .env        # điền SITE_URL, DB_PASSWORD, DB_ROOT_PASSWORD (tạo bằng: openssl rand -hex 24)
docker compose up -d
docker compose logs -f wordpress   # chờ dòng "resuming normal operations"
```

Lần đầu mất khoảng 30 giây để nhập database. Kiểm tra: `curl -I -H 'Host: quyethue.vn' -H 'X-Forwarded-Proto: https' http://127.0.0.1:8080` trả về `200`.

## 4. Proxy (bạn tự cấu hình)

Proxy cần:

- Chuyển tiếp header `Host` gốc.
- Gửi `X-Forwarded-Proto: https` khi người xem vào bằng HTTPS. WordPress dựa vào header này để biết đang chạy HTTPS; thiếu nó, trang sẽ chuyển hướng lặp vô hạn.
- Cho phép body tới 64 MB để upload ảnh (nginx: `client_max_body_size 64M;`).

Ví dụ nginx:

```nginx
location / {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

Nếu proxy ở máy khác (ví dụ Load Balancer), đặt `WP_BIND=0.0.0.0` trong `.env` và chỉ mở cổng đó cho proxy qua Security Group.

## 5. Sau khi lên mạng

1. **Đổi mật khẩu quản trị** (đang giống bản local):
   `./wp.sh user update admin --prompt=user_pass`
2. **Email form Liên hệ**: container không có máy chủ thư, nên phải cài plugin **WP Mail SMTP** (Gmail, Amazon SES...) và dùng cổng 587 (EC2 chặn cổng 25), rồi gửi thử form.

## 6. Cập nhật về sau

| Muốn | Làm |
|---|---|
| Cập nhật giao diện (`setup/mu-plugins/`) | Build image mới (mục 1, 2), rồi trên EC2: `docker compose up -d` (đổi `TAG` trong `.env` nếu dùng tag cụ thể). Nội dung không bị ảnh hưởng |
| Đưa nội dung local lên đè EC2 | Ở local: `deploy/xuat-du-lieu.sh .`, chép file `ban-sao/quyethue-*.tar` lên EC2, rồi `./nhap-du-lieu.sh quyethue-<ngày giờ>.tar`. Mọi thay đổi trên EC2 sẽ mất |
| Cài lại từ đầu bằng dữ liệu trong image | `docker compose down -v && docker compose up -d`. **Xoá hẳn** dữ liệu đang có trên EC2 |
| Cập nhật plugin, WordPress | Làm như bình thường trong trang quản trị |

## 7. Sao lưu

Thêm vào `crontab -e` trên EC2 (3 giờ sáng mỗi ngày, giữ 14 ngày):

```
0 3 * * * cd ~/quyethue && GIU_LAI_NGAY=14 ./xuat-du-lieu.sh >> ban-sao/log.txt 2>&1
```

Nên đẩy thêm lên S3 (`aws s3 sync ban-sao s3://ten-bucket/quyethue/`) hoặc bật snapshot EBS, vì bản sao nằm cùng EC2 sẽ mất theo nếu instance hỏng.

Khôi phục: `./nhap-du-lieu.sh ban-sao/quyethue-<ngày giờ>.tar`.

## Các file

| File | Dùng ở | Dùng để |
|---|---|---|
| `build-image.sh` | local | Xuất dữ liệu và build 2 image |
| `image/db/`, `image/wordpress/` | local | Dockerfile và script khởi động của 2 image |
| `docker-compose.yml`, `.env.example` | EC2 | Chạy 2 image |
| `wp.sh` | EC2 | Chạy WP-CLI, ví dụ `./wp.sh plugin list` |
| `xuat-du-lieu.sh` | cả hai | Xuất database + `wp-content` thành 1 file `.tar` |
| `nhap-du-lieu.sh` | EC2 | Khôi phục từ file `.tar` |
