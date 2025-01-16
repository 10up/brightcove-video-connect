/* global wpbc, jQuery */

// eslint-disable-next-line
(function ($) {
	const { views } = wp.mce;
	// eslint-disable-next-line
	const bc_video_preview_edit = function () {
		if (!wpbc.modal) {
			// eslint-disable-next-line
			wpbc.modal = new BrightcoveModalView({
				el: brightcoveModalContainer, // eslint-disable-line
				tab: 'videos',
			});
			wpbc.modal.render();
		} else {
			wpbc.modal.$el.show();
		}
	};

	const utilities = {
		bc_sanitize_ids(id) {
			return id.replace(/\D/g, '');
		},
	};

	/**
	 * Generates an iframe element to be applied to a wrapper.
	 */
	const generateIframe = function (src, width, height) {
		const iframe = jQuery('<iframe />');
		iframe.attr('style', `width: ${width}px; height: ${height}px;`);
		iframe.attr('src', src);
		iframe.attr('mozallowfullscreen', '');
		iframe.attr('webkitallowfullscreen', '');
		iframe.attr('allowfullscreen', '');

		return iframe.get(0);
	};

	// for WP version 4.2 and above use
	// This approach https://make.wordpress.org/core/2015/04/23/tinymce-views-api-improvements/
	// eslint-disable-next-line
	if (typeof bctiny.wp_version !== undefined && parseFloat(bctiny.wp_version) >= 4.2) {
		// replace bc_video shortcode with iframe to preview video
		views.register('bc_video', {
			initialize() {
				const self = this;

				let videoHeight = self.shortcode.attrs.named.height;
				let videoWidth = self.shortcode.attrs.named.width;
				const playerId = self.shortcode.attrs.named.player_id;

				if (typeof videoHeight === 'undefined') {
					videoHeight = 250;
				}

				if (typeof videoWidth === 'undefined') {
					videoWidth = 500;
				}

				const src = `//players.brightcove.net/${utilities.bc_sanitize_ids(
					self.shortcode.attrs.named.account_id,
				)}/${playerId}_default/index.html?videoId=${utilities.bc_sanitize_ids(self.shortcode.attrs.named.video_id)}`;

				// There is no way to easily convert an element into string. So we are
				// using a wrapper. This is needed since VIP doesn't allow direct
				// string concatenation. Details at
				// https://wordpressvip.zendesk.com/hc/en-us/requests/63849
				const wrapper = document.createElement('p');
				wrapper.appendChild(generateIframe(src, videoWidth, videoHeight));

				self.content = wrapper.innerHTML;

				// add allowfullscreen attribute to main iframe to allow video preview in full screen
				if (
					typeof document.getElementById('content_ifr') !== 'undefined' &&
					document.getElementById('content_ifr') !== null
				) {
					document.getElementById('content_ifr').setAttribute('allowFullScreen', '');
				}
			},
			edit() {
				wpbc.triggerModal();
			},
		});

		views.register('bc_playlist', {
			initialize() {
				const self = this;

				let playlistHeight = self.shortcode.attrs.named.height;
				let playlistWidth = self.shortcode.attrs.named.width;

				if (typeof playlistHeight === 'undefined') {
					playlistHeight = 250;
				}

				if (typeof playlistWidth === 'undefined') {
					playlistWidth = 500;
				}

				const player_id = self.shortcode.attrs.named.player_id || 'default';

				const src = `//players.brightcove.net/${utilities.bc_sanitize_ids(
					self.shortcode.attrs.named.account_id,
				)}/${player_id}_default/index.html?playlistId=${utilities.bc_sanitize_ids(self.shortcode.attrs.named.playlist_id)}`;

				const wrapper = document.createElement('p');
				wrapper.appendChild(generateIframe(src, playlistWidth, playlistHeight));

				self.content = wrapper.innerHTML;

				// add allowfullscreen attribute to main iframe to allow video preview in full screen
				if (
					typeof document.getElementById('content_ifr') !== 'undefined' &&
					document.getElementById('content_ifr') !== null
				) {
					document.getElementById('content_ifr').setAttribute('allowFullScreen', '');
				}
			},
			edit() {
				wpbc.triggerModal();
			},
		});
		views.register('bc_experience', {
			initialize() {
				const self = this;
				let videoHeight = self.shortcode.attrs.named.height;
				let videoWidth = self.shortcode.attrs.named.width;
				const experienceId = self.shortcode.attrs.named.experience_id;
				let urlAttrs;
				if (typeof self.shortcode.attrs.named.video_ids !== 'undefined') {
					urlAttrs = `videoIds=${utilities.bc_sanitize_ids(self.shortcode.attrs.named.video_ids)}`;
				} else {
					urlAttrs = `playlistId=${utilities.bc_sanitize_ids(self.shortcode.attrs.named.playlist_id)}`;
				}

				if (typeof videoHeight === 'undefined') {
					videoHeight = 250;
				}

				if (typeof videoWidth === 'undefined') {
					videoWidth = 500;
				}

				const src = `//players.brightcove.net/${utilities.bc_sanitize_ids(
					self.shortcode.attrs.named.account_id,
				)}/experience_${experienceId}/index.html?${urlAttrs}`;

				// There is no way to easily convert an element into string. So we are
				// using a wrapper. This is needed since VIP doesn't allow direct
				// string concatenation. Details at
				// https://wordpressvip.zendesk.com/hc/en-us/requests/63849
				const wrapper = document.createElement('p');
				wrapper.appendChild(generateIframe(src, videoWidth, videoHeight));

				self.content = wrapper.innerHTML;

				// add allowfullscreen attribute to main iframe to allow video preview in full screen
				if (
					typeof document.getElementById('content_ifr') !== 'undefined' &&
					document.getElementById('content_ifr') !== null
				) {
					document.getElementById('content_ifr').setAttribute('allowFullScreen', '');
				}
			},
			edit() {
				wpbc.triggerModal();
			},
		});
	} else {
		views.register('bc_video', {
			View: {
				initialize(options) {
					// eslint-disable-next-line
					let videoHeight = self.shortcode.attrs.named.height;
					// eslint-disable-next-line
					let videoWidth = self.shortcode.attrs.named.width;

					if (typeof videoHeight === 'undefined') {
						videoHeight = 250;
					}

					if (typeof videoWidth === 'undefined') {
						videoWidth = 500;
					}

					const src = `//players.brightcove.net/${utilities.bc_sanitize_ids(
						options.shortcode.attrs.named.account_id,
					)}/default_default/index.html?videoId=${utilities.bc_sanitize_ids(options.shortcode.attrs.named.video_id)}`;

					const wrapper = document.createElement('p');
					wrapper.appendChild(generateIframe(src, videoWidth, videoHeight));

					// eslint-disable-next-line
					self.content = wrapper.innerHTML;

					// add allowfullscreen attribute to main iframe to allow video preview in full screen
					if (typeof document.getElementById('content_ifr') !== 'undefined') {
						document.getElementById('content_ifr').setAttribute('allowFullScreen', '');
					}
				},
				edit() {
					wpbc.broadcast.trigger('triggerModal');
				},
				getHtml() {
					return this.content;
				},
			},
		});
		views.register('bc_playlist', {
			View: {
				initialize(options) {
					// eslint-disable-next-line
					let playlistHeight = self.shortcode.attrs.named.height;
					// eslint-disable-next-line
					let playlistWidth = self.shortcode.attrs.named.width;

					if (typeof playlistHeight === 'undefined') {
						playlistHeight = 250;
					}

					if (typeof playlistWidth === 'undefined') {
						playlistWidth = 500;
					}

					// eslint-disable-next-line
					const player_id = self.shortcode.attrs.named.player_id || 'default';

					const src = `//players.brightcove.net/${utilities.bc_sanitize_ids(
						options.shortcode.attrs.named.account_id,
					)}/${player_id}_default/index.html?playlistId=${utilities.bc_sanitize_ids(options.shortcode.attrs.named.playlist_id)}`;

					const wrapper = document.createElement('p');
					wrapper.appendChild(generateIframe(src, playlistWidth, playlistHeight));

					// eslint-disable-next-line
					self.content = wrapper.innerHTML;

					// add allowfullscreen attribute to main iframe to allow video preview in full screen
					if (typeof document.getElementById('content_ifr') !== 'undefined') {
						document.getElementById('content_ifr').setAttribute('allowFullScreen', '');
					}
				},
				edit() {
					wpbc.broadcast.trigger('triggerModal');
				},
				getHtml() {
					return this.content;
				},
			},
		});
		views.register('bc_experience', {
			View: {
				initialize(options) {
					// eslint-disable-next-line
					let videoHeight = self.shortcode.attrs.named.height;
					// eslint-disable-next-line
					let videoWidth = self.shortcode.attrs.named.width;
					// eslint-disable-next-line
					const experienceId = self.shortcode.attrs.named.experience_id;
					// eslint-disable-next-line
					if (typeof self.shortcode.attrs.named.video_ids !== 'undefined') {
						// eslint-disable-next-line
						urlAttrs = `videoIds=${utilities.bc_sanitize_ids(self.shortcode.attrs.named.video_ids)}`;
					} else {
						// eslint-disable-next-line
						urlAttrs = `playlistId=${utilities.bc_sanitize_ids(self.shortcode.attrs.named.playlist_id)}`;
					}

					if (typeof videoHeight === 'undefined') {
						videoHeight = 250;
					}

					if (typeof videoWidth === 'undefined') {
						videoWidth = 500;
					}

					// eslint-disable-next-line
					const src = `//players.brightcove.net/${utilities.bc_sanitize_ids(options.shortcode.attrs.named.account_id)}/experience_${experienceId}/index.html?${urlAttrs}`;

					const wrapper = document.createElement('p');
					wrapper.appendChild(generateIframe(src, videoWidth, videoHeight));

					// eslint-disable-next-line
					self.content = wrapper.innerHTML;

					// add allowfullscreen attribute to main iframe to allow video preview in full screen
					if (typeof document.getElementById('content_ifr') !== 'undefined') {
						document.getElementById('content_ifr').setAttribute('allowFullScreen', '');
					}
				},
				edit() {
					wpbc.broadcast.trigger('triggerModal');
				},
				getHtml() {
					return this.content;
				},
			},
		});
	}
})(jQuery);
