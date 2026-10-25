# Hướng dẫn dùng và sửa website Quyết Huệ

## 1. Bật / tắt website trên máy

Cần mở ứng dụng **Docker Desktop** trước, sau đó chạy trong thư mục này:

```bash
docker compose up -d      # bật
docker compose stop       # tắt (dữ liệu vẫn giữ)
```

- Xem web: http://localhost:8080
- Trang quản trị: http://localhost:8080/wp-admin (tài khoản ghi trong `TAI-KHOAN-LOCAL.txt`)

## 2. Sửa chữ và ảnh trên một trang

1. Vào **Trang** → bấm tên trang (Trang chủ, Giới thiệu, Sản phẩm, Tin tức, Liên hệ). Sản phẩm sửa ở mục 5, bài tin tức ở mục 6.
2. Bấm thẳng vào đoạn chữ để sửa. Bấm vào ảnh → **Thay thế** để đổi ảnh.
3. Bấm **Lưu** (góc trên bên phải).

Mẹo: bấm biểu tượng **≡ (Dạng xem danh sách)** ở góc trên bên trái để thấy cấu trúc các khối và chọn cho dễ.

## 3. Khối dùng chung (sửa 1 lần, mọi trang đổi theo)

Trong trang quản trị, bấm mục **Khối dùng chung** ở menu bên trái → bấm **Chỉnh sửa** khối cần sửa → **Lưu**:

| Khối | Xuất hiện ở |
|---|---|
| Danh sách sản phẩm (tự động) | Trang chủ, trang Sản phẩm. Tự lấy từ mục Sản phẩm, không cần sửa tay |
| Giá trị cốt lõi | Trang chủ, Giới thiệu |
| Dải kêu gọi liên hệ | Cuối Trang chủ, Giới thiệu, Sản phẩm, Tin tức và cuối mỗi bài viết |
| Chân trang | Cuối mọi trang (địa chỉ, hotline, email, mã QR Zalo) |
| Nút gọi và Zalo nổi | Góc phải màn hình mọi trang |

## 4. Đổi số điện thoại / Zalo

Số hotline đang nằm ở các chỗ sau, đổi lần lượt:

1. **Nút đỏ trên menu**: Giao diện → Sửa giao diện → mục **Đầu trang** → bấm vào nút "0969 686 369" trong khung xem trước → sửa chữ và đường link `tel:...` → **Lưu và công bố**.
2. **Khối dùng chung**: "Dải kêu gọi liên hệ", "Chân trang", "Nút gọi và Zalo nổi" (mục 3).
3. **Trang Liên hệ**.
4. **Nút "Gọi …" trên trang chi tiết sản phẩm**: nằm trong file `setup/mu-plugins/quyethue.php`, dòng `const QH_HOTLINE = '0969686369';` → sửa số rồi chạy `./cap-nhat-giao-dien.sh`.

Link Zalo có dạng `https://zalo.me/SỐ_ĐIỆN_THOẠI`. Hiện đang để số 0969686369, cần xác nhận đúng số có Zalo.

## 5. Quản lý sản phẩm

Sản phẩm quản lý ở mục **Sản phẩm** trong menu quản trị (WooCommerce, dạng trưng bày, chưa có giỏ hàng). Thêm, sửa, ẩn sản phẩm là Trang chủ và trang Sản phẩm **tự cập nhật**.

**Thêm sản phẩm mới**: Sản phẩm → **Thêm sản phẩm mới**, rồi điền:

| Ô | Điền gì |
|---|---|
| Tên sản phẩm | Ví dụ "Gạo Nếp Cái" |
| Ô soạn thảo lớn (mô tả) | Mô tả chi tiết: đặc điểm, phù hợp để, quy cách. Hiện ở tab "Mô tả" |
| Dữ liệu sản phẩm → Cài đặt chung → **Giá thông thường** | Để **trống** = hiện "Liên hệ báo giá". Nhập số (ví dụ 35000) = hiện "35.000 ₫" |
| Dữ liệu sản phẩm → **Thuộc tính** | Thêm "Quy cách" với các giá trị `5 kg \| 10 kg \| 25 kg`, tích "Hiển thị trên trang sản phẩm". Hiện ở tab "Thông tin bổ sung" |
| Dữ liệu sản phẩm → **Nâng cao** → "Menu đơn hàng" | Thứ tự hiển thị: số nhỏ đứng trước (4 loại hiện có là 1–4, sản phẩm mới đặt 5, 6…). Để 0 thì đứng đầu |
| Mô tả ngắn của sản phẩm (dưới cùng) | 1 câu ngắn, hiện trên thẻ sản phẩm và cạnh ảnh ở trang chi tiết |
| Ảnh sản phẩm (cột phải) | Ảnh chính, nên ảnh vuông hoặc ngang |
| Album hình ảnh sản phẩm (cột phải) | Thêm ảnh phụ (không bắt buộc) |
| Danh mục sản phẩm (cột phải) | Tích "Gạo nếp" (hoặc tạo danh mục mới, ví dụ "Gạo tẻ") |

Bấm **Xuất bản**. Xong: sản phẩm tự hiện ở Trang chủ, trang Sản phẩm và mục "Sản phẩm tương tự".

**Sửa / ẩn / xoá**: Sản phẩm → Tất cả sản phẩm → bấm tên để sửa. Muốn ẩn tạm: trong ô Xuất bản đổi Trạng thái thành "Bản nháp". Muốn xoá: "Xóa tạm".

