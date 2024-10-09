var VideoEditView = BrightcoveView.extend({
	tagName: 'div',
	className: 'video-edit brightcove attachment-details',
	template: wp.template('brightcove-video-edit'),

	events: {
		'click    .brightcove.button.save-sync': 'saveSync',
		'click    .brightcove.delete': 'deleteVideo',
		'click    .brightcove.button.back': 'back',
		'click    .setting .button': 'openMediaManager',
		'click    .attachment .check': 'removeAttachment',
		'click    .caption-secondary-fields .delete': 'removeCaptionRow',
		'click    .add-remote-caption': 'addCaptionRow',
		'change   .brightcove-variant': 'changeVariant',
		'click    .bc-labels-list .remove-label': 'removeLabel',
		'click    .bc-add-label': 'addLabel',
	},

	/**
	 * Changes template variant based on current variant.
	 * @param event
	 */
	changeVariant: function (event) {
		const valueSelected = this.$el.find('.brightcove-variant').val(),
			options = this.model.toJSON();

		let template;

		if (valueSelected === 'none') {
			template = wp.template('brightcove-video-edit');
			$('.brightcove-variant-details').replaceWith(template(options));
			this.setCustomFields(options.custom_fields);
		} else {
			let variantIndex = options.variants
				.map(function (variant) {
					return variant.language;
				})
				.indexOf(valueSelected);
			let variant = options.variants[variantIndex];
			variant.variantList = options.variants;
			variant.valueSelected = valueSelected;

			template = wp.template('brightcove-variants');
			this.setCustomFields(variant.custom_fields);
			$('.brightcove-variant-details').replaceWith(template(variant));
		}
		this.renderCustomFields();
	},

	back: function (event) {
		event.preventDefault();

		// Exit if the 'button' is disabled.
		if ($(event.currentTarget).hasClass('disabled')) {
			return;
		}
		wpbc.broadcast.trigger('start:gridview');
	},

	deleteVideo: function () {
		if (confirm(wpbc.preload.messages.confirmDelete)) {
			wpbc.broadcast.trigger('spinner:on');
			this.model.set('mediaType', 'videos');
			this.model.destroy();
		}
	},

	/**
	 * Allow the user to attach a video still or thumbnail.
	 *
	 * @param {Event} evnt
	 */
	openMediaManager: function (evnt) {
		evnt.preventDefault();

		const options = {
			title: wp.media.view.l10n.addMedia,
			multiple: false,
			library: {
				type: ['image/jpeg', 'image/png'],
			},
		};

		const mediaManager = wp.media(options);
		const that = this;

		// Open the media manager
		mediaManager.open();

		// Listen for selection of media
		mediaManager.on('select', function () {
			var media = mediaManager.state().get('selection').first().toJSON(),
				field = $(evnt).parents('.setting');

			// Set the selected attachment to the correct field
			that.setAttachment(media, field);

			// Make this action available to other areas of the application
			wpbc.broadcast.trigger('media:selected');
		});
	},

	/**
	 * Set the hidden input to the ID of the selected attachment.
	 *
	 * @param {Object} media
	 * @param {String} field
	 * @returns {boolean}
	 */
	setAttachment: function (media, field) {
		var field = field.prevObject[0].currentTarget,
			field = $(field).prev('input'),
			attachment = field.parents('.attachment'),
			preview = attachment.find('.-image'),
			ThumbnailOrPoster = ['brightcove-poster', 'brightcove-thumbnail'];

		// Perform different setup actions based on the type of upload
		if (ThumbnailOrPoster.includes(field.attr('class'))) {
			// Executed if the user is uploading a poster image or thumbnail
			var selectedMedia = {
				url: media.sizes.full.url,
				width: media.sizes.full.width,
				height: media.sizes.full.height,
			};

			// Set up our preview image
			var image = document.createElement('img');

			// Set image properties
			image.src = media.sizes.full.url;
			image.className = 'thumbnail';

			// Display a preview image
			attachment.addClass('active');
			preview.html(image); // .html() considered okay because auth is required to view this screen
		} else {
			// Executed if the user is uploading a closed caption
			if (media.subtype === 'vtt') {
				this.addCaptionRow(false, media);
			} else {
				var template = wp.template('brightcove-badformat-notice');

				// Throw a notice to the user that the file is not the correct format
				$('.brightcove-media-videos').prepend(template);

				// Allow the user to dismiss the notice
				$('.badformat.notice-dismiss').on('click', function () {
					$('.notice.badformat')
						.first()
						.fadeOut(500, function () {
							$(this).remove();
						});
				});
			}
		}

		// Add our meta to the hidden field
		field.val(JSON.stringify(selectedMedia));
	},

	/**
	 * Allow the user to remove media from a given field.
	 *
	 * @param {Event} evnt
	 * @returns {boolean}
	 */
	removeAttachment: function (evnt) {
		var container = $(evnt.currentTarget).parents('.attachment'),
			image = container.find('.-image'),
			field = container.next('input');

		// Empty the field
		field.val('');

		// Remove the preview image
		container.removeClass('active');
		image.empty();
	},

	/**
	 * Add a caption row
	 *
	 * @param {Event} evnt
	 * @param {Object} media
	 */
	addCaptionRow: function (evnt, media) {
		// If using the add remote file link, prevent the page from jumping to the top
		if (evnt) {
			evnt.preventDefault();
		}

		var source = undefined;
		if (media) {
			source = media.url;
		}

		this.addCaption(source);
	},

	addCaption: function (source, language, label, defaultcap) {
		var newRow = $(document.getElementById('js-caption-empty-row')).clone(),
			container = document.getElementById('js-captions'),
			captionUrl = document.getElementById('js-caption-url');

		// Clean up our cloned row
		newRow.find('input').prop('disabled', false);
		newRow.removeAttr('id');
		newRow.removeClass('empty-row');

		if (source) {
			newRow.find('.brightcove-captions').val(source);
		}

		if (language) {
			newRow.find('.brightcove-captions-language').val(language);
		}

		if (label) {
			newRow.find('.brightcove-captions-label').val(label);
		}

		if (defaultcap) {
			newRow.find('.brightcove-captions-default').val(defaultcap);
		}

		// Append our new row to the container
		$(container).append(newRow);

		// Update the context button text
		this.updateCaptionText();
	},

	/**
	 * Remove a caption
	 *
	 * @param {Event} evnt
	 */
	removeCaptionRow: function (evnt) {
		evnt.preventDefault();

		var caption = evnt.currentTarget,
			container = $(caption).parents('.caption-repeater'),
			source = container.find('.brightcove-captions'),
			language = container.find('.brightcove-captions-launguage'),
			label = container.find('.brightcove-captions-label'),
			defaultcap = container.find('.brightcove-captions-default');

		// Empty the input fields
		$(source).val('');
		$(language).val('');
		$(label).val('');
		$(defaultcap).val('');

		// Remove the container entirely
		container.remove();

		// Update the context button text
		this.updateCaptionText();
	},

	/**
	 * Updates the caption text based on number of captions
	 */
	updateCaptionText: function () {
		var button = $('.captions .button-secondary'),
			link = $('.add-remote-caption');

		if (document.getElementsByClassName('caption-repeater').length > 1) {
			button.text(wpbc.str_addcaption);
			link.text(wpbc.str_addremote);
		} else {
			button.text(wpbc.str_selectfile);
			link.text(wpbc.str_useremote);
		}
	},

	saveSync: function (evnt) {
		evnt.preventDefault();

		var $mediaFrame = $(evnt.currentTarget).parents('.media-modal'),
			$allButtons = $mediaFrame.find('.button, .button-link'),
			SELF = this;

		// Exit if the 'button' is disabled.
		if ($allButtons.hasClass('disabled')) {
			return;
		}

		// Disable the button for the duration of the request.
		$allButtons.addClass('disabled');

		// Hide the delete link for the duration of the request.
		$mediaFrame.find('.delete-action').hide();

		wpbc.broadcast.trigger('spinner:on');
		this.model.set('state', this.$el.find('.brightcove-state').val());
		this.model.set('name', this.$el.find('.brightcove-name').val());
		this.model.set('description', this.$el.find('.brightcove-description').val());
		this.model.set('long_description', this.$el.find('.brightcove-long-description').val());

		const startDate = this.$el.find('.brightcove-start-date').val();
		if (startDate) {
			const startDateHour = this.$el.find('.brightcove-start-date-hour').val();
			const startDateMinute = this.$el.find('.brightcove-start-date-minute').val();
			const startDateAMPM = this.$el.find('.brightcove-start-date-am-pm').val();

			this.model.set(
				'scheduled_start_date',
				`${startDate} ${startDateHour}:${startDateMinute}:00 ${startDateAMPM}`,
			);
		}

		const endDate = this.$el.find('.brightcove-end-date').val();
		if (endDate) {
			const endDateHour = this.$el.find('.brightcove-end-date-hour').val();
			const endDateMinute = this.$el.find('.brightcove-end-date-minute').val();
			const endDateAMPM = this.$el.find('.brightcove-end-date-am-pm').val();

			this.model.set(
				'scheduled_end_date',
				`${endDate} ${endDateHour}:${endDateMinute}:00 ${endDateAMPM}`,
			);
		}

		const brightcoveTags = this.$el.find('.brightcove-tags');

		if (brightcoveTags.length !== 0) {
			// Trim whitespace and commas from tags beginning/end.
			this.model.set(
				'tags',
				brightcoveTags
					.val()
					.trim()
					.replace(/(^,)|(,$)/g, ''),
			);
		}

		this.model.set('mediaType', 'videos');
		this.model.set('height', this.$el.find('.brightcove-height').val());
		this.model.set('width', this.$el.find('.brightcove-width').val());
		this.model.set('poster', this.$el.find('.brightcove-poster').val());
		this.model.set('thumbnail', this.$el.find('.brightcove-thumbnail').val());
		this.model.set('folder_id', this.$el.find('.brightcove-folder').val());

		const isVariant = this.$el.find('.brightcove-variant').val();

		if (isVariant !== 'none' && this.model.get('mediaType') === 'videos') {
			this.model.set('subType', 'variant');
			this.model.set('language', isVariant);
		}

		// Captions
		var captions = [];
		this.$el
			.find('.caption-repeater.repeater-row')
			.not('.empty-row')
			.each(function () {
				var caption = $(this),
					fileName = caption.find('.brightcove-captions').val(),
					extension = fileName.split('?')[0], // if the URL has a query string, strip it before validating filetype
					extension = extension.split('.').pop();

				if (extension === 'vtt') {
					captions.push({
						source: fileName,
						language: caption.find('.brightcove-captions-language').val(),
						label: caption.find('.brightcove-captions-label').val(),
						default: caption.find('.brightcove-captions-default').attr('checked'),
					});
				} else {
					var template = wp.template('brightcove-badformat-notice');

					// Throw a notice to the user that the file is not the correct format
					$('.brightcove-media-videos').prepend(template);

					// Allow the user to dismiss the notice
					$('.badformat.notice-dismiss').on('click', function () {
						$('.notice.badformat')
							.first()
							.fadeOut(500, function () {
								$(this).remove();
							});
					});
				}
			});
		this.model.set('captions', captions);

		// Labels
		let labels = this.$el.find('.bc-labels-value').val();
		labels = labels !== '' ? labels.split(',') : [];
		this.model.set('labels', labels);

		// Custom fields
		var custom = {},
			custom_fields = this.model.get('custom');

		_.each(
			this.$el.find('.brightcove-custom-string, .brightcove-custom-enum'),
			function (item) {
				var key = item.getAttribute('data-id'),
					val = item.value.trim();

				if (val !== '') {
					custom[key] = val;

					var obj = _.find(custom_fields, function (item) {
						return item.id == key;
					});
					obj.value = val;
				}
			},
		);

		this.model.set('custom_fields', custom);
		this.model.set('custom', custom_fields);

		const history = this.el.querySelector('.brightcove-change-history').value;
		const historyJson = JSON.stringify(history.split('\n').map((line) => line.trim()));
		this.model.set('_change_history', historyJson);

		this.model
			.save()
			.done(function () {
				if ($mediaFrame.length > 0) {
					// Update the tag dropdown and wpbc.preload.tags with any new tag values.
					var tagInput = $mediaFrame.find('.brightcove-tags').val(),
						editTags,
						newTags;

					if (tagInput) {
						editTags = tagInput.split(',');
						newTags = _.difference(editTags, wpbc.preload.tags);
					}

					// Add any new tags to the tags object and the dropdown.
					_.each(newTags, function (newTag) {
						newTag = newTag.trim();
						if (newTag !== '') {
							wpbc.preload.tags.push(newTag);
						}
					});
					wpbc.preload.tags.sort();
				}
			})
			.always(function () {
				// Re-enable the button when the request has completed.
				$allButtons.removeClass('disabled');

				// Show the delete link.
				$mediaFrame.find('.delete-action').show();
			});

		// Hide the video edit screen after save.
		wpbc.broadcast.trigger('start:gridview');
	},

	/**
	 * Renders the "Custom fields" from a video/variant.
	 */
	renderCustomFields: function () {
		var customContainer = this.$el.find('#brightcove-custom-fields'),
			stringTmp = wp.template('brightcove-video-edit-custom-string'),
			enumTmp = wp.template('brightcove-video-edit-custom-enum');
		_.each(this.model.get('custom'), function (custom) {
			if (custom.id === '_change_history') {
				return;
			}
			switch (custom.type) {
				case 'string':
					customContainer.append(stringTmp(custom));
					break;
				case 'enum':
					customContainer.append(enumTmp(custom));
					break;
			}
		});
	},

	/**
	 * In order to switch models accordingly to render a video/variant, we update the current model custom fields.
	 * @param {array} custom_fields
	 */
	setCustomFields: function (custom_fields) {
		let custom = this.model.get('custom');

		custom.forEach((customField) => {
			let CustomFieldsKeys = Object.keys(custom_fields);
			CustomFieldsKeys.forEach((index) => {
				if (customField.id === index) {
					customField.value = custom_fields[index];
				}
			});
		});

		this.model.set('custom', custom);
	},

	/**
	 * Adds a label.
	 * @param {Event} event
	 */
	addLabel: function (event) {
		event.preventDefault();

		const elem = this.el.querySelector('.brightcove-labels');
		const labelsValElem = this.el.querySelector('.bc-labels-value');
		const value = elem.value;

		elem.querySelector('option[value="' + value + '"]').setAttribute('disabled', true);
		elem.selectedIndex = 0;

		const listItem = `<li>
											<button class="remove-label" aria-label="Remove label: ${value}" data-label="${value}"><span aria-hidden="true">×</span></button>
											<span class="label-name">${value}</span>
										</li>`;

		this.el.querySelector('.bc-labels-list').insertAdjacentHTML('beforeend', listItem);

		labelsValElem.value = labelsValElem.value ? labelsValElem.value + ',' + value : value;
	},

	/**
	 * Removes a label.
	 * @param {Event} event
	 */
	removeLabel: function (event) {
		event.preventDefault();

		const label = event.currentTarget.dataset.label;
		const labelsValElem = this.el.querySelector('.bc-labels-value');
		const labelElem = this.el.querySelector('.brightcove-labels');
		const selectedLabels = labelsValElem.value.split(',');

		var index = selectedLabels.indexOf(label);
		if (index > -1) {
			selectedLabels.splice(index, 1);
		}

		labelsValElem.value = selectedLabels.join(',');
		labelElem.querySelector('option[value="' + label + '"]').removeAttribute('disabled');
		event.currentTarget.parentElement.remove();
	},

	/**
	 * Render the actual view for the Video Edit screen.
	 *
	 * @param {Object} options
	 */
	render: function (options) {
		this.listenTo(wpbc.broadcast, 'save:media', this.saveSync);
		this.listenTo(wpbc.broadcast, 'back:editvideo', this.back);

		this.listenTo(wpbc.broadcast, 'insert:shortcode', this.insertShortcode);
		options = this.model.toJSON();
		options.folders = wpbc.preload.folders;

		// Due to a change in the API response, text_tracks might not be defined if the video lacks text_tracks.
		options.text_tracks = options.text_tracks || [];

		this.model.set('old_folder_id', options.folder_id);

		// Render the model into the template
		this.$el.html(this.template(options));

		this.$('.brightcove-datetime').datepicker();

		this.renderCustomFields();

		// Render the change history
		var history = this.model.get('history');

		if (history !== undefined) {
			var historyStr = '';

			// Parse our fetched JSON object
			history = JSON.parse(history);

			_.each(history, function (item) {
				historyStr += item + '\n';
			});

			if (historyStr !== '') {
				this.$el.find('textarea.brightcove-change-history').val(historyStr);
			}
		}

		// Configure a spinner to provide feedback during updates
		var spinner = this.$el.find('.spinner');
		this.listenTo(wpbc.broadcast, 'spinner:on', function () {
			spinner.addClass('is-active').removeClass('hidden');
		});
		this.listenTo(wpbc.broadcast, 'spinner:off', function () {
			spinner.removeClass('is-active').addClass('hidden');
		});

		// Captions
		if (this.model.get('captions')) {
			var captions = this.model.get('captions');
			for (var i = 0, l = captions.length; i < l; i++) {
				var caption = captions[i];
				this.addCaption(caption.source, caption.language, caption.label, caption.default);
			}
		}
	},
});
