	var App = {
		renderMediaManager: function(mediaType) {
			var brightcoveMediaContainer = $('.brightcove-media-' + mediaType);
			var content_ifr = document.getElementById('content_ifr');
			if ( brightcoveMediaContainer.length ) {
				var brightcoveMediaManager = new BrightcoveMediaManagerView({
					el: brightcoveMediaContainer,
					date: 'all',
					embedType: 'page',
					preload: true,
					mode: 'manager',
					search: '',
					accounts: 'all',
					tags: 'all',
					mediaType: mediaType,
					viewType: 'grid'
				});
				brightcoveMediaManager.render();
			}
		},

		load: function() {
			wpbc.requests = [];
			wpbc.responses = {};
			wpbc.broadcast = _.extend({}, Backbone.Events); // pubSub object

			wpbc.selfSync = function () {
				$.get(ajaxurl, {action: 'bc_initial_sync'}, function (data) {
					if (!data.success) {
						// At most every ten seconds.
						_.delay(wpbc.selfSync, 10000);
					} else {
						wpbc.broadcast.trigger('remove:permanentMessage');
					}
				});
			};

			/* If we have to finish our inital sync, then lets help it along*/
			if (!!wpbc.initialSync) {
				wpbc.selfSync();
			}

			/* Wait until the window is loaded and the anchor element exists in the DOM */
			$(window).load(this.loaded);

			/* If we're on the videos/playlists pages, sometimes the $(window).load has already fired
			we test for this and fire up the app anyway.
			 */
			if (window.location.href.indexOf('page-brightcove') ) {
				_.delay(_.bind(function() {
					if (!wpbc.triggerModal) {
						this.loaded();
					}
				}, this), 100);
			}

		},

		loaded: function() {
			var brightcoveModalContainer = $('.brightcove-modal');
			wpbc.triggerModal = function() {
				if (!wpbc.modal) {
					wpbc.modal = new BrightcoveModalView({
						el: brightcoveModalContainer,
						tab: 'videos'
					});
					wpbc.modal.render();
				} else {
					wpbc.modal.$el.show();
				}
			};

			var bc_sanitize_ids = function( id ) {
				return id.replace(/\D/g,'');
			};

			// Load the appropriate media type manager into the container element,
			// We only support loading one per page.
			_.each(['videos', 'playlists'], function(mediaType){
				App.renderMediaManager(mediaType);
			});

			$('.account-toggle-button').on('click',function(event){
				event.preventDefault();
				$(this).hide();
				$('.brightcove-account-row.hidden').show();
			});

			$('.brightcove-add-new-video').on('click', function(e) {
				e.preventDefault();
				wpbc.broadcast.trigger('upload:video');
			});

			$('.brightcove-add-media').on('click', function() {
				wpbc.triggerModal();
			});

			$(document).keyup(function(e) {
				if (27 === e.keyCode) {
					// Close modal on ESCAPE if it's open.
					wpbc.broadcast.trigger('close:modal');
				}
			});

			$('a.brightcove-action-delete-source').on('click',function(e){
				var message = $(this).data('alert-message');
				if( !confirm( message ) ) {
					return false;
				}
			});

		}
	};

	$( document ).ready( function() {
			App.load();
	});
