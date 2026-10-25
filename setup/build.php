<?php
/**
 * Dựng toàn bộ website Quyết Huệ. Chạy lại nhiều lần được (cập nhật thay vì tạo trùng).
 *   ./wp.sh plugin install contact-form-7 woocommerce --activate   (một lần)
 *   ./wp.sh eval-file /setup/build.php
 * Lưu ý: chạy lại sẽ GHI ĐÈ nội dung các trang và khối dùng chung bằng bản trong thư mục setup/.
 */

if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WPCF7_ContactForm' ) ) {
	WP_CLI::error( 'Cần cài trước: ./wp.sh plugin install contact-form-7 woocommerce --activate' );
}

const QH_SETUP  = '/setup';
const QH_ASSETS = '/assets';

// eval-file chạy trong phạm vi hàm, khai báo global để qh_fill() đọc được.
global $media, $patterns, $cf7_shortcode;

function qh_log( $msg ) {
	WP_CLI::log( $msg );
}

// ---------- 1. Plugin giao diện (mu-plugin) ----------
$mu = WP_CONTENT_DIR . '/mu-plugins';
wp_mkdir_p( $mu );
foreach ( array( 'quyethue.php', 'quyethue.css' ) as $f ) {
	if ( ! copy( QH_SETUP . '/mu-plugins/' . $f, "$mu/$f" ) ) {
		WP_CLI::error( "Không chép được $f vào $mu (kiểm tra quyền thư mục)." );
	}
}
qh_log( 'mu-plugin: đã cài' );

// ---------- 2. Ảnh ----------
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$media_alt = array(
	'logo-mark'       => 'Logo Quyết Huệ',
	'logo-full'       => 'Logo Quyết Huệ – Gạo sạch, chất lượng, bền vững',
	'nep-tren-tay'    => 'Gạo nếp Quyết Huệ',
	'nep-ra-may'      => 'Hạt nếp thành phẩm',
	'kho-gao'         => 'Kho gạo nếp Quyết Huệ',
	'silo'            => 'Silo chứa nguyên liệu',
	'day-chuyen-xat'  => 'Dây chuyền làm sạch và xát',
	'may-dong-bao'    => 'Khu vực đóng bao',
	'kho-bang-tai'    => 'Kho và băng tải xuất hàng',
	'qr-zalo'         => 'Mã QR Zalo Quyết Huệ',
	'sp-nep-doi'      => 'Gạo Nếp Đồi',
	'sp-nep-nhung'    => 'Gạo Nếp Nhung',
	'sp-nep-cau'      => 'Gạo Nếp Cau',
	'sp-nep-hoa-vang' => 'Gạo Nếp Hoa Vàng',
);

$media = array();
foreach ( $media_alt as $key => $alt ) {
	$found = get_posts( array(
		'post_type'   => 'attachment',
		'post_status' => 'inherit',
		'meta_key'    => '_qh_key',
		'meta_value'  => $key,
		'fields'      => 'ids',
		'numberposts' => 1,
	) );
	if ( $found ) {
		$id = $found[0];
	} else {
		$tmp = wp_tempnam( $key . '.jpg' );
		copy( QH_ASSETS . "/$key.jpg", $tmp );
		$id = media_handle_sideload( array( 'name' => "$key.jpg", 'tmp_name' => $tmp ), 0, $alt );
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( "Không nhập được ảnh $key: " . $id->get_error_message() );
		}
		update_post_meta( $id, '_qh_key', $key );
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	$media[ $key ] = array( 'id' => $id, 'url' => wp_get_attachment_url( $id ) );
}
qh_log( 'Ảnh: ' . count( $media ) . ' ảnh' );

