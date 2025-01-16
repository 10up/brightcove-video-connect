/* global Backbone */

/**
 * Model to contain pending upload
 */

const UploadModel = Backbone.Model.extend({
	// eslint-disable-next-line
	initialize(options) {},

	humanReadableSize() {
		const bytes = this.get('size');
		if (bytes === 0) {
			return '0 Byte';
		}
		const k = 1000;
		const sizes = ['Bytes', 'KB', 'MB', 'GB'];
		const i = Math.floor(Math.log(bytes) / Math.log(k));
		return `${(bytes / k ** i).toPrecision(3)} ${sizes[i]}`;
	},
});

export default UploadModel;
