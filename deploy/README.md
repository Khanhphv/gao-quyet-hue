# Đưa website Quyết Huệ lên EC2 (Docker + nginx)

Mô hình: **nginx trên EC2** nhận HTTP/HTTPS (chứng chỉ do certbot cấp) → proxy sang **container WordPress** ở `127.0.0.1:8080` → **container MariaDB**. Cả hai container chỉ nghe trên máy, không mở ra internet.

Thư mục `deploy/` là thứ duy nhất cần chép lên EC2, cùng một file bản sao dữ liệu xuất từ máy local.

| File | Dùng để |
|---|---|
| `docker-compose.yml` | MariaDB + WordPress, WordPress mở cổng `127.0.0.1:WP_PORT` |
| `nginx-quyethue.conf` | Khối `server` cho nginx: proxy, giới hạn upload 64 MB, chặn `xmlrpc.php` |
| `.env.example` | Mẫu cho `.env`: tên miền, cổng, mật khẩu database |
| `php.ini` | Cho phép upload ảnh tới 64 MB |
| `xuat-du-lieu.sh` | Xuất database + `wp-content` thành 1 file `.tar` (dùng ở local và để sao lưu trên EC2) |
| `nhap-du-lieu.sh` | Nhập file `.tar` vào EC2 và đổi `http://localhost:8080` thành `https://tên-miền` |
| `wp.sh` | Chạy WP-CLI trên EC2 |

## 1. Chuẩn bị EC2 (một lần)

- **Instance**: tối thiểu `t3.small` (2 GB RAM). WordPress + WooCommerce + MariaDB chạy trên 1 GB RAM dễ bị thiếu bộ nhớ. Nên gắn **Elastic IP** để IP không đổi khi khởi động lại.
- **Security Group**: mở inbound 22 (chỉ IP của bạn), 80, 443. **Không** mở 8080.
- **Docker** (Ubuntu):
  ```bash
  curl -fsSL https://get.docker.com | sudo sh
  ```
  ```bash
  sudo usermod -aG docker ubuntu
  ```
  Đăng xuất SSH rồi vào lại để chạy `docker` không cần `sudo`. Trên Amazon Linux 2023 cài bằng `sudo dnf install docker`, rồi cài thêm plugin Docker Compose theo hướng dẫn của Docker.
- **DNS**: tạo bản ghi `A` cho `quyethue.vn` và `www.quyethue.vn` trỏ về Elastic IP.

## 2. Trên máy local: xuất dữ liệu

Site local phải đang chạy (`docker compose up -d`). Đứng ở thư mục dự án:

```bash
deploy/xuat-du-lieu.sh .
```

Kết quả: `ban-sao/quyethue-<ngày giờ>.tar`. Chép lên EC2:

```bash
scp -i khoa-ec2.pem -r deploy ubuntu@IP_EC2:~/quyethue
```

```bash
scp -i khoa-ec2.pem ban-sao/quyethue-20261025-101500.tar ubuntu@IP_EC2:~/quyethue/
```

## 3. Trên EC2: bật site và nhập dữ liệu

```bash
cd ~/quyethue
cp .env.example .env
nano .env        # điền DOMAIN, DB_PASSWORD, DB_ROOT_PASSWORD (tạo bằng: openssl rand -hex 24)
./nhap-du-lieu.sh quyethue-20261025-101500.tar
```

Script sẽ bật container, nhập database, nhập ảnh/plugin/theme, đổi đường dẫn sang `https://DOMAIN`, rồi in ra tài khoản quản trị. Kiểm tra container đã nhận request: `curl -I -H 'Host: quyethue.vn' http://127.0.0.1:8080` phải trả về `200` hoặc `301`.

## 4. Cấu hình nginx và HTTPS

```bash
sudo cp nginx-quyethue.conf /etc/nginx/sites-available/quyethue
sudo ln -s /etc/nginx/sites-available/quyethue /etc/nginx/sites-enabled/
sudo nano /etc/nginx/sites-available/quyethue    # sửa server_name nếu tên miền khác quyethue.vn
sudo nginx -t && sudo systemctl reload nginx
```

(Amazon Linux: chép vào `/etc/nginx/conf.d/quyethue.conf` thay cho 2 lệnh đầu.)

Cấp chứng chỉ HTTPS (cần DNS đã trỏ đúng):

```bash
sudo certbot --nginx -d quyethue.vn -d www.quyethue.vn
```

Chưa có certbot thì cài bằng `sudo apt install certbot python3-certbot-nginx`. Certbot tự thêm khối 443, chuyển http sang https và tự gia hạn.

> Site đã được đổi sang `https://`, nên trước khi chạy certbot, mở trang sẽ bị chuyển sang https và báo lỗi. Đó là bình thường, chạy certbot xong là hết.

## 5. Sau khi lên mạng

1. **Đổi mật khẩu quản trị** (đang giống bản local):
   `./wp.sh user update <user_login> --prompt=user_pass`
2. **Email form Liên hệ**: container không có máy chủ thư, nên phải cài plugin **WP Mail SMTP** (dùng Gmail, Amazon SES hoặc SMTP của nhà cung cấp tên miền), rồi gửi thử form. EC2 mặc định chặn gửi thư qua cổng 25, nên dùng cổng 587.
3. Mở `https://tên-miền`, bấm qua các trang, thử đăng nhập `/wp-admin`.

## 6. Sao lưu định kỳ

Thêm vào `crontab -e` trên EC2 (3 giờ sáng mỗi ngày, giữ 14 ngày):

```
0 3 * * * cd ~/quyethue && GIU_LAI_NGAY=14 ./xuat-du-lieu.sh >> ban-sao/log.txt 2>&1
```

Bản sao nằm cùng EC2 sẽ mất theo nếu instance hỏng, nên đẩy thêm lên S3 (ví dụ `aws s3 sync ban-sao s3://ten-bucket/quyethue/`) hoặc bật snapshot EBS định kỳ. File bản sao chứa toàn bộ dữ liệu (cả mật khẩu đã mã hoá), không để công khai.

Khôi phục từ một bản sao: `./nhap-du-lieu.sh ban-sao/quyethue-<ngày giờ>.tar`.

## 7. Vận hành hằng ngày

```bash
docker compose ps                 # xem trạng thái
docker compose logs -f wordpress  # xem log (wordpress, db)
docker compose pull && docker compose up -d   # cập nhật image MariaDB / WordPress
```

Container có `restart: unless-stopped` nên tự bật lại khi EC2 khởi động lại (Docker được bật cùng hệ thống). Cập nhật plugin và WordPress thì làm như bình thường trong trang quản trị.

**Sửa giao diện** (`setup/mu-plugins/quyethue.css` hoặc `quyethue.php`): chép file mới lên EC2 rồi chạy

```bash
docker compose cp quyethue.css wordpress:/var/www/html/wp-content/mu-plugins/quyethue.css
```

**Đưa bản local lên đè lại EC2**: làm lại bước 2 và 3. Chú ý mọi thay đổi trên EC2 (bài viết, sản phẩm…) sẽ bị ghi đè.
