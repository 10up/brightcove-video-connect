var VideoPreviewView = BrightcoveView.extend( {
	tagName :   'div',
	className : 'video-preview brightcove',
	template :  wp.template( 'brightcove-video-preview' ),
	shortcode: '',

	initialize: function( options ) {
		this.shortcode = options.shortcode;
	},

	render : function ( options ) {
		var that = this;

		options            = options || {};
		options.id         = this.model.get( 'id' );
		options.account_id = this.model.get( 'account_id' );

		$.ajax({
			url: ajaxurl,
			dataType: 'json',
			method: 'POST',
			data: {
				'action':'bc_resolve_shortcode',
				'shortcode': this.shortcode
			},
			success: function( results ) {
				that.$el.html( results.data );
			}
		});

		this.listenTo( wpbc.broadcast, 'insert:shortcode', this.insertShortcode );
	}
} );
