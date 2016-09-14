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
