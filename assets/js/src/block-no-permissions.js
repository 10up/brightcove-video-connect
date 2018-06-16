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
		category: 'common',
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
		supports: {
			inserter: false,
			html: false
		},

		edit: function( props ) {
			// Set the field we want to target
			var target = 'brightcove-' + props.id;

			// Attributes needed to render
			var accountId = props.attributes.account_id || '';
			var playerId = props.attributes.player_id || '';
			var videoId = props.attributes.video_id || '';
			var playlistId = props.attributes.playlist_id || '';

			// If no video has been selected yet, show a warning message
			if ( ! accountId.length && ! playerId.length && ( ! videoId.length || ! playlistId.length ) ) {
				return el( Placeholder, {
					icon: 'media-video',
					label: 'Brightcove',
					instructions: 'You don\'t have permissions to add Brightcove videos.',
					children: [
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
					el( 'iframe', { src: src, style: { height: 250, width: 500, display: 'block', margin: '0 auto' }, key: 'iframe' } ),
					el( 'input', { id: target, hidden: true, key: 'input' } )
				];
			}
		},

		save: function( props ) {
			return null;
		}

	} );

} )( window.wp.blocks, window.wp.element, window.wp.components );
