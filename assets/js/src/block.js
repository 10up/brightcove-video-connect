/* global wp */

( function( blocks, element, components ) {
	var el = element.createElement,
		registerBlockType = blocks.registerBlockType,
		Placeholder = components.Placeholder,
		//Button = components.Button,
		//MediaUpload = blocks.MediaUpload,
		RichText = blocks.RichText;

	registerBlockType( 'bc/brightcove', {
		title: 'Brightcove',
		description: 'The Brightcove block allows you to embed videos from Brightcove.',
		icon: 'video-alt3',
		category: 'common', // embed?

		edit: function() {
			// var onSelectVideo = function( media ) {
			// 	console.log( 'selected ' + media.length );
			// };

			//var btn = el( Button, { isLarge: true }, 'Brightcove Library' );
			//var upload = el( MediaUpload, { onSelect: onSelectVideo, render: function() { return btn; } } );

			return el( Placeholder, {
				icon: 'media-video',
				label: 'Brightcove',
				instructions: 'Select a video file from your Brightcove library',
				children: [
					el( 'button', { className: 'brightcove-add-media button button-large', 'data-target': '#test123', key: 1 }, 'Brightcove Media' ),
					el( 'input', { id: 'test123', key: 2 } )
				]
			} );
		},

		save: function() {
			return el( 'p', 'Hello saved content.' );
		}

	} );

} )( window.wp.blocks, window.wp.element, window.wp.components );
