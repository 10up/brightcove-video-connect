define(['jquery', 'underscore', 'backbone', 'models/media', 'views/brightcove'], function ($, _, Backbone, MediaModel, BrightcoveView) {
    var VideoEditView = BrightcoveView.extend({
        tagName: 'div',
        className: '',
        template: wp.template('brightcove-playlist-edit'),

        events: {
            'click .brightcove.button.save-sync': 'saveSync',
            'click .brightcove.back': 'back'
        },

        render: function (options) {
            options = this.model.toJSON();
            this.$el.html( this.template( options ) );
        }

    });

    return VideoEditView;
});
