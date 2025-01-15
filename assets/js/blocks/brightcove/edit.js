/* global wpbc,bcBlock,jQuery */

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps, BlockControls, InspectorControls } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import {
	Button,
	ToolbarGroup,
	ToolbarButton,
	Placeholder,
	RadioControl,
	PanelBody,
	SelectControl,
	TextControl,
	CheckboxControl,
	FocusableIframe,
} from '@wordpress/components';

/**
 * Edit component.
 *
 * @param {object}   props                  The block props.
 * @param {object}   props.attributes       Block attributes.
 * @param {string}   props.attributes.title Custom title to be displayed.
 * @param {string}   props.className        Class name for the block.
 * @param {Function} props.setAttributes    Sets the value for block attributes.
 * @returns {Function} Render the edit screen
 */
const BrightCoveBlockEdit = (props) => {
	const { attributes, setAttributes, clientId } = props;
	const accountId = attributes.account_id || '';
	const playerId = attributes.player_id || '';
	const videoId = attributes.video_id || '';
	const playlistId = attributes.playlist_id || '';
	const experienceId = attributes.experience_id || '';
	const videoIds = attributes.video_ids || '';
	const autoplay = attributes.autoplay || '';
	const playsinline = attributes.playsinline || '';
	const pictureinpicture = attributes.picture_in_picture || '';
	const languageDetection = attributes.language_detection || '';
	const applicationId = attributes.application_id || '';
	const embed = attributes.embed || '';
	const mute = attributes.mute || '';
	const sizing = attributes.sizing || 'responsive';
	const aspectRatio = attributes.aspect_ratio || '16:9';
	const width = attributes.width || '640px';
	const height = attributes.height || '360px';
	const inPageExperienceId = attributes.in_page_experience_id || '';

	const $ = jQuery;
	const userPermission = !!bcBlock.userPermission;

	const [isHeightFieldDisabled, setIsHeightFieldDisabled] = useState(true);

	// Set the field we want to target
	const target = `brightcove-${clientId}`;

	const account = Object.values(wpbc?.preload?.accounts || {}).find(
		(account) => account?.account_id === accountId,
	);

	const accountName = account?.account_name || '';

	const blockProps = useBlockProps();

	useEffect(() => {
		if (aspectRatio === 'custom') {
			setIsHeightFieldDisabled(false);
		} else {
			setIsHeightFieldDisabled(true);
		}
	}, [aspectRatio]);

	useEffect(() => {
		if (!experienceId) {
			let newHeight;
			if (aspectRatio === '16:9') {
				newHeight = `${parseInt(parseInt(width, 10) * (9 / 16), 10)}px`;
			} else if (aspectRatio === '4:3') {
				newHeight = `${parseInt(parseInt(width, 10) * (3 / 4), 10)}px`;
			} else {
				newHeight = height;
			}

			setAttributes({
				...attributes,
				height: newHeight,
			});
		}
	}, [width, sizing, aspectRatio, height, experienceId]);

	useEffect(() => {
		if (embed === 'in-page-horizontal' || embed === 'in-page-vertical') {
			setAttributes({ ...attributes, sizing: 'fixed' });
		}
	}, [embed]);

	useEffect(() => {
		if (pictureinpicture === 'pictureinpicture' && embed === 'iframe') {
			setAttributes({ ...attributes, embed: 'in-page' });
		}
	}, [pictureinpicture]);

	// Sanitize the IDs we need
	const sanitizeIds = function (ids) {
		if (!ids) {
			return ids;
		}

		return ids
			.split(',')
			.map(function (id) {
				id = id.trim();
				if (id.indexOf('ref:') === 0) {
					return id;
				}
				return id.replace(/\D/g, '');
			})
			.join(',');
	};

	/**
	 * Set attributes when a video is selected.
	 *
	 * Listens to the change event on our hidden
	 * input and will grab the shortcode from the
	 * inputs value, parsing out the attributes
	 * we need and setting those as props.
	 */
	const onSelectVideo = function () {
		const btn = document.getElementById(target);
		const attrs = wp.shortcode.attrs(btn.value);

		const setAttrs = {
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
			language_detection: '',
			embed: attrs.named.embed,
			sizing: attrs.named.sizing,
			aspect_ratio: attrs.named.aspect_ratio,
		};

		if (attrs.numeric[0] === '[bc_video') {
			setAttrs.player_id = attrs.named.player_id;
			setAttrs.video_id = sanitizeIds(attrs.named.video_id);
			setAttrs.autoplay = attrs.named.autoplay;
			setAttrs.mute = attrs.named.mute;
			setAttrs.playsinline = attrs.named.playsinline;
			setAttrs.picture_in_picture = attrs.named.picture_in_picture;
			setAttrs.application_id = attrs.named.application_id;
			setAttrs.language_detection = attrs.named.language_detection;
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
		} else if (attrs.numeric[0] === '[bc_in_page_experience') {
			setAttrs.in_page_experience_id = attrs.named.in_page_experience_id;
		}

		// Prevent set attributes with empty values
		if (btn.value) {
			setAttributes(setAttrs);
		}
	};

	// Listen for a change event on our hidden input
	$(document).on('change', `#${target}`, onSelectVideo);

	// If no video has been selected yet, show the selection view
	if (
		!accountId.length &&
		(!playerId.length || !experienceId.length || !inPageExperienceId.length) &&
		(!videoId.length || !playlistId.length || videoIds.length)
	) {
		return (
			<div {...blockProps}>
				<Placeholder
					icon="media-video"
					label="Brightcove"
					instructions={
						userPermission
							? __(
									'Select a video file or playlist from your Brightcove library',
									'brightcove',
								)
							: __(
									"You don't have permissions to add Brightcove videos.",
									'brightcove',
								)
					}
				>
					{userPermission && (
						<Button
							className="brightcove-add-media"
							variant="secondary"
							data-target={`#${target}`}
							key="button"
							onClick={(e) => {
								wpbc.triggerModal();

								wpbc.modal.target = e.currentTarget.dataset.target;
							}}
						>
							{__('Brightcove Media', 'brightcove')}
						</Button>
					)}
					<input id={target} hidden key="input" />
				</Placeholder>
			</div>
		);
	}

	let src = '';

	if (experienceId.length) {
		let urlAttrs = '';
		if (videoIds.length) {
			urlAttrs = `videoIds=${videoIds}`;
		} else {
			urlAttrs = `playlistId=${playlistId}`;
		}
		src = `//players.brightcove.net/${accountId}/experience_${experienceId}/index.html?${urlAttrs}`;
	} else if (videoId.length) {
		src = `//players.brightcove.net/${accountId}/${playerId}_default/index.html?videoId=${videoId}`;
	} else if (inPageExperienceId.length) {
		src = `//players.brightcove.net/${accountId}/experience_${inPageExperienceId}/index.html`;
	} else {
		src = `//players.brightcove.net/${accountId}/${playerId}_default/index.html?playlistId=${playlistId}`;
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

	const isExperience = inPageExperienceId || experienceId;

	let embedStyleOptions = [{ label: __('iFrame', 'brightcove'), value: 'iframe' }];

	embedStyleOptions =
		playlistId && !isExperience
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
			: [{ label: __('JavaScript', 'brightcove'), value: 'in-page' }, ...embedStyleOptions];

	return (
		<div {...blockProps}>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						className="brightcove-add-Video"
						label={
							playlistId
								? __('Change Playlist', 'brightcove')
								: __('Change Video', 'brightcove')
						}
						icon="edit"
						data-target={`#${target}`}
						onClick={(e) => {
							wpbc.triggerModal();
							wpbc.modal.target = e.currentTarget.dataset.target;
						}}
					/>
				</ToolbarGroup>
			</BlockControls>

			<FocusableIframe
				src={src}
				style={{
					height,
					width,
					display: 'block',
					margin: '0 auto',
				}}
				allowFullScreen
				key="iframe"
			/>
			<input id={target} hidden key="input" />

			<InspectorControls>
				<PanelBody title={__('Settings', 'brightcove')} initialOpen>
					<p>{sprintf(__('Source: %1$s', 'brightcove'), accountName)}</p>
					{videoId && <p>{sprintf(__('Video ID: %1$s', 'brightcove'), videoId)}</p>}
					{playlistId && (
						<p>{sprintf(__('Playlist ID: %1$s', 'brightcove'), playlistId)}</p>
					)}

					{!isExperience && (
						<SelectControl
							label={__('Video Player', 'brightcove')}
							value={playerId}
							options={players}
							onChange={(value) => {
								setAttributes({
									...attributes,
									player_id: value,
								});
							}}
						/>
					)}

					{!isExperience && (
						<TextControl
							label={__('Application Id:', 'brightcove')}
							type="string"
							value={applicationId}
							onChange={(value) => {
								setAttributes({
									...attributes,
									application_id: value,
								});
							}}
						/>
					)}

					{!isExperience && (
						<CheckboxControl
							label={__('Autoplay', 'brightcove')}
							checked={autoplay}
							onChange={(value) => {
								setAttributes({
									...attributes,
									autoplay: value && 'autoplay',
									mute: value && 'muted',
									playsinline: value && 'playsinline',
								});
							}}
						/>
					)}

					{!isExperience && (
						<CheckboxControl
							label={__('Mute', 'brightcove')}
							checked={mute}
							onChange={(value) => {
								setAttributes({
									...attributes,
									mute: value,
								});
							}}
							disabled={autoplay === 'autoplay'}
						/>
					)}

					{!isExperience && (
						<CheckboxControl
							label={__('Plays in line', 'brightcove')}
							checked={playsinline}
							onChange={(value) => {
								setAttributes({
									...attributes,
									playsinline: value,
								});
							}}
							disabled={autoplay === 'autoplay'}
						/>
					)}

					{!playlistId && !isExperience && (
						<CheckboxControl
							label={__('Enable Picture in Picture', 'brightcove')}
							checked={pictureinpicture}
							onChange={(value) => {
								setAttributes({
									...attributes,
									picture_in_picture: value && 'pictureinpicture',
								});
							}}
						/>
					)}

					{!isExperience && (
						<CheckboxControl
							label={__('Enable Language Detection', 'brightcove')}
							checked={languageDetection}
							onChange={(value) => {
								setAttributes({
									...attributes,
									language_detection: value && 'languagedetection',
								});
							}}
						/>
					)}

					<RadioControl
						label={__('Embed Style', 'brightcove')}
						selected={embed}
						options={embedStyleOptions}
						onChange={(value) => {
							setAttributes({
								...attributes,
								embed: value,
							});
						}}
						disabled={
							languageDetection === 'languagedetection' ||
							pictureinpicture === 'pictureinpicture'
						}
					/>

					<RadioControl
						label={__('Sizing', 'brightcove')}
						selected={sizing}
						options={[
							{
								label: __('Responsive', 'brightcove'),
								value: 'responsive',
							},
							{ label: __('Fixed', 'brightcove'), value: 'fixed' },
						]}
						onChange={(value) => {
							setAttributes({
								...attributes,
								sizing: value,
							});
						}}
						disabled={embed === 'in-page-horizontal' || embed === 'in-page-vertical'}
					/>

					{!isExperience && (
						<SelectControl
							label={__('Aspect Ratio', 'brightcove')}
							value={aspectRatio}
							options={[
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
							]}
							onChange={(value) => {
								setAttributes({
									...attributes,
									aspect_ratio: value,
								});
							}}
						/>
					)}

					{!isExperience && (
						<TextControl
							label={__('Width', 'brightcove')}
							type="number"
							value={width?.replace(/[^0-9]/g, '')}
							onChange={(value) => {
								const newWidth = `${value}px`;
								setAttributes({
									...attributes,
									width: newWidth,
								});
							}}
						/>
					)}

					{!isExperience && (
						<TextControl
							label={__('Height', 'brightcove')}
							type="number"
							value={height?.replace(/[^0-9]/g, '')}
							disabled={isHeightFieldDisabled}
							onChange={(value) => {
								const height = `${value}px`;
								setAttributes({
									...attributes,
									height,
								});
							}}
						/>
					)}
				</PanelBody>
			</InspectorControls>
		</div>
	);
};
export default BrightCoveBlockEdit;
