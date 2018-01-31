( function( $ ){
/**
 * Media model for Media CPT
 */

var MediaModel = Backbone.Model.extend(
	{

		/**
		 * Copied largely from WP Attachment sync function
		 * Triggered when attachment details change
		 * Overrides Backbone.Model.sync
		 *
		 * @param {string} method
		 * @param {wp.media.model.Media} model
		 * @param {Object} [options={}]
		 *
		 * @returns {Promise}
		 */
		sync : function ( method, model, options ) {

			var accountHash = null;

			// Set the accountHash to the wpbc.preload.accounts[*] where the account_id
			// matches this media objects account_id.
			_.find( wpbc.preload.accounts, function ( account, hash ) {
				if ( account.account_id === this.get( 'account_id' ) ) {
					accountHash = hash;
					return true;
				}
			}, this );

			// If the attachment does not yet have an `id`, return an instantly
			// rejected promise. Otherwise, all of our requests will fail.
			if ( _.isUndefined( this.id ) ) {
				return $.Deferred().rejectWith( this ).promise();
			}

			// Overload the `read` request so Media.fetch() functions correctly.
			if ( 'read' === method ) {
				options         = options || {};
				options.context = this;
				options.data    = _.extend( options.data || {}, {
					action : 'bc_media_fetch',
					id :     this.id
				} );

				return wp.media.ajax( options );

				// Overload the `update` request so properties can be saved.
			} else if ( 'update' === method ) {
				options         = options || {};
				options.context = this;
				// Set the action and ID.
				options.data = _.extend( options.data || {}, {
					account :          accountHash,
					action :           'bc_media_update',
					description :      this.get( 'description' ),
					long_description : this.get( 'long_description' ),
					name :             this.get( 'name' ),
					nonce :            wpbc.preload.nonce,
					tags :             this.get( 'tags' ),
					type :             this.get( 'mediaType' ),
					custom_fields:     this.get( 'custom_fields' ),
					history:           this.get( '_change_history' ),
					poster:            this.get( 'poster' ),
					thumbnail:         this.get( 'thumbnail' ),
					captions:          this.get( 'captions' )
				} );

				var video_ids = this.get( 'video_ids' );
				if ( video_ids ) {
					options.data.playlist_id     = this.id;
					options.data.playlist_videos = video_ids;
					options.data.type            = 'playlists';
				} else {
					options.data.video_id = this.id;
				}

				options.success = this.successFunction;
				options.error   = this.failFunction;

				wpbc.broadcast.trigger( 'spinner:on' );
				return wp.media.ajax( options );

				// Overload the `delete` request so attachments can be removed.
				// This will permanently delete an attachment.
			} else if ( 'delete' === method ) {
				options = options || {};
				var self = this;

				options.data = _.extend( options.data || {}, {
					account : accountHash,
					action :  'bc_media_delete',
					id :      this.get( 'id' ),
					nonce :   wpbc.preload.nonce,
					type :    this.get( 'mediaType' ),
				} );

				return wp.media.ajax( options ).done( function ( response ) {
					self.destroyed = true;
					wpbc.broadcast.trigger( 'delete:successful', response );
					if ( 'videos' === self.get( 'mediaType' ) || ! _.isUndefined( self.get( 'video_ids' ) ) ) {
						wpbc.preload.videos = undefined;
					} else {
						wpbc.preload.playlists = undefined;
					}
					wpbc.responses = {};
				} ).fail( function ( response ) {
					self.destroyed = false;
					wpbc.broadcast.trigger( 'videoEdit:message', response, 'error' );
					wpbc.broadcast.trigger( 'spinner:off' );
				} );

				// Otherwise, fall back to `Backbone.sync()`.
			} else {
				/**
				 * Call `sync` directly on Backbone.Model
				 */
				return Backbone.Model.prototype.sync.apply( this, arguments );
			}
		},

		/**
		 * Convert date strings into Date objects.
		 *
		 * @param {Object} resp The raw response object, typically returned by fetch()
		 * @returns {Object} The modified response object, which is the attributes hash
		 *    to be set on the model.
		 */
		parse : function ( resp ) {
			if ( ! resp ) {
				return resp;
			}

			resp.date     = new Date( resp.date );
			resp.modified = new Date( resp.modified );
			return resp;
		},

		getAccountName : function () {

			var account_id      = this.get( 'account_id' );
			var matchingAccount = _.findWhere( wpbc.preload.accounts, {account_id : this.get( 'account_id' )} );
			return undefined === matchingAccount ? 'unavailable' : matchingAccount.account_name;
		},

		getReadableDuration : function () {

			var duration = this.get( 'duration' );

			if ( duration ) {
				duration    = Number( duration / 1000 );
				var hours   = Math.floor( duration / 3600 );
				var minutes = Math.floor( duration % 3600 / 60 );
				var seconds = Math.floor( duration % 3600 % 60 );
				return ((hours > 0 ? hours + ":" + (minutes < 10 ? "0" : "") : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds);
			}
			return duration;
		},

		getReadableDate : function ( field ) {

			var updated_at = this.get( field );

			if ( updated_at ) {

				var date = new Date( updated_at );

				var hour = date.getHours();
				var min  = date.getMinutes();
				var year = date.getFullYear();
				var mon  = date.getMonth() + 1;
				var day  = date.getDate();
				var ampm = hour >= 12 ? 'pm' : 'am';

				hour = hour % 12;
				hour = hour ? hour : 12;

				min = min < 10 ? '0' + min : min;

				var readableDate = year + '/' + mon + '/' + day + ' - ' + hour + ':' + min + ' ' + ampm;
				return readableDate;
			}
			return updated_at;
		},

		successFunction : function ( message ) {
			wpbc.broadcast.trigger( 'videoEdit:message', message, 'success' );
			wpbc.broadcast.trigger( 'spinner:off' );
			if ( _.isArray( this.get( 'video_ids' ) ) && wpbc.preload && wpbc.preload.playlists ) {
				var id = this.get( 'id' );
				_.each( wpbc.preload.playlists, function ( playlist, index ) {
					if ( playlist.id === id ) {
						wpbc.preload.playlists[index] = this.toJSON();
					}
				}, this );
			}
			wpbc.responses = {};
			if ( 'videos' === this.get( 'mediaType' ) || ! _.isUndefined( this.get( 'video_ids' ) ) ) {
				wpbc.preload.videos = undefined;
			} else {
				wpbc.preload.playlists = undefined;
			}
		},

		failFunction : function ( message ) {
			wpbc.broadcast.trigger( 'videoEdit:message', message, 'error' );
			wpbc.broadcast.trigger( 'spinner:off' );
		}
	}
);

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


/**
 * Media model for Media CPT
 */

var BrightcoveModalModel = Backbone.Model.extend(
	{

		getMediaManagerSettings : function () {
			var tab      = this.get( 'tab' );
			var settings = {
				'upload' :    {
					accounts :  'all',
					date :      'all',
					embedType : 'modal',
					mediaType : 'videos',
					mode :      'uploader',
					preload :   true,
					search :    '',
					tags :      'all',
					viewType :  'grid',
					poster:     {},
					thumbnail:  {}
				},
				'videos' :    {
					accounts :  'all',
					date :      'all',
					embedType : 'modal',
					mediaType : 'videos',
					mode :      'manager',
					preload :   true,
					search :    '',
					tags :      'all',
					viewType :  'grid'
				},
				'playlists' : {
					accounts :  'all',
					date :      'all',
					embedType : 'modal',
					mediaType : 'playlists',
					mode :      'manager',
					preload :   true,
					search :    '',
					tags :      'all',
					viewType :  'grid'
				}
			};

			if ( undefined !== settings[tab] ) {
				return settings[tab];
			}
			return false;

		}

	}
);


/**
 * Collection model to contain pending uploads
 */

var UploadModelCollection = Backbone.Collection.extend(
	{

		initialize : function ( options ) {
			this.listenTo( wpbc.broadcast, 'uploader:queuedFilesAdded', this.queuedFilesAdded );
		},

		queuedFilesAdded : function ( queuedFiles ) {
			_.each( queuedFiles, function ( queuedFile ) {
				this.add( new UploadModel( queuedFile ) );
			}, this );
		}

	}
);


/**
 * Model to contain pending upload
 */

var UploadModel = Backbone.Model.extend(
	{

		initialize : function ( options ) {
		},

		humanReadableSize : function () {
			var bytes = this.get( 'size' );
			if ( bytes === 0 ) {
				return '0 Byte';
			}
			var k     = 1000;
			var sizes = ['Bytes', 'KB', 'MB', 'GB'];
			var i     = Math.floor( Math.log( bytes ) / Math.log( k ) );
			return (bytes / Math.pow( k, i )).toPrecision( 3 ) + ' ' + sizes[i];
		}

	}
);

var BrightcoveView = wp.Backbone.View.extend(
	{
		subviews : null,

		registerSubview : function ( view ) {

			this.subviews = this.subviews || [];
			this.subviews.push( view );

		},

		remove : function () {

			_.invoke( this.subviews, 'remove' );
			wp.Backbone.View.prototype.remove.call( this );

		},

		insertShortcode : function () {

			if ( ! this.model ) {
				return;
			}

			var shortcode = wpbc.shortcode;

            if ( undefined === this.mediaType ) {
				var template = wp.template( 'brightcove-mediatype-notice' );

				// Throw a notice to the user that the file is not the correct format
				$( '#lost-connection-notice' ).before( template );

				// Allow the user to dismiss the notice
				$( '#js-mediatype-dismiss' ).on( 'click', function() {
					$( '#js-mediatype-notice' ).first().fadeOut( 500, function() {
						$( this ).remove();
					} );
				} );
			}

			if( wpbc.modal.target === 'content' ) {
				window.send_to_editor( shortcode );
			} else {
				$( wpbc.modal.target ).val( shortcode );
				$( wpbc.modal.target ).change();
			}

			wpbc.broadcast.trigger( 'close:modal' );
		}
	}
);

/**
 * This is the toolbar to handle sorting, filtering, searching and grid/list view toggles.
 * State is captured in the brightcove-media-manager model.
 */
var ToolbarView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'media-toolbar wp-filter',
		template :  wp.template( 'brightcove-media-toolbar' ),

		events : {
			'click .view-list' :                   'toggleList',
			'click .view-grid' :                   'toggleGrid',
			'click .brightcove-toolbar':           'toggleToolbar',
			'change .brightcove-media-source' :    'sourceChanged',
			'change .brightcove-media-dates' :     'datesChanged',
			'change .brightcove-media-tags' :      'tagsChanged',
			'change .brightcove-empty-playlists' : 'emptyPlaylistsChanged',
			'click #media-search' : 'searchHandler'
		},

		render : function () {
			var mediaType = this.model.get( 'mediaType' );
			var options   = {
				accounts :  wpbc.preload.accounts,
				dates :     {},
				mediaType : mediaType,
				tags :      wpbc.preload.tags,
				account :   this.model.get( 'account' )
			};

			var dates    = wpbc.preload.dates;
			var date_var = this.model.get( 'date' );
			/* @todo: find out if this is working */
			if ( dates !== undefined && dates[mediaType] !== undefined && dates[mediaType][date_var] !== undefined ) {
				options.dates = dates[mediaType][date_var];
			}

			this.$el.html( this.template( options ) );
			var spinner = this.$el.find( '.spinner' );
			this.listenTo( wpbc.broadcast, 'spinner:on', function () {
				spinner.addClass( 'is-active' ).removeClass( 'hidden' );
			} );
			this.listenTo( wpbc.broadcast, 'spinner:off', function () {
				spinner.removeClass( 'is-active' ).addClass( 'hidden' );
			} );
		},

		// List view Selected
		toggleList : function () {
			this.trigger( 'viewType', 'list' );
			this.$el.find( '.view-list' ).addClass( 'current' );
			this.$el.find( '.view-grid' ).removeClass( 'current' );
		},

		// Grid view Selected
		toggleGrid : function () {
			this.trigger( 'viewType', 'grid' );
			this.$el.find( '.view-grid' ).addClass( 'current' );
			this.$el.find( '.view-list' ).removeClass( 'current' );
		},

		// Toggle toolbar help
		toggleToolbar : function () {
			var template = wp.template( 'brightcove-tooltip-notice' );

			// Throw a notice to the user that the file is not the correct format
			$( '.brightcove-media-videos' ).before( template );
			// Allow the user to dismiss the notice
			$( '#js-tooltip-dismiss' ).on( 'click', function() {
				$( '#js-tooltip-notice' ).first().fadeOut( 500, function() {
					$( this ).remove();
				} );
			} );
		},

		// Brightcove source changed
		sourceChanged : function ( event ) {

			// Store the currently selected account on the model.
			this.model.set( 'account', event.target.value );
			wpbc.broadcast.trigger( 'change:activeAccount', event.target.value );
		},

		datesChanged : function ( event ) {
			wpbc.broadcast.trigger( 'change:date', event.target.value );
		},

		tagsChanged : function ( event ) {
			wpbc.broadcast.trigger( 'change:tag', event.target.value );
		},

		emptyPlaylistsChanged : function ( event ) {
			var emptyPlaylists = $( event.target ).prop( 'checked' );
			wpbc.broadcast.trigger( 'change:emptyPlaylists', emptyPlaylists );
		},

		searchHandler : function ( event ) {
			var searchTerm = $( '#media-search-input' ).val();

			if ( searchTerm.length > 2 && searchTerm !== this.model.get( 'search' ) ) {
				this.model.set( 'search', searchTerm );
				wpbc.broadcast.trigger( 'change:searchTerm', searchTerm );
			}
		}
	}
);


var UploadVideoManagerView = BrightcoveView.extend(
	{
		className : "brightcove-file-uploader",

		events : {
			'click .brightcove-start-upload' : 'triggerUpload'
		},

		initialize : function ( options ) {
			/**
			 * If you're looking for the Plupload instance, you're in the wrong place, check the UploadWindowView
			 */
			this.collection = new UploadModelCollection();
			if ( options ) {
				this.options = options;

				this.successMessage = options.successMessage || this.successMessage;
			}

			this.uploadWindow = new UploadWindowView();

			this.listenTo( this.collection, 'add', this.fileAdded );
			this.listenTo( wpbc.broadcast, 'pendingUpload:selectedItem', this.selectedItem );
			this.listenTo( wpbc.broadcast, 'uploader:prepareUpload', this.prepareUpload );
			this.listenTo( wpbc.broadcast, 'uploader:successMessage', this.successMessage );
			this.listenTo( wpbc.broadcast, 'uploader:errorMessage', this.errorMessage );
			this.listenTo( wpbc.broadcast, 'uploader:clear', this.resetUploads );
			this.listenTo( wpbc.broadcast, 'upload:video', this.resetUploads );
		},

		resetUploads : function () {
			while ( model = this.collection.first() ) {
				this.collection.remove( model );
			}
		},

		errorMessage : function ( message ) {
			this.message( message, 'error' );
		},

		successMessage : function ( message ) {
			this.message( message, 'success' );
		},

		message : function ( message, type ) {
			var messages       = this.$el.find( '.brightcove-messages' );
			var messageClasses = '';
			if ( 'success' === type ) {
				messageClasses = 'notice updated';
			} else if ( 'error' === type ) {
				messageClasses = 'error';
			}
			var newMessage = $( '<div class="wrap"><div class="brightcove-message"><p class="message-text"></p></div></div>' );
			messages.append( newMessage );
			newMessage.addClass( messageClasses ).find( '.message-text' ).text( message );
			newMessage.delay( 4000 ).fadeOut( 500, function () {
				$( this ).remove();
				wpbc.broadcast.trigger('upload:video');
			} );
		},

		prepareUpload : function () {
			wpbc.uploads = wpbc.uploads || {};
			this.collection.each( function ( upload ) {
				wpbc.uploads[upload.get( 'id' )] = {
					account : upload.get( 'account' ),
					name :    upload.get( 'fileName' ),
					tags :    upload.get( 'tags' )
				};
			} );
			wpbc.broadcast.trigger( 'uploader:startUpload' );
		},

		fileAdded : function ( model, collection ) {
			// Start upload triggers progress bars under every video.
			// Need to re-render when one model is added
			if ( this.collection.length === 1 ) {
				this.render();
			}
			var pendingUpload = new UploadView( {model : model} );
			pendingUpload.render();
			pendingUpload.$el.appendTo( this.$el.find( '.brightcove-pending-uploads' ) );
		},

		triggerUpload : function () {
			wpbc.broadcast.trigger( 'uploader:prepareUpload' );
		},

		selectedItem : function ( model ) {
			this.uploadDetails = new UploadDetailsView( {model : model} );
			this.uploadDetails.render();
			this.$el.find( '.brightcove-pending-upload-details' ).remove();
			this.uploadDetails.$el.appendTo( this.$el.find( '.brightcove-upload-queued-files' ) );
		},

		render : function ( options ) {
			if ( this.collection.length ) {
				this.template = wp.template( 'brightcove-uploader-queued-files' );
			} else {
				this.template = wp.template( 'brightcove-uploader-inline' );
				this.uploadWindow.render();
				this.uploadWindow.$el.appendTo( $( 'body' ) );
			}
			this.$el.html( this.template( options ) );
			if ( this.collection.length ) {
				this.$el.find( '.brightcove-start-upload' ).show();
			} else {
				this.$el.find( '.brightcove-start-upload' ).hide();
			}
		}
	}
);

var BrightcoveRouter = Backbone.Router.extend({
	routes: {
		'add-new-brightcove-video' : "addNew"
	},
	addNew: function() {
		wpbc.broadcast.trigger('upload:video');
	}
});

var BrightcoveMediaManagerView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'brightcove-media',

		events : {
			/*
			 'click .brightcove.media-button': 'insertIntoPost'
			 */
		},

		scrollHandler : function () {
			wpbc.broadcast.trigger( 'scroll:mediaGrid' );
		},

		initialize : function ( options ) {

			var scrollRefreshSensitivity = wp.media.isTouchDevice ? 300 : 200;
			this.scrollHandler           = _.chain( this.scrollHandler ).bind( this ).throttle( scrollRefreshSensitivity ).value();
			this.options                 = options;
			this.mode                    = options.mode || 'manager';

			options.preload = this.options.preload ? wpbc.preload[this.options.mediaType] : false;

			this.model = new BrightcoveMediaManagerModel( options );

			/* Search and dropdowns */
			this.toolbar = new ToolbarView( {model : this.model} );

			/* Uploader View */
			this.uploader = new UploadVideoManagerView();

			this.model.set( 'accounts', wpbc.preload.accounts ); // All accounts.
			this.model.set( 'activeAccount', options.account ); // Active account ID / All

			this.listenTo( this.toolbar, 'viewType', function ( viewType ) {
				this.model.set( 'view', viewType ); // Set the model view type
			} );

			this.listenTo( wpbc.broadcast, 'videoEdit:message', this.message );
			this.listenTo( wpbc.broadcast, 'permanent:message', this.permanentMessage );

			this.listenTo( wpbc.broadcast, 'remove:permanentMessage', function () {

				if ( wpbc.permanentMessage ) {
					wpbc.permanentMessage.remove();
				}

				this.$el.find( '.brightcove-message' ).addClass( 'hidden' );

			} );

			// We only care when a change occurs
			this.listenTo( this.model, 'change:view', function ( model, type ) {
				this.model.get( 'media-collection-view' ).setViewType( type );
			} );

			this.listenTo( this.model, 'change:mode', function ( model, mode ) {

				if ( 'uploader' !== mode ) {
					wpbc.broadcast.trigger( 'uploader:clear' );
				}

			} );

			this.listenTo( wpbc.broadcast, 'cancelPreview:media', function ( settings ) {
				this.clearPreview();
				this.detailsView = undefined;
				this.model.set( 'mode', 'manager' );
				this.render();

				// Disable "Insert Into Post" button since no video would be selected.
				wpbc.broadcast.trigger( 'toggle:insertButton' );
			} );

			this.listenTo( wpbc.broadcast, 'change:emptyPlaylists', function ( hideEmptyPlaylists ) {

				var mediaCollectionView = this.model.get( 'media-collection-view' );
				this.model.set( 'mode', 'manager' );

				_.each( mediaCollectionView.collection.models, function ( playlistModel ) {

					// Don't hide smart playlists. Only Manual playlists will have playlistType as 'EXPLICIT'.
					if ( 'EXPLICIT' !== playlistModel.get ( 'type' ) ) {
						return;
					}

					// Manual play list will have videos populated in video_ids. Empty playlists will have zero video_ids.
					if ( playlistModel.get( 'video_ids' ).length === 0 ) {
						if ( hideEmptyPlaylists ) {
							playlistModel.view.$el.hide();
						} else {
							playlistModel.view.$el.show();
						}
					}
				} );
			} );

			this.listenTo( wpbc.broadcast, 'delete:successful', function ( message ) {

				this.startGridView();
				this.message( message, 'success' );

			} );

			this.listenTo( wpbc.broadcast, 'change:activeAccount', function ( accountId ) {

				this.clearPreview();
				this.model.set( 'activeAccount', accountId );
				this.model.set( 'mode', 'manager' );
				this.render();

			} );

			this.listenTo( wpbc.broadcast, 'change:tag', function ( tag ) {

				this.clearPreview();
				this.model.set( 'tag', tag );

			} );

			this.listenTo( wpbc.broadcast, 'change:date', function ( date ) {

				this.clearPreview();
				this.model.set( 'date', date );

			} );

			this.listenTo( wpbc.broadcast, 'upload:video', function () {
				this.showUploader();
			} );

			this.listenTo( this.model, 'change:search', function ( model, searchTerm ) {
				this.model.get( 'search' );
			} );

			this.listenTo( wpbc.broadcast, 'start:gridview', function () {

				_.invoke( this.subviews, 'remove' );

				this.detailsView = null; // Prevent selected view from not being toggleable when we hit the back button on edit

				this.startGridView();

			} );

			this.listenTo( wpbc.broadcast, 'tabChange', function ( settings ) {

				this.model.set( settings );

				if ( this.detailsView instanceof MediaDetailsView ) {

					this.detailsView.remove();

					this.detailsView = undefined;

				}

				this.render();

			} );

			this.listenTo( wpbc.broadcast, 'edit:media', function ( model ) {

				var mediaType = this.model.get( 'mediaType' );

				if ( mediaType === 'videos' ) {

					// We just hit the edit button with the edit window already open.
					if ( 'editVideo' === this.model.get( 'mode' ) ) {
						return true;
					}

					// hide the previous notification
					var messages = this.$el.find( '.brightcove-message' );
					messages.addClass( 'hidden' );

					this.editView = new VideoEditView( {model : model} );

					this.registerSubview( this.editView );
					this.model.set( 'mode', 'editVideo' );
					this.render();

				} else {

					// We just hit the edit button with the edit window already open.
					if ( 'editPlaylist' === this.model.get( 'mode' ) ) {
						return true;
					}

					this.editView = new PlaylistEditView( {model : model} );

					this.registerSubview( this.editView );
					this.model.set( 'mode', 'editPlaylist' );
					this.render();

				}
			} );

			this.listenTo( wpbc.broadcast, 'preview:media', function ( model, shortcode ) {

				var mediaType = this.model.get( 'mediaType' );

				if ( mediaType === 'videos' ) {

					// We just hit the preview button with the preview window already open.
					if ( 'previewVideo' === this.model.get( 'mode' ) ) {
						return true;
					}

					this.previewView = new VideoPreviewView( {model : model, shortcode: shortcode} );

					this.registerSubview( this.previewView );
					this.model.set( 'mode', 'previewVideo' );
					this.render();

				} else {

					/**
					 * @todo: playlist preview view
					 */
					this.model.set( 'mode', 'editPlaylist' );

				}
			} );

			this.listenTo( wpbc.broadcast, 'change:searchTerm', function ( mediaView ) {
				this.clearPreview();
			} );

			this.listenTo( wpbc.broadcast, 'select:media', function ( mediaView ) {

				/* If user selects same thumbnail they want to hide the details view */
				if ( this.detailsView && this.detailsView.model === mediaView.model ) {

					this.detailsView.$el.toggle();
					mediaView.$el.toggleClass( 'highlighted' );
					this.model.get( 'media-collection-view' ).$el.toggleClass( 'menu-visible' );
					wpbc.broadcast.trigger( 'toggle:insertButton' );

				} else {

					this.clearPreview();
					this.detailsView = new MediaDetailsView( {model : mediaView.model, el : $( '.brightcove.media-frame-menu' ), mediaType : this.model.get( 'mediaType' )} );
					this.registerSubview( this.detailsView );

					this.detailsView.render();
					this.detailsView.$el.toggle( true ); // Always show new view
					this.model.get( 'media-collection-view' ).$el.addClass( 'menu-visible' );
					mediaView.$el.addClass( 'highlighted' );
					wpbc.broadcast.trigger( 'toggle:insertButton', 'enabled' );

				}
			} );

		},

		/**
		 * Clear the preview view and remove highlighted class from previous selected video.
		 */
		clearPreview : function () {

			if ( this.detailsView instanceof MediaDetailsView ) {
				this.detailsView.remove();
			}

			this.model.get( 'media-collection-view' ).$el.find( '.highlighted' ).removeClass( 'highlighted' );

		},

		startGridView : function () {

			this.model.set( 'mode', 'manager' );
			this.render();

		},

		message : function ( message, type, permanent ) {

			var messages = this.$el.find( '.brightcove-message' );

			if ( 'success' === type ) {

				messages.addClass( 'updated' );
				messages.removeClass( 'error' );

			} else if ( 'error' === type ) {

				messages.addClass( 'error' );
				messages.removeClass( 'updated' );

			}

			var newMessage = $( '<p></p>' );
			newMessage.text( message );

			messages.append( newMessage );
			messages.removeClass( 'hidden' );

			if ( permanent ) {

				if ( wpbc.permanentMessage ) {
					wpbc.permanentMessage.remove();
				}

				wpbc.permanentMessage = newMessage;

			} else {
				// Make the notice dismissable.
				messages.addClass( 'notice is-dismissible' );
				this.makeNoticesDismissible();
			}
		},

		// Make notices dismissible, mimics core function, fades them empties.
		makeNoticesDismissible : function() {
			$( '.notice.is-dismissible' ).each( function() {
				var $el = $( this ),
					$button = $( '<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>' ),
					btnText = commonL10n.dismiss || '';

				// Ensure plain text
				$button.find( '.screen-reader-text' ).text( btnText );
				$button.on( 'click.wp-dismiss-notice', function( event ) {
					event.preventDefault();
					$el.fadeTo( 100, 0, function() {
						$el.slideUp( 100, function() {
							$el.addClass( 'hidden' )
								.css( {
									'opacity': 1,
									'margin-bottom': 0,
									'display': ''
								} )
								.empty();
						});
					});
				});

				$el.append( $button );
			});
		},

		showUploader : function () {

			this.model.set( 'mode', 'uploader' );
			this.render();

		},

		permanentMessage : function ( message ) {
			this.message( message, 'error', true );
		},

		render : function () {

			var options = this.model.get( 'options' );
			var contentContainer;

			var mode = this.model.get( 'mode' );

			// Nuke all registered subviews
			_.invoke( this.subviews, 'remove' );

			if ( 'uploader' === mode ) {

				this.template = wp.template( 'brightcove-uploader-container' );

				this.$el.empty();
				this.$el.html( this.template( options ) );
				this.uploader.render();
				this.uploader.delegateEvents();
				this.uploader.$el.appendTo( $( '.brightcove-uploader' ) );

			} else if ( 'manager' === mode ) {

				this.template = wp.template( 'brightcove-media' );

				this.$el.html( this.template( options ) );
				this.toolbar.render();
				this.toolbar.delegateEvents();
				this.toolbar.$el.show();
				this.toolbar.$el.appendTo( this.$el.find( '.media-frame-router' ) );

				// Add the Media views to the media manager
				var mediaCollectionView = this.model.get( 'media-collection-view' );

				mediaCollectionView.render();
				mediaCollectionView.delegateEvents();

				var mediaCollectionContainer = this.$el.find( '.media-frame-content' );

				mediaCollectionContainer.on( 'scroll', this.scrollHandler );
				mediaCollectionView.$el.appendTo( mediaCollectionContainer );

				if ( ! ! wpbc.initialSync ) {

					wpbc.broadcast.trigger( 'remove:permanentMessage' );
					wpbc.broadcast.trigger( 'permanent:message', wpbc.preload.messages.ongoingSync );

				}
			} else if ( 'editVideo' === mode ) {

				this.toolbar.$el.hide();

				contentContainer = this.$el.find( '.media-frame-content' );

				contentContainer.empty();
				this.editView.render();
				this.editView.delegateEvents();
				this.editView.$el.appendTo( contentContainer );
				this.$el.find( '.brightcove.media-frame-content' ).addClass( 'edit-view' );

			} else if ( 'editPlaylist' === mode ) {

				this.toolbar.$el.hide();

				contentContainer = this.$el;

				contentContainer.empty();
				contentContainer.html( '<div class="playlist-edit-container"></div>' );

				contentContainer = contentContainer.find( '.playlist-edit-container' );

				this.editView.render();
				this.editView.delegateEvents();
				this.editView.$el.appendTo( contentContainer );
				contentContainer.addClass( 'playlist' );

			} else if ( 'previewVideo' === mode ) {

				this.toolbar.$el.hide();

				contentContainer = this.$el.find( '.media-frame-content' );

				contentContainer.empty();
				this.previewView.render();
				this.detailsView.render( {detailsMode : 'preview'} );
				this.previewView.delegateEvents();
				this.previewView.$el.appendTo( contentContainer );
				this.$el.find( '.brightcove.media-frame-toolbar' ).hide();
				brightcove.createExperiences();

			}

			if ( 'editPlaylist' !== mode ) {
				this.$el.find( '.media-frame-content' ).removeClass( 'playlist' );
			}

			return this;

		}

	}
);

