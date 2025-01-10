/**
 * Collection model to contain pending uploads
 */
import UploadModel from './upload';

const UploadModelCollection = Backbone.Collection.extend({
	initialize(options) {
		this.listenTo(wpbc.broadcast, 'uploader:queuedFilesAdded', this.queuedFilesAdded);
	},

	queuedFilesAdded(queuedFiles) {
		_.each(
			queuedFiles,
			function (queuedFile) {
				this.add(new UploadModel(queuedFile));
			},
			this,
		);
	},
});

export default UploadModelCollection;
