<?php
/**
 * Bài viết mẫu cho trang Tin tức. build.php chỉ tạo bài CHƯA có (theo slug), không ghi đè bài đã sửa.
 * File trả về một hàm nhận $media (ảnh đã nhập) và trả về danh sách bài.
 */

$p = function ( $html ) {
	return "<!-- wp:paragraph -->\n<p>$html</p>\n<!-- /wp:paragraph -->";
};
$h = function ( $text ) {
	return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">$text</h2>\n<!-- /wp:heading -->";
};
$ul = function ( array $items ) {
	$li = array_map( function ( $x ) {
		return "<!-- wp:list-item -->\n<li>$x</li>\n<!-- /wp:list-item -->";
	}, $items );
	return "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . implode( "\n\n", $li ) . "</ul>\n<!-- /wp:list -->";
};

return function ( array $media ) use ( $p, $h, $ul ) {
	$img = function ( $key, $alt ) use ( $media ) {
		$id  = $media[ $key ]['id'];
		$url = $media[ $key ]['url'];
		return "<!-- wp:image {\"id\":$id,\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} -->\n"
			. "<figure class=\"wp-block-image size-full\"><img src=\"$url\" alt=\"$alt\" class=\"wp-image-$id\"/></figure>\n"
			. '<!-- /wp:image -->';
	};
	$blocks = function ( array $parts ) {
		return implode( "\n\n", $parts );
	};

	return array(
		array(
			'slug'     => 'quy-trinh-san-xuat-gao-nep-tai-xuong-quyet-hue',
			'title'    => 'Quy trình sản xuất gạo nếp tại xưởng Quyết Huệ',
			'category' => 'tin-doanh-nghiep',
			'image'    => 'nep-ra-may',
			'date'     => '2026-10-21 09:00:00',
			'excerpt'  => 'Từ khâu chọn nguyên liệu, làm sạch, xát đến đóng gói và bảo quản, mỗi công đoạn tại xưởng 1.000 m² đều được kiểm soát chặt chẽ.',
			'content'  => $blocks( array(
				$p( 'Tại Quyết Huệ, chất lượng hạt nếp được kiểm soát từ khâu lựa chọn nguồn nguyên liệu, thu mua, làm sạch, chế biến, đóng gói đến bảo quản sản phẩm. Dưới đây là các công đoạn chính tại xưởng sản xuất 1.000 m² của chúng tôi.' ),
				$h( '1. Chọn nguyên liệu' ),
				$p( 'Nguồn nếp được lựa chọn phù hợp với đặc tính của từng loại, sau đó thu mua và đưa vào hệ thống silo để bảo quản trước khi chế biến.' ),
				$img( 'silo', 'Hệ thống silo chứa nguyên liệu' ),
				$h( '2. Làm sạch và xát' ),
				$p( 'Dây chuyền làm sạch và xát giúp loại bỏ tạp chất, cho hạt nếp trắng, đều và sạch.' ),
				$img( 'day-chuyen-xat', 'Dây chuyền làm sạch và xát' ),
				$h( '3. Đóng gói' ),
				$p( 'Sản phẩm được cân và đóng bao theo quy cách, đảm bảo vệ sinh và an toàn thực phẩm.' ),
				$img( 'may-dong-bao', 'Khu vực đóng bao' ),
				$h( '4. Bảo quản và giao hàng' ),
				$p( 'Thành phẩm được lưu trong kho rộng, xuất hàng bằng băng tải, đáp ứng các đơn hàng số lượng lớn cho đại lý và doanh nghiệp.' ),
				$img( 'kho-bang-tai', 'Kho và băng tải xuất hàng' ),
				$p( 'Quyết Huệ không ngừng cải tiến quy trình sản xuất để mỗi sản phẩm gạo nếp khi đến tay khách hàng đều đáp ứng tiêu chuẩn về chất lượng, an toàn và sự tin cậy.' ),
			) ),
		),
		array(
			'slug'     => 'phan-biet-4-loai-gao-nep',
			'title'    => 'Phân biệt 4 loại gạo nếp: Nếp Đồi, Nếp Nhung, Nếp Cau, Nếp Hoa Vàng',
			'category' => 'kien-thuc-gao-nep',
			'image'    => 'nep-tren-tay',
			'date'     => '2026-10-22 09:00:00',
			'excerpt'  => 'Mỗi loại nếp có đặc điểm và công dụng riêng. Cùng tìm hiểu để chọn đúng loại nếp cho món xôi, bánh chưng hay nấu rượu.',
			'content'  => $blocks( array(
				$p( 'Mỗi loại gạo nếp có đặc điểm hạt, độ dẻo và hương thơm khác nhau, phù hợp với những món ăn khác nhau. Dưới đây là 4 dòng nếp Quyết Huệ đang cung cấp.' ),
				$h( 'Nếp Đồi' ),
				$p( 'Hạt nếp đều, trắng, dẻo và thơm khi nấu chín. Phù hợp đồ xôi, nấu cơm nếp và làm bánh truyền thống. <a href="/san-pham/nep-doi/">Xem sản phẩm Nếp Đồi</a>.' ),
				$h( 'Nếp Nhung' ),
				$p( 'Hạt dẻo mềm, thơm nhẹ, được ưa chuộng cho xôi và chè, cũng như nhiều món bánh truyền thống. <a href="/san-pham/nep-nhung/">Xem sản phẩm Nếp Nhung</a>.' ),
				$h( 'Nếp Cau' ),
				$p( 'Hạt to tròn, trắng đục, dẻo và giữ hạt khi đồ, là lựa chọn quen thuộc để gói bánh chưng, bánh tét và đồ xôi. <a href="/san-pham/nep-cau/">Xem sản phẩm Nếp Cau</a>.' ),
				$h( 'Nếp Hoa Vàng' ),
				$p( 'Dòng nếp truyền thống nổi tiếng của miền Bắc, hạt tròn, thơm đậm, độ dẻo cao. Thường dùng đồ xôi, gói bánh chưng, làm bánh giầy và nấu rượu nếp. <a href="/san-pham/nep-hoa-vang/">Xem sản phẩm Nếp Hoa Vàng</a>.' ),
				$h( 'Cần tư vấn chọn loại nếp?' ),
				$p( 'Liên hệ hotline <a href="tel:0969686369">0969 686 369</a> hoặc <a href="/lien-he/">gửi yêu cầu báo giá</a>, Quyết Huệ sẽ tư vấn loại nếp và quy cách phù hợp với nhu cầu của bạn.' ),
			) ),
		),
		array(
			'slug'     => 'quyet-hue-ra-mat-website',
			'title'    => 'Quyết Huệ ra mắt website chính thức',
			'category' => 'tin-doanh-nghiep',
			'image'    => 'kho-gao',
			'date'     => '2026-10-23 09:00:00',
			'excerpt'  => 'Website chính thức của Công ty TNHH Nông sản Quyết Huệ: nơi giới thiệu doanh nghiệp, sản phẩm gạo nếp và cập nhật tin tức đến khách hàng, đối tác.',
			'content'  => $blocks( array(
				$p( 'Công ty TNHH Nông sản Quyết Huệ chính thức ra mắt website, nơi giới thiệu thông tin doanh nghiệp, các dòng sản phẩm gạo nếp và cập nhật tin tức hoạt động đến khách hàng, đại lý và đối tác.' ),
				$h( 'Về Quyết Huệ' ),
				$p( 'Quyết Huệ là doanh nghiệp chuyên sản xuất và cung ứng gạo nếp, với hơn 10 năm kinh nghiệm và xưởng sản xuất 1.000 m². Chúng tôi chú trọng kiểm soát chất lượng từ khâu lựa chọn nguyên liệu đến đóng gói, bảo quản.' ),
				$img( 'day-chuyen-xat', 'Dây chuyền sản xuất tại xưởng Quyết Huệ' ),
				$h( 'Sản phẩm' ),
				$ul( array(
					'<a href="/san-pham/nep-doi/">Gạo Nếp Đồi</a>',
					'<a href="/san-pham/nep-nhung/">Gạo Nếp Nhung</a>',
					'<a href="/san-pham/nep-cau/">Gạo Nếp Cau</a>',
					'<a href="/san-pham/nep-hoa-vang/">Gạo Nếp Hoa Vàng</a>',
				) ),
				$h( 'Liên hệ hợp tác' ),
				$ul( array(
					'Hotline: <a href="tel:0969686369">0969 686 369</a> – <a href="tel:0975297900">0975 297 900</a>',
					'Email: <a href="mailto:nongsanquyethue7375@gmail.com">nongsanquyethue7375@gmail.com</a>',
					'Địa chỉ: Trai Trang, Yên Mỹ, Hưng Yên',
				) ),
			) ),
		),
	);
};
