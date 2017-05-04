(function ( $ ) {
	var views                 = wp.mce.views;
	var bc_video_preview_edit = function () {
		if ( ! wpbc.modal ) {
			wpbc.modal = new BrightcoveModalView( {
				el :  brightcoveModalContainer,
				tab : 'videos'
			} );
			wpbc.modal.render();
		} else {
			wpbc.modal.$el.show();
		}
	};

	var utilities = {
		bc_sanitize_ids : function ( id ) {
			return id.replace( /\D/g, '' );
		}
	};

	// for WP version 4.2 and above use
	// This approach https://make.wordpress.org/core/2015/04/23/tinymce-views-api-improvements/
	if ( typeof bctiny.wp_version !== undefined && parseFloat( bctiny.wp_version ) >= 4.2 ) {
		// replace bc_video shortcode with iframe to preview video
		views.register( 'bc_video', {
			initialize : function () {
				var self     = this;

				var videoHeight = self.shortcode.attrs.named.height;
				var videoWidth = self.shortcode.attrs.named.width;
				var playerId = self.shortcode.attrs.named.player_id;

				if ( 'undefined' === typeof videoHeight ) {
					videoHeight = 250;
				}

				if ( 'undefined' === typeof videoWidth ) {
					videoWidth = 500;
				}

				var iframe = jQuery( '<iframe />' );
				iframe.attr( 'style', 'width: ' + videoWidth + 'px; height: ' + videoHeight + 'px;' );
				iframe.attr( 'src', '//players.brightcove.net/' + utilities.bc_sanitize_ids( self.shortcode.attrs.named.account_id ) + '/' + playerId + '_default/index.html?videoId=' + utilities.bc_sanitize_ids( self.shortcode.attrs.named.video_id ) );
				iframe.attr( 'mozallowfullscreen' , '' );
				iframe.attr( 'webkitallowfullscreen' , '' );
				iframe.attr( 'allowfullscreen' , '' );

				// There is no way to easily convert an element into string. So we are using a wrapper.
				// This is needed since VIP doesn't allow direct string concatenation.
				// Details at https://wordpressvip.zendesk.com/hc/en-us/requests/63849
				var wrapper = document.createElement("p");
				wrapper.appendChild( iframe.get(0) );

				self.content = wrapper.innerHTML;

				// add allowfullscreen attribute to main iframe to allow video preview in full screen
				if ( typeof document.getElementById( 'content_ifr' ) !== 'undefined' ) {
					document.getElementById( 'content_ifr' ).setAttribute( 'allowFullScreen', '' );
				}
			},
			edit :       function () {
				wpbc.triggerModal();
			}
		} );

		views.register( 'bc_playlist', {
			initialize : function () {
				var self      = this;

				var playlistHeight = self.shortcode.attrs.named.height;
				var playlistWidth = self.shortcode.attrs.named.width;

				if ( 'undefined' === typeof playlistHeight ) {
					playlistHeight = 250;
				}

				if ( 'undefined' === typeof playlistWidth ) {
					playlistWidth = 500;
				}

				var player_id = bctiny.playlistEnabledPlayers[self.shortcode.attrs.named.account_id][0] || 'default';
				self.content  = '<iframe style="width: ' + playlistWidth + 'px; height: ' + playlistHeight + 'px;" src="//players.brightcove.net/' + utilities.bc_sanitize_ids( self.shortcode.attrs.named.account_id ) + '/' + player_id + '_default/index.html?playlistId=' + utilities.bc_sanitize_ids( self.shortcode.attrs.named.playlist_id ) + '" width="645" height="352" allowfullscreen webkitallowfullscreen mozallowfullscreen></iframe>';
				// add allowfullscreen attribute to main iframe to allow video preview in full screen
				if ( typeof document.getElementById( 'content_ifr' ) !== 'undefined' ) {
					document.getElementById( 'content_ifr' ).setAttribute( 'allowFullScreen', '' );
				}
			},
			edit :       function () {
				wpbc.triggerModal();
			}
		} );
	} else {
		views.register( 'bc_video', {
			View : {
				initialize : function ( options ) {

					var videoHeight = self.shortcode.attrs.named.height;
					var videoWidth = self.shortcode.attrs.named.width;

					if ( 'undefined' === typeof videoHeight ) {
						videoHeight = 250;
					}

					if ( 'undefined' === typeof videoWidth ) {
						videoWidth = 500;
					}

					this.content = '<iframe style="width: ' + videoWidth + 'px; height: ' + videoHeight + 'px;" src="//players.brightcove.net/' + bc_sanitize_ids( options.shortcode.attrs.named.account_id ) + '/default_default/index.html?videoId=' + bc_sanitize_ids( options.shortcode.attrs.named.video_id ) + '" allowfullscreen webkitallowfullscreen mozallowfullscreen></iframe>';
					// add allowfullscreen attribute to main iframe to allow video preview in full screen
					if ( typeof document.getElementById( 'content_ifr' ) !== 'undefined' ) {
						document.getElementById( 'content_ifr' ).setAttribute( 'allowFullScreen', '' );
					}
				},
				edit :       function () {
					wpbc.broadcast.trigger( 'triggerModal' );
				},
				getHtml :    function () {
					return this.content;
				}
			}
		} );
		views.register( 'bc_playlist', {
			View : {
				initialize : function ( options ) {

					var playlistHeight = self.shortcode.attrs.named.height;
					var playlistWidth = self.shortcode.attrs.named.width;

					if ( 'undefined' === typeof playlistHeight ) {
						playlistHeight = 250;
					}

					if ( 'undefined' === typeof playlistWidth ) {
						playlistWidth = 500;
					}

					var player_id = bctiny.playlistEnabledPlayers[options.shortcode.attrs.named.account_id][0] || 'default';
					this.content  = '<iframe style="width: ' + playlistWidth + 'px; height: ' + playlistHeight + 'px;" src="//players.brightcove.net/' + utilities.bc_sanitize_ids( options.shortcode.attrs.named.account_id ) + '/' + player_id + '_default/index.html?playlistId=' + utilities.bc_sanitize_ids( options.shortcode.attrs.named.playlist_id ) + '" width="645" height="352" allowfullscreen webkitallowfullscreen mozallowfullscreen></iframe>';
					this.content = 'no';
					// add allowfullscreen attribute to main iframe to allow video preview in full screen
					if ( typeof document.getElementById( 'content_ifr' ) !== 'undefined' ) {
						document.getElementById( 'content_ifr' ).setAttribute( 'allowFullScreen', '' );
					}
				},
				edit :       function () {
					wpbc.broadcast.trigger( 'triggerModal' );
				},
				getHtml :    function () {
					return this.content;
				}
			}
		} );
	}
})( jQuery );

