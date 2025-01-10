import BrightcoveView from './brightcove';

const $ = jQuery;

const MediaDetailsView = BrightcoveView.extend({
	tagName: 'div',
	className: 'media-details',

	attributes() {
		return {
			tabIndex: 0,
			role: 'checkbox',
			'aria-label': this.model.get('title'),
			'aria-checked': false,
			'data-id': this.model.get('id'),
		};
	},

	events: {
		'click .brightcove.edit.button': 'triggerEditMedia',
		'click .brightcove.preview.button': 'triggerPreviewMedia',
		'click .brightcove.back.button': 'triggerCancelPreviewMedia',
		'click .playlist-details input[name="embed-style"]': 'togglePlaylistSizing',
		'change #aspect-ratio': 'toggleUnits',
		'change #autoplay': 'toggleAutoplay',
		'change #pictureinpicture': 'toggleIframe',
		'change #languagedetection': 'toggleIframe',
		'change .experience-details input[name="sizing"],.experience-details input[name="embed-style"]':
			'toggleExperienceUnits',
		'change #video-player, #applicationid, #autoplay, #pictureinpicture, #languagedetection, #playsinline, #mute, input[name="embed-style"], input[name="sizing"], #aspect-ratio, #height':
			'generateShortcode',
		'change #generate-shortcode': 'toggleShortcodeGeneration',
		'change #width': 'calculateHeight',
	},

	triggerEditMedia(event) {
		event.preventDefault();
		wpbc.broadcast.trigger('edit:media', this.model, this.mediaType);
	},

	triggerPreviewMedia(event) {
		event.preventDefault();
		const shortcode = $('#shortcode').val();
		wpbc.broadcast.trigger('preview:media', this.model, shortcode);
	},

	triggerCancelPreviewMedia(event) {
		wpbc.broadcast.trigger('cancelPreview:media', this.mediaType);
	},

	togglePlaylistSizing(event) {
		const embedStyle = $('.playlist-details input[name="embed-style"]:checked').val();
		const $sizing = $('#sizing-fixed, #sizing-responsive');

		if (embedStyle === 'iframe') {
			$sizing.removeAttr('disabled');
		} else {
			$sizing.attr('disabled', true);
		}
	},

	toggleUnits(event) {
		const value = $('#aspect-ratio').val();
		if (value === 'custom') {
			$('#height').removeAttr('readonly');
		} else {
			const $height = $('#height');
			const width = $('#width').val();

			$height.attr('readonly', true);

			if (width > 0) {
				if (value === '16:9') {
					$height.val(width / (16 / 9));
				} else {
					$height.val(width / (4 / 3));
				}
			}
		}
	},

	toggleIframe(event) {
		const $iframeRadioButton = $('#embed-style-iframe');
		const notAllowedOptions = [
			$('#pictureinpicture').is(':checked'),
			$('#languagedetection').is(':checked'),
		];

		if (notAllowedOptions.includes(true)) {
			$iframeRadioButton.prop('checked', false);
			$iframeRadioButton.attr('disabled', true);
		} else {
			$iframeRadioButton.attr('disabled', false);
		}
	},

	toggleAutoplay(event) {
		return $('#autoplay').is(':checked')
			? this.toggleAutoplayOptions(true)
			: this.toggleAutoplayOptions(false);
	},

	toggleAutoplayOptions(value) {
		const $playsinline = $('#playsinline');
		const $mute = $('#mute');
		$playsinline.prop('checked', value);
		$playsinline.prop('disabled', value);
		$mute.prop('checked', value);
		$mute.prop('disabled', value);
	},

	toggleExperienceUnits(event) {
		const $sizingField = $('input[name="sizing"]:checked');
		const $sizing = $sizingField.val();
		const $embedStyle = $('input[name="embed-style"]:checked').val();
		const $height = $('#height');
		const $width = $('#width');
		const $inputSizing = $('input[name="sizing"]');
		const $sizingDiv = $('.embed-sizing-div');

		$height.removeAttr('readonly');
		$width.removeAttr('readonly');
		$sizingField.show();
		$sizingDiv.show();

		if ($sizing === 'responsive' && $embedStyle === 'in-page') {
			$height.attr('readonly', true);
		} else if ($sizing === 'fixed' && $embedStyle === 'in-page') {
			$height.removeAttr('readonly');
			$width.removeAttr('readonly');
		} else {
			$inputSizing.attr('checked', false);
			$('#sizing-fixed').attr('checked', true);
			$sizingDiv.hide();
		}
	},

	/**
	 * Calculate height based on aspect ratio.
	 */
	calculateHeight(event) {
		const aspectRatio = $('#aspect-ratio').val();
		const width = $('#width').val();

		if (aspectRatio === 'custom') {
			return;
		}

		const height = aspectRatio === '16:9' ? width / (16 / 9) : width / (4 / 3);
		$('#height').val(height);

		this.generateShortcode();
	},

	generateShortcode() {
		switch (this.mediaType) {
			case 'videos':
				this.generateVideoShortcode();
				break;
			case 'videoexperience':
				this.generateExperienceShortcode();
				break;
			case 'playlistexperience':
				this.generatePlaylistExperienceShortcode();
				break;
			case 'inpageexperiences':
				this.generateInPageExperienceShortcode();
				break;
			default:
				this.generatePlaylistShortcode();
		}
	},

	generateVideoShortcode() {
		const videoId = this.model.get('id').replace(/\D/g, '');
		const accountId = this.model.get('account_id').replace(/\D/g, '');
		const playerId = $('#video-player').val();
		const autoplay = $('#autoplay').is(':checked') ? 'autoplay' : '';
		const playsinline =
			$('#playsinline').is(':checked') || autoplay !== '' ? 'playsinline' : '';
		const mute = $('#mute').is(':checked') || autoplay !== '' ? 'muted' : '';
		const pictureinpicture = $('#pictureinpicture').is(':checked') ? 'pictureinpicture' : '';
		const languagedetection = $('#languagedetection').is(':checked') ? 'languagedetection' : '';
		const applicationId = $('#applicationid').val();
		const embedStyle = $('input[name="embed-style"]:checked').val();
		const sizing = $('input[name="sizing"]:checked').val();
		const aspectRatio = $('#aspect-ratio').val();
		const width = $('#width').val();
		const height = $('#height').val();
		const units = 'px';
		let shortcode;

		shortcode =
			`[bc_video video_id="${videoId}" account_id="${accountId}" player_id="${playerId}" ` +
			`embed="${embedStyle}" padding_top="${height}${units}" autoplay="${
				autoplay
			}" playsinline="${playsinline}" picture_in_picture="${
				pictureinpicture
			}" language_detection="${languagedetection}" application_id="${applicationId}" ` +
			`mute="${mute}" width="${width}${units}" height="${height}${units}" aspect_ratio="${
				aspectRatio
			}" sizing="${sizing}"` +
			` ]`;

		$('#shortcode').val(shortcode);
	},

	generateExperienceShortcode() {
		let videoIds;
		let accountId;
		if (typeof this.model.get('id') !== 'undefined') {
			this.model.set('account_id', this.model.get('account'));
			videoIds = this.model.get('id').join(',');
			accountId = this.model.get('account_id').replace(/\D/g, '');
		} else {
			videoIds = '';
			accountId = document.getElementById('brightcove-media-source').value;
		}

		const experienceId = $('#video-player').val();
		const embedStyle = $('input[name="embed-style"]:checked').val();
		const width = $('#width').val();
		const height = $('#height').val();
		const units = 'px';
		let shortcode;

		shortcode =
			`[bc_experience experience_id="${experienceId}" account_id="${accountId}" ` +
			`embed="${embedStyle}" width="${width}${units}" height="${height}${units}" ` +
			`video_ids="${videoIds}" ` +
			` ]`;

		$('#shortcode').val(shortcode);
	},

	generatePlaylistShortcode() {
		const playlistId = this.model.get('id').replace(/\D/g, '');
		const accountId = this.model.get('account_id').replace(/\D/g, '');
		const playerId = $('#video-player').val() || 'default';
		const autoplay = $('#autoplay').is(':checked') ? 'autoplay' : '';
		const playsinline =
			$('#playsinline').is(':checked') || autoplay !== '' ? 'playsinline' : '';
		const mute = $('#mute').is(':checked') || autoplay !== '' ? 'muted' : '';
		const embedStyle = $('input[name="embed-style"]:checked').val();
		const sizing = $('input[name="sizing"]:checked').val();
		const aspectRatio = $('#aspect-ratio').val();
		let paddingTop = '';
		let width = $('#width').val();
		let height = $('#height').val();
		const units = 'px';
		let minWidth = '0px;';
		const maxWidth = width + units;
		let shortcode;

		if (embedStyle === 'in-page-vertical') {
			shortcode =
				`[bc_playlist playlist_id="${playlistId}" account_id="${accountId}" player_id="${
					playerId
				}" ` +
				`embed="in-page-vertical" autoplay="${autoplay}" playsinline="${
					playsinline
				}" mute="${mute}" ` +
				`min_width="" max_width="" padding_top="" ` +
				`width="${width}${units}" height="${height}${units}"` +
				` ]`;
		} else if (embedStyle === 'in-page-horizontal') {
			shortcode =
				`[bc_playlist playlist_id="${playlistId}" account_id="${accountId}" player_id="${
					playerId
				}" ` +
				`embed="in-page-horizontal" autoplay="${autoplay}" playsinline="${
					playsinline
				}" mute="${mute}" ` +
				`min_width="" max_width="" padding_top="" ` +
				`width="${width}${units}" height="${height}${units}"` +
				` ]`;
		} else if (embedStyle === 'iframe') {
			if (aspectRatio === '16:9') {
				paddingTop = '56';
			} else if (aspectRatio === '4:3') {
				paddingTop = '75';
			} else {
				paddingTop = (height / width) * 100;
			}

			if (sizing === 'responsive') {
				width = '100%';
				height = '100%';
			} else {
				width += units;
				height += units;

				minWidth = width;
			}

			shortcode =
				`[bc_playlist playlist_id="${playlistId}" account_id="${accountId}" player_id="${
					playerId
				}" ` +
				`embed="iframe" autoplay="${autoplay}" playsinline="${playsinline}" mute="${
					mute
				}" ` +
				`min_width="${minWidth}" max_width="${maxWidth}" padding_top="${paddingTop}%" ` +
				`width="${width}" height="${height}"` +
				` ]`;
		}

		$('#shortcode').val(shortcode);
	},
	generatePlaylistExperienceShortcode() {
		const playlistId = this.model.get('id').replace(/\D/g, '');
		const accountId = this.model.get('account_id').replace(/\D/g, '');
		const experienceId = $('#video-player').val();
		const embedStyle = $('input[name="embed-style"]:checked').val();
		const sizing = $('input[name="sizing"]:checked').val();
		let width = $('#width').val();
		let height = $('#height').val();
		const units = 'px';
		let minWidth = '0px';
		const maxWidth = width + units;
		let shortcode;

		if (sizing === 'responsive') {
			width = '100%';
			height = '100%';
		} else {
			width += units;
			height += units;

			if (embedStyle === 'iframe') {
				minWidth = width;
			}
		}

		shortcode =
			`[bc_experience experience_id="${experienceId}" account_id="${accountId}" ` +
			`embed="${embedStyle}" min_width="${minWidth}" max_width="${maxWidth}" ` +
			`width="${width}" height="${height}" ` +
			`playlist_id="${playlistId}" ` +
			` ]`;

		$('#shortcode').val(shortcode);
	},

	generateInPageExperienceShortcode() {
		const accountId = `${this.model.get('accountId')}`.replace(/\D/g, '');
		const inPageExperienceId = this.model.get('id');
		const embedStyle = $('input[name="embed-style"]:checked').val();

		let width = '';
		let height = '';

		const widthValue = Number($('#width').val());
		const heightValue = Number($('#height').val());

		if (typeof widthValue === 'number' && widthValue > 0) {
			width = `${widthValue}px`;
		}

		if (typeof heightValue === 'number' && heightValue > 0) {
			height = `${heightValue}px`;
		}

		const shortcode = `[bc_in_page_experience \
			account_id="${accountId}" \
			in_page_experience_id="${inPageExperienceId}" \
			embed="${embedStyle}" \
			width="${width}" \
			height="${height}"\
		]
		`;

		// remove Tabs from shortcode
		$('#shortcode').val(shortcode.replace(/\t+/g, ' '));
	},

	toggleShortcodeGeneration() {
		const method = $('#generate-shortcode').val();
		const $fields = $(
			'#video-player, #autoplay, #mute, input[name="embed-style"], input[name="sizing"], #aspect-ratio, #width, #height, #units',
		);

		if (method === 'manual') {
			$('#shortcode').removeAttr('readonly');
			$fields.attr('disabled', true);
		} else {
			$('#shortcode').attr('readonly', true);
			$fields.removeAttr('disabled');
		}
	},

	initialize(options) {
		options = options || {};
		this.type = options.type ? options.type : 'grid';
		this.mediaType = options.mediaType;
		this.listenTo(wpbc.broadcast, 'insert:shortcode', this.insertShortcode);
		this.listenTo(this.model, 'change', this.render);
	},

	/**
	 * @returns {wp.media.view.Media} Returns itself to allow chaining
	 */
	render(options) {
		options = _.extend({}, options, this.model.toJSON());
		// options.duration = this.model.getReadableDuration();
		options.duration = '0:05';
		options.updated_at_readable = this.model.getReadableDate('updated_at');
		options.created_at_readable = this.model.getReadableDate('created_at');
		options.account_name = this.model.getAccountName();

		this.template = wp.template(`brightcove-media-item-details-${this.mediaType}`);

		this.$el.html(this.template(options));

		this.delegateEvents();
		this.generateShortcode();

		return this;
	},

	/* Prevent this.remove() from removing the container element for the details view */
	remove() {
		this.undelegateEvents();
		this.$el.empty();
		this.stopListening();
		return this;
	},
});

export default MediaDetailsView;
