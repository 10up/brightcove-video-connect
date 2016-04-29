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

			var brightcoveId = this.model.get( 'id' ).replace( /\D/g, '' ); // video or playlist id
			var accountId    = this.model.get( 'account_id' ).replace( /\D/g, '' );
			var playerId     = wpbc.selectedPlayer;
			var shortcode    = '';

			if ( ! playerId ) {
				var playerId = 'default';
			}

			if ( undefined !== this.mediaType ) {
				if ( this.mediaType === 'videos' ) {

					shortcode = '[bc_video video_id="' + brightcoveId + '" account_id="' + accountId + '" player_id="' + playerId +  '"]';

				} else {

					shortcode = '[bc_playlist playlist_id="' + brightcoveId + '" account_id="' + accountId + '"]';

				}
			} else {
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

			window.send_to_editor( shortcode );
			wpbc.broadcast.trigger( 'close:modal' );

		}
	}
);