// ---------- 3. Form liên hệ (Contact Form 7) ----------
$cf7_id = (int) get_option( 'qh_cf7_id' );
$cf     = $cf7_id ? wpcf7_contact_form( $cf7_id ) : null;
if ( ! $cf ) {
	$cf = WPCF7_ContactForm::get_template( array( 'title' => 'Yêu cầu báo giá' ) );
}
$props         = $cf->get_properties();
$props['form'] = <<<'FORM'
<label>Họ và tên (bắt buộc)
    [text* ho-ten autocomplete:name]</label>

<label>Số điện thoại (bắt buộc)
    [tel* so-dien-thoai autocomplete:tel]</label>

<label>Email
    [email email autocomplete:email]</label>

<label>Bạn là
    [select khach-hang "Đại lý phân phối" "Doanh nghiệp thực phẩm" "Người tiêu dùng" "Khác"]</label>

<label>Sản phẩm quan tâm
    [select san-pham "Nếp Đồi" "Nếp Nhung" "Nếp Cau" "Nếp Hoa Vàng" "Nhiều loại"]</label>

<label>Nội dung (số lượng, quy cách, nơi giao hàng...)
    [textarea noi-dung]</label>

[submit "Gửi yêu cầu"]
FORM;
$props['mail']['recipient']          = 'nongsanquyethue7375@gmail.com';
$props['mail']['subject']            = 'Yêu cầu báo giá từ [ho-ten]';
$props['mail']['additional_headers'] = 'Reply-To: [email]';
$props['mail']['body']               = "Họ và tên: [ho-ten]\nSố điện thoại: [so-dien-thoai]\nEmail: [email]\nBạn là: [khach-hang]\nSản phẩm quan tâm: [san-pham]\n\nNội dung:\n[noi-dung]\n\n-- \nGửi từ website [_site_title] ([_site_url])";
$props['mail_2']['active']           = false;
$props['messages']['mail_sent_ok']   = 'Cảm ơn bạn! Quyết Huệ đã nhận được yêu cầu và sẽ liên hệ lại sớm.';
$props['messages']['mail_sent_ng']   = 'Chưa gửi được yêu cầu. Bạn vui lòng gọi hotline 0969 686 369.';
$props['messages']['validation_error'] = 'Vui lòng kiểm tra lại các ô thông tin.';
$props['messages']['invalid_required'] = 'Vui lòng điền ô này.';
$cf->set_properties( $props );
$cf->save();
update_option( 'qh_cf7_id', $cf->id() );
$cf7_shortcode = sprintf( '[contact-form-7 id="%d" title="%s"]', $cf->id(), $cf->title() );
foreach ( get_posts( array( 'post_type' => 'wpcf7_contact_form', 'exclude' => array( $cf->id() ), 'fields' => 'ids', 'numberposts' => -1 ) ) as $other ) {
	wp_delete_post( $other, true ); // form mẫu "Contact form 1"
}
qh_log( 'Form liên hệ: ' . $cf7_shortcode );

// ---------- Thay thế ký hiệu {{...}} trong nội dung ----------
$patterns = array();
function qh_fill( $html ) {
	global $media, $patterns, $cf7_shortcode;
	return preg_replace_callback( '/\{\{(img|id|pattern):([a-z0-9-]+)\}\}|\{\{cf7\}\}/', function ( $m ) use ( $media, $patterns, $cf7_shortcode ) {
		if ( $m[0] === '{{cf7}}' ) {
			return $cf7_shortcode;
		}
		list( , $type, $key ) = $m;
		if ( $type === 'pattern' ) {
			if ( empty( $patterns[ $key ] ) ) {
				WP_CLI::error( "Thiếu khối dùng chung: $key" );
			}
			return '<!-- wp:block {"ref":' . $patterns[ $key ] . '} /-->';
		}
		if ( empty( $media[ $key ] ) ) {
			WP_CLI::error( "Thiếu ảnh: $key" );
		}
		return $type === 'img' ? $media[ $key ]['url'] : (string) $media[ $key ]['id'];
	}, $html );
}

