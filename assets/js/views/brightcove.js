const $ = jQuery;

const BrightcoveView = wp.Backbone.View.extend({
	subviews: null,

	registerSubview(view) {
		this.subviews = this.subviews || [];
		this.subviews.push(view);
	},

	remove() {
		_.invoke(this.subviews, 'remove');
		wp.Backbone.View.prototype.remove.call(this);
	},

	insertShortcode() {
		if (!this.model) {
			return;
		}

		const { shortcode } = wpbc;

		if (undefined === this.mediaType) {
			const template = wp.template('brightcove-mediatype-notice');

			// Throw a notice to the user that the file is not the correct format
			$('#lost-connection-notice').before(template);

			// Allow the user to dismiss the notice
			$('#js-mediatype-dismiss').on('click', function () {
				$('#js-mediatype-notice')
					.first()
					.fadeOut(500, function () {
						$(this).remove();
					});
			});
		}

		if (wpbc.modal.target === 'content') {
			window.send_to_editor(shortcode);
		} else {
			$(wpbc.modal.target).val(shortcode);
			$(wpbc.modal.target).change();
		}

		wpbc.broadcast.trigger('close:modal');
	},
});

export default BrightcoveView;
