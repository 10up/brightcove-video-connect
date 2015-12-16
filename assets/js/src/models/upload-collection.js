define(['jquery', 'underscore', 'backbone', 'models/upload'], function( $, _, Backbone, UploadModel) {

    /**
     * Collection model to contain pending uploads
     */

    var UploadCollection = Backbone.Collection.extend({

        initialize: function(options) {
            this.listenTo(wpbc.broadcast, 'uploader:queuedFilesAdded', this.queuedFilesAdded);
        },

        queuedFilesAdded: function(queuedFiles) {
            _.each( queuedFiles, function(queuedFile) {
                this.add(new UploadModel(queuedFile));
            }, this);
        }


    });

    return UploadCollection;
});
