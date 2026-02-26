<?php
/**
 * Receipt upload form OR receipt nudge (if already uploaded).
 *
 * Expected variables:
 *   $order       WC_Order
 *   $receipt_url string — empty if not yet uploaded
 *
 * @package Woo_Wise_Transfer
 */

defined( 'ABSPATH' ) || exit;

$order_id = $order->get_id();
?>

<?php if ( $receipt_url ) : ?>
<?php
	$receipt_filename = $order->get_meta( '_wise_receipt_filename' );
	$uploaded_at      = $order->get_meta( '_wise_receipt_uploaded_at' );
	$is_image         = preg_match( '/\.(jpe?g|png)$/i', $receipt_filename );
?>
<div class="wise-upload-card">
	<h4 class="wise-upload-card-title"><?php esc_html_e( 'Proof of Payment', 'woo-wise-transfer' ); ?></h4>
	<div class="wise-nudge">
		<?php if ( $is_image ) : ?>
		<img class="wise-nudge-thumb" src="<?php echo esc_url( $receipt_url ); ?>" alt="">
		<?php else : ?>
		<span class="wise-nudge-icon">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
		</span>
		<?php endif; ?>
		<div class="wise-nudge-body">
			<p class="wise-nudge-title"><?php echo esc_html( $receipt_filename ); ?></p>
			<?php if ( $uploaded_at ) : ?>
			<p class="wise-nudge-subtitle"><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $uploaded_at ) ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<div class="wise-alert wise-alert--success">
		<span class="wise-alert-icon">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="M8 12l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</span>
		<div class="wise-alert-body">
			<p class="wise-alert-title"><?php esc_html_e( 'Payment proof received', 'woo-wise-transfer' ); ?></p>
			<p class="wise-alert-text"><?php esc_html_e( 'Our team will review your payment and process your order shortly. You will receive an email once your payment has been confirmed.', 'woo-wise-transfer' ); ?></p>
		</div>
	</div>
</div>
<?php else : ?>
<div class="wise-upload-card">
	<h4 class="wise-upload-card-title"><?php esc_html_e( 'Proof of Payment', 'woo-wise-transfer' ); ?></h4>
	<form id="wise-upload-form" enctype="multipart/form-data">
		<input type="hidden" name="action" value="wise_upload_receipt">
		<input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>">
		<input type="hidden" name="order_key" value="<?php echo esc_attr( $order->get_order_key() ); ?>">
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'wise_transfer_upload' ) ); ?>">

		<div class="wise-upload-area" id="wise-upload-area">
			<input type="file" name="wise_receipt" id="wise-receipt-input" accept=".jpg,.jpeg,.png,.pdf" style="display:none;">
			<div class="wise-upload-placeholder" id="wise-upload-placeholder">
				<span class="wise-upload-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
				</span>
				<span class="wise-upload-label"><?php esc_html_e( 'Upload Proof of Payment', 'woo-wise-transfer' ); ?></span>
				<span class="wise-upload-sublabel"><?php esc_html_e( 'PDF, JPEG or PNG less than 5MB', 'woo-wise-transfer' ); ?></span>
				<span class="wise-upload-select-btn"><?php esc_html_e( 'Or select file', 'woo-wise-transfer' ); ?></span>
			</div>
			<div class="wise-upload-preview" id="wise-upload-preview" style="display:none;">
				<img id="wise-upload-thumb" class="wise-upload-thumb" src="" alt="">
				<span class="wise-upload-file-icon" id="wise-upload-pdf-icon" style="display:none;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
				</span>
				<span class="wise-upload-file-info">
					<span class="wise-upload-filename" id="wise-upload-filename"></span>
				</span>
				<button type="button" class="wise-upload-remove" id="wise-upload-remove" title="<?php esc_attr_e( 'Remove', 'woo-wise-transfer' ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
				</button>
			</div>
		</div>
		<div class="wise-upload-error" id="wise-upload-error" style="display:none;"></div>
		<button type="submit" class="wise-submit-btn" id="wise-submit-btn" disabled>
			<?php esc_html_e( 'Confirm My Payment', 'woo-wise-transfer' ); ?>
		</button>
	</form>
</div>

