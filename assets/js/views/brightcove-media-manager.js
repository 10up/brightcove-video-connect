/* global wpbc, jQuery, _, Backbone */

import BrightcoveView from './brightcove';
import BrightcoveMediaManagerModel from '../models/brightcove-media-manager';
import ToolbarView from './toolbar';
import UploadVideoManagerView from './upload-video-manager';
import MediaDetailsView from './media-details';
import VideoEditView from './video-edit';
import VideoPreviewView from './video-preview';
import PlaylistEditView from './playlist-edit';
import MediaModel from '../models/media';

const $ = jQuery;

const BrightcoveRouter = Backbone.Router.extend({
	routes: {
		'add-new-brightcove-video': 'addNew',
	},
	addNew() {
		wpbc.broadcast.trigger('upload:video');
	},
});

const BrightcoveMediaManagerView = BrightcoveView.extend({
	tagName: 'div',
	className: 'brightcove-media',

	events: {
		/*
			 'click .brightcove.media-button': 'insertIntoPost'
			 */
	},

	scrollHandler() {
		wpbc.broadcast.trigger('scroll:mediaGrid');
	},

	initialize(options) {
		const scrollRefreshSensitivity = wp.media.isTouchDevice ? 300 : 200;
		this.scrollHandler = _.chain(this.scrollHandler)
			.bind(this)
			.throttle(scrollRefreshSensitivity)
			.value();
		this.options = options;
		this.mode = options.mode || 'manager';

		options.preload = this.options.preload ? wpbc.preload[this.options.mediaType] : false;

		this.model = new BrightcoveMediaManagerModel(options);

		/* Search and dropdowns */
		this.toolbar = new ToolbarView({ model: this.model });

		/* Uploader View */
		this.uploader = new UploadVideoManagerView();

		this.model.set('accounts', wpbc.preload.accounts); // All accounts.
		this.model.set('activeAccount', options.account); // Active account ID / All

		this.listenTo(this.toolbar, 'viewType', function (viewType) {
			this.model.set('view', viewType); // Set the model view type
		});

		this.listenTo(wpbc.broadcast, 'videoEdit:message', this.message);
		this.listenTo(wpbc.broadcast, 'permanent:message', this.permanentMessage);

		this.listenTo(wpbc.broadcast, 'remove:permanentMessage', function () {
			if (wpbc.permanentMessage) {
				wpbc.permanentMessage.remove();
			}

			this.$el.find('.brightcove-message').addClass('hidden');
		});

		// We only care when a change occurs
		this.listenTo(this.model, 'change:view', function (model, type) {
			this.model.get('media-collection-view').setViewType(type);
		});

		this.listenTo(this.model, 'change:mode', function (model, mode) {
			if (mode !== 'uploader') {
				wpbc.broadcast.trigger('uploader:clear');
			}
		});

		// eslint-disable-next-line
		this.listenTo(wpbc.broadcast, 'cancelPreview:media', function (settings) {
			this.clearPreview();
			this.detailsView = undefined;
			this.model.set('mode', 'manager');
			this.render();

			// Disable "Insert Into Post" button since no video would be selected.
			wpbc.broadcast.trigger('toggle:insertButton');
		});

		this.listenTo(wpbc.broadcast, 'change:emptyPlaylists', function (hideEmptyPlaylists) {
			const mediaCollectionView = this.model.get('media-collection-view');
			this.model.set('mode', 'manager');

			_.each(mediaCollectionView.collection.models, function (playlistModel) {
				// Don't hide smart playlists. Only Manual playlists will have playlistType as 'EXPLICIT'.
				if (playlistModel.get('type') !== 'EXPLICIT') {
					return;
				}

				// Manual play list will have videos populated in video_ids. Empty playlists will have zero video_ids.
				if (playlistModel.get('video_ids').length === 0) {
					if (hideEmptyPlaylists) {
						playlistModel.view.$el.hide();
					} else {
						playlistModel.view.$el.show();
					}
				}
			});
		});

		this.listenTo(wpbc.broadcast, 'delete:successful', function (message) {
			this.startGridView();
			this.message(message, 'success');
		});

		this.listenTo(wpbc.broadcast, 'change:activeAccount', function (accountId) {
			this.clearPreview();
			this.model.set('activeAccount', accountId);
			this.model.set('mode', 'manager');
			this.render();
		});

		this.listenTo(wpbc.broadcast, 'change:tag', function (tag) {
			this.clearPreview();
			this.model.set('tag', tag);
		});

		this.listenTo(wpbc.broadcast, 'change:folder', function (folder) {
			this.clearPreview();
			this.model.set('old_folder_id', this.model.get('folder_id'));
			this.model.set('folder_id', folder);
		});

		this.listenTo(wpbc.broadcast, 'change:label', function (labelPath) {
			this.clearPreview();
			this.model.set('oldLabelPath', this.model.get('labelPath'));
			this.model.set('labelPath', labelPath);
		});

		// eslint-disable-next-line
		this.listenTo(wpbc.broadcast, 'change:stateChanged', function (state) {
			this.clearPreview();
			this.model.set('oldState', 'oldstate');
			this.model.set('state', 'newstate');
		});

		this.listenTo(wpbc.broadcast, 'change:date', function (date) {
			this.clearPreview();
			this.model.set('date', date);
		});

		this.listenTo(wpbc.broadcast, 'upload:video', function () {
			this.showUploader();
		});

		// eslint-disable-next-line
		this.listenTo(this.model, 'change:search', function (model, searchTerm) {
			this.model.get('search');
		});

		this.listenTo(wpbc.broadcast, 'start:gridview', function () {
			_.invoke(this.subviews, 'remove');

			this.detailsView = null; // Prevent selected view from not being toggleable when we hit the back button on edit

			this.startGridView();
		});

		this.listenTo(wpbc.broadcast, 'tabChange', function (settings) {
			this.model.set(settings);

			if (this.detailsView instanceof MediaDetailsView) {
				this.detailsView.remove();

				this.detailsView = undefined;
			}

			this.render();
		});

		// eslint-disable-next-line
		this.listenTo(wpbc.broadcast, 'edit:media', function (model) {
			const mediaType = this.model.get('mediaType');

			if (mediaType === 'videos') {
				// We just hit the edit button with the edit window already open.
				if (this.model.get('mode') === 'editVideo') {
					return true;
				}

				// hide the previous notification
				// eslint-disable-next-line
				var messages = this.$el.find('.brightcove-message');
				// eslint-disable-next-line
				messages.addClass('hidden');

				this.editView = new VideoEditView({ model });

				this.registerSubview(this.editView);
				this.model.set('mode', 'editVideo');
				this.render();
			} else if (mediaType === 'videoexperience') {
				// We just hit the edit button with the edit window already open.
				if (this.model.get('mode') === 'editVideo') {
					return true;
				}

				// hide the previous notification
				// eslint-disable-next-line
				var messages = this.$el.find('.brightcove-message');
				// eslint-disable-next-line
				messages.addClass('hidden');

				this.editView = new VideoEditView({ model });

				this.registerSubview(this.editView);
				this.model.set('mode', 'editVideo');
				this.render();
			} else {
				// We just hit the edit button with the edit window already open.
				if (this.model.get('mode') === 'editPlaylist') {
					return true;
				}

				this.editView = new PlaylistEditView({ model });

				this.registerSubview(this.editView);
				this.model.set('mode', 'editPlaylist');
				this.render();
			}
		});

		// eslint-disable-next-line
		this.listenTo(wpbc.broadcast, 'preview:media', function (model, shortcode) {
			const mediaType = this.model.get('mediaType');

			if (mediaType === 'videos') {
				// We just hit the preview button with the preview window already open.
				if (this.model.get('mode') === 'previewVideo') {
					return true;
				}

				this.previewView = new VideoPreviewView({ model, shortcode });

				this.registerSubview(this.previewView);
				this.model.set('mode', 'previewVideo');
				this.render();
			} else {
				/**
				 * @todo: playlist preview view
				 */
				this.model.set('mode', 'editPlaylist');
			}
		});

		// eslint-disable-next-line
		this.listenTo(wpbc.broadcast, 'change:searchTerm', function (mediaView) {
			this.clearPreview();
		});

		this.listenTo(wpbc.broadcast, 'select:media', function (mediaView) {
			// Handle selection in the video experience tab.
			if (
				mediaView.model.collection &&
				mediaView.model.collection.mediaType === 'videoexperience'
			) {
				// Toggle the selected state.
				mediaView.$el.toggleClass('highlighted');
				mediaView.model.set('isSelected', mediaView.$el.hasClass('highlighted'));

				// Collect the selected models and extract their IDs.
				const selected = _.filter(mediaView.model.collection.models, function (model) {
					return model.get('isSelected');
				});
				const selectedIds = _.map(selected, function (model) {
					return model.get('id');
				});

				this.detailsView.model.set('id', selectedIds);

				// Clear the shortcode and disable insertion if no items are selected.
				if (_.isEmpty(selectedIds) && this.model.get('mediaType') !== 'videoexperience') {
					wpbc.broadcast.trigger('toggle:insertButton');
					$('#shortcode').val('');
				} else {
					// Otherwise, enable insertion.
					wpbc.broadcast.trigger('toggle:insertButton', 'enabled');
				}
			} else {
				/* If user selects same thumbnail they want to hide the details view */
				// eslint-disable-next-line
				if (this.detailsView && this.detailsView.model === mediaView.model) {
					this.detailsView.$el.toggle();
					mediaView.$el.toggleClass('highlighted');
					this.model.get('media-collection-view').$el.toggleClass('menu-visible');
					wpbc.broadcast.trigger('toggle:insertButton');
				} else {
					this.clearPreview();
					this.detailsView = new MediaDetailsView({
						model: mediaView.model,
						el: $('.brightcove.media-frame-menu'),
						mediaType: this.model.get('mediaType'),
					});
					this.registerSubview(this.detailsView);

					this.detailsView.render();
					this.detailsView.$el.toggle(true); // Always show new view

					const contentElement = $('.brightcove-modal .media-frame-content').first();

					if (contentElement.length) {
						const maxTopValue =
							$('#brightcove-media-frame-content').outerHeight() -
							this.detailsView.$el.outerHeight();

						let topValue =
							contentElement.scrollTop() -
							$('#brightcove-media-frame-router').outerHeight() +
							25;

						if (topValue > maxTopValue) {
							topValue = maxTopValue;
						}

						this.detailsView.$el.css('top', topValue > 0 ? topValue : 0);
					}

					this.model.get('media-collection-view').$el.addClass('menu-visible');
					mediaView.$el.addClass('highlighted');
					wpbc.broadcast.trigger('toggle:insertButton', 'enabled');
				}
			}
		});
	},

	/**
	 * Clear the preview view and remove highlighted class from previous
	 * selected video.
	 */
	clearPreview() {
		const messages = $('.brightcove-message');
		messages.addClass('hidden');

		if (this.detailsView instanceof MediaDetailsView) {
			this.detailsView.remove();
		}

		this.model.get('media-collection-view').$el.find('.highlighted').removeClass('highlighted');
	},

	startGridView() {
		this.model.set('mode', 'manager');
		this.render();
	},

	message(message, type, permanent) {
		const messages = this.$el.find('.brightcove-message');

		if (type === 'success') {
			messages.addClass('updated');
			messages.removeClass('error');
		} else if (type === 'error') {
			messages.addClass('error');
			messages.removeClass('updated');
		}

		const newMessage = $('<p></p>');
		newMessage.text(message);

		messages.html(newMessage);
		messages.removeClass('hidden');

		if (permanent) {
			if (wpbc.permanentMessage) {
				wpbc.permanentMessage.remove();
			}

			wpbc.permanentMessage = newMessage;
		} else {
			// Make the notice dismissable.
			messages.addClass('notice is-dismissible');
			this.makeNoticesDismissible();
		}
		$('html, body').animate({ scrollTop: 0 }, 'fast');
	},

	// Make notices dismissible, mimics core function, fades them empties.
	makeNoticesDismissible() {
		$('.notice.is-dismissible').each(function () {
			const $el = $(this);
			const $button = $(
				'<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>',
			);
			// eslint-disable-next-line
			const btnText = commonL10n.dismiss || '';

			// Ensure plain text
			$button.find('.screen-reader-text').text(btnText);
			$button.on('click.wp-dismiss-notice', function (event) {
				event.preventDefault();
				$el.fadeTo(100, 0, function () {
					$el.slideUp(100, function () {
						$el.addClass('hidden')
							.css({
								opacity: 1,
								'margin-bottom': 0,
								display: '',
							})
							.empty();
					});
				});
			});

			$el.append($button);
		});
	},

	showUploader() {
		this.model.set('mode', 'uploader');
		this.render();
	},

	permanentMessage(message) {
		this.message(message, 'error', true);
	},

	render() {
		const options = this.model.get('options');
		let contentContainer;

		const mode = this.model.get('mode');

		// Nuke all registered subviews
		_.invoke(this.subviews, 'remove');

		if (mode === 'uploader') {
			this.template = wp.template('brightcove-uploader-container');

			this.$el.empty();
			this.$el.html(this.template(options));
			this.uploader.render();
			this.uploader.delegateEvents();
			this.uploader.$el.appendTo($('.brightcove-uploader'));
		} else if (mode === 'manager') {
			this.template = wp.template('brightcove-media');

			this.$el.html(this.template(options));
			this.toolbar.render();
			this.toolbar.delegateEvents();
			this.toolbar.$el.show();
			this.toolbar.$el.appendTo(this.$el.find('.media-frame-router'));

			// Add the Media views to the media manager
			const mediaCollectionView = this.model.get('media-collection-view');

			mediaCollectionView.render();
			mediaCollectionView.delegateEvents();

			const mediaCollectionContainer = this.$el.find('.media-frame-content');

			mediaCollectionContainer.on('scroll', this.scrollHandler);
			mediaCollectionView.$el.appendTo(mediaCollectionContainer);

			if (wpbc.initialSync) {
				wpbc.broadcast.trigger('remove:permanentMessage');
				wpbc.broadcast.trigger('permanent:message', wpbc.preload.messages.ongoingSync);
			}
			if (this.model.get('mediaType') === 'videoexperience') {
				this.detailsView = new MediaDetailsView({
					model: new MediaModel(this.model.attributes),
					el: $('.brightcove.media-frame-menu'),
					mediaType: this.model.get('mediaType'),
				});
				this.registerSubview(this.detailsView);

				this.detailsView.render();
				this.detailsView.$el.toggle(true); // Always show new view
				wpbc.broadcast.trigger('toggle:insertButton', 'enabled');
				this.model.get('media-collection-view').$el.addClass('menu-visible');
			}
		} else if (mode === 'editVideo') {
			this.toolbar.$el.hide();

			contentContainer = this.$el.find('.media-frame-content');

			contentContainer.empty();
			this.editView.render();
			this.editView.delegateEvents();
			this.editView.$el.appendTo(contentContainer);
			this.$el.find('.brightcove.media-frame-content').addClass('edit-view');
		} else if (mode === 'editPlaylist') {
			this.toolbar.$el.hide();

			contentContainer = this.$el;

			contentContainer.empty();
			contentContainer.html('<div class="playlist-edit-container"></div>');

			contentContainer = contentContainer.find('.playlist-edit-container');

			this.editView.render();
			this.editView.delegateEvents();
			this.editView.$el.appendTo(contentContainer);
			contentContainer.addClass('playlist');
		} else if (mode === 'previewVideo') {
			this.toolbar.$el.hide();

			contentContainer = this.$el.find('.media-frame-content');

			contentContainer.empty();
			this.previewView.render();
			this.detailsView.render({ detailsMode: 'preview' });
			this.previewView.delegateEvents();
			this.previewView.$el.appendTo(contentContainer);
			this.$el.find('.brightcove.media-frame-toolbar').hide();
			// eslint-disable-next-line
			brightcove.createExperiences();
		}

		if (mode !== 'editPlaylist') {
			this.$el.find('.media-frame-content').removeClass('playlist');
		}

		return this;
	},
});

export { BrightcoveMediaManagerView, BrightcoveRouter };
