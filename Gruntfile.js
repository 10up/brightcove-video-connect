module.exports = function ( grunt ) {

	// Start out by loading the grunt modules we'll need
	require ( 'load-grunt-tasks' ) ( grunt );

	grunt.initConfig (
		{

			clean : ["assets/css/*", "assets/js/brightcove.*"],

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

			uglify : {

				production : {

					options : {
						beautify         : false,
						preserveComments : false,
						mangle           : {
							except : ['jQuery']
						}
					},

					files : {
						'assets/js/brightcove.min.js' : [
							'assets/js/src/video-page.js'
						]
					}

				},

				development : {

					options : {
						beautify         : true,
						preserveComments : true
					},

					files : {
						'assets/js/brightcove.js' : [
							'assets/js/src/video-page.js'
						]
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

					tasks : ['sass']

				},

				scripts : {

					files : [
						'assets/js/src/**/*'
					],

					tasks : ['uglify:development', 'uglify:production']

				}

			}

		}
	);

	// A very basic default task.
	grunt.registerTask ( 'default', ['clean', 'sass', 'autoprefixer', 'cssmin', 'uglify:development', 'uglify:production', 'watch'] );

};
