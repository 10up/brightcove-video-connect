module.exports = function ( grunt ) {

	// Start out by loading the grunt modules we'll need
	require ( 'load-grunt-tasks' ) ( grunt );

	grunt.initConfig (
		{

			clean : {
				files: ["assets/css/*", "assets/js/brightcove*"]
			},

			autoprefixer : {

				options : {
					browsers : ['last 5 versions']
				},

				files : {
					expand  : true,
					flatten : true,
					src     : 'assets/css/*.css',
					dest    : 'assets/css'
				}

			},

			cssmin : {

				target : {

					files : [{
						expand : true,
						cwd    : 'assets/css',
						src    : ['*.css'],
						dest   : 'assets/css',
						ext    : '.min.css'
					}]

				}

			},

			sass : {

				production : {

					options : {
						style     : 'expanded',
						noCache   : true
					},

					files : {
						'assets/css/brightcove_video_connect.css' : 'assets/scss/brightcove_video_connect.scss',
						'assets/css/brightcove_playlist.css' : 'assets/scss/brightcove_playlist.scss'
					}

				}

			},

			concat: {
				development: {

					options : {

						// Wrap our scripts to limit their scope.
						banner: '( function( $ ){\n',
						footer: '\n} )( jQuery );',
						sourceMap: true
					},

					files : {
						'assets/js/brightcove-admin.js' : [
							// MODELS
							'assets/js/src/models/media.js',
							'assets/js/src/models/media-collection.js',
							'assets/js/src/models/brightcove-media-manager.js',
							'assets/js/src/models/brightcove-modal.js',
							'assets/js/src/models/upload-collection.js',
							'assets/js/src/models/upload.js',

							// VIEWS
							'assets/js/src/views/brightcove.js',
							'assets/js/src/views/toolbar.js',
							'assets/js/src/views/upload-video-manager.js',
							'assets/js/src/views/brightcove-media-manager.js',
							'assets/js/src/views/brightcove-modal.js',
							'assets/js/src/views/media-details.js',
							'assets/js/src/views/media.js',
							'assets/js/src/views/playlist-edit.js',
							'assets/js/src/views/upload-details.js',
							'assets/js/src/views/upload-window.js',
							'assets/js/src/views/upload.js',
							'assets/js/src/views/video-edit.js',
							'assets/js/src/views/video-preview.js',
							'assets/js/src/views/media-collection.js',
							'assets/js/src/app.js'
						]
					}
				}
			},

			uglify : {

				production : {

					options : {
						beautify         : false,
						preserveComments : 'all',
						mangle           : {
							except : ['jQuery']
						},
						separator: ';\n',

						// Wrap our scripts to limit their scope.
						banner: '( function( $ ){\n',
						footer: '\n} )( jQuery );'
					},

					files : {
						'assets/js/brightcove-admin.min.js' : [
							// MODELS
							'assets/js/src/models/media.js',
							'assets/js/src/models/media-collection.js',
							'assets/js/src/models/brightcove-media-manager.js',
							'assets/js/src/models/brightcove-modal.js',
							'assets/js/src/models/upload-collection.js',
							'assets/js/src/models/upload.js',

							// VIEWS
							'assets/js/src/views/brightcove.js',
							'assets/js/src/views/toolbar.js',
							'assets/js/src/views/upload-video-manager.js',
							'assets/js/src/views/brightcove-media-manager.js',
							'assets/js/src/views/brightcove-modal.js',
							'assets/js/src/views/media-details.js',
							'assets/js/src/views/media.js',
							'assets/js/src/views/playlist-edit.js',
							'assets/js/src/views/upload-details.js',
							'assets/js/src/views/upload-window.js',
							'assets/js/src/views/upload.js',
							'assets/js/src/views/video-edit.js',
							'assets/js/src/views/video-preview.js',
							'assets/js/src/views/media-collection.js',
							'assets/js/src/app.js'
						],
						'assets/js/bc-status.min.js' : [
							'assets/js/src/bc-status.js'
						]
					}

				},

			},

			makepot : {

				target : {
					options : {
						type       : 'wp-plugin',
						domainPath : '/languages',
						mainFile   : 'brightcove-video-connect.php'
					}
				}

			},

			watch : {

				options : {
					livereload : true
				},

				styles : {

					files : [
						'assets/scss/**/*'
					],

					tasks : ['sass', 'cssmin']

				},

				scripts : {

					files : [
						'assets/js/src/**/*'
					],

					tasks : ['concat:development', 'uglify:production']

				}

			}

		}
	);

	// A very basic default task.
	grunt.registerTask ( 'default', ['clean', 'sass', 'autoprefixer', 'cssmin', 'concat:development', 'uglify:production', 'makepot'] );

};
