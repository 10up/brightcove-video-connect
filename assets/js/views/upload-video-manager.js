import BrightcoveView from './brightcove';
import UploadModelCollection from '../models/upload-collection';
import UploadWindowView from './upload-window';
import UploadView from './upload';
import UploadDetailsView from './upload-details';

const $ = jQuery;

const UploadVideoManagerView = BrightcoveView.extend({
	className: 'brightcove-file-uploader',

	events: {
		'click .brightcove-start-upload': 'triggerUpload',
	},

	initialize(options) {
		/**
		 * If you're looking for the Plupload instance, you're in the wrong place, check the UploadWindowView
		 */
		this.collection = new UploadModelCollection();
		if (options) {
			this.options = options;

			this.successMessage = options.successMessage || this.successMessage;
		}

		this.uploadWindow = new UploadWindowView();

		this.listenTo(this.collection, 'add', this.fileAdded);
		this.listenTo(wpbc.broadcast, 'pendingUpload:selectedItem', this.selectedItem);
		this.listenTo(wpbc.broadcast, 'uploader:prepareUpload', this.prepareUpload);
		this.listenTo(wpbc.broadcast, 'uploader:successMessage', this.successMessage);
		this.listenTo(wpbc.broadcast, 'uploader:errorMessage', this.errorMessage);
		this.listenTo(wpbc.broadcast, 'uploader:clear', this.resetUploads);
		this.listenTo(wpbc.broadcast, 'upload:video', this.resetUploads);
	},

	resetUploads() {
		let model;
		while ((model = this.collection.first())) {
			this.collection.remove(model);
		}
	},

	errorMessage(message) {
		this.message(message, 'error');
	},

	successMessage(message) {
		this.message(message, 'success');
	},

	message(message, type) {
		const messages = this.$el.find('.brightcove-messages');
		let messageClasses = '';
		if (type === 'success') {
			messageClasses = 'notice updated';
		} else if (type === 'error') {
			messageClasses = 'error';
		}
		const newMessage = $(
			'<div class="wrap"><div class="brightcove-message"><p class="message-text"></p></div></div>',
		);
		messages.append(newMessage);
		newMessage.addClass(messageClasses).find('.message-text').text(message);
		newMessage.delay(4000).fadeOut(500, function () {
			$(this).remove();
			wpbc.broadcast.trigger('upload:video');
		});
	},

	prepareUpload() {
		wpbc.uploads = wpbc.uploads || {};
		this.collection.each(function (upload) {
			wpbc.uploads[upload.get('id')] = {
				account: upload.get('account'),
				name: upload.get('fileName'),
				tags: upload.get('tags'),
			};
		});
		wpbc.broadcast.trigger('uploader:startUpload');
	},

	fileAdded(model, collection) {
		// Start upload triggers progress bars under every video.
		// Need to re-render when one model is added
		if (this.collection.length === 1) {
			this.render();
		}
		const pendingUpload = new UploadView({ model });
		pendingUpload.render();
		pendingUpload.$el.appendTo(this.$el.find('.brightcove-pending-uploads'));
	},

	triggerUpload() {
		wpbc.broadcast.trigger('uploader:prepareUpload');
	},

	selectedItem(model) {
		this.uploadDetails = new UploadDetailsView({ model });
		this.uploadDetails.render();
		this.$el.find('.brightcove-pending-upload-details').remove();
		this.uploadDetails.$el.appendTo(this.$el.find('.brightcove-upload-queued-files'));
	},

	render(options) {
		if (this.collection.length) {
			this.template = wp.template('brightcove-uploader-queued-files');
		} else {
			this.template = wp.template('brightcove-uploader-inline');
			this.uploadWindow.render();
			this.uploadWindow.$el.appendTo($('body'));
		}
		this.$el.html(this.template(options));
		if (this.collection.length) {
			this.$el.find('.brightcove-start-upload').show();
		} else {
			this.$el.find('.brightcove-start-upload').hide();
		}
	},
});

export default UploadVideoManagerView;