// ---------- 4. Khối dùng chung (synced patterns) ----------
$pattern_titles = array(
	'san-pham-the'    => 'Danh sách sản phẩm (tự động)',
	'gia-tri-cot-loi' => 'Giá trị cốt lõi',
	'keu-goi-lien-he' => 'Dải kêu gọi liên hệ',
	'chan-trang'      => 'Chân trang',
	'nut-noi'         => 'Nút gọi và Zalo nổi',
);
foreach ( $pattern_titles as $slug => $title ) {
	$existing = get_page_by_path( $slug, OBJECT, 'wp_block' );
	$data     = array(
		'post_type'    => 'wp_block',
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => qh_fill( file_get_contents( QH_SETUP . "/patterns/$slug.html" ) ),
	);
	if ( $existing ) {
		$data['ID'] = $existing->ID;
	}
	$patterns[ $slug ] = wp_insert_post( wp_slash( $data ), true );
	if ( is_wp_error( $patterns[ $slug ] ) ) {
		WP_CLI::error( $patterns[ $slug ]->get_error_message() );
	}
}
update_option( 'qh_footer_block', $patterns['chan-trang'] );
update_option( 'qh_float_block', $patterns['nut-noi'] );
update_option( 'qh_cta_block', $patterns['keu-goi-lien-he'] );
qh_log( 'Khối dùng chung: ' . count( $patterns ) );

// ---------- 5. Trang ----------
$pages = array(
	// slug => [tiêu đề, slug trang cha, thứ tự]
	'trang-chu'    => array( 'Trang chủ', '', 0 ),
	'gioi-thieu'   => array( 'Giới thiệu', '', 1 ),
	'san-pham'     => array( 'Sản phẩm', '', 2 ),
	'tin-tuc'      => array( 'Tin tức', '', 3 ),
	'lien-he'      => array( 'Liên hệ', '', 4 ),
);
$page_ids = array();
foreach ( $pages as $slug => list( $title, $parent, $order ) ) {
	$parent_id = $parent ? $page_ids[ $parent ] : 0;
	$path      = $parent ? "$parent/$slug" : $slug;
	$existing  = get_page_by_path( $path );
	$data      = array(
		'post_type'    => 'page',
		'post_author'  => 1,
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_parent'  => $parent_id,
		'menu_order'   => $order,
		'post_content' => qh_fill( file_get_contents( QH_SETUP . "/pages/$slug.html" ) ),
	);
	if ( $existing ) {
		$data['ID'] = $existing->ID;
	}
	$id = wp_insert_post( wp_slash( $data ), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	// Astra: tràn toàn màn hình, không thanh bên, ẩn tiêu đề mặc định (đã có banner riêng).
	update_post_meta( $id, 'ast-site-content-layout', 'full-width-container' );
	update_post_meta( $id, 'site-content-style', 'unboxed' );
	update_post_meta( $id, 'site-sidebar-layout', 'no-sidebar' );
	update_post_meta( $id, 'site-post-title', 'disabled' );
	update_post_meta( $id, 'ast-featured-img', 'disabled' );
	update_post_meta( $id, 'ast-breadcrumbs-content', 'disabled' );
	$page_ids[ $slug ] = $id;
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_ids['trang-chu'] );
qh_log( 'Trang: ' . count( $page_ids ) );

// Xoá nội dung mẫu của WordPress và 4 trang sản phẩm cũ (nay là sản phẩm WooCommerce).
$obsolete = array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ), array( 'mau', 'page' ) );
foreach ( array( 'nep-doi', 'nep-nhung', 'nep-cau', 'nep-hoa-vang' ) as $slug ) {
	$obsolete[] = array( "san-pham/$slug", 'page' );
}
foreach ( $obsolete as list( $slug, $type ) ) {
	$p = get_page_by_path( $slug, OBJECT, $type );
	if ( $p ) {
		wp_delete_post( $p->ID, true );
	}
}

