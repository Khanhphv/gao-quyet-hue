# Đưa website Quyết Huệ lên EC2

Source quản lý bằng git. Trên EC2: clone repo, build image tại chỗ, nhập dữ liệu lần đầu bằng tay, rồi trỏ proxy vào cổng của container.

- **Image `quyethue-wordpress`** (build từ repo) chỉ chứa code: WordPress (PHP 8.3, Apache), WP-CLI, `php.ini` và mu-plugin giao diện trong `setup/mu-plugins/`.
- **Database** dùng image `mariadb:11.4`.
- **Dữ liệu** (database, plugin, theme Astra, ảnh) không nằm trong git hay trong image, mà nằm trong 2 volume `db_data` và `wp_data` trên EC2. Lần đầu nhập từ một bản sao xuất ở máy local.

Mỗi lần container WordPress khởi động, nó tự làm 2 việc:
- chép đè mu-plugin từ image;
- nếu đã có dữ liệu và đường dẫn trong database khác `SITE_URL` thì đổi đường dẫn (ví dụ `http://localhost:8080` thành `https://quyethue.vn`).

## 1. Cài đặt lần đầu trên EC2

```bash
git clone https://github.com/Khanhphv/gao-quyet-hue.git ~/gao-quyet-hue
cd ~/gao-quyet-hue/deploy
cp .env.example .env
nano .env        # điền SITE_URL, WP_PORT (cổng chưa bị dùng), DB_PASSWORD, DB_ROOT_PASSWORD (tạo bằng: openssl rand -hex 24)
docker compose up -d --build
```

Lúc này database còn trống, log WordPress (`docker compose logs wordpress`) sẽ báo `database còn trống`. **Chưa trỏ proxy vào**, vì trang cài đặt WordPress đang mở và ai vào trước cũng có thể tạo tài khoản admin.

## 2. Nhập dữ liệu lần đầu (chạy tay)

Ở máy local (site local đang chạy), đứng ở thư mục repo:

```bash
deploy/xuat-du-lieu.sh .
```

```bash
scp -i khoa-ec2.pem ban-sao/quyethue-<ngày giờ>.tar ec2-user@IP_EC2:~/
```

Trên EC2:

```bash
cd ~/gao-quyet-hue/deploy
./nhap-du-lieu.sh ~/quyethue-<ngày giờ>.tar
rm ~/quyethue-<ngày giờ>.tar
```

Script nhập database và `wp-content`, khởi động lại WordPress (tự đổi đường dẫn sang `SITE_URL`), rồi in ra tài khoản quản trị. Kiểm tra (thay 8081 bằng `WP_PORT`):

```bash
curl -I -H 'Host: quyethue.vn' -H 'X-Forwarded-Proto: https' http://127.0.0.1:8081
```

Kết quả phải là `200`.

## 3. Proxy (bạn tự cấu hình)

Proxy trỏ vào `http://127.0.0.1:WP_PORT` và cần:

- Giữ nguyên header `Host`.
- Gửi `X-Forwarded-Proto: https` khi người xem vào bằng HTTPS. WordPress dựa vào header này để biết đang chạy HTTPS; thiếu nó, trang sẽ chuyển hướng lặp vô hạn.
- Cho phép body tới 64 MB để upload ảnh (nginx: `client_max_body_size 64M;`).

Ví dụ nginx:

```nginx
location / {
    proxy_pass http://127.0.0.1:8081;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

## 4. Sau khi lên mạng

1. **Đổi mật khẩu quản trị** (đang giống bản local):
   `./wp.sh user update admin --prompt=user_pass`
2. **Email form Liên hệ**: container không có máy chủ thư, nên phải cài plugin **WP Mail SMTP** (Gmail, Amazon SES...) và dùng cổng 587 (EC2 chặn cổng 25), rồi gửi thử form.

## 5. Cập nhật về sau

| Muốn | Làm trên EC2 (ở `~/gao-quyet-hue/deploy`) |
|---|---|
| Cập nhật code (giao diện trong `setup/mu-plugins/`, Dockerfile...) | `git pull && docker compose up -d --build`. Nội dung site không bị ảnh hưởng |
| Cập nhật bản WordPress gốc / MariaDB | `docker compose build --pull && docker compose pull db && docker compose up -d` |
| Cập nhật plugin, theme | Làm như bình thường trong trang quản trị |
| Đưa nội dung local lên đè EC2 | Làm lại mục 2. Mọi thay đổi trên EC2 (bài viết, sản phẩm...) sẽ mất |

## 6. Sao lưu

Thêm vào `crontab -e` trên EC2 (3 giờ sáng mỗi ngày, giữ 14 ngày):

```
0 3 * * * cd ~/gao-quyet-hue/deploy && GIU_LAI_NGAY=14 ./xuat-du-lieu.sh >> ban-sao/log.txt 2>&1
```

Bản sao nằm ở `deploy/ban-sao/` (git đã bỏ qua). Nên đẩy thêm lên S3 (`aws s3 sync ban-sao s3://ten-bucket/quyethue/`) hoặc bật snapshot EBS, vì bản sao nằm cùng EC2 sẽ mất theo nếu instance hỏng. File bản sao chứa toàn bộ dữ liệu, không commit và không để công khai.

Khôi phục: `./nhap-du-lieu.sh ban-sao/quyethue-<ngày giờ>.tar`.

## Các file

| File | Dùng để |
|---|---|
| `docker-compose.yml`, `.env.example` | Chạy MariaDB + WordPress trên EC2 |
| `image/wordpress/` | Dockerfile và script khởi động của image WordPress |
| `xuat-du-lieu.sh` | Xuất database + `wp-content` thành 1 file `.tar` (ở local: `deploy/xuat-du-lieu.sh .`; ở EC2: `./xuat-du-lieu.sh`) |
| `nhap-du-lieu.sh` | Nhập dữ liệu lần đầu hoặc khôi phục từ file `.tar` |
| `wp.sh` | Chạy WP-CLI trên EC2, ví dụ `./wp.sh plugin list` |
