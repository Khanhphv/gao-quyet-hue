<?php
/**
 * Sản phẩm ban đầu. build.php chỉ tạo sản phẩm CHƯA có (theo slug), không ghi đè sản phẩm đã sửa trong quản trị.
 */

$qh_ul = function ( array $items ) {
	return "<ul>\n<li>" . implode( "</li>\n<li>", $items ) . "</li>\n</ul>";
};

$qh_desc = function ( $intro, array $features, array $uses ) use ( $qh_ul ) {
	return "<p>$intro</p>\n\n<h3>Đặc điểm</h3>\n" . $qh_ul( $features )
		. "\n\n<h3>Phù hợp để</h3>\n" . $qh_ul( $uses )
		. "\n\n<h3>Quy cách và giá</h3>\n" . $qh_ul( array(
			'Quy cách đóng gói: bao 5 kg, 10 kg, 25 kg, 50 kg (cập nhật theo thực tế)',
			'Giá: liên hệ để nhận báo giá theo số lượng',
			'Nhận đơn sỉ cho đại lý, doanh nghiệp và đơn lẻ cho gia đình',
		) );
};

return array(
	array(
		'slug'  => 'nep-doi',
		'name'  => 'Gạo Nếp Đồi',
		'image' => 'sp-nep-doi',
		'short' => 'Hạt nếp dẻo, thơm, phù hợp đồ xôi và làm bánh truyền thống.',
		'desc'  => $qh_desc(
			'Gạo nếp Đồi được Quyết Huệ lựa chọn kỹ từ nguồn nguyên liệu phù hợp, làm sạch và chế biến trên dây chuyền khép kín, cho hạt nếp dẻo, thơm và chất lượng ổn định.',
			array( 'Hạt nếp đều, trắng, ít tạp chất', 'Dẻo, thơm khi nấu chín', 'Chất lượng ổn định giữa các lô hàng' ),
			array( 'Đồ xôi, nấu cơm nếp', 'Làm bánh truyền thống', 'Cung ứng cho cơ sở chế biến thực phẩm' )
		),
	),
	array(
		'slug'  => 'nep-nhung',
		'name'  => 'Gạo Nếp Nhung',
		'image' => 'sp-nep-nhung',
		'short' => 'Hạt dẻo mềm, thơm nhẹ, được ưa chuộng cho xôi và chè.',
		'desc'  => $qh_desc(
			'Gạo nếp Nhung có hạt dẻo mềm, hương thơm nhẹ, được nhiều gia đình và cơ sở làm bánh lựa chọn. Quyết Huệ kiểm soát chất lượng từ khâu thu mua đến đóng gói.',
			array( 'Hạt dẻo mềm, thơm nhẹ', 'Hạt trắng, đều, sạch', 'Chất lượng ổn định giữa các lô hàng' ),
			array( 'Đồ xôi, nấu chè', 'Làm bánh, món truyền thống', 'Cung ứng cho đại lý và cửa hàng lương thực' )
		),
	),
	array(
		'slug'  => 'nep-cau',
		'name'  => 'Gạo Nếp Cau',
		'image' => 'sp-nep-cau',
		'short' => 'Hạt to tròn, trắng đục, dẻo, thích hợp gói bánh chưng, đồ xôi.',
		'desc'  => $qh_desc(
			'Gạo nếp Cau có hạt to tròn, trắng đục, dẻo và thơm, là lựa chọn quen thuộc cho các món xôi và bánh chưng ngày Tết.',
			array( 'Hạt to tròn, trắng đục', 'Dẻo, thơm, giữ hạt khi đồ', 'Chất lượng ổn định giữa các lô hàng' ),
			array( 'Gói bánh chưng, bánh tét', 'Đồ xôi các loại', 'Cung ứng cho cơ sở làm bánh, nhà hàng' )
		),
	),
	array(
		'slug'  => 'nep-hoa-vang',
		'name'  => 'Gạo Nếp Hoa Vàng',
		'image' => 'sp-nep-hoa-vang',
		'short' => 'Dòng nếp truyền thống miền Bắc, thơm đậm, độ dẻo cao.',
		'desc'  => $qh_desc(
			'Nếp Hoa Vàng là dòng nếp truyền thống nổi tiếng của miền Bắc, hạt tròn, thơm đậm và có độ dẻo cao. Quyết Huệ chọn lọc, làm sạch và đóng gói để giữ trọn hương vị của hạt nếp.',
			array( 'Hạt tròn, mẩy, thơm đậm', 'Độ dẻo cao', 'Chất lượng ổn định giữa các lô hàng' ),
			array( 'Đồ xôi, gói bánh chưng', 'Làm bánh giầy, bánh dày', 'Nấu rượu nếp' )
		),
	),
);