var BrightcoveModalView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'media-modal brightcove',
		template :  wp.template( 'brightcove-media-modal' ),

		events : {
			'click .brightcove.media-menu-item'     : 'changeTab',
			'click .brightcove.media-button-insert' : 'insertIntoPost',
			'click .media-modal-close'              : 'closeModal',
			'click .brightcove.save-sync'           : 'saveSync',
			'click .brightcove.button.back'         : 'back'
		},

		initialize : function ( options ) {
			this.model                  = new BrightcoveModalModel( {tab : options.tab} );
			this.brightcoveMediaManager = new BrightcoveMediaManagerView( this.model.getMediaManagerSettings() );
			this.registerSubview( this.brightcoveMediaManager );
			this.listenTo( wpbc.broadcast, 'toggle:insertButton', function ( state ) {
				this.toggleInsertButton( state );
			} );
			this.listenTo( wpbc.broadcast, 'close:modal', this.closeModal );
		},

		saveSync : function( evnt ) {
			// This event is triggered when the "Save and Sync Changes" button is clicked from edit video screen.
			wpbc.broadcast.trigger( 'save:media', evnt );
		},

		back : function( evnt ) {
			// This event is triggered when the "Back" button is clicked from edit video screen.
			wpbc.broadcast.trigger( 'back:editvideo', evnt );
		},

		insertIntoPost : function ( evnt ) {
			evnt.preventDefault();

			// Exit if the 'button' is disabled.
			if ( $( evnt.currentTarget ).hasClass( 'disabled' ) ) {
				return;
			}

			wpbc.shortcode = $( '#shortcode' ).val();

			// Media Details will trigger the insertion since it's always active and contains
			// the model we're inserting
			wpbc.broadcast.trigger( 'insert:shortcode' );
		},

		toggleInsertButton : function ( state ) {
			var button     = this.$el.find( '.brightcove.media-button-insert' ),
				processing = $('.attachment.highlighted' ).find( '.processing' ).length;

			button.show();

			if ( 1 === processing ) {
				button.attr( 'disabled', 'disabled' );
			} else if ( 'enabled' === state ) {
				button.removeAttr( 'disabled' );
			} else if ( 'disabled' === state ) {
				button.attr( 'disabled', 'disabled' );
			} else if ( undefined !== button.attr( 'disabled' ) ) {
				button.removeAttr( 'disabled' );
			} else {
				button.attr( 'disabled', 'disabled' );
			}
		},

		changeTab : function ( event ) {
			event.preventDefault();

			if ( $( event.target ).hasClass( 'active' ) ) {
				return; // Clicking the already active tab
			}
			$( event.target ).addClass( 'active' );
			var tab  = _.without( event.target.classList, 'media-menu-item', 'brightcove' )[0];
			var tabs = ['videos', 'upload', 'playlists'];
			_.each( _.without( tabs, tab ), function ( otherTab ) {
				$( '.brightcove.media-menu-item.' + otherTab ).removeClass( 'active' );
			} );

			if ( _.contains( tabs, tab ) ) {
				this.model.set( 'tab', tab );
				wpbc.broadcast.trigger( 'spinner:off' );
				wpbc.broadcast.trigger( 'tabChange', this.model.getMediaManagerSettings() );
			}

		},

		closeModal : function ( evnt ) {

			// If we are in the editVideo mode, switch back to the video view.
			if ( 'editVideo' === wpbc.modal.brightcoveMediaManager.model.get('mode') ) {
				wpbc.broadcast.trigger( 'start:gridview' );
			}

			// Exit if the container button is disabled.
			if ( ! _.isUndefined( evnt ) && $( evnt.currentTarget ).parent().hasClass( 'disabled' ) ) {
				return;
			}
			this.$el.hide();
			$( 'body' ).removeClass( 'modal-open' );
		},

		message : function ( message ) {
			var messageContainer = this.$el.find( '.brightcove-message' );

		},

		render : function ( options ) {
			this.$el.html( this.template( options ) );

			this.brightcoveMediaManager.render();
			this.brightcoveMediaManager.$el.appendTo( this.$el.find( '.media-frame-content' ) );

			this.listenTo( wpbc.broadcast, 'edit:media', function( model, mediaType ) {
				if ( 'videos' === mediaType ) {
					// When edit Video screen is opened, hide the "Insert Into Post" button and show video save button.
					this.$el.find( '.brightcove.button.save-sync' ).show();
					this.$el.find( '.brightcove.button.back' ).show();
					this.$el.find( '.brightcove.media-button-insert' ).hide();
				} else {
					// When edit playlist screen is opened, hide all the buttons.
					this.$el.find( '.brightcove.button.save-sync' ).hide();
					this.$el.find( '.brightcove.button.back' ).hide();
					this.$el.find( '.brightcove.media-button-insert' ).hide();
				}
			} );

			this.listenTo( wpbc.broadcast, 'save:media back:editvideo start:gridView', function() {
				this.$el.find( '.brightcove.button.save-sync' ).hide();
				this.$el.find( '.brightcove.button.back' ).hide();
				this.$el.find( '.brightcove.media-button-insert' ).show();
				wpbc.broadcast.trigger( 'toggle:insertButton' );
			} );
		}

	}
);