// ---------- 5b. WooCommerce dạng catalog + sản phẩm ----------
update_option( 'woocommerce_coming_soon', 'no' ); // không thì khách chỉ thấy trang "Sắp ra mắt"
update_option( 'woocommerce_store_pages_only', 'no' );
update_option( 'woocommerce_onboarding_profile', array( 'skipped' => true ) );
update_option( 'woocommerce_task_list_hidden', 'yes' );
update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
update_option( 'woocommerce_allow_tracking', 'no' );
update_option( 'woocommerce_currency', 'VND' );
update_option( 'woocommerce_currency_pos', 'right_space' );
update_option( 'woocommerce_price_num_decimals', 0 );
update_option( 'woocommerce_price_thousand_sep', '.' );
update_option( 'woocommerce_price_decimal_sep', ',' );
update_option( 'woocommerce_default_country', 'VN' );
update_option( 'woocommerce_store_address', 'Trai Trang' );
update_option( 'woocommerce_store_city', 'Yên Mỹ, Hưng Yên' );
update_option( 'woocommerce_enable_reviews', 'no' );
update_option( 'woocommerce_manage_stock', 'no' );
update_option( 'woocommerce_weight_unit', 'kg' );
update_option( 'woocommerce_shop_page_display', '' );

// URL sản phẩm: /san-pham/ten-san-pham/ ; danh mục: /danh-muc-san-pham/ten-danh-muc/
$wc_permalinks                  = (array) get_option( 'woocommerce_permalinks', array() );
$wc_permalinks['product_base']  = '/san-pham';
$wc_permalinks['category_base'] = 'danh-muc-san-pham';
$wc_permalinks['tag_base']      = 'the-san-pham';
update_option( 'woocommerce_permalinks', $wc_permalinks );

// Không dùng trang Cửa hàng/Giỏ hàng/Thanh toán/Tài khoản của Woo (chưa bán online).
foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $wc_page ) {
	$wc_page_id = (int) get_option( "woocommerce_{$wc_page}_page_id" );
	if ( $wc_page_id && get_post_status( $wc_page_id ) === 'publish' ) {
		wp_update_post( array( 'ID' => $wc_page_id, 'post_status' => 'draft' ) );
	}
}

$cat = term_exists( 'gao-nep', 'product_cat' );
if ( ! $cat ) {
	$cat = wp_insert_term( 'Gạo nếp', 'product_cat', array( 'slug' => 'gao-nep' ) );
}
$cat_id = (int) $cat['term_id'];
// Danh mục mặc định "Uncategorized"/"Chưa phân loại" đổi thành "Gạo nếp" để sản phẩm mới khỏi lạc loại.
update_option( 'default_product_cat', $cat_id );
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'slug' => array( 'uncategorized', 'chua-phan-loai' ) ) ) as $t ) {
	if ( (int) $t->term_id !== $cat_id ) {
		wp_delete_term( $t->term_id, 'product_cat' );
	}
}

