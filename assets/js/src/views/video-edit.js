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
			'click .attachment .check' :               'removeAttachment'
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
				preview         = attachment.find( '.-image' ),
				image           = document.createElement( 'img' );
				image.src       = media.sizes.thumbnail.url;
				image.className = 'thumbnail';

			// Setup an object of necessary info to be stored as JSON
			var selectedMedia = {
				url:    media.sizes.full.url,
				width:  media.sizes.full.width,
				height: media.sizes.full.height
			}

			// Add our meta to the hidden field
			field.val( JSON.stringify( selectedMedia ) );

			// Display a preview image
			attachment.addClass( 'active' );
			preview.html( image );
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
		 * Display the attachment if one is already set.
		 *
		 * @param {Event} evnt
		 * @returns {boolean}
		 */
		displayAttachment: function( field ) {
			if ( 'poster' === field ) {
				var field = '.button-secondary.-poster';
			}

			if ( 'thumbnail' === field ) {
				var field = '.button-secondary.-thumbnail';
			}

			var attachment      = $( field ).prev( '.attachment' ),
				preview         = attachment.find( '.-image' ),
				image           = document.createElement( 'img' );
				image.src       = media.sizes.thumbnail.url;
				image.className = 'thumbnail';

			// Display a preview image
			attachment.addClass( 'active' );
			preview.html( image );
		},

		saveSync : function ( evnt ) {
			var $mediaFrame = $( evnt.currentTarget ).parents( '.media-modal' ),
				$allButtons = $mediaFrame.find( '.button, .button-link' );
				$thumbnail  = this.$el.find( '.brightcove-tags.-thumbnail' ).val();

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
			this.model.set( 'poster', this.$el.find( '.brightcove-tags.-poster' ).val() );
			this.model.set( 'thumbnail', this.$el.find( '.brightcove-tags.-thumbnail' ).val() );

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