var MediaDetailsView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'media-details',

		attributes : function () {
			return {
				'tabIndex' :     0,
				'role' :         'checkbox',
				'aria-label' :   this.model.get( 'title' ),
				'aria-checked' : false,
				'data-id' :      this.model.get( 'id' )
			};
		},

		events : {
			'click .brightcove.edit.button' :    'triggerEditMedia',
			'click .brightcove.preview.button' : 'triggerPreviewMedia',
			'click .brightcove.back.button' :    'triggerCancelPreviewMedia',
			'click .playlist-details input[name="embed-style"]' :  'togglePlaylistSizing',
            'change #aspect-ratio' : 'toggleUnits',
            'change #video-player, #autoplay, input[name="embed-style"], input[name="sizing"], #aspect-ratio, #width, #height' : 'generateShortcode',
			'change #generate-shortcode' : 'toggleShortcodeGeneration',
		},

		triggerEditMedia : function ( event ) {
			event.preventDefault();
			wpbc.broadcast.trigger( 'edit:media', this.model, this.mediaType );
		},

		triggerPreviewMedia : function ( event ) {
			event.preventDefault();
			var shortcode = $( '#shortcode' ).val();
			wpbc.broadcast.trigger( 'preview:media', this.model, shortcode );
		},

		triggerCancelPreviewMedia : function ( event ) {
			wpbc.broadcast.trigger( 'cancelPreview:media', this.mediaType );
		},

		togglePlaylistSizing: function( event ) {
			var embedStyle = $( '.playlist-details input[name="embed-style"]:checked' ).val(),
				$sizing = $( '#sizing-fixed, #sizing-responsive' );

			if ( 'iframe' === embedStyle ) {
				$sizing.removeAttr( 'disabled' );
			} else {
				$sizing.attr( 'disabled', true );
			}
		},

		toggleUnits: function( event ) {
			var value = $( '#aspect-ratio' ).val();

			if ( 'custom' === value ) {
				$( '#height' ).removeAttr( 'readonly' );
			} else {
				var $height = $( '#height' ),
					width = $( '#width' ).val();

				$height.attr( 'readonly', true );

				if ( width > 0 ) {
					if ( '16:9' === value ) {
						$height.val( width/( 16/9 ) );
					} else {
						$height.val( width/( 4/3 ) );
					}
				}
			}
		},

		generateShortcode: function () {
			if ( 'videos' === this.mediaType ) {
				this.generateVideoShortcode();
			} else {
				this.generatePlaylistShortcode();
			}
		},

		generateVideoShortcode: function () {
			var videoId = this.model.get( 'id' ).replace( /\D/g, '' ),
				accountId = this.model.get( 'account_id' ).replace( /\D/g, '' ),
				playerId = $( '#video-player' ).val(),
				autoplay = ( $( '#autoplay' ).is( ':checked' ) ) ? 'autoplay': '',
				embedStyle = $( 'input[name="embed-style"]:checked' ).val(),
				sizing = $( 'input[name="sizing"]:checked' ).val(),
				aspectRatio = $( '#aspect-ratio' ).val(),
				paddingTop = '',
				width = $( '#width' ).val(),
				height = $( '#height' ).val(),
				units = 'px',
				minWidth = '0px',
				maxWidth = width + units,
				shortcode;

			if ( '16:9' === aspectRatio ) {
				paddingTop = '56';
			} else if ( '4:3' === aspectRatio ) {
				paddingTop = '75';
			} else {
				paddingTop = ( ( height / width ) * 100 );
			}

			if ( 'responsive' === sizing ) {
				width = '100%';
				height = '100%';
			} else {
				width = width + units;
				height = height + units;

				if ( 'iframe' === embedStyle ) {
					minWidth = width;
				}
			}

			shortcode = '[bc_video video_id="' + videoId + '" account_id="' + accountId + '" player_id="' + playerId + '" ' +
				'embed="' + embedStyle + '" padding_top="' + paddingTop + '%" autoplay="' + autoplay + '" ' +
				'min_width="' + minWidth + '" max_width="' + maxWidth + '" ' +
				'width="' + width + '" height="' + height + '"' +
				']';

			$( '#shortcode' ).val( shortcode );
		},

		generatePlaylistShortcode: function () {
		    var playlistId = this.model.get( 'id' ).replace( /\D/g, '' ),
                accountId = this.model.get( 'account_id' ).replace( /\D/g, '' ),
				playerId = $( '#video-player' ).val(),
				autoplay = ( $( '#autoplay' ).is( ':checked' ) ) ? 'autoplay': '',
				embedStyle = $( 'input[name="embed-style"]:checked' ).val(),
                sizing = $( 'input[name="sizing"]:checked' ).val(),
				aspectRatio = $( '#aspect-ratio' ).val(),
				paddingTop = '',
				width = $( '#width' ).val(),
				height = $( '#height' ).val(),
			    units = 'px',
			    minWidth = '0px;',
			    maxWidth = width + units,
				shortcode;

		    if ( 'in-page-vertical' === embedStyle ) {
			    shortcode = '[bc_playlist playlist_id="' + playlistId + '" account_id="' + accountId + '" player_id="' + playerId + '" ' +
				    'embed="in-page-vertical" autoplay="' + autoplay + '" ' +
				    'min_width="" max_width="" padding_top="" ' +
				    'width="' + width + units + '" height="' + height + units + '"' +
				    ']';
		    } else if ( 'in-page-horizontal' === embedStyle ) {
			    shortcode = '[bc_playlist playlist_id="' + playlistId + '" account_id="' + accountId + '" player_id="' + playerId + '" ' +
				    'embed="in-page-horizontal" autoplay="' + autoplay + '" ' +
				    'min_width="" max_width="" padding_top="" ' +
				    'width="' + width + units + '" height="' + height + units + '"' +
				    ']';
		    } else if ( 'iframe' === embedStyle ) {
			    if ( '16:9' === aspectRatio ) {
				    paddingTop = '56';
			    } else if ( '4:3' === aspectRatio ) {
				    paddingTop = '75';
			    } else {
				    paddingTop = ( ( height / width ) * 100 );
			    }

			    if ( 'responsive' === sizing ) {
				    width = '100%';
				    height = '100%';
			    } else {
			    	width = width + units;
			    	height = height + units;

					minWidth = width;
			    }

			    shortcode = '[bc_playlist playlist_id="' + playlistId + '" account_id="' + accountId + '" player_id="' + playerId + '" ' +
				    'embed="iframe" autoplay="' + autoplay + '" ' +
				    'min_width="' + minWidth + '" max_width="' + maxWidth + '" padding_top="' + paddingTop + '%" ' +
				    'width="' + width + '" height="' + height + '"' +
				    ']';
		    }

		    $( '#shortcode' ).val( shortcode );
        },

		toggleShortcodeGeneration: function () {
		    var method = $( '#generate-shortcode' ).val(),
                $fields = $( '#video-player, #autoplay, input[name="embed-style"], input[name="sizing"], #aspect-ratio, #width, #height, #units' );

		    if ( 'manual' === method ) {
		    	$( '#shortcode' ).removeAttr( 'readonly' );
                $fields.attr( 'disabled', true );
			} else {
                $( '#shortcode' ).attr( 'readonly', true );
                $fields.removeAttr( 'disabled' );
			}
        },

		initialize : function ( options ) {
			options        = options || {};
			this.type      = options.type ? options.type : 'grid';
			this.mediaType = options.mediaType;
			this.listenTo( wpbc.broadcast, 'insert:shortcode', this.insertShortcode );
			this.listenTo( this.model, 'change', this.render );
		},

		/**
		 * @returns {wp.media.view.Media} Returns itself to allow chaining
		 */
		render : function ( options ) {
			options                     = _.extend( {}, options, this.model.toJSON() );
			options.duration            = this.model.getReadableDuration();
			options.updated_at_readable = this.model.getReadableDate( 'updated_at' );
			options.created_at_readable = this.model.getReadableDate( 'created_at' );
			options.account_name        = this.model.getAccountName();

			this.template = wp.template( 'brightcove-media-item-details-' + this.mediaType );

			this.$el.html( this.template( options ) );

			this.delegateEvents();
            this.generateShortcode();

			return this;
		},

		/* Prevent this.remove() from removing the container element for the details view */
		remove : function () {
			this.undelegateEvents();
			this.$el.empty();
			this.stopListening();
			return this;
		}
	}
);


