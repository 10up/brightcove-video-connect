/* global Backbone, wpbc, _ */

/**
 * Media model for Media CPT
 */

const MediaModel = Backbone.Model.extend({
	/**
	 * Copied largely from WP Attachment sync function
	 * Triggered when attachment details change
	 * Overrides Backbone.Model.sync
	 *
	 * @param {string} method
	 * @param {wp.media.model.Media} model
	 * @param {object} [options={}]
	 *
	 * @returns {Promise}
	 */
	sync(method, model, options) {
		let accountHash = null;

		// Set the accountHash to the wpbc.preload.accounts[*] where the account_id
		// matches this media objects account_id.
		_.find(
			wpbc.preload.accounts,
			// eslint-disable-next-line
			function (account, hash) {
				if (account.account_id === this.get('account_id')) {
					accountHash = hash;
					return true;
				}
			},
			this,
		);

		// If the attachment does not yet have an `id`, return an instantly
		// rejected promise. Otherwise, all of our requests will fail.
		if (_.isUndefined(this.id)) {
			// eslint-disable-next-line
			return $.Deferred().rejectWith(this).promise();
		}

		// Overload the `read` request so Media.fetch() functions correctly.
		if (method === 'read') {
			// eslint-disable-next-line
			options = options || {};
			options.context = this;
			options.data = _.extend(options.data || {}, {
				action: 'bc_media_fetch',
				id: this.id,
			});

			return wp.media.ajax(options);

			// Overload the `update` request so properties can be saved.
		}
		if (method === 'update') {
			// eslint-disable-next-line
			options = options || {};
			options.context = this;
			// Set the action and ID.
			options.data = _.extend(options.data || {}, {
				account: accountHash,
				action: 'bc_media_update',
				description: this.get('description'),
				long_description: this.get('long_description'),
				name: this.get('name'),
				nonce: wpbc.preload.nonce,
				tags: this.get('tags'),
				old_folder_id: this.get('old_folder_id'),
				folder_id: this.get('folder_id'),
				type: this.get('mediaType'),
				custom_fields: this.get('custom_fields'),
				history: this.get('_change_history'),
				poster: this.get('poster'),
				thumbnail: this.get('thumbnail'),
				captions: this.get('captions'),
				labels: this.get('labels'),
				sub_type: this.get('subType'),
				language: this.get('language'),
			});

			const video_ids = this.get('video_ids');
			if (video_ids) {
				options.data.playlist_id = this.id;
				options.data.playlist_videos = video_ids;
				options.data.type = 'playlists';
			} else {
				options.data.video_id = this.id;
				options.data.state = this.get('state');
				options.data.scheduled_start_date = this.get('scheduled_start_date');
				options.data.scheduled_end_date = this.get('scheduled_end_date');
			}

			options.success = this.successFunction;
			options.error = this.failFunction;

			wpbc.broadcast.trigger('spinner:on');
			return wp.media.ajax(options);

			// Overload the `delete` request so attachments can be removed.
			// This will permanently delete an attachment.
		}
		if (method === 'delete') {
			// eslint-disable-next-line
			options = options || {};
			const self = this;

			options.data = _.extend(options.data || {}, {
				account: accountHash,
				action: 'bc_media_delete',
				id: this.get('id'),
				nonce: wpbc.preload.nonce,
				type: this.get('mediaType'),
			});

			return wp.media
				.ajax(options)
				.done(function (response) {
					self.destroyed = true;
					wpbc.broadcast.trigger('delete:successful', response);
					if (
						self.get('mediaType') === 'videos' ||
						!_.isUndefined(self.get('video_ids'))
					) {
						wpbc.preload.videos = undefined;
					} else {
						wpbc.preload.playlists = undefined;
					}
					wpbc.responses = {};
				})
				.fail(function (response) {
					self.destroyed = false;
					wpbc.broadcast.trigger('videoEdit:message', response, 'error');
					wpbc.broadcast.trigger('spinner:off');
				});

			// Otherwise, fall back to `Backbone.sync()`.
		}
		/**
		 * Call `sync` directly on Backbone.Model
		 */
		// eslint-disable-next-line
		return Backbone.Model.prototype.sync.apply(this, arguments);
	},

	/**
	 * Convert date strings into Date objects.
	 *
	 * @param {object} resp The raw response object, typically returned by fetch()
	 * @returns {object} The modified response object, which is the attributes hash
	 *    to be set on the model.
	 */
	parse(resp) {
		if (!resp) {
			return resp;
		}

		resp.date = new Date(resp.date);
		resp.modified = new Date(resp.modified);
		return resp;
	},

	getAccountName() {
		// eslint-disable-next-line
		const account_id = this.get('account_id');
		const matchingAccount = _.findWhere(wpbc.preload.accounts, {
			account_id: this.get('account_id'),
		});
		return undefined === matchingAccount
			? this.getSelectedAccountName()
			: matchingAccount.account_name;
	},

	getSelectedAccountName() {
		const elt = document.getElementById('brightcove-media-source');

		if (elt.selectedIndex === -1) {
			return 'unavailable';
		}

		return elt.options[elt.selectedIndex].text;
	},

	getReadableDuration() {
		let duration = this.get('duration');

		if (duration) {
			duration = Number(duration / 1000);
			const hours = Math.floor(duration / 3600);
			const minutes = Math.floor((duration % 3600) / 60);
			const seconds = Math.floor((duration % 3600) % 60);
			return `${(hours > 0 ? `${hours}:${minutes < 10 ? '0' : ''}` : '') + minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
		}
		return duration;
	},

	getReadableDate(field) {
		const updated_at = this.get(field);

		if (updated_at) {
			const date = new Date(updated_at);

			let hour = date.getHours();
			let min = date.getMinutes();
			const year = date.getFullYear();
			const mon = date.getMonth() + 1;
			const day = date.getDate();
			const ampm = hour >= 12 ? 'pm' : 'am';

			hour %= 12;
			hour = hour || 12;

			min = min < 10 ? `0${min}` : min;

			const readableDate = `${year}/${mon}/${day} - ${hour}:${min} ${ampm}`;
			return readableDate;
		}
		return updated_at;
	},

	successFunction(message) {
		wpbc.broadcast.trigger('videoEdit:message', message, 'success');
		wpbc.broadcast.trigger('spinner:off');
		if (_.isArray(this.get('video_ids')) && wpbc.preload && wpbc.preload.playlists) {
			const id = this.get('id');
			_.each(
				wpbc.preload.playlists,
				function (playlist, index) {
					if (playlist.id === id) {
						wpbc.preload.playlists[index] = this.toJSON();
					}
				},
				this,
			);
		}
		wpbc.responses = {};
		if (this.get('mediaType') === 'videos' || !_.isUndefined(this.get('video_ids'))) {
			wpbc.preload.videos = undefined;
		} else {
			wpbc.preload.playlists = undefined;
		}
	},

	failFunction(message) {
		wpbc.broadcast.trigger('videoEdit:message', message, 'error');
		wpbc.broadcast.trigger('spinner:off');
	},
});

export default MediaModel;
