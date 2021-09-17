/* global wp, bctiny, bcBlock */
(function (blocks, element, components, editor) {
	const { __ } = wp.i18n;

	var el = element.createElement,
		registerBlockType = blocks.registerBlockType,
		Placeholder = components.Placeholder,
		BlockControls = editor.BlockControls,
		IconButton = components.IconButton,
		userPermission = !!bcBlock.userPermission,
		InspectorControls = wp.blockEditor.InspectorControls,
		TextControl = components.TextControl;

	registerBlockType('bc/brightcove', {
		title: __('Brightcove', 'brightcove'),
		description: __(
			'The Brightcove block allows you to embed videos from Brightcove.',
			'brightcove',
		),
		icon: 'video-alt3',
		category: 'common',
		supports: {
			inserter: userPermission,
			html: false,
		},

		edit: function (props) {
			const [isHeightFieldDisabled, setIsHeightFieldDisabled] = element.useState(true);

			// Set the field we want to target
			var target = 'brightcove-' + props.clientId;

			// Attributes needed to render
			var accountId = props.attributes.account_id || '';
			var playerId = props.attributes.player_id || '';
			var videoId = props.attributes.video_id || '';
			var playlistId = props.attributes.playlist_id || '';
			var experienceId = props.attributes.experience_id || '';
			var videoIds = props.attributes.video_ids || '';
			var minWidth = props.attributes.min_width || '';
			var paddingTop = props.attributes.padding_top || '';
			var autoplay = props.attributes.autoplay || '';
			var playsinline = props.attributes.playsinline || '';
			var pictureinpicture = props.attributes.picture_in_picture || '';
			var embed = props.attributes.embed || '';
			var mute = props.attributes.mute || '';
			var sizing = props.attributes.sizing || 'responsive';
			var aspectRatio = props.attributes.aspect_ratio || '16:9';
			var width =
				sizing === 'fixed'
					? props.attributes.width?.replace(/[^0-9]/g, '') || '640'
					: props.attributes.max_width?.replace(/[^0-9]/g, '') || '640';

			var height = props.attributes.height?.replace(/[^0-9]/g, '');

			const maxHeight = props.attributes.max_height?.replace(/[^0-9]/g, '') || height;
			const maxWidth = props.attributes.max_width?.replace(/[^0-9]/g, '') || width;

			const account = _.findWhere(wpbc.preload.accounts, {
				account_id: accountId,
			});
			const accountName = account.account_name;

			element.useEffect(() => {
				if (aspectRatio === 'custom') {
					setIsHeightFieldDisabled(false);
				} else {
					setIsHeightFieldDisabled(true);
				}
			}, [aspectRatio]);

			element.useEffect(() => {
				if (!props.attributes.max_height) {
					const height =
						props.attributes.sizing === 'responsive' ? '100%' : props.attributes.height;
					props.setAttributes({
						...props.attributes,
						max_height: props.attributes.height,
						height,
					});
				}
			}, []);

			element.useEffect(() => {
				if (
					props.attributes.sizing === 'responsive' &&
					props.attributes.height !== '100%'
				) {
					props.setAttributes({
						...props.attributes,
						height: '100%',
						max_height: `${props.attributes.height?.replace(/[^0-9]/g, '')}px`,
					});
				}
			}, [props.attributes.height, props.attributes.sizing]);

			element.useEffect(() => {
				if (
					props.attributes.sizing === 'fixed' &&
					(props.attributes.width === '100%' || props.attributes.height === '100%')
				) {
					props.setAttributes({
						...props.attributes,
						width:
							props.attributes.width === '100%'
								? props.attributes.max_width
								: undefined,
						height:
							props.attributes.height === '100%'
								? props.attributes.max_height
								: undefined,
					});
				}
			}, [props.attributes.width, props.attributes.sizing]);

			element.useEffect(() => {
				if (aspectRatio === 'custom') {
					const padding_top =
						typeof maxHeight === 'number' && typeof maxWidth === 'number'
							? `${(maxHeight / maxWidth) * 100}%`
							: '56%';

					props.setAttributes({ ...props.attributes, padding_top });
				}
			}, [maxWidth, maxHeight, aspectRatio]);

			element.useEffect(() => {
				if (embed === 'in-page-horizontal' || embed === 'in-page-vertical') {
					props.setAttributes({ ...props.attributes, sizing: 'fixed' });
				}
			}, [embed]);

			// Sanitize the IDs we need
			var sanitizeIds = function (id) {
				if (id.indexOf('ref:') === 0) {
					return id;
				}
				return id.replace(/\D/g, '');
			};

			/**
			 * Set attributes when a video is selected.
			 *
			 * Listens to the change event on our hidden
			 * input and will grab the shortcode from the
			 * inputs value, parsing out the attributes
			 * we need and setting those as props.
			 */
			var onSelectVideo = function () {
				var btn = document.getElementById(target);
				var attrs = wp.shortcode.attrs(btn.value);
				var setAttrs = {
					account_id: sanitizeIds(attrs.named.account_id),
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
					mute: '',
					playsinline: '',
					picture_in_picture: '',
					embed: attrs.named.embed,
				};

				if (attrs.numeric[0] === '[bc_video') {
					setAttrs.player_id = attrs.named.player_id;
					setAttrs.video_id = sanitizeIds(attrs.named.video_id);
					setAttrs.autoplay = attrs.named.autoplay;
					setAttrs.mute = attrs.named.mute;
					setAttrs.playsinline = attrs.named.playsinline;
					setAttrs.picture_in_picture = attrs.named.picture_in_picture;
					setAttrs.padding_top = attrs.named.padding_top;
				} else if (attrs.numeric[0] === '[bc_playlist') {
					setAttrs.player_id = attrs.named.player_id;
					setAttrs.playlist_id = sanitizeIds(attrs.named.playlist_id);
					setAttrs.autoplay = attrs.named.autoplay;
					setAttrs.mute = attrs.named.mute;
					setAttrs.playsinline = attrs.named.playsinline;
					setAttrs.padding_top = attrs.named.padding_top;
				} else if (attrs.numeric[0] === '[bc_experience') {
					setAttrs.experience_id = attrs.named.experience_id;

					if (typeof attrs.named.video_ids !== 'undefined') {
						setAttrs.video_ids = sanitizeIds(attrs.named.video_ids);
					} else {
						setAttrs.playlist_id = sanitizeIds(attrs.named.playlist_id);
					}
				}

				props.setAttributes(setAttrs);
			};

			// Listen for a change event on our hidden input
			jQuery(document).on('change', '#' + target, onSelectVideo);

			// Set up our controls
			var controls = el(
				BlockControls,
				{ key: 'controls' },
				el(
					'div',
					{ className: 'components-toolbar' },
					el(IconButton, {
						className:
							'brightcove-add-media components-icon-button components-toolbar__control',
						label: videoId.playlist_id
							? __('Change Playlist', 'brightcove')
							: __('Change Video', 'brightcove'),
						icon: 'edit',
						'data-target': '#' + target,
					}),
				),
			);

			// If no video has been selected yet, show the selection view
			if (
				!accountId.length &&
				(!playerId.length || !experienceId.length) &&
				(!videoId.length || !playlistId.length || videoIds.length)
			) {
				return el(Placeholder, {
					icon: 'media-video',
					label: 'Brightcove',
					instructions: userPermission
						? __(
								'Select a video file or playlist from your Brightcove library',
								'brightcove',
						  )
						: __("You don't have permissions to add Brightcove videos.", 'brightcove'),
					children: [
						userPermission
							? el(
									'button',
									{
										className: 'brightcove-add-media button button-large',
										'data-target': '#' + target,
										key: 'button',
									},
									__('Brightcove Media', 'brightcove'),
							  )
							: '',
						el('input', { id: target, hidden: true, key: 'input' }),
					],
				});

				// Otherwise render the shortcode
			}
			var src = '';

			if (experienceId.length) {
				var urlAttrs = '';
				if (videoIds.length) {
					urlAttrs = 'videoIds=' + videoIds;
				} else {
					urlAttrs = 'playlistId=' + playlistId;
				}
				src =
					'//players.brightcove.net/' +
					accountId +
					'/experience_' +
					experienceId +
					'/index.html?' +
					urlAttrs;
			} else if (videoId.length) {
				src =
					'//players.brightcove.net/' +
					accountId +
					'/' +
					playerId +
					'_default/index.html?videoId=' +
					videoId;
			} else {
				src =
					'//players.brightcove.net/' +
					accountId +
					'/' +
					playerId +
					'_default/index.html?playlistId=' +
					playlistId;
			}

			if (typeof height === 'undefined') {
				height = 250;
			}

			if (typeof width === 'undefined') {
				width = 500;
			}

			const players = wpbc.players[accountId]
				.filter((player) => {
					return playlistId ? player.is_playlist : !player.is_playlist;
				})
				.reduce((previousValue, currentValue) => {
					return [
						...previousValue,
						{
							label: currentValue.name,
							value: currentValue.id,
						},
					];
				}, []);

			let embedStyleOptions = [{ label: __('iFrame', 'brightcove'), value: 'iframe' }];
			embedStyleOptions = playlistId
				? [
						...embedStyleOptions,
						{
							label: __('JavaScript Horizontal', 'brightcove'),
							value: 'in-page-horizontal',
						},
						{
							label: __('JavaScript Vertical', 'brightcove'),
							value: 'in-page-vertical',
						},
				  ]
				: [
						{ label: __('JavaScript', 'brightcove'), value: 'in-page' },
						...embedStyleOptions,
				  ];

			const sizingField = el(components.RadioControl, {
				label: __('Sizing', 'brightcove'),
				selected: sizing,
				options: [
					{
						label: __('Responsive', 'brightcove'),
						value: 'responsive',
					},
					{ label: __('Fixed', 'brightcove'), value: 'fixed' },
				],
				onChange: function (value) {
					props.setAttributes({
						...props.attributes,
						sizing: value,
					});
				},
			});

			return [
				userPermission ? controls : '',
				el('iframe', {
					src: src,
					style: { height: height, width: width, display: 'block', margin: '0 auto' },
					allowFullScreen: true,
					key: 'iframe',
				}),
				el('input', { id: target, hidden: true, key: 'input' }),
				el(
					InspectorControls,
					{ key: 'inspector' }, // Display the block options in the inspector panel.
					el(
						components.PanelBody,
						{
							title: __('Settings', 'brightcove'),
							initialOpen: true,
						},
						el('p', {}, `Source: ${accountName}`),
						videoId && el('p', {}, `Video ID: ${videoId}`),
						playlistId && el('p', {}, `Playlist ID: ${playlistId}`),
						el(components.SelectControl, {
							label: __('Video Player', 'brightcove'),
							value: playerId,
							options: players,
							onChange: function (value) {
								props.setAttributes({
									...props.attributes,
									player_id: value,
								});
							},
						}),
						el(components.CheckboxControl, {
							label: __('Autoplay', 'brightcove'),
							checked: autoplay,
							onChange: function (value) {
								props.setAttributes({
									...props.attributes,
									autoplay: value && 'autoplay',
								});
							},
						}),
						el(components.CheckboxControl, {
							label: __('Mute', 'brightcove'),
							checked: mute,
							onChange: function (value) {
								props.setAttributes({
									...props.attributes,
									mute: value && 'muted',
								});
							},
						}),
						el(components.CheckboxControl, {
							label: __('Plays in line', 'brightcove'),
							checked: playsinline,
							onChange: function (value) {
								props.setAttributes({
									...props.attributes,
									playsinline: value && 'playsinline',
								});
							},
						}),
						!playlistId &&
							el(components.CheckboxControl, {
								label: __('Enable Picture in Picturee', 'brightcove'),
								checked: pictureinpicture,
								onChange: function (value) {
									props.setAttributes({
										...props.attributes,
										picture_in_picture: value && 'pictureinpicture',
									});
								},
							}),
						el(components.RadioControl, {
							label: __('Embed Style', 'brightcove'),
							selected: embed,
							options: embedStyleOptions,
							onChange: function (value) {
								props.setAttributes({
									...props.attributes,
									embed: value,
								});
							},
						}),
						embed === 'in-page-horizontal' || embed === 'in-page-vertical'
							? el(components.Disabled, {}, sizingField)
							: sizingField,
						el(components.SelectControl, {
							label: __('Aspect Ratio', 'brightcove'),
							value: aspectRatio,
							options: [
								{
									label: __('16:9', 'brightcove'),
									value: '16:9',
								},
								{
									label: __('4:3', 'brightcove'),
									value: '4:3',
								},
								{
									label: __('Custom', 'brightcove'),
									value: 'custom',
								},
							],
							onChange: function (value) {
								let height;
								let padding_top;

								if (value === '16:9') {
									height = '360px';
									padding_top = '56%';
								} else if (value === '4:3') {
									height = '480px';
									padding_top = '75%';
								} else {
									height = `${maxHeight}px`;

									padding_top =
										typeof maxHeight === 'number' &&
										typeof maxWidth === 'number'
											? `${(maxHeight / maxWidth) * 100}%`
											: '56%';
								}

								props.setAttributes({
									...props.attributes,
									aspect_ratio: value,
									height,
									padding_top,
								});
							},
						}),
						el(components.TextControl, {
							label: __('Width', 'brightcove'),
							type: 'number',
							value: width,
							onChange: function (value) {
								let width = `${value}px`;
								const max_width = width;

								if (sizing === 'responsive') {
									width = '100%';
								}

								props.setAttributes({
									...props.attributes,
									width,
									max_width,
								});
							},
						}),
						el(components.TextControl, {
							label: __('Height', 'brightcove'),
							type: 'number',
							value: sizing === 'fixed' ? height : maxHeight,
							disabled: isHeightFieldDisabled,
							onChange: function (value) {
								let height = `${value}px`;
								const max_height = height;

								if (sizing === 'responsive') {
									height = '100%';
								}

								props.setAttributes({
									...props.attributes,
									height,
									max_height,
								});
							},
						}),
					),
				),
			];
		},

		save: function () {
			return null;
		},
	});
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.editor);
