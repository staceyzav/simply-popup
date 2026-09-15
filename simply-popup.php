<?php
/**
 * Plugin Name: Simply Pop-Up
 * Plugin URI:  https://simplydesign.com/simply-popup
 * Description: Lightweight popup with image, CTA, and optional expiration. Auto-injects on every page. Zero dependencies — works on any theme.
 * Author:      Simply Design
 * Author URI:  https://simplydesign.com
 * Version:     1.0.4
 * License:     GPL-2.0-or-later
 * Text Domain: simply-popup
 * Requires at least: 5.4
 * Requires PHP: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SPU_VERSION', '1.0.3' );
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

// ── EDITOR: remove Add Media button on simply_popup ──────────────────────────

add_filter( 'wp_editor_settings', 'spu_editor_settings', 10, 2 );
function spu_editor_settings( $settings, $editor_id ) {
	if ( get_post_type() === 'simply_popup' && $editor_id === 'content' ) {
		$settings['media_buttons'] = false;
	}
	return $settings;
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
	$show_on      = get_post_meta( $post->ID, '_popup_show_on',      true ) ?: 'home';
	$show_pages   = get_post_meta( $post->ID, '_popup_show_pages',   true ) ?: '';

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
		'top'         => 'Image top',
		'left'        => 'Image left',
		'right'       => 'Image right',
		'image_only'  => 'Image only',
		'sticky_only' => 'Sticky bar only (no popup)',
	];

	$add_sticky = get_post_meta( $post->ID, '_popup_add_sticky', true );

	$align         = get_post_meta( $post->ID, '_popup_text_align', true ) ?: 'center';
	$align_options = [
		'left'   => 'Left',
		'center' => 'Center',
		'right'  => 'Right',
	];

	$height_value = get_post_meta( $post->ID, '_popup_height_value', true ) ?: '50';
	$height_unit  = get_post_meta( $post->ID, '_popup_height_unit',  true ) ?: 'vh';
	$hide_mobile  = get_post_meta( $post->ID, '_popup_hide_mobile',  true );
	?>

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

	<p id="spu_height_row">
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

	<p id="spu_align_row">
		<label for="popup_text_align"><strong><?php esc_html_e( 'Content alignment', 'simply-popup' ); ?></strong></label><br>
		<select name="popup_text_align" id="popup_text_align" style="width:100%;margin-top:4px;">
			<?php foreach ( $align_options as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $align, $val ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<div id="spu_image_note" style="<?php echo $layout === 'sticky_only' ? 'display:none;' : ''; ?>">
		<p style="background:#f0f6fc;border-left:3px solid #72aee6;padding:8px 10px;margin:0 0 12px;font-size:11px;color:#444;line-height:1.5;">
			<?php esc_html_e( 'Add your image using the Featured Image panel.', 'simply-popup' ); ?>
		</p>
	</div>

	<hr style="margin:12px 0;border:none;border-top:1px solid #eee;">

	<?php $sticky_text = get_post_meta( $post->ID, '_popup_sticky_text', true ); ?>

	<div id="spu_add_sticky_row" style="<?php echo $layout === 'sticky_only' ? 'display:none;' : ''; ?>">
		<p>
			<label style="display:flex;align-items:center;gap:6px;font-size:13px;">
				<input type="checkbox" name="popup_add_sticky" id="popup_add_sticky" value="1" <?php checked( $add_sticky, '1' ); ?>>
				<strong><?php esc_html_e( 'Also show bottom sticky bar', 'simply-popup' ); ?></strong>
			</label>
		</p>
	</div>

	<div id="spu_sticky_fields" style="<?php echo ( $layout !== 'sticky_only' && $add_sticky !== '1' ) ? 'display:none;' : ''; ?>">
		<p style="background:#fff8e1;border-left:3px solid #f0b429;padding:8px 10px;margin:0 0 8px;font-size:11px;color:#444;line-height:1.5;">
			<?php esc_html_e( 'Sticky bar text. Keep it short — one line is ideal. Basic HTML allowed (links, bold, em).', 'simply-popup' ); ?>
		</p>
		<p>
			<textarea name="popup_sticky_text" id="popup_sticky_text" rows="3"
			          style="width:100%;font-size:13px;"><?php echo wp_kses_post( $sticky_text ); ?></textarea>
		</p>
	</div>

	<hr style="margin:12px 0;border:none;border-top:1px solid #eee;">

	<p>
		<label><strong><?php esc_html_e( 'Visibility', 'simply-popup' ); ?></strong></label><br>
		<label style="display:flex;align-items:center;gap:6px;margin-top:4px;">
			<input type="checkbox" name="popup_hide_mobile" id="popup_hide_mobile" value="1" <?php checked( $hide_mobile, '1' ); ?>>
			<?php esc_html_e( 'Hide on mobile', 'simply-popup' ); ?>
		</label>
	</p>

	<hr style="margin:12px 0;border:none;border-top:1px solid #eee;">

	<p>
		<label for="popup_show_on"><strong><?php esc_html_e( 'Show on', 'simply-popup' ); ?></strong></label><br>
		<select name="popup_show_on" id="popup_show_on" style="width:100%;margin-top:4px;">
			<option value="home"     <?php selected( $show_on, 'home' );     ?>><?php esc_html_e( 'Home page only', 'simply-popup' ); ?></option>
			<option value="all"      <?php selected( $show_on, 'all' );      ?>><?php esc_html_e( 'All pages',      'simply-popup' ); ?></option>
			<option value="specific" <?php selected( $show_on, 'specific' ); ?>><?php esc_html_e( 'Specific pages', 'simply-popup' ); ?></option>
		</select>
	</p>

	<?php
	$selected_ids = array_filter( array_map( 'absint', explode( ',', $show_pages ) ) );
	$all_pages    = get_pages( [ 'post_status' => 'publish', 'sort_column' => 'post_title' ] );
	?>
	<div id="spu_show_pages_row" style="<?php echo $show_on !== 'specific' ? 'display:none;' : ''; ?>">
		<p style="margin-bottom:4px;"><strong><?php esc_html_e( 'Select pages', 'simply-popup' ); ?></strong></p>
		<div style="max-height:160px;overflow-y:auto;border:1px solid #ddd;border-radius:3px;padding:6px 8px;">
			<?php foreach ( $all_pages as $page ) : ?>
			<label style="display:flex;align-items:center;gap:6px;padding:2px 0;font-size:12px;">
				<input type="checkbox" name="popup_show_pages[]"
				       value="<?php echo esc_attr( $page->ID ); ?>"
				       <?php checked( in_array( $page->ID, $selected_ids, true ) ); ?>>
				<?php echo esc_html( $page->post_title ); ?>
			</label>
			<?php endforeach; ?>
		</div>
	</div>

	<hr style="margin:12px 0;border:none;border-top:1px solid #eee;">

	<p>
		<label for="popup_url"><strong><?php esc_html_e( 'Link URL', 'simply-popup' ); ?></strong></label><br>
		<input type="url" name="popup_url" id="popup_url"
		       value="<?php echo esc_attr( $url ); ?>"
		       placeholder="https://"
		       style="width:100%;margin-top:4px;">
	</p>

	<?php $new_tab = get_post_meta( $post->ID, '_popup_new_tab', true ); ?>
	<p>
		<label style="display:flex;align-items:center;gap:6px;font-size:13px;">
			<input type="checkbox" name="popup_new_tab" value="1" <?php checked( $new_tab, '1' ); ?>>
			<?php esc_html_e( 'Open link in new tab', 'simply-popup' ); ?>
		</label>
	</p>

	<p id="spu_cta_row">
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

	<script>
	( function() {
		// Border toggle
		var cb        = document.getElementById( 'popup_border' );
		var colorRow  = document.getElementById( 'spu_border_color_row' );
		var colorSel  = document.getElementById( 'popup_border_color' );
		var customRow = document.getElementById( 'spu_custom_color_row' );

		cb.addEventListener( 'change', function() {
			colorRow.style.display = cb.checked ? '' : 'none';
		} );
		colorSel.addEventListener( 'change', function() {
			customRow.style.display = colorSel.value === 'custom' ? '' : 'none';
		} );

		// Show on — pages checklist
		var showOnSel   = document.getElementById( 'popup_show_on' );
		var pagesRow    = document.getElementById( 'spu_show_pages_row' );
		showOnSel.addEventListener( 'change', function() {
			pagesRow.style.display = showOnSel.value === 'specific' ? '' : 'none';
		} );

		// Layout + sticky toggle
		var layoutSel       = document.getElementById( 'popup_layout' );
		var ctaRow          = document.getElementById( 'spu_cta_row' );
		var heightRow       = document.getElementById( 'spu_height_row' );
		var alignRow        = document.getElementById( 'spu_align_row' );
		var imageNote       = document.getElementById( 'spu_image_note' );
		var addStickyRow    = document.getElementById( 'spu_add_sticky_row' );
		var addStickyCb     = document.getElementById( 'popup_add_sticky' );
		var stickyFields    = document.getElementById( 'spu_sticky_fields' );

		function toggleLayoutFields() {
			var isStickyOnly = layoutSel.value === 'sticky_only';
			var isImageOnly  = layoutSel.value === 'image_only';

			ctaRow.style.display       = ( isImageOnly || isStickyOnly ) ? 'none' : '';
			heightRow.style.display    = ( isImageOnly || isStickyOnly ) ? 'none' : '';
			alignRow.style.display     = ( isImageOnly || isStickyOnly ) ? 'none' : '';
			imageNote.style.display    = isStickyOnly ? 'none' : '';
			addStickyRow.style.display = isStickyOnly ? 'none' : '';

			// Sticky fields: always visible for sticky_only; otherwise follow checkbox
			stickyFields.style.display = ( isStickyOnly || addStickyCb.checked ) ? '' : 'none';
		}

		addStickyCb.addEventListener( 'change', function() {
			stickyFields.style.display = addStickyCb.checked ? '' : 'none';
		} );

		layoutSel.addEventListener( 'change', toggleLayoutFields );
		toggleLayoutFields(); // set correct state on page load
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

	$allowed_layouts = [ 'top', 'left', 'right', 'image_only', 'sticky_only' ];
	if ( isset( $_POST['popup_layout'] ) && in_array( $_POST['popup_layout'], $allowed_layouts, true ) ) {
		update_post_meta( $post_id, '_popup_layout', $_POST['popup_layout'] );
	}

	update_post_meta( $post_id, '_popup_add_sticky', isset( $_POST['popup_add_sticky'] ) ? '1' : '0' );

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

	update_post_meta( $post_id, '_popup_hide_mobile', isset( $_POST['popup_hide_mobile'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_popup_new_tab',     isset( $_POST['popup_new_tab'] )     ? '1' : '0' );

	if ( isset( $_POST['popup_sticky_text'] ) ) {
		update_post_meta( $post_id, '_popup_sticky_text', wp_kses_post( wp_unslash( $_POST['popup_sticky_text'] ) ) );
	}

	$allowed_show = [ 'home', 'all', 'specific' ];
	if ( isset( $_POST['popup_show_on'] ) && in_array( $_POST['popup_show_on'], $allowed_show, true ) ) {
		update_post_meta( $post_id, '_popup_show_on', $_POST['popup_show_on'] );
	}
	if ( isset( $_POST['popup_show_pages'] ) && is_array( $_POST['popup_show_pages'] ) ) {
		$ids = implode( ',', array_map( 'absint', $_POST['popup_show_pages'] ) );
		update_post_meta( $post_id, '_popup_show_pages', $ids );
	} else {
		update_post_meta( $post_id, '_popup_show_pages', '' );
	}
}

// ── ACTIVE POPUP HELPERS ──────────────────────────────────────────────────────

// Returns the first published, non-expired popup that should show on this page.
// Used for the modal only — respects show_on page visibility rules.
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

		$layout = get_post_meta( $popup->ID, '_popup_layout', true ) ?: 'top';
		if ( $layout === 'sticky_only' ) continue; // sticky-only popups never render a modal

		$show_on    = get_post_meta( $popup->ID, '_popup_show_on',    true ) ?: 'home';
		$show_pages = get_post_meta( $popup->ID, '_popup_show_pages', true ) ?: '';

		if ( $show_on === 'home' && ! is_front_page() ) continue;
		if ( $show_on === 'specific' ) {
			$ids = array_filter( array_map( 'absint', explode( ',', $show_pages ) ) );
			if ( empty( $ids ) || ! is_page( $ids ) ) continue;
		}

		return $popup;
	}

	return null;
}

// Returns the first published, non-expired popup that has a sticky bar enabled.
// Ignores show_on — sticky bar always shows on all pages.
function spu_get_active_sticky_popup() {
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

		$layout     = get_post_meta( $popup->ID, '_popup_layout',     true ) ?: 'top';
		$add_sticky = get_post_meta( $popup->ID, '_popup_add_sticky', true );

		if ( $layout === 'sticky_only' || $add_sticky === '1' ) {
			return $popup;
		}
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
	if ( ! spu_get_active_popup() && ! spu_get_active_sticky_popup() ) return;

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
	$hide_mobile   = get_post_meta( $popup->ID, '_popup_hide_mobile',  true );
	$new_tab       = get_post_meta( $popup->ID, '_popup_new_tab',      true );
	$target        = $new_tab === '1' ? ' target="_blank" rel="noopener noreferrer"' : '';
	$height_value  = get_post_meta( $popup->ID, '_popup_height_value', true ) ?: '50';
	$height_unit   = get_post_meta( $popup->ID, '_popup_height_unit',  true ) ?: 'vh';
	$title        = $popup->post_title;
	$img_id       = get_post_thumbnail_id( $popup->ID );
	$img          = $img_id ? wp_get_attachment_image( $img_id, 'large', false, [ 'alt' => esc_attr( $title ) ] ) : '';

	// Box classes + inline style
	$box_classes = 'spu-popup__box';
	$box_styles  = $layout !== 'image_only' ? [ 'min-height:' . absint( $height_value ) . $height_unit ] : [];
	if ( $border === '1' ) {
		$box_classes .= ' has-border';
		$box_styles[]  = '--spu-border-color:' . spu_border_color_var( $border_color, $border_custom );
	}
	$box_style = ' style="' . implode( ';', $box_styles ) . '"';
	?>
	<div id="spu-popup"
	     class="spu-popup spu-layout--<?php echo esc_attr( $layout ); ?> spu-align--<?php echo esc_attr( $text_align ); ?><?php echo $hide_mobile === '1' ? ' spu-hide-mobile' : ''; ?>"
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
				<a href="<?php echo esc_url( $url ); ?>"<?php echo $target; ?>><?php echo $img; ?></a>
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
					<a href="<?php echo esc_url( $url ); ?>" class="ss-btn spu-btn btn"<?php echo $target; ?>><?php echo esc_html( $cta ); ?></a>
				</div>
				<?php endif; ?>
			</div>

			<?php endif; ?>

		</div>
	</div>

	<?php
}

// ── STICKY BAR — independent footer render ────────────────────────────────────

add_action( 'wp_footer', 'spu_render_sticky_footer' );
function spu_render_sticky_footer() {
	$popup = spu_get_active_sticky_popup();
	if ( ! $popup ) return;

	$sticky_text = get_post_meta( $popup->ID, '_popup_sticky_text', true );
	if ( ! $sticky_text ) return;

	$url     = get_post_meta( $popup->ID, '_popup_url',      true );
	$cta     = get_post_meta( $popup->ID, '_popup_cta_text', true ) ?: __( 'Learn More', 'simply-popup' );
	$new_tab = get_post_meta( $popup->ID, '_popup_new_tab',  true );
	$target  = $new_tab === '1' ? ' target="_blank" rel="noopener noreferrer"' : '';
	$cookie  = get_post_meta( $popup->ID, '_popup_cookie',   true ) ?: 'always';

	spu_render_sticky_bar( $sticky_text, $url, $cta, $target, $cookie );
}

// ── STICKY BAR HELPER ─────────────────────────────────────────────────────────

function spu_render_sticky_bar( $text, $url, $cta, $target, $cookie ) {
	?>
	<div id="spu-sticky"
	     class="spu-sticky"
	     data-cookie="<?php echo esc_attr( $cookie ); ?>"
	     hidden>
		<div class="spu-sticky__inner">
			<div class="spu-sticky__text"><?php echo wp_kses_post( $text ); ?></div>
			<?php if ( $url ) : ?>
			<a href="<?php echo esc_url( $url ); ?>" class="spu-sticky__btn"<?php echo $target; ?>>
				<?php echo esc_html( $cta ); ?>
			</a>
			<?php endif; ?>
			<span class="spu-sticky__close spu-close" role="button" tabindex="0"
			      aria-label="<?php esc_attr_e( 'Close', 'simply-popup' ); ?>">&times;</span>
		</div>
	</div>
	<?php
}