var MediaView = BrightcoveView.extend(
	{
		tagName :   'li',
		className : 'attachment brightcove',

		attributes : function () {
			return {
				'tabIndex' :     0,
				'role' :         'checkbox',
				'aria-label' :   this.model.get( 'title' ),
				'aria-checked' : false,
				'data-id' :      this.model.get( 'id' )
			};
		},

		events : {
			'click .attachment-preview' : 'toggleDetailView',
			'click .video-move-up' :      'videoMoveUp',
			'click .video-move-down' :    'videoMoveDown',
			'click .trash' :              'removeVideoFromPlaylist',
			'click .add-to-playlist' :    'videoAdd',
			'click .edit' :               'triggerEditMedia',
			'click .preview' :            'triggerPreviewMedia'
		},

		triggerEditMedia : function ( event ) {
			event.preventDefault();
			wpbc.broadcast.trigger( 'edit:media', this.model );
		},

		triggerPreviewMedia : function ( event ) {
			event.preventDefault();
			wpbc.broadcast.trigger( 'preview:media', this.model );
		},

		buttons : {},

		initialize : function ( options ) {
			options   = options || {};
			this.type = options.type ? options.type : 'grid';

			// We only care when a change occurs
			this.listenTo( this.model, 'change:view', function ( model, type ) {
				if ( this.type !== type ) {
					this.type = type;
					this.render();
				}
			} );

			this.render();
		},

		render : function () {
			var options                 = this.model.toJSON();
			options.duration            = this.model.getReadableDuration();
			options.updated_at_readable = this.model.getReadableDate( 'updated_at' );
			options.account_name        = this.model.getAccountName();

			if ( 'existingPlaylists' === options.viewType ) {
				this.template = wp.template( 'brightcove-playlist-edit-video-in-playlist' );
			} else if ( 'libraryPlaylists' === options.viewType ) {
				this.template = wp.template( 'brightcove-playlist-edit-video-in-library' );
			} else {
				this.template = wp.template( 'brightcove-media-item-' + this.type );
			}

			options.buttons = this.buttons;

			this.$el.html( this.template( options ) );

			this.$el.toggleClass( 'uploading', options.uploading );

			return this;
		},

		toggleDetailView : function () {
			wpbc.broadcast.trigger( 'select:media', this );
		},

		videoMoveUp : function () {
			wpbc.broadcast.trigger( 'playlist:moveUp', this );
		},

		videoMoveDown : function () {
			wpbc.broadcast.trigger( 'playlist:moveDown', this );
		},

		videoAdd : function () {
			wpbc.broadcast.trigger( 'playlist:add', this );
		},

		removeVideoFromPlaylist : function () {
			wpbc.broadcast.trigger( 'playlist:remove', this );
		}
	}
);

