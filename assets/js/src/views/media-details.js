    var MediaDetailsView = BrightcoveView.extend({
        tagName:   'div',
        className: 'media-details',

        attributes: function() {
            return {
                'tabIndex':     0,
                'role':         'checkbox',
                'aria-label':   this.model.get( 'title' ),
                'aria-checked': false,
                'data-id':      this.model.get( 'id' )
            };
        },

        events: {
            'click .brightcove.edit.button': 'triggerEditMedia',
            'click .brightcove.preview.button': 'triggerPreviewMedia',
            'click .brightcove.back.button': 'backButton'
        },

        triggerEditMedia: function(event) {
            event.preventDefault();
            wpbc.broadcast.trigger('edit:media', this.model);
        },

        triggerPreviewMedia: function(event) {
            event.preventDefault();
            wpbc.broadcast.trigger('preview:media', this.model);
        },

        backButton: function(event) {
            wpbc.broadcast.trigger('backButton', this.mediaType);
        },

        initialize: function(options) {
            options = options || {};
            this.type = options.type ? options.type : 'grid';
            this.mediaType = options.mediaType;
            this.listenTo(wpbc.broadcast, 'insert:shortcode', this.insertShortcode);
            this.listenTo(this.model, 'change', this.render);
        },

        /**
         * @returns {wp.media.view.Media} Returns itself to allow chaining
         */
        render: function(options) {
            options = _.extend({}, options, this.model.toJSON());
            options.duration = this.model.getReadableDuration();
            options.updated_at_readable = this.model.getReadableDate( 'updated_at' );
            options.created_at_readable = this.model.getReadableDate( 'created_at' );
            options.account_name = this.model.getAccountName();

            this.template = wp.template('brightcove-media-item-details-' + this.mediaType );

            this.$el.html( this.template( options ) );

            this.delegateEvents();
            return this;
        },

        /* Prevent this.remove() from removing the container element for the details view */
        remove: function() {
            this.undelegateEvents();
            this.$el.empty();
            this.stopListening();
            return this;
        }

    });

