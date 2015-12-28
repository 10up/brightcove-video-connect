var VideoPreviewView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'video-preview brightcove',
		template :  wp.template( 'brightcove-video-preview' ),

		render : function ( options ) {
			options            = options || {};
			options.id         = this.model.get( 'id' );
			options.account_id = this.model.get( 'account_id' );
			this.$el.html( this.template( options ) );
			this.listenTo( wpbc.broadcast, 'insert:shortcode', this.insertShortcode );
		}

	}
);