var PlaylistEditView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'playlist-edit brightcove attachment-details',
		template :  wp.template( 'brightcove-playlist-edit' ),

		events : {
			'click .brightcove.button.save-sync' : 'saveSync',
			'click .brightcove.playlist-back' :    'back',
			'change .brightcove-name' :            'updatedName'
		},

		deleteVideo : function ( event ) {
			event.preventDefault();
			this.model.set( 'mediaType', 'videos' );
			this.model.destroy();
		},

		updatedName : function ( event ) {
			var name = this.model.get( 'name' );
			if ( name !== event.target.value ) {
				this.model.set( 'name', event.target.value );
				this.model.save();
			}
		},

		back : function ( event ) {
			event.preventDefault();
			wpbc.broadcast.trigger( 'start:gridview' );

		},

		saveSync : function ( event ) {
			event.preventDefault();
			this.model.set( 'name', this.$el.find( '.brightcove-name' ).val() );
			this.model.set( 'description', this.$el.find( '.brightcove-description' ).val() );
			this.model.set( 'long_description', this.$el.find( '.brightcove-long-description' ).val() );
			this.model.set( 'tags', this.$el.find( '.brightcove-tags' ).val() );
			this.model.set( 'mediaType', 'videos' );
			this.model.save();
		},

		initialize : function () {
			this.listenTo( wpbc.broadcast, 'tabChange', function () {
				_.invoke( this.subviews, 'remove' );
			} );
			wpbc.broadcast.trigger( 'spinner:off' );
		},

		render : function ( options ) {
			options = this.model.toJSON();
			this.$el.html( this.template( options ) );
			this.spinner = this.$el.find( '.spinner' );

			if ( options.video_ids ) {
				this.killPendingRequests();

				this.playlistVideosView = new MediaCollectionView( {
					el : this.$el.find( '.existing-videos' ),
					videoIds : options.video_ids,
					activeAccount : this.model.get( 'account_id' ),
					mediaCollectionViewType : 'existingPlaylists',
					mediaType : 'playlists'
				} );

				this.libraryVideosView  = new MediaCollectionView( {
					el : this.$el.find( '.library-videos' ),
					excludeVideoIds : options.video_ids,
					activeAccount : this.model.get( 'account_id' ),
					mediaCollectionViewType : 'libraryPlaylists',
					mediaType : 'playlists'
				} );

				this.registerSubview( this.playlistVideosView );
				this.registerSubview( this.libraryVideosView );

				this.listenTo( wpbc.broadcast, 'playlist:changed', _.throttle( this.playlistChanged, 300 ) );
				this.listenTo( wpbc.broadcast, 'insert:shortcode', this.insertShortcode );
			}

			this.listenTo( wpbc.broadcast, 'spinner:on', function () {
				this.spinner.addClass( 'is-active' ).removeClass( 'hidden' );
			} );

			this.listenTo( wpbc.broadcast, 'spinner:off', function () {
				this.spinner.removeClass( 'is-active' ).addClass( 'hidden' );
			} );
		},

		playlistChanged : function ( videoIds ) {
			this.killPendingRequests();
			this.model.set( 'video_ids', videoIds );
			this.model.save();
		},

		killPendingRequests : function () {
			// Kill all pending requests
			_.each( wpbc.requests, function ( request ) {
				request.abort();
			} );

			wpbc.requests = [];
		}
	}
);

var UploadDetailsView = BrightcoveView.extend(
	{
		className : 'brightcove-pending-upload-details attachment-details',
		tagName :   'div',
		template :  wp.template( 'brightcove-pending-upload-details' ),

		events : {
			'keyup .brightcove-name' :          'nameChanged',
			'keyup .brightcove-tags' :          'tagsChanged',
			'change .brightcove-media-source' : 'accountChanged'
		},

		initialize : function ( options ) {
			this.listenTo( wpbc.broadcast, 'pendingUpload:hideDetails', this.hide );
			this.listenTo( wpbc.broadcast, 'uploader:fileUploaded', function ( file ) {
				if ( file.id === this.model.get( 'id' ) ) {
					this.model.set( 'uploaded', true );
					this.render();
				}
			} );
			this.model.set( 'ingestSuccess', true );
			this.model.set( 'uploadSuccess', true );
		},

		nameChanged : function ( event ) {
			this.model.set( 'fileName', event.target.value );
		},

		tagsChanged : function ( event ) {
			this.model.set( 'tags', event.target.value );
		},

		accountChanged : function ( event ) {
			this.model.set( 'account', event.target.value );
		},

		hide : function () {
			this.$el.hide();
		},

		render : function ( options ) {
			options          = options || {};
			options.fileName = this.model.get( 'fileName' );
			options.tags     = this.model.get( 'tags' );
			options.size     = this.model.humanReadableSize();
			options.accounts = this.model.get( 'accounts' );
			options.account  = this.model.get( 'account' );
			options.uploaded = this.model.get( 'uploaded' );
			this.$el.html( this.template( options ) );
		}

	}
);

UploadWindowView = BrightcoveView.extend(
	{
		className : 'uploader-window',
		template :  wp.template( 'brightcove-uploader-window' ),

		initialize : function ( options ) {
			_.bindAll( this, 'uploaderFilesAdded' );
			this.listenTo( wpbc.broadcast, 'uploader:queuedFilesAdded', this.hide );
			this.listenTo( wpbc.broadcast, 'uploader:startUpload', this.uploaderStartUpload );
			this.listenTo( wpbc.broadcast, 'uploader:clear', this.resetUploads );
		},

		render : function ( options ) {
			this.$el.html( this.template( options ) );
			_.defer( _.bind( this.afterRender, this ) );
		},

		resetUploads : function () {
			if ( this.uploader && this.uploader.files ) {
				this.uploader.files = []; // Reset pending uploads
			}
		},

		afterRender : function () {
			this.uploader = new plupload.Uploader( _.defaults( this.options, wpbc.preload.plupload ) );

			// Uploader has neither .on nor .listenTo
			this.uploader.added    = this.uploaderFilesAdded;
			this.uploader.progress = this.uploaderUploadProgress;
			this.uploader.bind( 'FilesAdded', this.uploaderFilesAdded );
			this.uploader.bind( 'UploadProgress', this.uploaderUploadProgress );
			this.uploader.bind( 'BeforeUpload', this.uploaderBeforeUpload );
			this.uploader.bind( 'FileUploaded', this.uploaderFileUploaded );

			this.uploader.bind( 'init', this.uploaderAfterInit );

			this.uploader.init();
			$( 'html' ).on( 'dragenter', _.bind( this.show, this ) );
			/* the following dropzone function code is taken from the wp.Uploader code */
			var drop_element = wpbc.preload.plupload.drop_element.replace( /[^a-zA-Z0-9-]+/g, '' );
			var dropzone     = $( '#' + drop_element );
			dropzone.on( 'dropzone:leave', _.bind( this.hide, this ) );
		},

		uploaderAfterInit : function ( uploader ) {
			var drop_element = wpbc.preload.plupload.drop_element.replace( /[^a-zA-Z0-9-]+/g, '' );
			var timer, active, dragdrop,
			    dropzone     = $( '#' + drop_element );

			dragdrop = uploader.features.dragdrop;

			// Generate drag/drop helper classes.
			if ( ! dropzone ) {
				return;
			}

			dropzone.toggleClass( 'supports-drag-drop', ! ! dragdrop );

			if ( ! dragdrop ) {
				return dropzone.unbind( '.wp-uploader' );
			}

			// 'dragenter' doesn't fire correctly, simulate it with a limited 'dragover'.
			dropzone.bind( 'dragover.wp-uploader', function () {
				if ( timer ) {
					clearTimeout( timer );
				}

				if ( active ) {
					return;
				}

				dropzone.trigger( 'dropzone:enter' ).addClass( 'drag-over' );
				active = true;
			} );

			dropzone.bind( 'dragleave.wp-uploader, drop.wp-uploader', function () {
				// Using an instant timer prevents the drag-over class from
				// being quickly removed and re-added when elements inside the
				// dropzone are repositioned.
				//
				// @see https://core.trac.wordpress.org/ticket/21705
				timer = setTimeout( function () {
					active = false;
					dropzone.trigger( 'dropzone:leave' ).removeClass( 'drag-over' );
				}, 0 );
			} );
		},

		show : function () {
			var $el = this.$el.show();

			// Ensure that the animation is triggered by waiting until
			// the transparent element is painted into the DOM.
			_.defer( function () {
				$el.css( {opacity : 1} );
			} );
		},

		hide : function () {
			var $el = this.$el.css( {opacity : 0} );

			wp.media.transition( $el ).done( function () {
				// Transition end events are subject to race conditions.
				// Make sure that the value is set as intended.
				if ( '0' === $el.css( 'opacity' ) ) {
					$el.hide();
				}
			} );

			// https://core.trac.wordpress.org/ticket/27341
			_.delay( function () {
				if ( '0' === $el.css( 'opacity' ) && $el.is( ':visible' ) ) {
					$el.hide();
				}
			}, 500 );
		},

		uploaderFilesAdded : function ( uploader, queuedFiles ) {
			wpbc.broadcast.trigger( 'uploader:queuedFilesAdded', queuedFiles );
		},

		uploaderStartUpload : function () {
			this.uploader.start();
		},

		uploaderUploadProgress : function ( up, file ) {
			wpbc.broadcast.trigger( 'uploader:uploadProgress', file );
		},

		uploaderBeforeUpload : function ( up, file ) {
			up.settings.multipart_params = _.defaults(
				wpbc.uploads[file.id],
				wpbc.preload.plupload.multipart_params,
				{nonce : wpbc.preload.nonce}
			);
		},

		uploaderFileUploaded : function ( up, file, response ) {
			var status = JSON.parse( response.response );
			wpbc.broadcast.trigger( 'uploader:fileUploaded', file );
			if ( status.data.upload === 'success' && status.data.ingest === 'success' ) {
				if ( status.data.videoDetails ) {
					// Add newly uploaded file to preload list.
					wpbc.broadcast.trigger( 'uploader:uploadedFileDetails', status.data.videoDetails );
				}
				wpbc.broadcast.trigger( 'uploader:successfulUploadIngest', file );
			} else {
				file.percent = 0;
				file.status  = plupload.UPLOADING;
				up.state     = plupload.STARTED;
				up.trigger( 'StateChanged' );
				wpbc.broadcast.trigger( 'uploader:failedUploadIngest', file );
			}
		}
	}
);

