var BrightcoveMediaManagerModel = Backbone.Model.extend(
	{
		defaults :   {
			view :    'grid',
			date :    'all',
			tags :    'all',
			type :    null, // enum[playlist, video]
			preload : true,
			search :  '',
			account : wpbc.preload.defaultAccountId,
			poster: {},
			thumbnail: {}
		},
		initialize : function ( options ) {
			_.defaults( options, this.defaults );

			wp.heartbeat.enqueue( 'brightcove_heartbeat', { 'accountId': wpbc.preload.defaultAccountId }, true );

			var collection = new MediaCollection( [], {mediaType : options.mediaType} );
			collection.reset();
			/* Prevent empty element from living in our collection */

			if ( options.preload && options.preload.length ) {
				collection.add( options.preload );
			}

			options.preload = ! ! options.preload; // Whether or not a preload var was present.

			this.set( 'media-collection-view', new MediaCollectionView( {collection : collection} ) );
			this.set( 'options', options );

		}
	}
);

