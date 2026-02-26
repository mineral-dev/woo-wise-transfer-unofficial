document.addEventListener('DOMContentLoaded', function() {
	document.querySelectorAll('.wise-view-order-wrapper .wise-copy-btn').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			var text = this.getAttribute('data-copy');
			navigator.clipboard.writeText(text).then(function() {
				this.classList.add('copied');
				this.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
				setTimeout(function() {
					this.classList.remove('copied');
					this.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
				}.bind(this), 2000);
			}.bind(this));
		});
	});
});
