define(['jquery', 'underscore', 'backbone', 'models/media-collection', 'views/media-collection'], function( $, _, Backbone, MediaCollection, MediaCollectionView) {
    var BrightcoveMediaManagerModel = Backbone.Model.extend({
        defaults: {
            view: 'grid',
            date: 'all',
            tags: 'all',
            type: null, // enum[playlist, video]
            preload: true,
            search: '',
            account: 'all'
        },
        initialize: function(options) {
            _.defaults(options, this.defaults);

            var collection = new MediaCollection([], {mediaType: options.mediaType});
            collection.reset(); /* Prevent empty element from living in our collection */

            if (options.preload && options.preload.length) {
                collection.add(options.preload);
            }

            options.preload = !! options.preload; // Whether or not a preload var was present.

            this.set('media-collection-view', new MediaCollectionView({collection: collection}));
            this.set('options', options);

        }
    });

    return BrightcoveMediaManagerModel;
});
