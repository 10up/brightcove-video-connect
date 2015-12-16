define(['jquery', 'underscore', 'backbone', 'views/brightcove-media-manager', 'views/video-preview', 'views/brightcove'], function ($, _, Backbone, BrightcoveMediaManagerView, VideoPreviewView, BrightcoveView) {
    var VideoEditView = BrightcoveView.extend({
        tagName: 'div',
        className: 'video-edit brightcove attachment-details',
        template: wp.template('brightcove-video-edit'),

		events: {
			'click .brightcove.button.save-sync': 'saveSync',
			'click .brightcove.delete': 'deleteVideo',
			'click .brightcove.button.back': 'back'
		},

		back: function(event) {
			event.preventDefault();
			wpbc.broadcast.trigger('start:gridview');
		},

        deleteVideo: function() {
            if (confirm( wpbc.preload.messages.confirmDelete )){
                wpbc.broadcast.trigger('spinner:on');
                this.model.set('mediaType', 'videos');
                this.model.destroy();
            }
        },

        saveSync: function() {
			wpbc.broadcast.trigger('spinner:on');
            this.model.set('name', this.$el.find('.brightcove-name').val());
            this.model.set('description', this.$el.find('.brightcove-description').val());
            this.model.set('long_description', this.$el.find('.brightcove-long-description').val());
            this.model.set('tags', this.$el.find('.brightcove-tags').val());
            this.model.set('height', this.$el.find('.brightcove-height').val());
            this.model.set('width', this.$el.find('.brightcove-width').val());
            this.model.set('mediaType', 'videos');
            this.model.save();
        },

        render: function (options) {
            this.listenTo(wpbc.broadcast, 'insert:shortcode', this.insertShortcode);
            options = this.model.toJSON();
            this.$el.html( this.template( options ) );
			var spinner = this.$el.find('.spinner');
			this.listenTo(wpbc.broadcast, 'spinner:on', function() {
				spinner.addClass('is-active').removeClass('hidden');
			});
			this.listenTo(wpbc.broadcast, 'spinner:off', function() {
				spinner.removeClass('is-active').addClass('hidden');
			});
        }

    });

    return VideoEditView;
});
