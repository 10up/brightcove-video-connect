var UploadDetailsView = BrightcoveView.extend(
	{
		className : 'brightcove-pending-upload-details attachment-details',
		tagName :   'div',
		template :  wp.template( 'brightcove-pending-upload-details' ),

		events : {
			'keyup .brightcove-name' :          'nameChanged',
			'keyup .brightcove-tags' :          'tagsChanged',
			'change .brightcove-media-source' : 'accountChanged'
		},

		initialize : function ( options ) {
			this.listenTo( wpbc.broadcast, 'pendingUpload:hideDetails', this.hide );
			this.listenTo( wpbc.broadcast, 'uploader:fileUploaded', function ( file ) {
				if ( file.id === this.model.get( 'id' ) ) {
					this.model.set( 'uploaded', true );
					this.render();
				}
			} );
			this.model.set( 'ingestSuccess', true );
			this.model.set( 'uploadSuccess', true );
		},

		nameChanged : function ( event ) {
			this.model.set( 'fileName', event.target.value );
		},

		tagsChanged : function ( event ) {
			this.model.set( 'tags', event.target.value );
		},

		accountChanged : function ( event ) {
			this.model.set( 'account', event.target.value );
		},

		hide : function () {
			this.$el.hide();
		},

		render : function ( options ) {
			options          = options || {};
			options.fileName = this.model.get( 'fileName' );
			options.tags     = this.model.get( 'tags' );
			options.size     = this.model.humanReadableSize();
			options.accounts = this.model.get( 'accounts' );
			options.account  = this.model.get( 'account' );
			options.uploaded = this.model.get( 'uploaded' );
			this.$el.html( this.template( options ) );
		}

	}
);
