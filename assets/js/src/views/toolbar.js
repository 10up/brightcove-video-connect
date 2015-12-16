    /**
     * This is the toolbar to handle sorting, filtering, searching and grid/list view toggles.
     * State is captured in the brightcove-media-manager model.
     */
    var ToolbarView = BrightcoveView.extend({
        tagName: 'div',
        className: 'media-toolbar wp-filter',
        template:  wp.template('brightcove-media-toolbar'),

        events: {
            'click .view-list': 'toggleList',
            'click .view-grid': 'toggleGrid',
            'change .brightcove-media-source': 'sourceChanged',
            'change .brightcove-media-dates': 'datesChanged',
            'change .brightcove-media-tags': 'tagsChanged',
			'change .brightcove-empty-playlists': 'emptyPlaylistsChanged',
            'keyup .search': 'searchHandler'
        },

        render: function() {
            var mediaType = this.model.get('mediaType');
            var options = {
                accounts: wpbc.preload.accounts,
                dates: {},
                mediaType: mediaType,
                tags: wpbc.preload.tags
            };

            var dates = wpbc.preload.dates;
            var date_var = this.model.get('date'); /* @todo: find out if this is working */
            if (dates !== undefined && dates[mediaType] !== undefined && dates[mediaType][date_var] !== undefined) {
                options.dates = dates[mediaType][date_var];
            }

            this.$el.html(this.template(options));
            var spinner = this.$el.find('.spinner');
            this.listenTo(wpbc.broadcast, 'spinner:on', function() {
                spinner.addClass('is-active').removeClass('hidden');
            });
            this.listenTo(wpbc.broadcast, 'spinner:off', function() {
                spinner.removeClass('is-active').addClass('hidden');
            });
        },

        // List view Selected
        toggleList: function() {
            this.trigger('viewType', 'list');
            this.$el.find('.view-list').addClass('current');
            this.$el.find('.view-grid').removeClass('current');
        },

        // Grid view Selected
        toggleGrid: function() {
            this.trigger('viewType', 'grid');
            this.$el.find('.view-grid').addClass('current');
            this.$el.find('.view-list').removeClass('current');
        },

        // Brightcove source changed
        sourceChanged: function(event) {
            wpbc.broadcast.trigger('change:activeAccount', event.target.value);
        },

        datesChanged: function(event) {
            wpbc.broadcast.trigger('change:date', event.target.value);
        },

        tagsChanged: function(event) {
            wpbc.broadcast.trigger('change:tag', event.target.value);
        },

		emptyPlaylistsChanged: function(event) {
			var emptyPlaylists = $(event.target).prop('checked');
			wpbc.broadcast.trigger('change:emptyPlaylists', emptyPlaylists );
		},

        searchHandler: function(event) {
            // Enter / Carriage Return
            if (event.keyCode === 13) {
                this.model.set('search', event.target.value);
                wpbc.broadcast.trigger('change:searchTerm', event.target.value);
            }
        }

    });

