var MediaCollection = Backbone.Collection.extend(
	{
		model :      MediaModel,
		/**
		 * @param {Array} [models=[]] Array of models used to populate the collection.
		 * @param {Object} [options={}]
		 */
		initialize : function ( models, options ) {
			options = options || {};
			if ( options.activeAccount ) {
				this.activeAccount = options.activeAccount;
			}

			this.additionalRequest = false;

			this.pageNumber = this.pageNumber || 1;

			if ( ! this.mediaType && (this.mediaCollectionViewType === 'existingPlaylists' || this.mediaCollectionViewType === 'libraryPlaylists') ) {
				this.mediaType = 'videos';
			}

			this.mediaCollectionViewType = options.mediaCollectionViewType || 'grid';

			if ( options.excludeVideoIds && 'libraryPlaylists' === options.mediaCollectionViewType ) {
				this.excludeVideoIds = options.excludeVideoIds;
			}

			if ( options.videoIds && ! models ) {
				this.mediaType = 'videos';
				this.videoIds  = options.videoIds;
				this.fetch();
			} else if ( 'playlists' !== options.mediaType ) {
				this.mediaType = 'videos';
				this.fetch();
			}

			this.mediaType = options.mediaType;

			if ( 'videos' === this.mediaType ) {
				this.listenTo( wpbc.broadcast, 'uploader:uploadedFileDetails', function ( video ) {
					// Add the newly uploaded file
					this.add( video, {at : 0} );
				} );
			}

			this.activeAccount = options.activeAccount || 'all';
			this.searchTerm    = options.searchTerm || '';
			this.dates         = options.dates || 'all';
			this.tag           = options.tag || '';

			this.listenTo( wpbc.broadcast, 'change:activeAccount', function ( accountId ) {
				this.activeAccount = accountId;
				wp.heartbeat.enqueue( 'brightcove_heartbeat', { 'accountId': accountId }, true );
				this.fetch();
			} );

			$( document ).on( 'heartbeat-tick.brightcove_heartbeat', function( event, data ) {
				if ( data.hasOwnProperty( 'brightcove_heartbeat' ) ) {
					wp.heartbeat.enqueue( 'brightcove_heartbeat', { 'accountId': data['brightcove_heartbeat']['account_id'] }, true );
				}
			} );

			this.listenTo( wpbc.broadcast, 'change:searchTerm', function ( searchTerm ) {
				this.searchTerm = searchTerm;
				this.fetch();
			} );

			this.listenTo( wpbc.broadcast, 'change:tag', function ( tag ) {

				if ( 'all' === tag ) {
					tag = '';
				}

				this.tag = tag;
				this.fetch();

			} );

			this.listenTo( wpbc.broadcast, 'change:date', function ( date ) {
				this.date = date;
				this.fetch();
			} );

			this.listenTo( wpbc.broadcast, 'tabChange', function ( settings ) {
				this.killPendingRequests();
				if ( settings.mediaType !== this.mediaType ) {
					this.mediaType = settings.mediaType;
					var preload    = wpbc.preload[this.mediaType];
					var model;
					// Remove all models from the collection
					while ( model = this.first() ) {
						this.remove( model );
					}
					if ( preload !== undefined ) {
						this.add( preload );
					} else {
						this.fetch();
					}
				}
			} );
		},

		killPendingRequests : function () {
			// Kill all pending requests
			_.each( wpbc.requests, function ( request ) {
				request.abort();
			} );

			wpbc.requests = [];
		},

		checksum : function ( object ) {
			if ( ! _.isString( object ) ) {
				if ( _.isFunction( object.toJSON ) ) {
					object = object.toJSON();
				} else {
					object = JSON.stringify( object );
				}

			}
			var checksum = 0x12345678;

			for ( var i = 0; i < object.length; i ++ ) {
				checksum += (object.charCodeAt( i ) * (i + 1));
			}

			return checksum;
		},

		/**
		 * Overrides Backbone.Collection.sync
		 *
		 * @param {String} method
		 * @param {Backbone.Model} model
		 * @param {Object} [options={}]
		 * @returns {Promise}
		 */
		sync : function ( method, model, options ) {
			var args, fallback;

			// Overload the read method so Media.fetch() functions correctly.
			if ( 'read' === method ) {
				options      = options || {};
				options.data = _.extend( options.data || {}, {
					action :         'bc_media_query',
					account :        this.activeAccount || wpbc.preload.defaultAccountId,
					dates :          this.date,
					posts_per_page : wpbc.posts_per_page,
					page_number :    this.pageNumber,
					nonce :          wpbc.preload.nonce,
					search :         this.searchTerm,
					tags :           this.tag,
					tagName :        wpbc.preload.tags[this.tag],
					type : this.mediaType || 'videos'
				} );

				var previousRequest = _.pick( options.data, 'account', 'dates', 'posts_per_page', 'search', 'tags', 'type' );

				// Determine if we're infinite scrolling or not.
				this.additionalRequest = _.isEqual( previousRequest, wpbc.previousRequest );
				if ( ! this.additionalRequest ) {
					options.data.page_number = 1;
				}
				/* Prevent reloading on the playlist edit as the playlist videos are one request and library videos another */
				if ( this.mediaCollectionViewType !== 'existingPlaylists' ) {
					wpbc.previousRequest = previousRequest;
				}

				if ( this.videoIds ) {
					options.data.videoIds = this.videoIds.length ? this.videoIds : 'none';
				}

				options.data.query = args;

				if ( ! _.contains( ['libraryPlaylists', 'existingPlaylists'], this.mediaCollectionViewType ) ) {
					this.killPendingRequests();
				}

				var requestChecksum = this.checksum( options.data );

				if ( ! _.isUndefined( wpbc.responses[requestChecksum] ) ) {
					this.parse( {data : wpbc.responses[requestChecksum]}, 'cached' );
					return true;
				}

				var request = $.ajax( {
					                      type :    'POST',
					                      url :     wp.ajax.settings.url,
					                      context : this,
					                      data :    options.data
				                      } ).done( function ( response, status, request ) {
					this.parse( response, status, request, requestChecksum );
				} ).fail( this.fetchFail );

				wpbc.requests.push( request );
				wpbc.broadcast.trigger( 'spinner:on' );

				return request;

				// Otherwise, fall back to Backbone.sync()
			} else {
				/**
				 * Call wp.media.model.MediaCollection.sync or Backbone.sync
				 */
				fallback = MediaCollection.prototype.sync ? MediaCollection.prototype : Backbone;
				return fallback.sync.apply( this, arguments );
			}
		},

		fetchFail : function () {
			if ( this.pageNumber > 1 ) {
				this.pageNumber --;
			}
			wpbc.broadcast.trigger( 'fetch:finished' );
			wpbc.broadcast.trigger( 'spinner:off' );
			wpbc.broadcast.trigger( 'fetch:apiError' );
			if ( 'abort' === status ) {
				return;
			}
		},

		/**
		 * A custom AJAX-response parser.
		 *
		 * See trac ticket #24753
		 *
		 * @param {Object|Array} resp The raw response Object/Array.
		 * @param {Object} xhr
		 * @returns {Array} The array of model attributes to be added to the collection
		 */
		parse : function ( response, status, request, checksum ) {
			wpbc.broadcast.trigger( 'fetch:finished' );
			wpbc.broadcast.trigger( 'spinner:off' );
			if ( ! _.contains( ['success', 'cached'], status ) || ( 'cached' !== status && ! response['success'] ) ) {
				wpbc.broadcast.trigger( 'fetch:apiError' );
				return false;
			}

			var data = response.data;

			if ( "success" === status ) {
				wpbc.responses[checksum] = data;
			}

			if ( false === data ) {
				return false;
			}

			if ( ! _.isArray( data ) ) {
				data = [data];
			}

			/**
			 * In playlist video search, we remove the videos that already exist in the playlist.
			 */
			if ( _.isArray( this.excludeVideoIds ) ) {
				_.each( this.excludeVideoIds, function ( videoId ) {
					data = _.without( data, _.findWhere( data, {id : videoId} ) );
				} );
			}

			var allMedia = _.map( data, function ( attrs ) {
				var id, media, newAttributes;

				if ( attrs instanceof Backbone.Model ) {
					id    = attrs.get( 'id' );
					attrs = attrs.attributes;
				} else {
					id = attrs.id;
				}

				media = this.findWhere( {id : id} );
				if ( ! media ) {
					media = this.add( attrs );
				} else {
					newAttributes = media.parse( attrs );

					if ( ! _.isEqual( media.attributes, newAttributes ) ) {
						media.set( newAttributes );
					}
				}

				media.set( 'viewType', this.mediaCollectionViewType );
				return media;
			}, this );

			if ( this.additionalRequest ) {
				this.add( allMedia );
			} else {
				this.set( allMedia );
			}
		}
	}
);
