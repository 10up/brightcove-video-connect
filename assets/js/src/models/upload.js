/**
 * Model to contain pending upload
 */

var UploadModel = Backbone.Model.extend(
	{

		initialize : function ( options ) {
		},

		humanReadableSize : function () {
			var bytes = this.get( 'size' );
			if ( bytes === 0 ) {
				return '0 Byte';
			}
			var k     = 1000;
			var sizes = ['Bytes', 'KB', 'MB', 'GB'];
			var i     = Math.floor( Math.log( bytes ) / Math.log( k ) );
			return (bytes / Math.pow( k, i )).toPrecision( 3 ) + ' ' + sizes[i];
		}

	}
);
