var VideoEditView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'video-edit brightcove attachment-details',
		template :  wp.template( 'brightcove-video-edit' ),

		events : {
			'click .brightcove.button.save-sync' :     'saveSync',
			'click .brightcove.delete' :               'deleteVideo',
			'click .brightcove.button.back' :          'back',
			'click .setting .button' :                 'openMediaManager',
			'click .attachment .check' :               'removeAttachment',
			'click #caption-extra-fields .delete' :    'removeCaption'
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
				options      = {
					state:    'insert',
					title:    wp.media.view.l10n.addMedia,
					multiple: false
				};

			// Open the media manager
			mediaManager.open( editor, options );

			// Listen for selection of media
			this.listenTo( mediaManager, 'select', function() {
				var media = mediaManager.state().get( 'selection' ).first().toJSON(),
					field = $( evnt ).parents( '.setting' );

				// Set the selected attachment to the correct field
				this.setAttachment( media, field );

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
				attachment      = field.prev( '.attachment' ),
				preview         = attachment.find( '.-image' );

			// Perform different setup actions based on the type of upload
			if ( attachment.context.className.indexOf( 'captions' ) > -1 ) {
				// Executed if the user is uploading a closed caption
				if ( 'vtt' === media.subtype ) {
					var captionExtras = document.getElementById( 'js-caption-fields' ),
						captionUrl    = document.getElementById( 'js-caption-url' ),
						selectedMedia = {
							src: media.url
						};

					// Expose the additional caption fields
					$( captionExtras ).addClass( 'active' );

					// Display the selected captions file url
					$( captionUrl ).empty().html( media.url ); // .html() considered okay because auth is required to view this screen
				} else {
					// Alert the user that the file is not the correct format
					alert( 'This file is not the proper format. Please use .vtt files, see: https://support.brightcove.com/en/video-cloud/docs/adding-captions-videos#captionsfile' );
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
		 * Remove a caption
		 *
		 * @param {Event} evnt
		 */
		removeCaption: function( evnt ) {
			var caption   = evnt.currentTarget,
				container = document.getElementById( 'js-caption-fields' ),
				input     = document.getElementsByClassName( 'brightcove-captions' ),
				preview   = document.getElementById( 'js-caption-url' ),
				language  = document.getElementsByClassName( 'brightcove-captions-language' ),
				label     = document.getElementsByClassName( 'brightcove-captions-label' ),
				kind      = document.getElementsByClassName( 'brightcove-captions-kind' );

			// Empty the input fields
			$( input ).val( '' );
			$( language ).val( '' );
			$( label ).val( '' );
			$( kind ).val( '' );

			// Empty the preview field
			$( preview ).empty();

			// Hide the extra fields
			$( container ).removeClass( 'active' );
		},

		/**
		 * Add a caption row
		 *
		 * @param {Event} evnt
		 */
		addCaptionRow: function( evnt ) {

		},

		saveSync : function ( evnt ) {
			var $mediaFrame = $( evnt.currentTarget ).parents( '.media-modal' ),
				$allButtons = $mediaFrame.find( '.button, .button-link' );

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
						var editTags     = $mediaFrame.find( '.brightcove-tags' ).val().split( ',' ),
							newTags      = _.difference( editTags, wpbc.preload.tags );

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
		},

		/**
		 * Render the actual view for the Video Edit screen.
		 *
		 * @param {Object} options
		 */
		render : function ( options ) {
			this.listenTo( wpbc.broadcast, 'insert:shortcode', this.insertShortcode );
			options = this.model.toJSON();

			// Render the model into the template
			this.$el.html( this.template( options ) );

			// Render custom fields into the template
			var customContainer = this.$el.find( '#brightcove-custom-fields' ),
				stringTmp = wp.template( 'brightcove-video-edit-custom-string' ),
				enumTmp = wp.template( 'brightcove-video-edit-custom-enum' );

			_.each( this.model.get('custom'), function( custom ) {
				switch( custom.type ) {
					case 'string':
						customContainer.append( stringTmp( custom ) );
						break;
					case 'enum':
						customContainer.append( enumTmp( custom ) );
						break;
				}
			} );

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
		}

	}
);