**Menu xổ xuống**: menu "Sản phẩm" trên đầu trang có danh sách con các loại nếp, phần này **không** tự cập nhật. Thêm sản phẩm mới vào: Giao diện → Thiết lập Menu → cột trái mở mục "Sản phẩm" (không thấy thì bấm **Tùy chọn màn hình** ở góc trên, tích "Sản phẩm") → tích sản phẩm → Thêm vào menu → kéo thụt vào dưới mục "Sản phẩm" → Lưu menu.

**Muốn bán online sau này** (giỏ hàng, thanh toán): mở `setup/mu-plugins/quyethue.php`, xoá phần "WooCommerce dạng catalog", chạy `./cap-nhat-giao-dien.sh`, rồi cài đặt thanh toán và vận chuyển trong WooCommerce → Cài đặt.

## 6. Đăng tin tức

Trang **Tin tức** (http://localhost:8080/tin-tuc/) và khối "Tin tức mới" trên Trang chủ **tự lấy bài mới nhất**: đăng bài là tự hiện, không cần sửa trang.

**Đăng bài mới**: Bài viết → **Viết bài mới**:

1. Gõ **tiêu đề** và **nội dung** (bấm dấu **+** để chèn ảnh, tiêu đề phụ, danh sách…).
2. Cột phải, tab **Bài viết**:
   - **Ảnh đại diện**: ảnh hiện trên thẻ tin và đầu bài (nên dùng ảnh ngang).
   - **Danh mục**: "Tin doanh nghiệp" hoặc "Kiến thức gạo nếp" (không chọn thì tự vào "Tin doanh nghiệp").
   - **Mô tả ngắn** (không bắt buộc): 1–2 câu hiện trên thẻ tin. Để trống thì lấy đoạn đầu bài.
3. Bấm **Xuất bản**.

Bài mới nhất đứng đầu. Trang Tin tức hiện 9 bài mỗi trang (có nút chuyển trang), Trang chủ hiện 3 bài mới nhất. Đường dẫn bài có dạng `/tin-tuc/ten-bai/`.

**Sửa / xoá bài**: Bài viết → Tất cả bài viết. 3 bài đang có là **bài mẫu** viết từ thông tin trong phiếu yêu cầu, sửa hoặc xoá tuỳ ý.

**Thêm danh mục**: Bài viết → Danh mục.

## 7. Màu sắc và font

| Màu | Mã | Dùng cho |
|---|---|---|
| Xanh lá đậm | `#1B5E20` | Màu chính, tiêu đề, nút |
| Xanh lá lúa | `#6AA329` | Điểm nhấn phụ |
| Vàng lúa | `#C99A3E` | Nút chính, con số, đường kẻ |
| Đỏ | `#C62828` | Chỉ cho hotline |
| Kem | `#FBF8EF` | Nền |
| Chữ | `#1F2A1F` | Nội dung |

Font: **Playfair Display** (tiêu đề) và **Montserrat** (nội dung). Toàn bộ nằm trong `setup/mu-plugins/quyethue.css`. Sửa file đó xong chạy:

```bash
./cap-nhat-giao-dien.sh
```

## 8. Những chỗ đang để tạm, cần bổ sung

- [ ] Ảnh bao bì thật của 4 loại nếp (hiện là ảnh cận hạt nếp cắt từ ảnh xưởng): thay ở ô **Ảnh sản phẩm** của từng sản phẩm.
- [ ] Mô tả chi tiết từng loại nếp (hiện là mô tả chung, cần doanh nghiệp xác nhận đặc điểm từng loại).
- [ ] Quy cách đóng gói thật (hiện ghi "5 kg, 10 kg, 25 kg, 50 kg (cập nhật theo thực tế)").
- [ ] Số có Zalo, link fanpage, YouTube.
- [ ] File logo gốc nền trong suốt (hiện dùng ảnh JPG nền kem).
- [ ] Vị trí chính xác trên Google Maps (hiện tìm theo "Trai Trang, Yên Mỹ, Hưng Yên").
- [ ] Xem lại 3 bài tin mẫu, sửa hoặc thay bằng tin thật.

## 9. Đưa website lên mạng

**Cách 1: lên EC2 bằng Docker (build từ git, nhập dữ liệu lần đầu bằng tay)**: xem [deploy/README.md](deploy/README.md).

**Cách 2: lên hosting WordPress thông thường:**

1. Mua hosting có WordPress + tên miền (ví dụ `quyethue.vn` hoặc `nongsanquyethue.com`).
2. Trên máy local: cài plugin **All-in-One WP Migration** (miễn phí) → Xuất ra file.
3. Trên hosting: cài WordPress + plugin đó → Nhập file. Plugin tự đổi `localhost:8080` thành tên miền mới.
4. Kiểm tra form Liên hệ gửi được email (trên máy local form **không** gửi được email vì chưa có máy chủ thư, điều này là bình thường). Nếu hosting không gửi được, cài thêm plugin **WP Mail SMTP** dùng Gmail.
5. Đổi mật khẩu quản trị thành mật khẩu mạnh, mới.

## 10. Dựng lại từ đầu (dành cho kỹ thuật)

```bash
./wp.sh plugin install contact-form-7 woocommerce --activate
./wp.sh eval-file /setup/build.php
```

Lệnh build **ghi đè** nội dung 5 trang (Trang chủ, Giới thiệu, Sản phẩm, Tin tức, Liên hệ), 5 khối dùng chung và menu bằng bản trong thư mục `setup/`. Chỉ chạy khi muốn quay về bản gốc; chỉnh sửa trang trong quản trị sẽ bị mất. **Sản phẩm và bài viết thì không bị ghi đè**: build chỉ tạo 4 loại nếp và 3 bài mẫu nếu chúng chưa có.
