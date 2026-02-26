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
						var d = response.data || {};
						var fname = $('<span>').text(d.filename || '').html();
						var udate = d.uploaded_at ? $('<span>').text(d.uploaded_at).html() : '';
						var thumbHtml = d.thumb_url
							? '<img class="wise-nudge-thumb" src="' + $('<span>').text(d.thumb_url).html() + '" alt="">'
							: '<span class="wise-nudge-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>';
						$('#wise-upload-form').replaceWith(
							'<div class="wise-nudge">' +
								thumbHtml +
								'<div class="wise-nudge-body">' +
									'<p class="wise-nudge-title">' + fname + '</p>' +
									(udate ? '<p class="wise-nudge-subtitle">' + udate + '</p>' : '') +
								'</div>' +
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
