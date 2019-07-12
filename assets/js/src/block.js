/* global wp, bctiny, bcBlock */

( function( blocks, element, components, editor ) {
	var el = element.createElement,
		registerBlockType = blocks.registerBlockType,
		Placeholder = components.Placeholder,
		BlockControls = editor.BlockControls,
		IconButton = components.IconButton,
		userPermission = !!bcBlock.userPermission;

	registerBlockType( 'bc/brightcove', {
		title: 'Brightcove',
		description: 'The Brightcove block allows you to embed videos from Brightcove.',
		icon: 'video-alt3',
		category: 'common',
		supports: {
			inserter: userPermission,
			html: false
		},

		edit: function( props ) {
			// Set the field we want to target
			var target = 'brightcove-' + props.clientId;

			// Attributes needed to render
			var accountId = props.attributes.account_id || '';
			var playerId = props.attributes.player_id || '';
			var videoId = props.attributes.video_id || '';
			var playlistId = props.attributes.playlist_id || '';
			var experienceId = props.attributes.experience_id || '';
			var videoIds = props.attributes.video_ids || '';
			var height = props.attributes.height || '';
			var width = props.attributes.width || '';
			var minWidth = props.attributes.min_width || '';
			var maxWidth = props.attributes.max_width || '';
			var paddingTop = props.attributes.padding_top || '';
			var autoplay = props.attributes.autoplay || '';
			var embed = props.attributes.embed || '';

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
				var setAttrs = {
					account_id: sanitizeIds( attrs.named.account_id ),
					player_id: '',
					video_id: '',
					playlist_id: '',
					experience_id: '',
					video_ids: '',
					height: attrs.named.height,
					width: attrs.named.width,
					min_width: attrs.named.min_width,
					max_width: attrs.named.max_width,
					padding_top: '',
					autoplay: '',
					embed: attrs.named.embed
				};

				if ( '[bc_video' === attrs.numeric[0] ) {
					setAttrs.player_id = attrs.named.player_id;
					setAttrs.video_id = sanitizeIds( attrs.named.video_id );
					setAttrs.autoplay = attrs.named.autoplay;
					setAttrs.padding_top = attrs.named.padding_top;
				} else if ( '[bc_playlist' === attrs.numeric[0] ) {
					setAttrs.player_id = attrs.named.player_id;
					setAttrs.playlist_id = sanitizeIds( attrs.named.playlist_id );
					setAttrs.autoplay = attrs.named.autoplay;
					setAttrs.padding_top = attrs.named.padding_top;
				} else if ( '[bc_experience' === attrs.numeric[0] ) {
					setAttrs.experience_id = attrs.named.experience_id;

					if ( 'undefined' !== typeof attrs.named.video_ids ) {
						setAttrs.video_ids = sanitizeIds( attrs.named.video_ids );
					} else {
						setAttrs.playlist_id = sanitizeIds( attrs.named.playlist_id );
					}
				}

				props.setAttributes( setAttrs );
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
							label: videoId.playlist_id ? 'Change Playlist' : 'Change Video',
							icon: 'edit',
							'data-target': '#' + target
						}
					)
				)
			);

			// If no video has been selected yet, show the selection view
			if ( ! accountId.length && ( ! playerId.length || ! experienceId.length ) && ( ! videoId.length || ! playlistId.length || videoIds.length ) ) {
				return el( Placeholder, {
					icon: 'media-video',
					label: 'Brightcove',
					instructions: userPermission ? 'Select a video file or playlist from your Brightcove library' : 'You don\'t have permissions to add Brightcove videos.',
					children: [
						userPermission ? el( 'button', { className: 'brightcove-add-media button button-large', 'data-target': '#' + target, key: 'button' }, 'Brightcove Media' ) : '',
						el( 'input', { id: target, hidden: true, key: 'input' } )
					]
				} );

			// Otherwise render the shortcode
			} else {
				var src = '';

				if ( experienceId.length ) {
					var urlAttrs = '';
					if ( videoIds.length ) {
						urlAttrs = 'videoIds=' + videoIds;
					} else {
						urlAttrs = 'playlistId=' + playlistId;
					}
					src = '//players.brightcove.net/' + accountId + '/experience_' + experienceId + '/index.html?' + urlAttrs;
				} else if ( videoId.length ) {
					src = '//players.brightcove.net/' + accountId + '/' + playerId + '_default/index.html?videoId=' + videoId;
				} else {
					playerId = bctiny.playlistEnabledPlayers[ accountId ][0] || 'default';
					src = '//players.brightcove.net/' + accountId + '/' + playerId + '_default/index.html?playlistId=' + playlistId;
				}

				if ( 'undefined' === typeof height ) {
					height = 250;
				}

				if ( 'undefined' === typeof width ) {
					width = 500;
				}

				return [
					userPermission ? controls : '',
					el( 'iframe', { src: src, style: { height: height, width: width, display: 'block', margin: '0 auto' }, allowFullScreen: true, key: 'iframe' } ),
					el( 'input', { id: target, hidden: true, key: 'input' } )
				];
			}
		},

		save: function() {
			return null;
		}

	} );

} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.editor );
