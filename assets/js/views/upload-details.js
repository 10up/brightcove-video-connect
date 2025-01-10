import BrightcoveView from './brightcove';

const UploadDetailsView = BrightcoveView.extend({
	className: 'brightcove-pending-upload-details attachment-details',
	tagName: 'div',
	template: wp.template('brightcove-pending-upload-details'),

	events: {
		'keyup .brightcove-name': 'nameChanged',
		'keyup .brightcove-tags': 'tagsChanged',
		'change .brightcove-media-source': 'accountChanged',
	},

	initialize(options) {
		this.listenTo(wpbc.broadcast, 'pendingUpload:hideDetails', this.hide);
		this.listenTo(wpbc.broadcast, 'uploader:fileUploaded', function (file) {
			if (file.id === this.model.get('id')) {
				this.model.set('uploaded', true);
				this.render();
			}
		});
		this.model.set('ingestSuccess', true);
		this.model.set('uploadSuccess', true);
	},

	nameChanged(event) {
		this.model.set('fileName', event.target.value);
	},

	tagsChanged(event) {
		this.model.set('tags', event.target.value);
	},

	accountChanged(event) {
		this.model.set('account', event.target.value);
	},

	hide() {
		this.$el.hide();
	},

	render(options) {
		options = options || {};
		options.fileName = this.model.get('fileName');
		options.tags = this.model.get('tags');
		options.size = this.model.humanReadableSize();
		options.accounts = this.model.get('accounts');
		options.account = this.model.get('account');
		options.uploaded = this.model.get('uploaded');
		this.$el.html(this.template(options));
	},
});

export default UploadDetailsView;