var UploadView = BrightcoveView.extend(
	{
		className : 'brightcove-pending-upload',
		tagName :   'tr',
		template :  wp.template( 'brightcove-pending-upload' ),

		events : {
			'click' : 'toggleRow'
		},

		initialize : function () {
			this.listenTo( wpbc.broadcast, 'pendingUpload:selectedRow', this.otherToggledRow );
			this.listenTo( wpbc.broadcast, 'uploader:uploadProgress', this.uploadProgress );
			this.listenTo( wpbc.broadcast, 'uploader:getParams', this.getParams );
			this.listenTo( wpbc.broadcast, 'uploader:successfulUploadIngest', this.successfulUploadIngest );
			this.listenTo( wpbc.broadcast, 'uploader:failedUploadIngest', this.failedUploadIngest );

			var options = {
				'fileName' :      this.model.get( 'name' ),
				'tags' :          '',
				'accounts' :      wpbc.preload.accounts, // All accounts.
				'account' :       wpbc.preload.defaultAccount,
				'ingestSuccess' : false,
				'uploadSuccess' : false,
				'uploaded' :      false
			};

			this.model.set( options );

			this.listenTo( this.model, 'change:fileName', this.render );
			this.listenTo( this.model, 'change:account', this.render );
		},

		render : function ( options ) {
			options               = options || {};
			options.fileName      = this.model.get( 'fileName' );
			options.size          = this.model.humanReadableSize();
			var sourceHash        = this.model.get( 'account' );
			options.accountName   = wpbc.preload.accounts[sourceHash].account_name;
			options.percent       = this.model.get( 'percent' );
			options.activeUpload  = this.model.get( 'activeUpload' );
			options.ingestSuccess = this.model.get( 'ingestSuccess' );
			options.uploadSuccess = this.model.get( 'uploadSuccess' );

			this.$el.html( this.template( options ) );
			if ( this.model.get( 'selected' ) ) {
				this.$el.addClass( 'selected' );
			}
			if ( this.model.get( 'ingestSuccess' ) ) {
				this.$el.addClass( 'ingest-success' );
			}
			if ( this.model.get( 'uploadSuccess' ) ) {
				this.$el.addClass( 'upload-success' );
			}
		},

		getParams : function ( fileId ) {
			wpbc.broadcast.trigger( 'uploader:params', "abcde" );
		},

		failedUploadIngest : function ( file ) {
			// Make sure we're acting on the right file.
			if ( file.id === this.model.get( 'id' ) ) {
				wpbc.broadcast.trigger( 'uploader:errorMessage', wpbc.preload.messages.unableToUpload.replace( '%%s%%', this.model.get( 'fileName' ) ) );
				this.render();
			}
		},

		successfulUploadIngest : function ( file ) {
			// Make sure we're acting on the right file.
			if ( file.id === this.model.get( 'id' ) ) {
				wpbc.broadcast.trigger( 'uploader:successMessage', wpbc.preload.messages.successUpload.replace( '%%s%%', this.model.get( 'fileName' ) ) );
				this.render();
			}
		},

		/**
		 * Render if we're the active upload.
		 * Re-render if we thought we were but we no longer are.
		 * @param file Fired from UploadProgress on plUpload
		 */
		uploadProgress :         function ( file ) {
			// Make sure we're acting on the right file.
			if ( file.id === this.model.get( 'id' ) ) {
				this.model.set( 'activeUpload', true );
				this.model.set( 'percent', file.percent );
				this.render();
			} else {
				if ( this.model.get( 'activeUpload' ) ) {
					this.model.unset( 'activeUpload' );
					this.render();
				}
			}
		},

		toggleRow : function ( event ) {
			this.$el.toggleClass( 'selected' );
			if ( this.$el.hasClass( 'selected' ) ) {
				this.model.set( 'selected', true );
				wpbc.broadcast.trigger( 'pendingUpload:selectedRow', this.cid );
			} else {
				wpbc.broadcast.trigger( 'pendingUpload:hideDetails', this.cid );
			}
		},

		otherToggledRow : function ( cid ) {
			// Ignore broadcast from self
			if ( cid !== this.cid ) {
				this.$el.removeClass( 'selected' );
				this.model.unset( 'selected' );
			} else {
				wpbc.broadcast.trigger( 'pendingUpload:selectedItem', this.model );
			}
		}
	}
);

