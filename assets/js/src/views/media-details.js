var MediaDetailsView = BrightcoveView.extend({
	tagName: 'div',
	className: 'media-details',

	attributes: function () {
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
		'change #pictureinpicture': 'togglePictureinpicture',
		'change .experience-details input[name="sizing"],.experience-details input[name="embed-style"]':
			'toggleExperienceUnits',
		'change #video-player, #autoplay, #pictureinpicture, #playsinline, #mute, input[name="embed-style"], input[name="sizing"], #aspect-ratio, #width, #height':
			'generateShortcode',
		'change #generate-shortcode': 'toggleShortcodeGeneration',
	},

	triggerEditMedia: function (event) {
		event.preventDefault();
		wpbc.broadcast.trigger('edit:media', this.model, this.mediaType);
	},

	triggerPreviewMedia: function (event) {
		event.preventDefault();
		var shortcode = $('#shortcode').val();
		wpbc.broadcast.trigger('preview:media', this.model, shortcode);
	},

	triggerCancelPreviewMedia: function (event) {
		wpbc.broadcast.trigger('cancelPreview:media', this.mediaType);
	},

	togglePlaylistSizing: function (event) {
		var embedStyle = $('.playlist-details input[name="embed-style"]:checked').val(),
			$sizing = $('#sizing-fixed, #sizing-responsive');

		if (embedStyle === 'iframe') {
			$sizing.removeAttr('disabled');
		} else {
			$sizing.attr('disabled', true);
		}
	},

	toggleUnits: function (event) {
		var value = $('#aspect-ratio').val();

		if (value === 'custom') {
			$('#height').removeAttr('readonly');
		} else {
			var $height = $('#height'),
				width = $('#width').val();

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

	togglePictureinpicture: function (event) {
		var $iframeRadioButton = $('#embed-style-iframe'),
			$pictureinpicture_checked = $('#pictureinpicture').is(':checked');

		if ($pictureinpicture_checked) {
			$iframeRadioButton.prop('checked', false);
			$iframeRadioButton.attr('disabled', true);
		} else {
			$iframeRadioButton.attr('disabled', false);
		}
	},

	toggleExperienceUnits: function (event) {
		var $sizingField = $('input[name="sizing"]:checked');
		var $sizing = $sizingField.val();
		var $embedStyle = $('input[name="embed-style"]:checked').val();
		var $height = $('#height');
		var $width = $('#width');
		var $inputSizing = $('input[name="sizing"]');
		var $sizingDiv = $('.embed-sizing-div');

		$height.removeAttr('readonly');
		$width.removeAttr('readonly');
		$sizingField.show();
		$sizingDiv.show();

		if ($sizing === 'responsive' && $embedStyle === 'javascript') {
			$height.attr('readonly', true);
		} else if ($sizing === 'fixed' && $embedStyle === 'javascript') {
			$height.removeAttr('readonly');
			$width.removeAttr('readonly');
		} else {
			$inputSizing.attr('checked', false);
			$('#sizing-fixed').attr('checked', true);
			$sizingDiv.hide();
		}
	},

	generateShortcode: function () {
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
			default:
				this.generatePlaylistShortcode();
		}
	},

	generateVideoShortcode: function () {
		var videoId = this.model.get('id').replace(/\D/g, ''),
			accountId = this.model.get('account_id').replace(/\D/g, ''),
			playerId = $('#video-player').val(),
			autoplay = $('#autoplay').is(':checked') ? 'autoplay' : '',
			playsinline = $('#playsinline').is(':checked') ? 'playsinline' : '',
			pictureinpicture = $('#pictureinpicture').is(':checked') ? 'pictureinpicture' : '',
			mute = $('#mute').is(':checked') ? 'muted' : '',
			embedStyle = $('input[name="embed-style"]:checked').val(),
			sizing = $('input[name="sizing"]:checked').val(),
			aspectRatio = $('#aspect-ratio').val(),
			paddingTop = '',
			width = $('#width').val(),
			height = $('#height').val(),
			units = 'px',
			minWidth = '0px',
			maxWidth = width + units,
			shortcode;

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

			if (embedStyle === 'iframe') {
				minWidth = width;
			}
		}

		shortcode =
			'[bc_video video_id="' +
			videoId +
			'" account_id="' +
			accountId +
			'" player_id="' +
			playerId +
			'" ' +
			'embed="' +
			embedStyle +
			'" padding_top="' +
			paddingTop +
			'%" autoplay="' +
			autoplay +
			'" ' +
			'min_width="' +
			minWidth +
			'" playsinline="' +
			playsinline +
			'" picture_in_picture="' +
			pictureinpicture +
			'" max_width="' +
			maxWidth +
			'" ' +
			'mute="' +
			mute +
			'" width="' +
			width +
			'" height="' +
			height +
			'"' +
			' ]';

		$('#shortcode').val(shortcode);
	},
	generateExperienceShortcode: function () {
		var videoIds, accountId;
		if (typeof this.model.get('id') !== 'undefined') {
			this.model.set('account_id', this.model.get('account'));
			videoIds = this.model.get('id').join(',');
			accountId = this.model.get('account_id').replace(/\D/g, '');
		} else {
			videoIds = '';
			accountId = document.getElementById('brightcove-media-source').value;
		}

		var experienceId = $('#video-player').val(),
			embedStyle = $('input[name="embed-style"]:checked').val(),
			sizing = $('input[name="sizing"]:checked').val(),
			width = $('#width').val(),
			height = $('#height').val(),
			units = 'px',
			minWidth = '0px',
			maxWidth = width + units,
			shortcode;

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
			'[bc_experience experience_id="' +
			experienceId +
			'" account_id="' +
			accountId +
			'" ' +
			'embed="' +
			embedStyle +
			'" min_width="' +
			minWidth +
			'" max_width="' +
			maxWidth +
			'" ' +
			'width="' +
			width +
			'" height="' +
			height +
			'" ' +
			'video_ids="' +
			videoIds +
			'" ' +
			' ]';

		$('#shortcode').val(shortcode);
	},

	generatePlaylistShortcode: function () {
		var playlistId = this.model.get('id').replace(/\D/g, ''),
			accountId = this.model.get('account_id').replace(/\D/g, ''),
			playerId = $('#video-player').val() || 'default',
			autoplay = $('#autoplay').is(':checked') ? 'autoplay' : '',
			playsinline = $('#playsinline').is(':checked') ? 'playsinline' : '',
			mute = $('#mute').is(':checked') ? 'muted' : '',
			embedStyle = $('input[name="embed-style"]:checked').val(),
			sizing = $('input[name="sizing"]:checked').val(),
			aspectRatio = $('#aspect-ratio').val(),
			paddingTop = '',
			width = $('#width').val(),
			height = $('#height').val(),
			units = 'px',
			minWidth = '0px;',
			maxWidth = width + units,
			shortcode;

		if (embedStyle === 'in-page-vertical') {
			shortcode =
				'[bc_playlist playlist_id="' +
				playlistId +
				'" account_id="' +
				accountId +
				'" player_id="' +
				playerId +
				'" ' +
				'embed="in-page-vertical" autoplay="' +
				autoplay +
				'" playsinline="' +
				playsinline +
				'" mute="' +
				mute +
				'" ' +
				'min_width="" max_width="" padding_top="" ' +
				'width="' +
				width +
				units +
				'" height="' +
				height +
				units +
				'"' +
				' ]';
		} else if (embedStyle === 'in-page-horizontal') {
			shortcode =
				'[bc_playlist playlist_id="' +
				playlistId +
				'" account_id="' +
				accountId +
				'" player_id="' +
				playerId +
				'" ' +
				'embed="in-page-horizontal" autoplay="' +
				autoplay +
				'" playsinline="' +
				playsinline +
				'" mute="' +
				mute +
				'" ' +
				'min_width="" max_width="" padding_top="" ' +
				'width="' +
				width +
				units +
				'" height="' +
				height +
				units +
				'"' +
				' ]';
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
				'[bc_playlist playlist_id="' +
				playlistId +
				'" account_id="' +
				accountId +
				'" player_id="' +
				playerId +
				'" ' +
				'embed="iframe" autoplay="' +
				autoplay +
				'" playsinline="' +
				playsinline +
				'" mute="' +
				mute +
				'" ' +
				'min_width="' +
				minWidth +
				'" max_width="' +
				maxWidth +
				'" padding_top="' +
				paddingTop +
				'%" ' +
				'width="' +
				width +
				'" height="' +
				height +
				'"' +
				' ]';
		}

		$('#shortcode').val(shortcode);
	},
	generatePlaylistExperienceShortcode: function () {
		var playlistId = this.model.get('id').replace(/\D/g, ''),
			accountId = this.model.get('account_id').replace(/\D/g, ''),
			experienceId = $('#video-player').val(),
			embedStyle = $('input[name="embed-style"]:checked').val(),
			sizing = $('input[name="sizing"]:checked').val(),
			width = $('#width').val(),
			height = $('#height').val(),
			units = 'px',
			minWidth = '0px',
			maxWidth = width + units,
			shortcode;

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
			'[bc_experience experience_id="' +
			experienceId +
			'" account_id="' +
			accountId +
			'" ' +
			'embed="' +
			embedStyle +
			'" min_width="' +
			minWidth +
			'" max_width="' +
			maxWidth +
			'" ' +
			'width="' +
			width +
			'" height="' +
			height +
			'" ' +
			'playlist_id="' +
			playlistId +
			'" ' +
			' ]';

		$('#shortcode').val(shortcode);
	},

	toggleShortcodeGeneration: function () {
		var method = $('#generate-shortcode').val(),
			$fields = $(
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

	initialize: function (options) {
		options = options || {};
		this.type = options.type ? options.type : 'grid';
		this.mediaType = options.mediaType;
		this.listenTo(wpbc.broadcast, 'insert:shortcode', this.insertShortcode);
		this.listenTo(this.model, 'change', this.render);
	},

	/**
	 * @returns {wp.media.view.Media} Returns itself to allow chaining
	 */
	render: function (options) {
		options = _.extend({}, options, this.model.toJSON());
		options.duration = this.model.getReadableDuration();
		options.updated_at_readable = this.model.getReadableDate('updated_at');
		options.created_at_readable = this.model.getReadableDate('created_at');
		options.account_name = this.model.getAccountName();

		this.template = wp.template('brightcove-media-item-details-' + this.mediaType);

		this.$el.html(this.template(options));

		this.delegateEvents();
		this.generateShortcode();

		return this;
	},

	/* Prevent this.remove() from removing the container element for the details view */
	remove: function () {
		this.undelegateEvents();
		this.$el.empty();
		this.stopListening();
		return this;
	},
});
