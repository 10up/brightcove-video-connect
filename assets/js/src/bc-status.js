/**
 * Used to provide status warnings from the Brightcove servers.
 */
jQuery( document ).ready( function ( $ ) {

	jQuery( '.brightcove-service-error .notice-dismiss' ).click( function () {

		var data = {
			action : 'bc_status_dismiss',
			nonce :  bcStatus.nonce
		};

		// Send dismissal to WordPress.
		$.ajax(
			{
				url :      ajaxurl,
				type :     'POST',
				data :     data,
				complete : function ( response ) {

					if ( true === response.responseJSON.success ) {

						var date = new Date();
						date.setTime( date.getTime() + (300 * 1000) );
						var expires = '; expires=' + date.toGMTString();

						document.cookie = 'bc-status-dismissed=' + true + expires + '; path=/';
					}
				}

			}
		);

	} );
} );