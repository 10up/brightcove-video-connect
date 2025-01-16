/* global jQuery, bcStatus, ajaxurl */

/**
 * Used to provide status warnings from the Brightcove servers.
 */
jQuery(document).ready(function ($) {
	jQuery('.brightcove-service-error .notice-dismiss').click(function () {
		const data = {
			action: 'bc_status_dismiss',
			nonce: bcStatus.nonce,
		};

		// Send dismissal to WordPress.
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data,
			complete(response) {
				if (response.responseJSON.success === true) {
					const date = new Date();
					date.setTime(date.getTime() + 300 * 1000);
					const expires = `; expires=${date.toGMTString()}`;

					document.cookie = `bc-status-dismissed=${true}${expires}; path=/`;
				}
			},
		});
	});
});
