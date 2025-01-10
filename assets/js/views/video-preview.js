/* global jQuery, wpbc, ajaxurl */

import BrightcoveView from './brightcove';

const $ = jQuery;

const VideoPreviewView = BrightcoveView.extend({
	tagName: 'div',
	className: 'video-preview brightcove',
	template: wp.template('brightcove-video-preview'),
	shortcode: '',

	initialize(options) {
		this.shortcode = options.shortcode;
	},

	render(options) {
		const that = this;

		options = options || {};
		options.id = this.model.get('id');
		options.account_id = this.model.get('account_id');

		$.ajax({
			url: ajaxurl,
			dataType: 'json',
			method: 'POST',
			data: {
				action: 'bc_resolve_shortcode',
				shortcode: this.shortcode,
				video_id: options.id,
				account_id: options.account_id,
			},
			success(results) {
				that.$el.html(results.data);
			},
		});

		this.listenTo(wpbc.broadcast, 'insert:shortcode', this.insertShortcode);
	},
});

export default VideoPreviewView;
