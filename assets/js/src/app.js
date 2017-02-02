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

			this.loaded();

		},

		loaded: function() {
			var brightcoveModalContainer = $('.brightcove-modal');

			var router = new BrightcoveRouter;
			wpbc.triggerModal = function() {
				if (!wpbc.modal) {
					wpbc.modal = new BrightcoveModalView({
						el: brightcoveModalContainer,
						tab: 'videos'
					});
					wpbc.modal.render();
					wpbc.modal.$el.find( '.spinner' ).addClass( 'is-active' );
				} else {
					wpbc.modal.$el.show();
				}

				// Prevent body scrolling by adding a class to 'body'.
				$( 'body' ).addClass( 'modal-open' );
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
				router.navigate('add-new-brightcove-video', { trigger:true });
			});

			$(document).on('click', '.brightcove-add-media', function( e ) {
				e.preventDefault();
				wpbc.triggerModal();
				wpbc.modal.target = e.currentTarget.dataset.target;
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

	jQuery( document ).ready( function() {
		App.load();
		var router = new BrightcoveRouter;
		Backbone.history.start();
	} );
