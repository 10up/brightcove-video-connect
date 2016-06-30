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
