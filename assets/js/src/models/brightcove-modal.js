/**
 * Media model for Media CPT
 */

var BrightcoveModalModel = Backbone.Model.extend({
	getMediaManagerSettings: function () {
		var tab = this.get('tab');
		var settings = {
			upload: {
				accounts: 'all',
				date: 'all',
				embedType: 'modal',
				mediaType: 'videos',
				mode: 'uploader',
				preload: true,
				search: '',
				tags: 'all',
				viewType: 'grid',
				poster: {},
				thumbnail: {},
			},
			videos: {
				accounts: 'all',
				date: 'all',
				embedType: 'modal',
				mediaType: 'videos',
				mode: 'manager',
				preload: true,
				search: '',
				tags: 'all',
				viewType: 'grid',
			},
			playlists: {
				accounts: 'all',
				date: 'all',
				embedType: 'modal',
				mediaType: 'playlists',
				mode: 'manager',
				preload: true,
				search: '',
				tags: 'all',
				viewType: 'grid',
			},
			'video-experience': {
				accounts: 'all',
				date: 'all',
				embedType: 'modal',
				mediaType: 'videoexperience',
				mode: 'manager',
				preload: true,
				search: '',
				tags: 'all',
				viewType: 'grid',
			},
			'playlist-experience': {
				accounts: 'all',
				date: 'all',
				embedType: 'modal',
				mediaType: 'playlistexperience',
				mode: 'manager',
				preload: true,
				search: '',
				tags: 'all',
				viewType: 'grid',
			},
			'in-page-experiences': {
				accounts: 'all',
				date: 'all',
				embedType: 'modal',
				mediaType: 'inpageexperiences',
				mode: 'manager',
				preload: true,
				search: '',
				tags: 'all',
				viewType: 'grid',
			},
		};

		if (undefined !== settings[tab]) {
			return settings[tab];
		}
		return false;
	},
});
