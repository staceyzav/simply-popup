<?php
/**
 * Plugin Name: Simply Pop-Up
 * Plugin URI:  https://simplydesign.com/simply-popup
 * Description: Lightweight popup with image, CTA, and optional expiration. Auto-injects on every page. Zero dependencies — works on any theme.
 * Author:      Simply Design
 * Author URI:  https://simplydesign.com
 * Version:     1.0.0
 * License:     GPL-2.0-or-later
 * Text Domain: simply-popup
 * Requires at least: 5.4
 * Requires PHP: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SPU_VERSION', '1.0.0' );
define( 'SPU_PATH',    plugin_dir_path( __FILE__ ) );
define( 'SPU_URL',     plugin_dir_url( __FILE__ ) );

require_once SPU_PATH . 'includes/class-github-updater.php';
new Simply_GitHub_Updater( 'plugin', plugin_basename( __FILE__ ), 'staceyzav/simply-popup', SPU_VERSION );

// ── CPT ───────────────────────────────────────────────────────────────────────

add_action( 'init', 'spu_register_cpt' );
function spu_register_cpt() {
	register_post_type( 'simply_popup', [
		'labels' => [
			'name'          => __( 'Pop-Ups',        'simply-popup' ),
			'singular_name' => __( 'Pop-Up',         'simply-popup' ),
			'add_new_item'  => __( 'Add New Pop-Up', 'simply-popup' ),
			'edit_item'     => __( 'Edit Pop-Up',    'simply-popup' ),
			'all_items'     => __( 'All Pop-Ups',    'simply-popup' ),
			'menu_name'     => __( 'Pop-Ups',        'simply-popup' ),
		],
		'public'        => false,
		'show_ui'       => true,
		'show_in_menu'  => true,
		'menu_icon'     => 'dashicons-external',
		'menu_position' => 26,
		'supports'      => [ 'title', 'editor', 'thumbnail' ],
		'rewrite'       => false,
	] );
}

// ── META BOX ─────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', 'spu_add_meta_box' );
function spu_add_meta_box() {
	add_meta_box(
		'spu_details',
		__( 'Pop-Up Settings', 'simply-popup' ),
		'spu_meta_box_cb',
		'simply_popup',
		'side',
		'high'
	);
}

function spu_meta_box_cb( $post ) {
	wp_nonce_field( 'spu_save_meta', 'spu_nonce' );

	$url          = get_post_meta( $post->ID, '_popup_url',          true );
	$cta          = get_post_meta( $post->ID, '_popup_cta_text',     true );
	$expires      = get_post_meta( $post->ID, '_popup_expires',      true );
	$border       = get_post_meta( $post->ID, '_popup_border',       true );
	$border_color = get_post_meta( $post->ID, '_popup_border_color', true ) ?: 'accent';
	$cookie       = get_post_meta( $post->ID, '_popup_cookie',       true ) ?: 'always';
	$layout       = get_post_meta( $post->ID, '_popup_layout',       true ) ?: 'top';

	$border_colors = [
		'accent' => 'Accent',
		'brand1' => 'Brand 1',
		'brand2' => 'Brand 2',
		'dark'   => 'Dark',
		'custom' => 'Custom color…',
	];
	$border_custom = get_post_meta( $post->ID, '_popup_border_custom_color', true ) ?: '#000000';

	$cookie_options = [
		'always'  => 'Always show',
		'session' => 'Once per session',
		'7'       => 'Hide for 7 days',
		'30'      => 'Hide for 30 days',
	];

	$layout_options = [
		'top'        => 'Image top',
		'left'       => 'Image left',
		'right'      => 'Image right',
		'image_only' => 'Image only',
	];

	$align         = get_post_meta( $post->ID, '_popup_text_align', true ) ?: 'center';
	$align_options = [
		'left'   => 'Left',
		'center' => 'Center',
		'right'  => 'Right',
	];

	$height_value = get_post_meta( $post->ID, '_popup_height_value', true ) ?: '50';
	$height_unit  = get_post_meta( $post->ID, '_popup_height_unit',  true ) ?: 'vh';
	?>

	<p>
		<label for="popup_url"><strong><?php esc_html_e( 'Link URL', 'simply-popup' ); ?></strong></label><br>
		<input type="url" name="popup_url" id="popup_url"
		       value="<?php echo esc_attr( $url ); ?>"
		       placeholder="https://"
		       style="width:100%;margin-top:4px;">
	</p>

	<p>
		<label for="popup_cta_text"><strong><?php esc_html_e( 'CTA Button Text', 'simply-popup' ); ?></strong></label><br>
		<input type="text" name="popup_cta_text" id="popup_cta_text"
		       value="<?php echo esc_attr( $cta ); ?>"
		       placeholder="Learn More"
		       style="width:100%;margin-top:4px;">
		<span style="color:#666;font-size:11px;">Defaults to "Learn More" if blank.</span>
	</p>

	<p>
		<label for="popup_expires"><strong><?php esc_html_e( 'Expiration Date / Time', 'simply-popup' ); ?></strong></label><br>
		<input type="datetime-local" name="popup_expires" id="popup_expires"
		       value="<?php echo esc_attr( $expires ); ?>"
		       style="width:100%;margin-top:4px;">
		<span style="color:#666;font-size:11px;">Leave blank to show indefinitely.</span>
	</p>

	<hr style="margin:12px 0;border:none;border-top:1px solid #eee;">

	<p>
		<label><strong><?php esc_html_e( 'Border', 'simply-popup' ); ?></strong></label><br>
		<label style="display:flex;align-items:center;gap:6px;margin-top:4px;">
			<input type="checkbox" name="popup_border" id="popup_border" value="1" <?php checked( $border, '1' ); ?>>
			<?php esc_html_e( 'Show border', 'simply-popup' ); ?>
		</label>
	</p>

	<div id="spu_border_color_row" style="<?php echo $border !== '1' ? 'display:none;' : ''; ?>">
		<p>
			<label for="popup_border_color"><?php esc_html_e( 'Border color', 'simply-popup' ); ?></label><br>
			<select name="popup_border_color" id="popup_border_color" style="width:100%;margin-top:4px;">
				<?php foreach ( $border_colors as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $border_color, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p id="spu_custom_color_row" style="<?php echo $border_color !== 'custom' ? 'display:none;' : ''; ?>">
			<label for="popup_border_custom_color"><?php esc_html_e( 'Pick a color', 'simply-popup' ); ?></label><br>
			<input type="color" name="popup_border_custom_color" id="popup_border_custom_color"
			       value="<?php echo esc_attr( $border_custom ); ?>"
			       style="margin-top:4px;height:36px;width:100%;padding:2px;cursor:pointer;">
		</p>
	</div>

	<hr style="margin:12px 0;border:none;border-top:1px solid #eee;">

	<p>
		<label for="popup_cookie"><strong><?php esc_html_e( 'Cookie / Frequency', 'simply-popup' ); ?></strong></label><br>
		<select name="popup_cookie" id="popup_cookie" style="width:100%;margin-top:4px;">
			<?php foreach ( $cookie_options as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $cookie, $val ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<hr style="margin:12px 0;border:none;border-top:1px solid #eee;">

	<p>
		<label for="popup_layout"><strong><?php esc_html_e( 'Layout', 'simply-popup' ); ?></strong></label><br>
		<select name="popup_layout" id="popup_layout" style="width:100%;margin-top:4px;">
			<?php foreach ( $layout_options as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $layout, $val ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<p>
		<label><strong><?php esc_html_e( 'Height (Minimum)', 'simply-popup' ); ?></strong></label><br>
		<span style="display:flex;gap:6px;margin-top:4px;">
			<input type="number" name="popup_height_value" id="popup_height_value"
			       value="<?php echo esc_attr( $height_value ); ?>"
			       min="1" style="flex:1;">
			<select name="popup_height_unit" id="popup_height_unit" style="width:64px;">
				<option value="vh" <?php selected( $height_unit, 'vh' ); ?>>vh</option>
				<option value="px" <?php selected( $height_unit, 'px' ); ?>>px</option>
			</select>
		</span>
	</p>

	<p>
		<label for="popup_text_align"><strong><?php esc_html_e( 'Content alignment', 'simply-popup' ); ?></strong></label><br>
		<select name="popup_text_align" id="popup_text_align" style="width:100%;margin-top:4px;">
			<?php foreach ( $align_options as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $align, $val ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<p style="color:#666;font-size:11px;border-top:1px solid #eee;padding-top:8px;margin-top:8px;">
		<?php esc_html_e( 'Title image → set via "Featured Image" panel below.', 'simply-popup' ); ?>
	</p>

	<script>
	( function() {
		var cb         = document.getElementById( 'popup_border' );
		var colorRow   = document.getElementById( 'spu_border_color_row' );
		var colorSel   = document.getElementById( 'popup_border_color' );
		var customRow  = document.getElementById( 'spu_custom_color_row' );

		cb.addEventListener( 'change', function() {
			colorRow.style.display = cb.checked ? '' : 'none';
		} );

		colorSel.addEventListener( 'change', function() {
			customRow.style.display = colorSel.value === 'custom' ? '' : 'none';
		} );
	} )();
	</script>
	<?php
}

add_action( 'save_post_simply_popup', 'spu_save_meta' );
function spu_save_meta( $post_id ) {
	if (
		! isset( $_POST['spu_nonce'] ) ||
		! wp_verify_nonce( $_POST['spu_nonce'], 'spu_save_meta' ) ||
		( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
	) return;

	if ( isset( $_POST['popup_url'] ) ) {
		update_post_meta( $post_id, '_popup_url', esc_url_raw( $_POST['popup_url'] ) );
	}
	if ( isset( $_POST['popup_cta_text'] ) ) {
		update_post_meta( $post_id, '_popup_cta_text', sanitize_text_field( $_POST['popup_cta_text'] ) );
	}
	if ( isset( $_POST['popup_expires'] ) ) {
		update_post_meta( $post_id, '_popup_expires', sanitize_text_field( $_POST['popup_expires'] ) );
	}

	// Border (checkbox — only present in POST when checked)
	update_post_meta( $post_id, '_popup_border', isset( $_POST['popup_border'] ) ? '1' : '0' );

	$allowed_colors = [ 'accent', 'brand1', 'brand2', 'dark', 'custom' ];
	if ( isset( $_POST['popup_border_color'] ) && in_array( $_POST['popup_border_color'], $allowed_colors, true ) ) {
		update_post_meta( $post_id, '_popup_border_color', $_POST['popup_border_color'] );
	}
	if ( isset( $_POST['popup_border_custom_color'] ) ) {
		$hex = sanitize_hex_color( $_POST['popup_border_custom_color'] );
		if ( $hex ) update_post_meta( $post_id, '_popup_border_custom_color', $hex );
	}

	$allowed_cookies = [ 'always', 'session', '7', '30' ];
	if ( isset( $_POST['popup_cookie'] ) && in_array( $_POST['popup_cookie'], $allowed_cookies, true ) ) {
		update_post_meta( $post_id, '_popup_cookie', $_POST['popup_cookie'] );
	}

	$allowed_layouts = [ 'top', 'left', 'right', 'image_only' ];
	if ( isset( $_POST['popup_layout'] ) && in_array( $_POST['popup_layout'], $allowed_layouts, true ) ) {
		update_post_meta( $post_id, '_popup_layout', $_POST['popup_layout'] );
	}

	$allowed_aligns = [ 'left', 'center', 'right' ];
	if ( isset( $_POST['popup_text_align'] ) && in_array( $_POST['popup_text_align'], $allowed_aligns, true ) ) {
		update_post_meta( $post_id, '_popup_text_align', $_POST['popup_text_align'] );
	}

	if ( isset( $_POST['popup_height_value'] ) ) {
		$hval = absint( $_POST['popup_height_value'] );
		if ( $hval > 0 ) update_post_meta( $post_id, '_popup_height_value', $hval );
	}
	$allowed_units = [ 'vh', 'px' ];
	if ( isset( $_POST['popup_height_unit'] ) && in_array( $_POST['popup_height_unit'], $allowed_units, true ) ) {
		update_post_meta( $post_id, '_popup_height_unit', $_POST['popup_height_unit'] );
	}
}

// ── ACTIVE POPUP HELPER ───────────────────────────────────────────────────────

function spu_get_active_popup() {
	$popups = get_posts( [
		'post_type'      => 'simply_popup',
		'posts_per_page' => 10,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );

	$now = current_time( 'Y-m-d\TH:i' );

	foreach ( $popups as $popup ) {
		$expires = get_post_meta( $popup->ID, '_popup_expires', true );
		if ( $expires && $expires < $now ) continue;
		return $popup;
	}

	return null;
}

// ── BORDER COLOR MAP ──────────────────────────────────────────────────────────

function spu_border_color_var( $key, $custom = '' ) {
	if ( $key === 'custom' ) {
		return $custom ?: '#333333';
	}
	$map = [
		'accent' => 'var(--client-accent, #333)',
		'brand1' => 'var(--client-section-dark-bg, #333)',
		'brand2' => 'var(--client-section-brand2-bg, #666)',
		'dark'   => 'var(--client-dark, #333)',
	];
	return $map[ $key ] ?? $map['accent'];
}

// ── ENQUEUE ───────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'spu_enqueue' );
function spu_enqueue() {
	if ( ! spu_get_active_popup() ) return;

	wp_enqueue_style(  'simply-popup', SPU_URL . 'assets/css/simply-popup.css', [], SPU_VERSION );
	wp_enqueue_script( 'simply-popup', SPU_URL . 'assets/js/simply-popup.js',   [], SPU_VERSION, true );
}

// ── WP FOOTER INJECT ─────────────────────────────────────────────────────────

add_action( 'wp_footer', 'spu_render_popup' );
function spu_render_popup() {
	$popup = spu_get_active_popup();
	if ( ! $popup ) return;

	$url           = get_post_meta( $popup->ID, '_popup_url',                true );
	$cta           = get_post_meta( $popup->ID, '_popup_cta_text',          true ) ?: __( 'Learn More', 'simply-popup' );
	$border        = get_post_meta( $popup->ID, '_popup_border',            true );
	$border_color  = get_post_meta( $popup->ID, '_popup_border_color',      true ) ?: 'accent';
	$border_custom = get_post_meta( $popup->ID, '_popup_border_custom_color', true );
	$cookie       = get_post_meta( $popup->ID, '_popup_cookie',       true ) ?: 'always';
	$layout       = get_post_meta( $popup->ID, '_popup_layout',       true ) ?: 'top';
	$text_align    = get_post_meta( $popup->ID, '_popup_text_align',   true ) ?: 'center';
	$height_value  = get_post_meta( $popup->ID, '_popup_height_value', true ) ?: '50';
	$height_unit   = get_post_meta( $popup->ID, '_popup_height_unit',  true ) ?: 'vh';
	$title        = $popup->post_title;
	$img_id       = get_post_thumbnail_id( $popup->ID );
	$img          = $img_id ? wp_get_attachment_image( $img_id, 'large', false, [ 'alt' => esc_attr( $title ) ] ) : '';

	// Box classes + inline style
	$box_classes = 'spu-popup__box';
	$box_styles  = [ 'min-height:' . absint( $height_value ) . $height_unit ];
	if ( $border === '1' ) {
		$box_classes .= ' has-border';
		$box_styles[]  = '--spu-border-color:' . spu_border_color_var( $border_color, $border_custom );
	}
	$box_style = ' style="' . implode( ';', $box_styles ) . '"';
	?>
	<div id="spu-popup"
	     class="spu-popup spu-layout--<?php echo esc_attr( $layout ); ?> spu-align--<?php echo esc_attr( $text_align ); ?>"
	     data-cookie="<?php echo esc_attr( $cookie ); ?>"
	     role="dialog" aria-modal="true"
	     aria-label="<?php echo esc_attr( $title ?: __( 'Pop-Up', 'simply-popup' ) ); ?>"
	     hidden>
		<div class="spu-popup__overlay spu-close"></div>
		<div class="<?php echo esc_attr( $box_classes ); ?>"<?php echo $box_style; ?>>

			<span class="spu-popup__close spu-close" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Close', 'simply-popup' ); ?>">&times;</span>

			<?php if ( $layout === 'image_only' ) : ?>

			<?php if ( $img ) : ?>
			<div class="spu-popup__image">
				<?php if ( $url ) : ?>
				<a href="<?php echo esc_url( $url ); ?>"><?php echo $img; ?></a>
				<?php else : ?>
				<?php echo $img; ?>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php else : ?>

			<?php if ( $img ) : ?>
			<div class="spu-popup__image">
				<?php echo $img; ?>
			</div>
			<?php endif; ?>

			<div class="spu-popup__body">
				<?php if ( $title ) : ?>
				<div class="spu-popup__title">
					<h2><?php echo esc_html( $title ); ?></h2>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $popup->post_content ) ) : ?>
				<div class="spu-popup__content">
					<?php echo wp_kses_post( apply_filters( 'the_content', $popup->post_content ) ); ?>
				</div>
				<?php endif; ?>

				<?php if ( $url ) : ?>
				<div class="spu-popup__cta">
					<a href="<?php echo esc_url( $url ); ?>" class="ss-btn spu-btn"><?php echo esc_html( $cta ); ?></a>
				</div>
				<?php endif; ?>
			</div>

			<?php endif; ?>

		</div>
	</div>
	<?php
}
