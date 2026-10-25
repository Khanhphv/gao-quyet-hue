<?php
/**
 * Plugin Name: Quyết Huệ – giao diện
 * Description: Màu sắc, font chữ, chân trang và nút gọi/Zalo nổi cho website Nông sản Quyết Huệ. Nội dung chân trang và nút nổi sửa trong Giao diện → Mẫu (Patterns).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Cho xem web qua ngrok (hoặc proxy khác) mà ảnh/CSS/JS vẫn load được.
// WordPress lưu cố định địa chỉ site là "http://localhost:8080" trong CSDL; khi truy cập qua
// một tên miền khác (ví dụ domain ngrok), đoạn này tạm đổi địa chỉ site sang đúng tên miền + https
// đang dùng, để các đường dẫn ảnh/CSS/JS không còn trỏ về "localhost" (máy ngoài không vào được).
// Mở bằng http://localhost:8080 như cũ thì không bị ảnh hưởng.
if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}
if ( ! empty( $_SERVER['HTTP_HOST'] ) && ! in_array( strtok( $_SERVER['HTTP_HOST'], ':' ), array( 'localhost', '127.0.0.1' ), true ) ) {
	add_filter( 'option_home', 'qh_external_site_url' );
	add_filter( 'option_siteurl', 'qh_external_site_url' );

	// Ảnh chèn trong nội dung trang/bài và vài file JS (WooCommerce) vẫn bị ghi cố định
	// "localhost:8080" (WordPress lưu thẳng vào nội dung hoặc tính trước khi mu-plugin này chạy),
	// 2 filter trên không sửa được -> thay trực tiếp trong HTML trả về trước khi gửi ra trình duyệt.
	add_action( 'init', function () {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if ( is_admin() || wp_doing_ajax() || 0 === strpos( $uri, '/wp-json/' ) ) {
			return;
		}
		ob_start( function ( $html ) {
			$new = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'];
			return str_ireplace( array( 'http://localhost:8080', 'https://localhost:8080' ), $new, $html );
		} );
	}, 0 );
}
function qh_external_site_url() {
	$scheme = is_ssl() ? 'https' : 'http';
	return $scheme . '://' . $_SERVER['HTTP_HOST'];
}

const QH_FONTS_URL = 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap';

// Font + CSS dùng chung cho trang web và trình soạn thảo (sửa trang thấy đúng như ngoài web).
add_action( 'enqueue_block_assets', function () {
	wp_enqueue_style( 'qh-fonts', QH_FONTS_URL, array(), null );
	$css = __DIR__ . '/quyethue.css';
	wp_enqueue_style( 'qh-style', plugins_url( 'quyethue.css', __FILE__ ), array(), filemtime( $css ) );
}, 20 );

// Hiển thị một mẫu đồng bộ (synced pattern) theo ID lưu trong option.
function qh_render_synced_pattern( $option ) {
	$id = (int) get_option( $option );
	if ( $id && get_post_status( $id ) === 'publish' ) {
		echo do_blocks( '<!-- wp:block {"ref":' . $id . '} /-->' );
	}
}

add_action( 'astra_footer_before', function () {
	// Cuối mỗi bài tin tức: dải "Trở thành đối tác" (khối dùng chung).
	if ( is_singular( 'post' ) ) {
		qh_render_synced_pattern( 'qh_cta_block' );
	}
	echo '<div class="qh-site-footer">';
	qh_render_synced_pattern( 'qh_footer_block' );
	echo '</div>';
} );

add_action( 'wp_footer', function () {
	qh_render_synced_pattern( 'qh_float_block' );
} );

// Lối tắt trong trang quản trị tới danh sách khối dùng chung (chân trang, nút nổi, thẻ sản phẩm...).
add_action( 'admin_menu', function () {
	add_menu_page( 'Khối dùng chung', 'Khối dùng chung', 'edit_posts', 'edit.php?post_type=wp_block', '', 'dashicons-screenoptions', 21 );
} );

/*
 * ---------- WooCommerce dạng catalog (chỉ trưng bày, chưa bán online) ----------
 * Muốn bật giỏ hàng / thanh toán sau này: xoá toàn bộ phần dưới đây.
 */
const QH_HOTLINE = '0969686369';

add_action( 'init', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	add_filter( 'woocommerce_is_purchasable', '__return_false' );

	// Danh sách sản phẩm: sản phẩm không mua được nên nút dẫn tới trang chi tiết, đổi chữ cho rõ.
	add_filter( 'woocommerce_product_add_to_cart_text', function () {
		return 'Xem chi tiết';
	} );

	// Thẻ sản phẩm: thêm mô tả ngắn dưới tên.
	add_action( 'astra_woo_shop_title_after', function () {
		$short = get_the_excerpt();
		if ( $short ) {
			echo '<p class="qh-loop-desc">' . esc_html( wp_trim_words( $short, 18 ) ) . '</p>';
		}
	} );

	// Trang chi tiết: bỏ nút giỏ hàng, thêm nút Gọi + Nhận báo giá dưới mô tả ngắn.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	add_action( 'woocommerce_single_product_summary', function () {
		$tel = esc_attr( QH_HOTLINE );
		$hot = esc_html( preg_replace( '/(\d{4})(\d{3})(\d{3})/', '$1 $2 $3', QH_HOTLINE ) );
		echo '<div class="wp-block-buttons qh-product-actions">'
			. '<div class="wp-block-button qh-btn-red"><a class="wp-block-button__link wp-element-button" href="tel:' . $tel . '">Gọi ' . $hot . '</a></div>'
			. '<div class="wp-block-button qh-btn-green"><a class="wp-block-button__link wp-element-button" href="' . esc_url( home_url( '/lien-he/' ) ) . '">Nhận báo giá</a></div>'
			. '</div>';
	}, 30 );
} );

// Giá để trống → "Liên hệ báo giá".
add_filter( 'woocommerce_empty_price_html', function () {
	return '<span class="qh-price-contact">Liên hệ báo giá</span>';
} );