$product_ids = array();
foreach ( require QH_SETUP . '/products.php' as $i => $p ) {
	$existing = get_page_by_path( $p['slug'], OBJECT, 'product' );
	if ( $existing ) {
		$product_ids[ $p['slug'] ] = $existing->ID; // đã có: giữ nguyên bản người dùng đã sửa
		continue;
	}
	$product = new WC_Product_Simple();
	$product->set_name( $p['name'] );
	$product->set_slug( $p['slug'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_short_description( $p['short'] );
	$product->set_description( $p['desc'] );
	$product->set_image_id( $media[ $p['image'] ]['id'] );
	$product->set_category_ids( array( $cat_id ) );
	$product->set_menu_order( $i + 1 );
	$product->set_reviews_allowed( false );
	$attr = new WC_Product_Attribute();
	$attr->set_name( 'Quy cách' );
	$attr->set_options( array( '5 kg', '10 kg', '25 kg', '50 kg' ) );
	$attr->set_visible( true );
	$product->set_attributes( array( $attr ) );
	$product_ids[ $p['slug'] ] = $product->save();
}
qh_log( 'Sản phẩm: ' . count( $product_ids ) );

// ---------- 5c. Tin tức (bài viết) ----------
// Đường dẫn bài viết: /tin-tuc/ten-bai/ (trang và sản phẩm không đổi).
global $wp_rewrite;
$wp_rewrite->set_permalink_structure( '/tin-tuc/%postname%/' );
update_option( 'default_comment_status', 'closed' );
update_option( 'default_ping_status', 'closed' );

$post_cats = array( 'tin-doanh-nghiep' => 'Tin doanh nghiệp', 'kien-thuc-gao-nep' => 'Kiến thức gạo nếp' );
$cat_ids   = array();
foreach ( $post_cats as $slug => $name ) {
	$t = term_exists( $slug, 'category' );
	if ( ! $t ) {
		$t = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
	}
	$cat_ids[ $slug ] = (int) $t['term_id'];
}
update_option( 'default_category', $cat_ids['tin-doanh-nghiep'] );
$uncat = get_term_by( 'slug', 'uncategorized', 'category' );
if ( $uncat ) {
	wp_delete_term( $uncat->term_id, 'category' ); // bài thuộc "Uncategorized" tự chuyển sang danh mục mặc định
}

$make_posts = require QH_SETUP . '/posts.php';
$post_count = 0;
foreach ( $make_posts( $media ) as $p ) {
	$post_count++;
	if ( get_page_by_path( $p['slug'], OBJECT, 'post' ) ) {
		continue; // đã có: giữ nguyên bản người dùng đã sửa
	}
	$id = wp_insert_post( wp_slash( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'post_author'    => 1,
		'post_name'      => $p['slug'],
		'post_title'     => $p['title'],
		'post_excerpt'   => $p['excerpt'],
		'post_content'   => $p['content'],
		'post_date'      => $p['date'],
		'post_category'  => array( $cat_ids[ $p['category'] ] ),
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	) ), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	set_post_thumbnail( $id, $media[ $p['image'] ]['id'] );
}
qh_log( "Bài viết mẫu: $post_count" );

// ---------- 6. Menu ----------
$menu_name = 'Menu chính';
$menu      = wp_get_nav_menu_object( $menu_name );
$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu( $menu_name );
foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
	wp_delete_post( $item->ID, true );
}
$add = function ( $slug, $parent_item = 0, $type = 'page' ) use ( $menu_id, $page_ids, $product_ids ) {
	return wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-object-id' => $type === 'product' ? $product_ids[ $slug ] : $page_ids[ $slug ],
		'menu-item-object'    => $type,
		'menu-item-type'      => 'post_type',
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => $parent_item,
	) );
};
$add( 'trang-chu' );
$add( 'gioi-thieu' );
$sp = $add( 'san-pham' );
foreach ( array_keys( $product_ids ) as $slug ) {
	$add( $slug, $sp, 'product' );
}
$add( 'tin-tuc' );
$add( 'lien-he' );
$locations                = get_theme_mod( 'nav_menu_locations', array() );
$locations['primary']     = $menu_id;
$locations['mobile_menu'] = $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );
qh_log( 'Menu: đã gán' );

// ---------- 7. Thông tin site + Astra ----------
update_option( 'blogname', 'Quyết Huệ' );
update_option( 'blogdescription', 'Gạo sạch · Chất lượng · Bền vững' );
update_option( 'timezone_string', 'Asia/Ho_Chi_Minh' );
update_option( 'date_format', 'd/m/Y' );
update_option( 'site_icon', $media['logo-mark']['id'] );
set_theme_mod( 'custom_logo', $media['logo-mark']['id'] );

