## Developer Guide

### Local Development

Portions of the plugin require a publicly-accessible web server to work. These features include

 * Media ingestion (video and image upload)
 * Status updates
 
While the plugin itself will function on a local or firewalled machine, some operations like the publishing of media to Brightcove require Brightcove to have access _back in_ to your site. If you're developing on a local server, you will need to use a proxy tunnel to make your machine accessible to Brightcove. An example of one such service is [ngrok](https://ngrok.com/), which creates a temporary, publicly-accessible URL that tunnels to a local service.

## PHP Hooks

### Filters:


 * `brightcove_account_actions`: Filter the available actions for each source on the Brightcove admin settings page. Enables adding or removing source actions on the settings screen. Default actions are `Edit Source` and `Delete Source`.
 * `brightcove_max_posts_per_page`: Filter the maximum number of items the brightcove media call will query for.  Enables adjusting the `posts_per_page` parameter used when querying for media. Absint is applied, so a positive number should be supplied. Defaults to 100.
 * `brightcove_proxy_cache_time_in_seconds`: Filter the length of time to cache proxied remote calls to the Brightcove API. Defaults to 180 seconds.


### Actions:

The brightcove plugin uses these hooks internally when rendering out plugin pages. Developers can add content via these hooks, augmenting the existing admin pages.

 * `brightcove/admin/settings_page`: Fires when the setting page loads.
 * `brightcove/admin/videos_page`: Fires when the videos page loads.
 * `brightcove/admin/playlists_page`: Fires when the playlist page loads.
 * `brightcove/admin/edit_source_page`: Fires when the edit source page loads.

## JavaScript architecture

### Event bus driven
The Brightcove plugin interface is a Backbone application that uses a single bus architecture to communicate changes. The plugin exposes a global `wpbc` object and uses `wpbc.broadcast` extended from `Backbone.Events` as an event pipeline.

Events are triggered by user interaction and async responses. Triggered events are listened for by views. The events sometimes contain data used by the view to change its underlying model data, and typically initiate a re-render of the view.

*Events fired include:*

`spinner:on`, `spinner:off`, `delete:successful`, `videoEdit:message`, `fetch:finished`, `close:modal`, `change:activeAccount`, `change:date`, `change:tag`, `change:emptyPlaylists`, `change:searchTerm`, `uploader:startUpload`, `uploader:prepareUpload`, `scroll:mediaGrid`, `uploader:clear`, `toggle:insertButton`, `remove:permanentMessage`, `permanent:message`, `insert:shortcode`,`tabChange`, `start:gridview`, `edit:media`, `preview:media`,`backButton`, `view:toggled`, `playlist:moveUp`, `playlist:moveDown`, `playlist:add`, `playlist:remove`, `uploader:queuedFilesAdded`, `uploader:fileUploaded`, `uploader:uploadedFileDetails`, `uploader:successfulUploadIngest`, `uploader:failedUploadIngest`, `uploader:params`, `uploader:errorMessage`, `uploader:successMessage`, `pendingUpload:selectedRow`, `pendingUpload:hideDetails`, `pendingUpload:selectedItem`, `upload:video`, `close:modal`, `delete:successful`, `videoEdit:message`

### Tracking the modal state

When the Brightcove modal is open, the modal state is tracked via `wpbc.modal.brightcoveMediaManager.model`