var VideoEditView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'video-edit brightcove attachment-details',
		template :  wp.template( 'brightcove-video-edit' ),

		events : {
			'click .brightcove.button.save-sync' :      'saveSync',
			'click .brightcove.delete' :                'deleteVideo',
			'click .brightcove.button.back' :           'back',
			'click .setting .button' :                  'openMediaManager',
			'click .attachment .check' :                'removeAttachment',
			'click .caption-secondary-fields .delete' : 'removeCaptionRow',
			'click .add-remote-caption' :               'addCaptionRow'
		},

		back : function ( event ) {
			event.preventDefault();

			// Exit if the 'button' is disabled.
			if ( $( event.currentTarget ).hasClass( 'disabled' ) ) {
				return;
			}
			wpbc.broadcast.trigger( 'start:gridview' );
		},

		deleteVideo : function () {
			if ( confirm( wpbc.preload.messages.confirmDelete ) ) {
				wpbc.broadcast.trigger( 'spinner:on' );
				this.model.set( 'mediaType', 'videos' );
				this.model.destroy();
			}
		},

		/**
		 * Allow the user to attach a video still or thumbnail.
		 *
		 * @param {Event} evnt
		 */
		openMediaManager: function ( evnt ) {
			evnt.preventDefault();

			var elem         = $( evnt.currentTarget ).parents( '.setting' ),
				editor       = elem.data('editor'),
				mediaManager = wp.media.frames.brightcove = wp.media(),
				that         = this,
				options      = {
					state:    'insert',
					title:    wp.media.view.l10n.addMedia,
					multiple: false
				};

			// Open the media manager
			mediaManager.open( editor, options );

			// Listen for selection of media
			mediaManager.on( 'select', function() {
				var media = mediaManager.state().get( 'selection' ).first().toJSON(),
					field = $( evnt ).parents( '.setting' );

				// Set the selected attachment to the correct field
				that.setAttachment( media, field );

				// Make this action available to other areas of the application
				wpbc.broadcast.trigger( 'media:selected' );
			});
		},

		/**
		 * Set the hidden input to the ID of the selected attachment.
		 *
		 * @param {Object} media
		 * @param {String} field
		 * @returns {boolean}
		 */
		setAttachment: function( media, field ) {
			var field           = field.prevObject[0].currentTarget,
				field           = $( field ).prev( 'input' ),
				attachment      = field.parents( '.attachment' ),
				preview         = attachment.find( '.-image' );

			// Perform different setup actions based on the type of upload
			if ( attachment.context.className.indexOf( 'captions' ) > -1 ) {
				// Executed if the user is uploading a closed caption
				if ( 'vtt' === media.subtype ) {
					this.addCaptionRow( false, media );
				} else {
					var template = wp.template( 'brightcove-badformat-notice' );

					// Throw a notice to the user that the file is not the correct format
					$( '.brightcove-media-videos' ).prepend( template );

					// Allow the user to dismiss the notice
					$( '.badformat.notice-dismiss' ).on( 'click', function() {
						$( '.notice.badformat' ).first().fadeOut( 500, function() {
							$( this ).remove();
						} );
					} );
				}
			} else {
				// Executed if the user is uploading a poster image or thumbnail
				var selectedMedia = {
					url:    media.sizes.full.url,
					width:  media.sizes.full.width,
					height: media.sizes.full.height
				};

				// Set up our preview image
				var image = document.createElement( 'img' );

				// Set image properties
				image.src       = media.sizes.full.url;
				image.className = 'thumbnail';

				// Display a preview image
				attachment.addClass( 'active' );
				preview.html( image ); // .html() considered okay because auth is required to view this screen
			}

			// Add our meta to the hidden field
			field.val( JSON.stringify( selectedMedia ) );
		},

		/**
		 * Allow the user to remove media from a given field.
		 *
		 * @param {Event} evnt
		 * @returns {boolean}
		 */
		removeAttachment: function( evnt ) {
			var container = $( evnt.currentTarget ).parents( '.attachment' ),
				image     = container.find( '.-image' ),
				field     = container.next( 'input' );

			// Empty the field
			field.val( '' );

			// Remove the preview image
			container.removeClass( 'active' );
			image.empty();
		},

		/**
		 * Add a caption row
		 *
		 * @param {Event} evnt
		 * @param {Object} media
		 */
		addCaptionRow: function( evnt, media ) {
			// If using the add remote file link, prevent the page from jumping to the top
			if ( evnt ) {
				evnt.preventDefault();
			}

			var source = undefined;
			if ( media ) {
				source = media.url;
			}

			this.addCaption( source );
		},

		addCaption: function( source, language, label ) {
			var newRow     = $( document.getElementById( 'js-caption-empty-row' ) ).clone(),
				container  = document.getElementById( 'js-captions' ),
				captionUrl = document.getElementById( 'js-caption-url' );

			// Clean up our cloned row
			newRow.find( 'input' ).prop( 'disabled', false );
			newRow.removeAttr( 'id' );
			newRow.removeClass( 'empty-row' );

			if ( source ) {
				newRow.find( '.brightcove-captions' ).val( source );
			}

			if ( language ) {
				newRow.find( '.brightcove-captions-language' ).val( language );
			}

			if ( label ) {
				newRow.find( '.brightcove-captions-label' ).val( label );
			}

			// Append our new row to the container
			$( container ).append( newRow );

			// Update the context button text
			this.updateCaptionText();
		},

		/**
		 * Remove a caption
		 *
		 * @param {Event} evnt
		 */
		removeCaptionRow: function( evnt ) {
			evnt.preventDefault();

			var caption   = evnt.currentTarget,
				container = $( caption ).parents( '.caption-repeater' ),
				source    = container.find( '.brightcove-captions' ),
				language  = container.find( '.brightcove-captions-launguage' ),
				label     = container.find( '.brightcove-captions-label' );

			// Empty the input fields
			$( source ).val( '' );
			$( language ).val( '' );
			$( label ).val( '' );

			// Remove the container entirely
			container.remove();

			// Update the context button text
			this.updateCaptionText();
		},

		/**
		 * Updates the caption text based on number of captions
		 */
		updateCaptionText: function() {
			var button = $( '.captions .button-secondary' ),
				link   = $( '.add-remote-caption' );

			if ( 1 < document.getElementsByClassName( 'caption-repeater' ).length ) {
				button.text( wpbc.str_addcaption );
				link.text( wpbc.str_addremote );
			} else {
				button.text( wpbc.str_selectfile );
				link.text( wpbc.str_useremote );
			}
		},

		saveSync : function ( evnt ) {
			evnt.preventDefault();

			var $mediaFrame = $( evnt.currentTarget ).parents( '.media-modal' ),
				$allButtons = $mediaFrame.find( '.button, .button-link'),
				SELF = this;

			// Exit if the 'button' is disabled.
			if ( $allButtons.hasClass( 'disabled' ) ) {
				return;
			}

			// Disable the button for the duration of the request.
			$allButtons.addClass( 'disabled' );

			// Hide the delete link for the duration of the request.
			$mediaFrame.find( '.delete-action' ).hide();

			wpbc.broadcast.trigger( 'spinner:on' );
			this.model.set( 'name', this.$el.find( '.brightcove-name' ).val() );
			this.model.set( 'description', this.$el.find( '.brightcove-description' ).val() );
			this.model.set( 'long_description', this.$el.find( '.brightcove-long-description' ).val() );

			// Trim whitespace and commas from tags beginning/end.
			this.model.set( 'tags', this.$el.find( '.brightcove-tags' ).val().trim().replace(/(^,)|(,$)/g, '' ) );
			this.model.set( 'height', this.$el.find( '.brightcove-height' ).val() );
			this.model.set( 'width', this.$el.find( '.brightcove-width' ).val() );
			this.model.set( 'mediaType', 'videos' );
			this.model.set( 'poster', this.$el.find( '.brightcove-poster' ).val() );
			this.model.set( 'thumbnail', this.$el.find( '.brightcove-thumbnail' ).val() );

			// Captions
			var captions = [];
			this.$el.find( '.caption-repeater.repeater-row' ).not( '.empty-row' ).each( function() {
				var caption   = $( this ),
					fileName  = caption.find( '.brightcove-captions' ).val(),
					extension = fileName.split( '?' )[0], // if the URL has a query string, strip it before validating filetype
					extension = extension.split( '.' ).pop();

				if ( 'vtt' === extension ) {
					captions.push(
						{
							'source'  : fileName,
							'language': caption.find( '.brightcove-captions-language' ).val(),
							'label'   : caption.find( '.brightcove-captions-label' ).val()
						}
					);
				} else {
					var template = wp.template( 'brightcove-badformat-notice' );

					// Throw a notice to the user that the file is not the correct format
					$( '.brightcove-media-videos' ).prepend( template );

					// Allow the user to dismiss the notice
					$( '.badformat.notice-dismiss' ).on( 'click', function() {
						$( '.notice.badformat' ).first().fadeOut( 500, function() {
							$( this ).remove();
						} );
					} );
					return;
				}
			} );
			this.model.set( 'captions', captions );

			// Custom fields
			var custom = {},
				custom_fields = this.model.get( 'custom' );

			_.each( this.$el.find( '.brightcove-custom-string, .brightcove-custom-enum' ), function( item ) {
				var key = item.getAttribute( 'data-id' ),
					val = item.value.trim();

				if ( '' !== val ) {
					custom[ key ] = val;

					var obj = _.find( custom_fields, function( item ) { return item.id == key } );
					obj.value = val;
				}
			} );

			this.model.set( 'custom_fields', custom );
			this.model.set( 'custom', custom_fields );

			this.model.save()
				.done( function() {
					if ( $mediaFrame.length > 0 ) {
						// Update the tag dropdown and wpbc.preload.tags with any new tag values.
						var tagInput =  $mediaFrame.find( '.brightcove-tags' ).val(),
							editTags,
							newTags;

						if ( tagInput ) {
							editTags     = tagInput.split( ',' );
							newTags      = _.difference( editTags, wpbc.preload.tags );
						}

						// Add any new tags to the tags object and the dropdown.
						_.each( newTags, function( newTag ){
							newTag = newTag.trim();
							if ( '' !== newTag ) {
								wpbc.preload.tags.push( newTag );
							}
						} );
						wpbc.preload.tags.sort();
					}
				} )
				.always( function() {
					// Re-enable the button when the request has completed.
					$allButtons.removeClass( 'disabled' );

					// Show the delete link.
					$mediaFrame.find( '.delete-action' ).show();
				} );

			// Hide the video edit screen after save.
			wpbc.broadcast.trigger( 'start:gridview' );
		},

		/**
		 * Render the actual view for the Video Edit screen.
		 *
		 * @param {Object} options
		 */
		render : function ( options ) {
			this.listenTo( wpbc.broadcast, 'save:media', this.saveSync );
			this.listenTo( wpbc.broadcast, 'back:editvideo', this.back );

			this.listenTo( wpbc.broadcast, 'insert:shortcode', this.insertShortcode );
			options = this.model.toJSON();

			// Render the model into the template
			this.$el.html( this.template( options ) );

			// Render custom fields into the template
			var customContainer = this.$el.find( '#brightcove-custom-fields' ),
				stringTmp = wp.template( 'brightcove-video-edit-custom-string' ),
				enumTmp = wp.template( 'brightcove-video-edit-custom-enum' );

			_.each( this.model.get('custom'), function( custom ) {
				if ( '_change_history' === custom.id ) {
					return;
				}

				switch( custom.type ) {
					case 'string':
						customContainer.append( stringTmp( custom ) );
						break;
					case 'enum':
						customContainer.append( enumTmp( custom ) );
						break;
				}
			} );

			// Render the change history
			var history = this.model.get( 'history' );

			if ( history !== undefined ) {
				var historyStr = '';

				// Parse our fetched JSON object
				history = JSON.parse( history );

				_.each( history, function( item ) {
					historyStr += item.user + ' - ' + item.time + '\n';
				} );

				if ( '' !== historyStr ) {
					this.$el.find( 'textarea.brightcove-change-history' ).val( historyStr );
				}
			}

			// Configure a spinner to provide feedback during updates
			var spinner = this.$el.find( '.spinner' );
			this.listenTo( wpbc.broadcast, 'spinner:on', function () {
				spinner.addClass( 'is-active' ).removeClass( 'hidden' );
			} );
			this.listenTo( wpbc.broadcast, 'spinner:off', function () {
				spinner.removeClass( 'is-active' ).addClass( 'hidden' );
			} );

			// If there's already a poster or thumbnail set, display it
			if ( this.model.get( 'poster' ) ) {
				this.displayAttachment( 'poster' );
			}

			if ( this.model.get( 'thumbnail' ) ) {
				this.displayAttachment( 'thumbnail' );
			}

			// Captions
			if ( this.model.get( 'captions' ) ) {
				var captions = this.model.get( 'captions' );
				for ( var i = 0, l = captions.length; i < l; i++ ) {
					var caption = captions[i];
					this.addCaption( caption.source, caption.language, caption.label );
				}
			}
		}
	}
);
var VideoPreviewView = BrightcoveView.extend( {
	tagName :   'div',
	className : 'video-preview brightcove',
	template :  wp.template( 'brightcove-video-preview' ),
	shortcode: '',

	initialize: function( options ) {
		this.shortcode = options.shortcode;
	},

	render : function ( options ) {
		var that = this;

		options            = options || {};
		options.id         = this.model.get( 'id' );
		options.account_id = this.model.get( 'account_id' );

		$.ajax({
			url: ajaxurl,
			dataType: 'json',
			method: 'POST',
			data: {
				'action':'bc_resolve_shortcode',
				'shortcode': this.shortcode
			},
			success: function( results ) {
				that.$el.html( results.data );
			}
		});

		this.listenTo( wpbc.broadcast, 'insert:shortcode', this.insertShortcode );
	}
} );