<script>
(function () {
	if ( typeof window._wiseUploadBound !== 'undefined' ) return;
	window._wiseUploadBound = true;

	var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	var i18n = {
		invalid_format: <?php echo wp_json_encode( __( 'Please upload a JPG, PNG, or PDF file.', 'woo-wise-transfer' ) ); ?>,
		file_too_large: <?php echo wp_json_encode( __( 'File is too large. Maximum size is 5MB.', 'woo-wise-transfer' ) ); ?>,
		uploading:      <?php echo wp_json_encode( __( 'Uploading...', 'woo-wise-transfer' ) ); ?>,
		upload_failed:  <?php echo wp_json_encode( __( 'Upload failed. Please try again.', 'woo-wise-transfer' ) ); ?>
	};

	function el(id) { return document.getElementById(id); }

	function showError(msg) {
		var e = el('wise-upload-error');
		if (e) { e.textContent = msg; e.style.display = 'block'; }
		var a = el('wise-upload-area');
		if (a) a.classList.add('has-error');
	}
	function hideError() {
		var e = el('wise-upload-error');
		if (e) { e.textContent = ''; e.style.display = 'none'; }
		var a = el('wise-upload-area');
		if (a) a.classList.remove('has-error');
	}
	function resetForm() {
		var input = el('wise-receipt-input');
		if (input) input.value = '';
		var ph = el('wise-upload-placeholder');
		if (ph) ph.style.display = '';
		var pv = el('wise-upload-preview');
		if (pv) pv.style.display = 'none';
		var thumb = el('wise-upload-thumb');
		if (thumb) { thumb.src = ''; thumb.style.display = 'none'; }
		var pdfIcon = el('wise-upload-pdf-icon');
		if (pdfIcon) pdfIcon.style.display = 'none';
		var fn = el('wise-upload-filename');
		if (fn) fn.textContent = '';
		var area = el('wise-upload-area');
		if (area) area.classList.remove('has-file');
		var btn = el('wise-submit-btn');
		if (btn) btn.disabled = true;
		hideError();
	}

	var area = el('wise-upload-area');
	if (area) {
		area.addEventListener('click', function (e) {
			if (e.target.closest && e.target.closest('#wise-upload-remove')) return;
			var input = el('wise-receipt-input');
			if (input) input.click();
		});
	}

	var fileInput = el('wise-receipt-input');
	if (fileInput) {
		fileInput.addEventListener('change', function () {
			var file = this.files[0];
			if (!file) return;

			var allowed = ['image/jpeg', 'image/png', 'application/pdf'];
			if (allowed.indexOf(file.type) === -1) {
				showError(i18n.invalid_format);
				this.value = '';
				return;
			}
			if (file.size > 5 * 1024 * 1024) {
				showError(i18n.file_too_large);
				this.value = '';
				return;
			}

			hideError();

			var fn = el('wise-upload-filename');
			if (fn) fn.textContent = file.name;

			var thumb = el('wise-upload-thumb');
			var pdfIcon = el('wise-upload-pdf-icon');

			if (file.type === 'application/pdf') {
				if (thumb) thumb.style.display = 'none';
				if (pdfIcon) pdfIcon.style.display = 'flex';
			} else {
				if (pdfIcon) pdfIcon.style.display = 'none';
				if (thumb) {
					var reader = new FileReader();
					reader.onload = function (ev) {
						thumb.src = ev.target.result;
						thumb.style.display = 'block';
					};
					reader.readAsDataURL(file);
				}
			}

			var ph = el('wise-upload-placeholder');
			if (ph) ph.style.display = 'none';
			var pv = el('wise-upload-preview');
			if (pv) pv.style.display = 'flex';
			var a = el('wise-upload-area');
			if (a) a.classList.add('has-file');
			var btn = el('wise-submit-btn');
			if (btn) btn.disabled = false;
		});
	}

	var removeBtn = el('wise-upload-remove');
	if (removeBtn) {
		removeBtn.addEventListener('click', function (e) {
			e.stopPropagation();
			resetForm();
		});
	}

	var form = el('wise-upload-form');
	if (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var input = el('wise-receipt-input');
			if (!input || !input.files || input.files.length === 0) return;

			var btn = el('wise-submit-btn');
			var originalText = btn ? btn.textContent : '';
			if (btn) { btn.disabled = true; btn.textContent = i18n.uploading; }

			var fd = new FormData(form);
			var xhr = new XMLHttpRequest();
			xhr.open('POST', ajaxUrl, true);
			xhr.onload = function () {
				if (btn) { btn.disabled = false; btn.textContent = originalText; }
				if (xhr.status === 0 || xhr.status >= 400) {
					showError(i18n.upload_failed + ' (HTTP ' + xhr.status + ')');
					return;
				}
				try {
					var resp = JSON.parse(xhr.responseText);
					if (resp.success) {
						var d = resp.data || {};
						var thumbHtml = d.thumb_url
							? '<img class="wise-nudge-thumb" src="' + d.thumb_url + '" alt="">'
							: '<span class="wise-nudge-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>';
						form.outerHTML =
							'<div class="wise-nudge">' +
								thumbHtml +
								'<div class="wise-nudge-body">' +
									'<p class="wise-nudge-title">' + (d.filename || '') + '</p>' +
									(d.uploaded_at ? '<p class="wise-nudge-subtitle">' + d.uploaded_at + '</p>' : '') +
								'</div>' +
							'</div>';
					} else {
						showError((resp.data && resp.data.message) || i18n.upload_failed);
					}
				} catch (ex) {
					showError(i18n.upload_failed + ' — ' + xhr.responseText.substring(0, 120));
				}
			};
			xhr.onerror = function () {
				if (btn) { btn.disabled = false; btn.textContent = originalText; }
				showError(i18n.upload_failed + ' (network error)');
			};
			xhr.send(fd);
		});
	}
})();
</script>
<?php endif; ?>
