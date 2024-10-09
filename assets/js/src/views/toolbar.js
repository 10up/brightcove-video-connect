/**
 * This is the toolbar to handle sorting, filtering, searching and grid/list
 * view toggles. State is captured in the brightcove-media-manager model.
 */
var ToolbarView = BrightcoveView.extend({
	tagName: 'div',
	className: 'media-toolbar wp-filter',
	template: wp.template('brightcove-media-toolbar'),

	events: {
		'click .view-list': 'toggleList',
		'click .view-grid': 'toggleGrid',
		'click .brightcove-toolbar': 'toggleToolbar',
		'change .brightcove-media-source': 'sourceChanged',
		'change .brightcove-media-dates': 'datesChanged',
		'change .brightcove-media-tags': 'tagsChanged',
		'change .brightcove-media-folders': 'foldersChanged',
		'change .brightcove-media-labels': 'labelsChanged',
		'change .brightcove-empty-playlists': 'emptyPlaylistsChanged',
		'change .brightcove-media-state-filters': 'stateChanged',
		'click #media-search': 'searchHandler',
		'keyup .search': 'enterHandler',
		'input #media-search-input': 'handleEmptySearchInput',
	},

	render: function () {
		var mediaType = this.model.get('mediaType');
		var options = {
			accounts: wpbc.preload.accounts,
			dates: {},
			mediaType: mediaType,
			tags: wpbc.preload.tags,
			folders: wpbc.preload.folders,
			labels: wpbc.preload.labels,
			labelPath: this.model.get('labelPath'),
			folder_id: this.model.get('folder_id'),
			account: this.model.get('account'),
		};

		var dates = wpbc.preload.dates;
		var date_var = this.model.get('date');
		/* @todo: find out if this is working */
		if (
			dates !== undefined &&
			dates[mediaType] !== undefined &&
			dates[mediaType][date_var] !== undefined
		) {
			options.dates = dates[mediaType][date_var];
		}

		this.$el.html(this.template(options));
		var spinner = this.$el.find('.spinner');
		this.listenTo(wpbc.broadcast, 'spinner:on', function () {
			spinner.addClass('is-active').removeClass('hidden');
		});
		this.listenTo(wpbc.broadcast, 'spinner:off', function () {
			spinner.removeClass('is-active').addClass('hidden');
		});
	},

	// List view Selected
	toggleList: function () {
		this.trigger('viewType', 'list');
		this.$el.find('.view-list').addClass('current');
		this.$el.find('.view-grid').removeClass('current');
	},

	// Grid view Selected
	toggleGrid: function () {
		this.trigger('viewType', 'grid');
		this.$el.find('.view-grid').addClass('current');
		this.$el.find('.view-list').removeClass('current');
	},

	// Toggle toolbar help
	toggleToolbar: function () {
		var template = wp.template('brightcove-tooltip-notice');

		// Remove any existing tooltip notice
		$('#js-tooltip-notice').remove();

		// Throw a notice to the user that the file is not the correct format
		$('.brightcove.media-frame-router').before(template);
		// Allow the user to dismiss the notice
		$('#js-tooltip-dismiss').on('click', function () {
			$('#js-tooltip-notice')
				.first()
				.fadeOut(500, function () {
					$(this).remove();
				});
		});
	},

	// Brightcove source changed
	sourceChanged: function (event) {
		// Store the currently selected account on the model.
		this.model.set('account', event.target.value);
		wpbc.broadcast.trigger('change:activeAccount', event.target.value);
		// Update wpbc object for later use on upload-details.js
		wpbc.preload.defaultAccountId = event.target.value;
		wpbc.preload.defaultAccount =
			event.target.options[event.target.selectedIndex].getAttribute('data-hash');
	},

	datesChanged: function (event) {
		wpbc.broadcast.trigger('change:date', event.target.value);
	},

	tagsChanged: function (event) {
		wpbc.broadcast.trigger('change:tag', event.target.value);
	},

	foldersChanged: function (event) {
		this.model.set('old_folder_id', this.model.get('folder_id'));
		this.model.set('folder_id', event.target.value);
		// hide search field if video folder is selected
		this.$el.find('#media-search-input, #media-search').toggle(event.target.value === 'all');

		wpbc.broadcast.trigger('change:folder', event.target.value);
	},

	labelsChanged: function (event) {
		this.model.set('oldLabelPath', this.model.get('labelPath'));
		this.model.set('labelPath', event.target.value);
		wpbc.broadcast.trigger('change:label', event.target.value);
	},

	emptyPlaylistsChanged: function (event) {
		var emptyPlaylists = $(event.target).prop('checked');
		wpbc.broadcast.trigger('change:emptyPlaylists', emptyPlaylists);
	},

	enterHandler: function (event) {
		if (event.keyCode === 13) {
			this.searchHandler(event);
		}
	},

	handleEmptySearchInput: function (event) {
		if (this.model.get('search') && !event.target.value) {
			this.model.set('search', '');
			wpbc.broadcast.trigger('change:searchTerm', '');
		}
	},

	stateChanged: function (event) {
		this.model.set('oldState', 'oldstate');
		this.model.set('state', 'newstate');
		wpbc.broadcast.trigger('change:stateChanged', event.target.value);
	},

	searchHandler: function (event) {
		var searchTerm = $('#media-search-input').val();

		if (searchTerm.length > 2 && searchTerm !== this.model.get('search')) {
			this.model.set('search', searchTerm);
			wpbc.broadcast.trigger('change:searchTerm', searchTerm);
		} else if (searchTerm.length === 0) {
			wpbc.broadcast.trigger('change:searchTerm', '');
		}
	},
});
