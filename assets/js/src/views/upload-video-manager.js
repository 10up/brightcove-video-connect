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
