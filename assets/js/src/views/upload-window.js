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
