/* global wpbc */

import BrightcoveView from './brightcove';

const MediaView = BrightcoveView.extend({
	tagName: 'li',
	className: 'attachment brightcove',

	attributes() {
		return {
			tabIndex: 0,
			role: 'checkbox',
			'aria-label': this.model.get('title'),
			'aria-checked': false,
			'data-id': this.model.get('id'),
		};
	},

	events: {
		'click .attachment-preview': 'toggleDetailView',
		'click .video-move-up': 'videoMoveUp',
		'click .video-move-down': 'videoMoveDown',
		'click .trash': 'removeVideoFromPlaylist',
		'click .add-to-playlist': 'videoAdd',
		'click .edit': 'triggerEditMedia',
		'click .preview': 'triggerPreviewMedia',
	},

	triggerEditMedia(event) {
		event.preventDefault();
		wpbc.broadcast.trigger('edit:media', this.model);
	},

	triggerPreviewMedia(event) {
		event.preventDefault();
		wpbc.broadcast.trigger('preview:media', this.model);
	},

	buttons: {},

	initialize(options) {
		// eslint-disable-next-line
		options = options || {};
		this.type = options.type ? options.type : 'grid';

		// We only care when a change occurs
		this.listenTo(this.model, 'change:view', function (model, type) {
			if (this.type !== type) {
				this.type = type;
				this.render();
			}
		});

		this.render();
	},

	render() {
		const options = this.model.toJSON();
		options.duration = this.model.getReadableDuration();
		options.updated_at_readable = options.updatedAt
			? this.model.getReadableDate('updatedAt')
			: this.model.getReadableDate('updated_at');
		options.account_name = this.model.getAccountName();
		options.height = this.model.getReadableDate('height');

		if (options.viewType === 'existingPlaylists') {
			this.template = wp.template('brightcove-playlist-edit-video-in-playlist');
		} else if (options.viewType === 'libraryPlaylists') {
			this.template = wp.template('brightcove-playlist-edit-video-in-library');
		} else {
			this.template = wp.template(`brightcove-media-item-${this.type}`);
		}

		options.buttons = this.buttons;

		this.$el.html(this.template(options));

		this.$el.toggleClass('uploading', options.uploading);

		return this;
	},

	toggleDetailView() {
		wpbc.broadcast.trigger('select:media', this);
	},

	videoMoveUp() {
		wpbc.broadcast.trigger('playlist:moveUp', this);
	},

	videoMoveDown() {
		wpbc.broadcast.trigger('playlist:moveDown', this);
	},

	videoAdd() {
		wpbc.broadcast.trigger('playlist:add', this);
	},

	removeVideoFromPlaylist() {
		wpbc.broadcast.trigger('playlist:remove', this);
	},
});

export default MediaView;