var MediaCollectionView = BrightcoveView.extend(
	{
		tagName :   'ul',
		className : 'brightcove-media attachments',

		attributes : {
			tabIndex : - 1
		},

		events : {
			/* scroll fired on playlist edits, but for media grids it's handled by firing 'scroll:mediaGrid' in brightcove-media-manager */
			'scroll' : 'scrollHandler'
		},

		loadMoreMediaItems : function () {
			this.fetchingResults = true;
			this.collection.fetch();
		},

		scrollHandler : function () {
			// We don't fetch for videos in an existing playlist
			if ( 'existingPlaylists' === this.collection.mediaCollectionViewType ) {
				return;
			}

			var scrollThreshold = 200; // How many px from bottom until we fetch the next page.
			if ( ! this.fetchingResults && this.el.scrollTop + this.el.clientHeight + scrollThreshold > this.el.scrollHeight ) {
				this.collection.pageNumber += 1;
				this.loadMoreMediaItems();
			}
		},

		initialize : function ( options ) {
			this.fetchingResults = false;
			this.listenTo( wpbc.broadcast, 'fetch:finished', function () {
				this.fetchingResults = false;
			} );

			this.listenTo( wpbc.broadcast, 'fetch:apiError', this.handleAPIError );

			var scrollRefreshSensitivity = wp.media.isTouchDevice ? 300 : 200;
			this.scrollHandler           = _.chain( this.scrollHandler ).bind( this ).throttle( scrollRefreshSensitivity ).value();
			this.listenTo( wpbc.broadcast, 'scroll:mediaGrid', this.scrollHandler );
			options    = options || {};
			this.el.id = _.uniqueId( '__attachments-view-' );

			// Occurs on playlist edit, existing videos.
			if ( ! this.collection && options.videoIds ) {
				this.collection = new MediaCollection(
					null,
					{
						videoIds : options.videoIds,
						activeAccount : options.activeAccount,
						mediaCollectionViewType : options.mediaCollectionViewType
					}
				);

				this.listenTo( wpbc.broadcast, 'playlist:moveUp', this.videoMoveUp );
				this.listenTo( wpbc.broadcast, 'playlist:moveDown', this.videoMoveDown );
				this.listenTo( wpbc.broadcast, 'playlist:remove', this.videoRemove );
				this.listenTo( wpbc.broadcast, 'playlist:add', this.videoAdd );
			} else if ( ! this.collection && 'libraryPlaylists' === options.mediaCollectionViewType ) {
				this.collection = new MediaCollection(
					null,
					{
						excludeVideoIds : options.excludeVideoIds,
						activeAccount : options.activeAccount,
						mediaCollectionViewType : options.mediaCollectionViewType
					}
				);

				this.listenTo( wpbc.broadcast, 'playlist:remove', this.videoRemove );
				this.listenTo( wpbc.broadcast, 'playlist:add', this.videoAdd );
			}

			_.defaults( this.options, {
				refreshSensitivity : wp.media.isTouchDevice ? 300 : 200,
				refreshThreshold :   3,
				VideoView :          wp.media.view.Video,
				sortable :           false,
				resize :             true,
				idealColumnWidth :   202
			} );

			this._viewsByCid = {};
			this.resizeEvent = 'resize.media-modal-columns';

			this.listenTo( this.collection, 'add', function ( media ) {
				this.views.add( this.createMediaView( media ), {
					at : this.collection.indexOf( media )
				} );
			}, this );

			this.listenTo( this.collection, 'remove', function ( media ) {
				if ( media ) {
					if ( media.view ) {
						media.view.remove();
					} else if ( media.cid && this._viewsByCid[media.cid] ) {
						this._viewsByCid[media.cid].remove();
					}
				}
			}, this );

			this.listenTo( this.collection, 'reset', this.render );

			// Throttle the scroll handler and bind this.
			this.scroll = _.chain( this.scroll ).bind( this ).throttle( this.options.refreshSensitivity ).value();

			this.options.scrollElement = this.options.scrollElement || this.el;
			$( this.options.scrollElement ).on( 'scroll', this.scroll );

			_.bindAll( this, 'setColumns' );

			if ( this.options.resize ) {
				this.on( 'ready', this.bindEvents );
				// this.controller.on('open', this.setColumns);

				// Call this.setColumns() after this view has been rendered in the DOM so
				// attachments get proper width applied.
				_.defer( this.setColumns, this );
			}
		},

		handleAPIError: function() {
			this.el.innerText = wpbc.str_apifailure;
		},

		render : function () {
			// hide the spinner when content has finished loading
			this.listenTo( wpbc.broadcast, 'spinner:off', function() {
				$( '#js-media-loading' ).css( 'display', 'none' );
			} );

			this.$el.empty();
			this.collection.each( function ( mediaModel ) {
				mediaModel.view = new MediaView( {model : mediaModel} );
				this.registerSubview( mediaModel.view );
				mediaModel.view.render();
				mediaModel.view.delegateEvents();
				mediaModel.view.$el.appendTo( this.$el );

				wpbc.broadcast.trigger( 'spinner:off' );
			}, this );
		},

		setViewType : function ( type ) {
			this.collection.each( function ( mediaModel ) {
				mediaModel.set( 'view', type );
			}, this );
		},

		bindEvents : function () {
			this.$window.off( this.resizeEvent ).on( this.resizeEvent, _.debounce( this.setColumns, 50 ) );
		},

		setColumns : function () {
			var prev  = this.columns,
			    width = this.$el.width();

			if ( width ) {
				this.columns = Math.min( Math.round( width / this.options.idealColumnWidth ), 12 ) || 1;

				if ( ! prev || prev !== this.columns ) {
					this.$el.closest( '.media-frame-content' ).attr( 'data-columns', this.columns );
				}
			}
		},

		/**
		 * @param {wp.media.model.Video} attachment
		 * @returns {wp.media.View}
		 */
		createMediaView : function ( attachment ) {
			attachment.set( 'viewType', this.collection.mediaCollectionViewType );
			var view = new MediaView( {
				controller : this.controller,
				model :      attachment,
				collection : this.collection,
				selection :  this.options.selection
			} );
			this.registerSubview( view );
			this._viewsByCid[attachment.cid] = view;
			return view;
		},

		prepare : function () {
			// Create all of the Video views, and replace
			// the list in a single DOM operation.
			if ( this.collection.length ) {
				this.views.set( this.collection.map( this.createMediaView, this ) );

				// If there are no elements, clear the views and load some.
			} else {
				this.views.unset();
				this.collection.more().done( this.scroll );
			}
		},

		ready : function () {
			// Trigger the scroll event to check if we're within the
			// threshold to query for additional attachments.
			this.scroll();
		},

		scroll : function () {
			var view      = this,
			    el        = this.options.scrollElement,
			    scrollTop = el.scrollTop,
			    toolbar;

			// The scroll event occurs on the document, but the element
			// that should be checked is the document body.
			if ( el === document ) {
				el        = document.body;
				scrollTop = $( document ).scrollTop();
			}

			if ( 'function' !== this.collection.hasMore || ! $( el ).is( ':visible' ) || ! this.collection.hasMore() ) {
				return;
			}

			toolbar = this.views.parent.toolbar;

			// Show the spinner only if we are close to the bottom.
			if ( el.scrollHeight - ( scrollTop + el.clientHeight ) < el.clientHeight / 3 ) {
				toolbar.get( 'spinner' ).show();
			}

			if ( el.scrollHeight < scrollTop + ( el.clientHeight * this.options.refreshThreshold ) ) {
				this.collection.more().done( function () {
					view.scroll();
					toolbar.get( 'spinner' ).hide();
				} );
			}
		},

		videoMoveUp : function ( videoView ) {
			var model = videoView.model;
			var index = this.collection.indexOf( model );
			if ( index > 0 ) {
				this.collection.remove( model, {silent : true} ); // silence this to stop excess event triggers
				this.collection.add( model, {at : index - 1} );
			}
			this.render();
			this.playlistChanged();
		},

		videoMoveDown : function ( videoView ) {
			var model = videoView.model;
			var index = this.collection.indexOf( model );
			if ( index < this.collection.models.length ) {
				this.collection.remove( model, {silent : true} ); // silence this to stop excess event triggers
				this.collection.add( model, {at : index + 1} );
			}
			this.render();
			this.playlistChanged();
		},

		videoRemove : function ( videoView ) {
			var model = videoView.model;
			if ( - 1 === this.collection.indexOf( model ) ) {
				// this is the library model
				this.collection.add( model );
			} else {
				// this is the playlist collection
				this.collection.remove( model, {silent : true} ); // silence this to stop excess event triggers
				this.playlistChanged();
			}
			this.render();
		},

		videoAdd : function ( videoView ) {
			/**
			 * Video add is heard by two collections, the one containing the videos for the playlists
			 * and the one containing the videos that we can add to them.
			 * We handle the add by adding from the collection where it doesn't exist (the playlist) and removing
			 * where it does (the library).
			 */
			var model = videoView.model;
			if ( - 1 === this.collection.indexOf( model ) ) {
				// this is the playlist collection
				this.collection.add( model );
				this.playlistChanged();
			} else {
				// this is the library model
				this.collection.remove( model, {silent : true} );
				this.render();
			}
		},

		playlistChanged : function () {
			var videoIds = [];
			this.collection.each( function ( video ) {
				videoIds.push( video.id );
			} );
			this.videoIds = videoIds;
			// var syncPlaylist = _.throttle(_.bind(this.syncPlaylist, this), 2000);
			this.syncPlaylist();
		},

		syncPlaylist : function () {
			wpbc.broadcast.trigger( 'playlist:changed', this.videoIds );
		}

	}
);


	var App = {
		renderMediaManager: function(mediaType) {
			var brightcoveMediaContainer = $('.brightcove-media-' + mediaType);
			var content_ifr = document.getElementById('content_ifr');
			if ( brightcoveMediaContainer.length ) {
				var brightcoveMediaManager = new BrightcoveMediaManagerView({
					el: brightcoveMediaContainer,
					date: 'all',
					embedType: 'page',
					preload: true,
					mode: 'manager',
					search: '',
					accounts: 'all',
					tags: 'all',
					mediaType: mediaType,
					viewType: 'grid'
				});
				brightcoveMediaManager.render();
			}
		},

		load: function() {
			wpbc.requests = [];
			wpbc.responses = {};
			wpbc.broadcast = _.extend({}, Backbone.Events); // pubSub object

			this.loaded();

		},

		loaded: function() {
			var brightcoveModalContainer = $('.brightcove-modal');

			var router = new BrightcoveRouter;
			wpbc.triggerModal = function() {
				if (!wpbc.modal) {
					wpbc.modal = new BrightcoveModalView({
						el: brightcoveModalContainer,
						tab: 'videos'
					});
					wpbc.modal.render();
					wpbc.modal.$el.find( '.spinner' ).addClass( 'is-active' );
				} else {
					wpbc.modal.$el.show();
				}

				// Prevent body scrolling by adding a class to 'body'.
				$( 'body' ).addClass( 'modal-open' );
			};

			var bc_sanitize_ids = function( id ) {
				return id.replace(/\D/g,'');
			};

			// Load the appropriate media type manager into the container element,
			// We only support loading one per page.
			_.each(['videos', 'playlists'], function(mediaType){
				App.renderMediaManager(mediaType);
			});

			$('.account-toggle-button').on('click',function(event){
				event.preventDefault();
				$(this).hide();
				$('.brightcove-account-row.hidden').show();
			});

			$('.brightcove-add-new-video').on('click', function(e) {
				e.preventDefault();
				router.navigate('add-new-brightcove-video', { trigger:true });
			});

			$(document).on('click', '.brightcove-add-media', function( e ) {
				e.preventDefault();
				wpbc.triggerModal();
				wpbc.modal.target = e.currentTarget.dataset.target;
			});

			$(document).keyup(function(e) {
				if (27 === e.keyCode) {
					// Close modal on ESCAPE if it's open.
					wpbc.broadcast.trigger('close:modal');
				}
			});

			$('a.brightcove-action-delete-source').on('click',function(e){
				var message = $(this).data('alert-message');
				if( !confirm( message ) ) {
					return false;
				}
			});

		}
	};

	jQuery( document ).ready( function() {
		App.load();
		var router = new BrightcoveRouter;
		Backbone.history.start();
	} );

} )( jQuery );
//# sourceMappingURL=brightcove-admin.js.map