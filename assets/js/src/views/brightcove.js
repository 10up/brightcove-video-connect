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
			var accountId   = this.model.get( 'account_id' ).replace( /\D/g, '' );
			var videoWidth  = this.model.get( 'width' );
			var videoHeight = this.model.get( 'height' );
			var shortcode   = '';

			var playerDimensions = '';

			if ( 0 != videoHeight && 0 != videoWidth ) {
				playerDimensions = ' width="' + videoWidth + '" height="' + videoHeight + '"';
			}

			if ( this.mediaType === 'videos' ) {

				shortcode = '[bc_video video_id="' + brightcoveId + '" account_id="' + accountId + '" player_id="default"' + playerDimensions + ']';

			} else {

				shortcode = '[bc_playlist playlist_id="' + brightcoveId + '" account_id="' + accountId + '"' + playerDimensions + ']';

			}

			window.send_to_editor( shortcode );
			wpbc.broadcast.trigger( 'close:modal' );

		}
	}
);

