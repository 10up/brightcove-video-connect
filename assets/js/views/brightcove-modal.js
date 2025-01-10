import BrightcoveView from './brightcove';
import BrightcoveModalModel from '../models/brightcove-modal';
import { BrightcoveMediaManagerView } from './brightcove-media-manager';

const $ = jQuery;

const BrightcoveModalView = BrightcoveView.extend({
	tagName: 'div',
	className: 'media-modal brightcove',
	template: wp.template('brightcove-media-modal'),

	events: {
		'click .brightcove.media-menu-item': 'changeTab',
		'click .brightcove.media-button-insert': 'insertIntoPost',
		'click .media-modal-close': 'closeModal',
		'click .brightcove.save-sync': 'saveSync',
		'click .brightcove.button.back': 'back',
	},

	initialize(options) {
		this.model = new BrightcoveModalModel({ tab: options.tab });
		this.brightcoveMediaManager = new BrightcoveMediaManagerView(
			this.model.getMediaManagerSettings(),
		);
		this.registerSubview(this.brightcoveMediaManager);
		this.listenTo(wpbc.broadcast, 'toggle:insertButton', function (state) {
			this.toggleInsertButton(state);
		});
		this.listenTo(wpbc.broadcast, 'close:modal', this.closeModal);
	},

	saveSync(evnt) {
		// This event is triggered when the "Save and Sync Changes" button is clicked from edit video screen.
		wpbc.broadcast.trigger('save:media', evnt);
	},

	back(evnt) {
		// This event is triggered when the "Back" button is clicked from edit video screen.
		wpbc.broadcast.trigger('back:editvideo', evnt);
	},

	insertIntoPost(evnt) {
		evnt.preventDefault();

		// Exit if the 'button' is disabled.
		if ($(evnt.currentTarget).hasClass('disabled')) {
			return;
		}

		wpbc.shortcode = $('#shortcode').val();

		// Media Details will trigger the insertion since it's always active and contains
		// the model we're inserting
		wpbc.broadcast.trigger('insert:shortcode');
	},

	toggleInsertButton(state) {
		const button = this.$el.find('.brightcove.media-button-insert');
		const processing = $('.attachment.highlighted').find('.processing').length;

		button.show();

		if (processing === 1) {
			button.attr('disabled', 'disabled');
		} else if (state === 'enabled') {
			button.removeAttr('disabled');
		} else if (state === 'disabled') {
			button.attr('disabled', 'disabled');
		} else if (undefined !== button.attr('disabled')) {
			button.removeAttr('disabled');
		} else {
			button.attr('disabled', 'disabled');
		}
	},

	changeTab(event) {
		event.preventDefault();

		if ($(event.target).hasClass('active')) {
			return; // Clicking the already active tab
		}
		$(event.target).addClass('active');
		const tab = _.without(event.target.classList, 'media-menu-item', 'brightcove')[0];
		const tabs = [
			'videos',
			'upload',
			'playlists',
			'in-page-experiences',
			'video-experience',
			'playlist-experience',
		];
		_.each(_.without(tabs, tab), function (otherTab) {
			$(`.brightcove.media-menu-item.${otherTab}`).removeClass('active');
		});

		if (_.contains(tabs, tab)) {
			this.model.set('tab', tab);
			wpbc.broadcast.trigger('spinner:off');
			wpbc.broadcast.trigger('tabChange', this.model.getMediaManagerSettings());
		}
	},

	closeModal(evnt) {
		// If we are in the editVideo mode, switch back to the video view.
		if (wpbc.modal.brightcoveMediaManager.model.get('mode') === 'editVideo') {
			wpbc.broadcast.trigger('start:gridview');
		}

		// Exit if the container button is disabled.
		if (!_.isUndefined(evnt) && $(evnt.currentTarget).parent().hasClass('disabled')) {
			return;
		}
		this.$el.hide();
		$('body').removeClass('modal-open');
	},

	message(message) {
		const messageContainer = this.$el.find('.brightcove-message');
	},

	render(options) {
		this.$el.html(this.template(options));

		this.brightcoveMediaManager.render();
		this.brightcoveMediaManager.$el.appendTo(this.$el.find('.media-frame-content'));

		this.listenTo(wpbc.broadcast, 'edit:media', function (model, mediaType) {
			if (mediaType === 'videos') {
				// When edit Video screen is opened, hide the "Insert Into Post" button and show video save button.
				this.$el.find('.brightcove.button.save-sync').show();
				this.$el.find('.brightcove.button.back').show();
				this.$el.find('.brightcove.media-button-insert').hide();
			} else {
				// When edit playlist screen is opened, hide all the buttons.
				this.$el.find('.brightcove.button.save-sync').hide();
				this.$el.find('.brightcove.button.back').hide();
				this.$el.find('.brightcove.media-button-insert').hide();
			}
		});

		this.listenTo(wpbc.broadcast, 'save:media back:editvideo start:gridView', function () {
			this.$el.find('.brightcove.button.save-sync').hide();
			this.$el.find('.brightcove.button.back').hide();
			this.$el.find('.brightcove.media-button-insert').show();
			wpbc.broadcast.trigger('toggle:insertButton');
		});
	},
});

export default BrightcoveModalView;
