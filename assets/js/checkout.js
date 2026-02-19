/**
 * Wise Transfer - Thank You Page Scripts
 */
(function ($) {
	'use strict';

	/* Guard: if inline script already bound, skip jQuery version */
	if ( typeof window._wiseUploadBound !== 'undefined' ) return;
	window._wiseUploadBound = true;

	var WiseTransfer = {
		init: function () {
			this.bindEvents();
		},

		bindEvents: function () {
			var self = this;

			// Click to upload
			$(document).on('click', '#wise-upload-area', function (e) {
				if ($(e.target).closest('#wise-upload-remove').length) {
					return;
				}
				$('#wise-receipt-input').trigger('click');
			});

			// File selected
			$(document).on('change', '#wise-receipt-input', function () {
				self.handleFileSelect(this);
			});

			// Remove file
			$(document).on('click', '#wise-upload-remove', function (e) {
				e.stopPropagation();
				self.removeFile();
			});

			// Copy buttons
			$(document).on('click', '.wise-copy-btn', function (e) {
				e.preventDefault();
				e.stopPropagation();
				self.copyToClipboard($(this));
			});

			// Upload form submit
			$(document).on('submit', '#wise-upload-form', function (e) {
				e.preventDefault();
				self.submitUpload();
			});
		},

		handleFileSelect: function (input) {
			var file = input.files[0];

			if (!file) {
				return;
			}

			// Validate file type
			var allowed = ['image/jpeg', 'image/png', 'application/pdf'];
			if (allowed.indexOf(file.type) === -1) {
				this.showError(woo_wise_transfer.i18n.invalid_format);
				$(input).val('');
				return;
			}

			// Validate file size (5MB)
			if (file.size > 5 * 1024 * 1024) {
				this.showError(woo_wise_transfer.i18n.file_too_large);
				$(input).val('');
				return;
			}

			this.hideError();
			$('#wise-upload-filename').text(file.name);

			// Show image thumbnail or PDF icon
			if (file.type === 'application/pdf') {
				$('#wise-upload-thumb').hide();
				$('#wise-upload-pdf-icon').css('display', 'flex');
			} else {
				$('#wise-upload-pdf-icon').hide();
				var reader = new FileReader();
				reader.onload = function (ev) {
					$('#wise-upload-thumb').attr('src', ev.target.result).show();
				};
				reader.readAsDataURL(file);
			}

			$('#wise-upload-placeholder').hide();
			$('#wise-upload-preview').css('display', 'flex');
			$('#wise-upload-area').addClass('has-file');
			$('#wise-submit-btn').prop('disabled', false);
		},

		removeFile: function () {
			$('#wise-receipt-input').val('');
			$('#wise-upload-placeholder').show();
			$('#wise-upload-preview').hide();
			$('#wise-upload-thumb').attr('src', '').hide();
			$('#wise-upload-pdf-icon').hide();
			$('#wise-upload-filename').text('');
			$('#wise-upload-area').removeClass('has-file');
			$('#wise-submit-btn').prop('disabled', true);
			this.hideError();
		},

		submitUpload: function () {
			var fileInput = document.getElementById('wise-receipt-input');
			if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
				return;
			}

			var $btn = $('#wise-submit-btn');
			var originalText = $btn.text();
			$btn.prop('disabled', true).text(woo_wise_transfer.i18n.uploading);

			var formData = new FormData(document.getElementById('wise-upload-form'));

			$.ajax({
				url: woo_wise_transfer.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function (response) {
					if (response.success) {
						// Replace upload area with success message
						$('#wise-upload-form').replaceWith(
							'<div class="wise-upload-success">' +
								'<span class="wise-upload-success-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>' +
								'<span class="wise-upload-success-text">' + $('<span>').text(response.data.filename).html() + '</span>' +
							'</div>'
						);
					} else {
						$btn.prop('disabled', false).text(originalText);
						WiseTransfer.showError(response.data.message || woo_wise_transfer.i18n.upload_failed);
					}
				},
				error: function () {
					$btn.prop('disabled', false).text(originalText);
					WiseTransfer.showError(woo_wise_transfer.i18n.upload_failed);
				}
			});
		},

		showError: function (message) {
			$('#wise-upload-error').text(message).show();
			$('#wise-upload-area').addClass('has-error');
		},

		hideError: function () {
			$('#wise-upload-error').hide().text('');
			$('#wise-upload-area').removeClass('has-error');
		},

		copyToClipboard: function ($btn) {
			var text = $btn.data('copy');
			if (!text) {
				return;
			}

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					showCopied($btn);
				});
			} else {
				var $temp = $('<textarea>');
				$('body').append($temp);
				$temp.val(text).select();
				document.execCommand('copy');
				$temp.remove();
				showCopied($btn);
			}

			function showCopied($el) {
				$el.addClass('copied');
				setTimeout(function () {
					$el.removeClass('copied');
				}, 1500);
			}
		}
	};

	$(document).ready(function () {
		WiseTransfer.init();
	});

})(jQuery);
