/* global wp, bctiny */

( function( blocks, element, components ) {
	var el = element.createElement,
		registerBlockType = blocks.registerBlockType,
		Placeholder = components.Placeholder,
		BlockControls = blocks.BlockControls,
		IconButton = components.IconButton;

	registerBlockType( 'bc/brightcove', {
		title: 'Brightcove',
		description: 'The Brightcove block allows you to embed videos from Brightcove.',
		icon: 'video-alt3',
		category: 'common', // embed?
		attributes: {
			account_id: {
				type: 'int'
			},
			player_id: {
				type: 'int'
			},
			video_id: {
				type: 'int'
			},
			playlist_id: {
				type: 'int'
			}
		},

		edit: function( props ) {
			// Set the field we want to target
			var target = 'brightcove-' + props.id;

			// Attributes needed to render
			var accountId = props.attributes.account_id || '';
			var playerId = props.attributes.player_id || '';
			var videoId = props.attributes.video_id || '';
			var playlistId = props.attributes.playlist_id || '';

			// Sanitize the IDs we need
			var sanitizeIds = function( id ) {
				return id.replace( /\D/g, '' );
			};

			/**
			 * Set attributes when a video is selected.
			 *
			 * Listens to the change event on our hidden
			 * input and will grab the shortcode from the
			 * inputs value, parsing out the attributes
			 * we need and setting those as props.
			 */
			var onSelectVideo = function() {
				var btn = document.getElementById( target );
				var attrs = wp.shortcode.attrs( btn.value );

				if ( '[bc_video' === attrs.numeric[0] ) {
					props.setAttributes( {
						account_id: sanitizeIds( attrs.named.account_id ),
						player_id: attrs.named.player_id,
						video_id: sanitizeIds( attrs.named.video_id ),
						playlist_id: ''
					} );
				} else if ( '[bc_playlist' === attrs.numeric[0] ) {
					props.setAttributes( {
						account_id: sanitizeIds( attrs.named.account_id ),
						player_id: attrs.named.player_id,
						video_id: '',
						playlist_id: sanitizeIds( attrs.named.playlist_id )
					} );
				}
			};

			// Listen for a change event on our hidden input
			jQuery( document ).on( 'change', '#' + target, onSelectVideo );

			// Set up our controls
			var controls = el(
				BlockControls,
				{ key: 'controls' },
				el( 'div', { className: 'components-toolbar' },
					el(
						IconButton,
						{
							className: 'brightcove-add-media components-icon-button components-toolbar__control',
							label: videoId.length ? 'Change Video' : 'Change Playlist',
							icon: 'edit',
							'data-target': '#' + target
						}
					)
				)
			);

			// If no video has been selected yet, show the selection view
			if ( ! accountId.length && ! playerId.length && ( ! videoId.length || ! playlistId.length ) ) {
				return el( Placeholder, {
					icon: 'media-video',
					label: 'Brightcove',
					instructions: 'Select a video file or playlist from your Brightcove library',
					children: [
						el( 'button', { className: 'brightcove-add-media button button-large', 'data-target': '#' + target, key: 'button' }, 'Brightcove Media' ),
						el( 'input', { id: target, hidden: true, key: 'input' } )
					]
				} );

			// Otherwise render the iframe
			} else {
				var src = '';

				if ( videoId.length ) {
					src = '//players.brightcove.net/' + accountId + '/' + playerId + '_default/index.html?videoId=' + videoId;
				} else {
					playerId = bctiny.playlistEnabledPlayers[ accountId ][0] || 'default';
					src = '//players.brightcove.net/' + accountId + '/' + playerId + '_default/index.html?playlistId=' + playlistId;
				}

				return [
					controls,
					el( 'iframe', { src: src, style: { height: 250, width: 500, display: 'block', margin: '0 auto' }, key: 'iframe' } ),
					el( 'input', { id: target, hidden: true, key: 'input' } )
				];
			}
		},

		save: function( props ) {
			var accountId = props.attributes.account_id || '';
			var playerId = props.attributes.player_id || '';
			var videoId = props.attributes.video_id || '';
			var playlistId = props.attributes.playlist_id || '';
			var src = '';

			if ( videoId.length ) {
				src = '//players.brightcove.net/' + accountId + '/' + playerId + '_default/index.html?videoId=' + videoId;
			} else {
				playerId = bctiny.playlistEnabledPlayers[ accountId ][0] || 'default';
				src = '//players.brightcove.net/' + accountId + '/' + playerId + '_default/index.html?playlistId=' + playlistId;
			}

			return el( 'iframe', { src: src, style: { height: 250, width: 500 } } );
		}

	} );

} )( window.wp.blocks, window.wp.element, window.wp.components );
