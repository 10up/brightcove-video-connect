var VideoEditView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'video-edit brightcove attachment-details',
		template :  wp.template( 'brightcove-video-edit' ),

		events : {
			'click .brightcove.button.save-sync' : 'saveSync',
			'click .brightcove.delete' :           'deleteVideo',
			'click .brightcove.button.back' :      'back'
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
			this.model.save()
				.done( function() {

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
				} )
				.always( function() {
					// Re-enable the button when the request has completed.
					$allButtons.removeClass( 'disabled' );

					// Show the delete link.
					$mediaFrame.find( '.delete-action' ).show();
				} );
		},

		render : function ( options ) {
			this.listenTo( wpbc.broadcast, 'insert:shortcode', this.insertShortcode );
			options = this.model.toJSON();
			this.$el.html( this.template( options ) );
			var spinner = this.$el.find( '.spinner' );
			this.listenTo( wpbc.broadcast, 'spinner:on', function () {
				spinner.addClass( 'is-active' ).removeClass( 'hidden' );
			} );
			this.listenTo( wpbc.broadcast, 'spinner:off', function () {
				spinner.removeClass( 'is-active' ).addClass( 'hidden' );
			} );
		}

	}
);

