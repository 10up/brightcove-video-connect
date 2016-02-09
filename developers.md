## Developer Guide

### Available filter hooks:


* `brightcove_account_actions`: Filter the available actions for each source on the Brightcove admin settings page. Enables adding or removing source actions on the settings screen. Default actions are `Edit Source` and `Delete Source`.
*`brightcove_max_posts_per_page`: Filter the maximum number of items the brightcove media call will query for.  Enables adjusting the `posts_per_page` parameter used when querying for media. Absint is applied, so a positive number should be supplied. Defaults to 100.
* `brightcove_proxy_cache_time_in_seconds`: Filter the length of time to cache proxied remote calls to the Brightcove API. Defaults to 180 seconds.


## Available action hooks:

The brightcove plugin uses these hooks internally when rendering out plugin pages. Developers can add content via these hooks, augmenting the existing admin pages.

* `brightcove/admin/settings_page`: Fires when the setting page loads.
* `brightcove/admin/videos_page`: Fires when the videos page loads.
* `brightcove/admin/playlists_page`: Fires when the playlist page loads.
* `brightcove/admin/edit_source_page`: Fires when the edit source page loads.