$s = get_option( 'astra-settings', array() );
$s['global-color-palette'] = array( 'palette' => array(
	'#1B5E20', // 0 màu thương hiệu: link, nút
	'#C99A3E', // 1 màu phụ: hover
	'#1B5E20', // 2 tiêu đề
	'#1F2A1F', // 3 chữ nội dung
	'#FBF8EF', // 4 nền chính (kem)
	'#FFFFFF', // 5 nền phụ (trắng)
	'#123F16', // 6 xanh đậm
	'#F6EBCB', // 7 vàng nhạt
	'#C62828', // 8 đỏ nhấn
) );

$desktop = isset( $s['header-desktop-items'] ) ? $s['header-desktop-items'] : astra_get_option( 'header-desktop-items' );
$desktop['primary']['primary_left']  = array( 'logo' );
$desktop['primary']['primary_right'] = array( 'menu-1', 'button-1' );
$s['header-desktop-items']           = $desktop;

$s['header-button1-text']        = '0969 686 369';
$s['header-button1-link-option'] = array( 'url' => 'tel:0969686369', 'new_tab' => false, 'link_rel' => '' );

$s['logo-title-inline']                = 1;
$s['ast-header-responsive-logo-width'] = array( 'desktop' => 64, 'tablet' => 54, 'mobile' => 46 );
$s['display-site-title-responsive']    = array( 'desktop' => 1, 'tablet' => 1, 'mobile' => 1 );
$s['display-site-tagline-responsive']  = array( 'desktop' => 1, 'tablet' => 1, 'mobile' => 0 );
$s['hb-header-height']                 = array( 'desktop' => 84, 'tablet' => 72, 'mobile' => 64 );

$s['site-content-layout']            = 'full-width-container';
$s['single-page-ast-content-layout'] = 'full-width-container';
$s['site-sidebar-layout']            = 'no-sidebar';
$s['single-page-sidebar-layout']     = 'no-sidebar';
$s['scroll-to-top-enable']           = false;

// Cửa hàng: thẻ dạng lưới cổ điển (không có nút túi xách trên ảnh), chỉ tên + giá + nút xem chi tiết.
$s['shop-style']               = 'shop-page-grid-style';
$s['shop-product-structure']   = array( 'title', 'price', 'add_cart' );
$s['single-product-structure'] = array( 'title', 'price', 'short_desc', 'add_cart', 'meta' );
$s['shop-grids']               = array( 'desktop' => 4, 'tablet' => 2, 'mobile' => 2 );

// Bài viết: khung hẹp dễ đọc, không thanh bên, dưới tiêu đề hiện ngày + danh mục (không hiện tác giả).
$s['single-post-ast-content-layout']  = 'narrow-width-container';
$s['single-post-sidebar-layout']      = 'no-sidebar';
$s['ast-dynamic-single-post-metadata'] = array( 'date', 'category' );
$s['blog-meta']                        = array( 'date', 'category' );
$s['ast-dynamic-single-post-structure'] = array( 'ast-dynamic-single-post-title', 'ast-dynamic-single-post-meta', 'ast-dynamic-single-post-image' );
$s['ast-dynamic-single-post-article-featured-image-position-layout-1'] = 'none'; // ảnh đại diện hiện ngay dưới ngày đăng

$s['footer-copyright-editor'] = 'Bản quyền [copyright] [current_year] Công ty TNHH Nông sản Quyết Huệ. Gạo sạch – Chất lượng – Bền vững.';
update_option( 'astra-settings', $s );

if ( class_exists( 'Astra_Dynamic_CSS' ) || function_exists( 'astra_clear_all_assets_cache' ) ) {
	do_action( 'astra_theme_update_after' );
}
qh_log( 'Astra: đã cấu hình' );

// Làm mới đường dẫn ở tiến trình riêng: tiến trình này đã nạp quy tắc danh mục theo cấu trúc cũ.
WP_CLI::runcommand( 'rewrite flush', array( 'launch' => true ) );
WP_CLI::success( 'Hoàn tất. Mở http://localhost:8080' );
