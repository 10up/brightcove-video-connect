/**
 * Collection model to contain pending uploads
 */
import UploadModel from './upload';

var UploadModelCollection = Backbone.Collection.extend({
	initialize: function (options) {
		this.listenTo(wpbc.broadcast, 'uploader:queuedFilesAdded', this.queuedFilesAdded);
	},

	queuedFilesAdded: function (queuedFiles) {
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
