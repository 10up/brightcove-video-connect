# Brightcove Video Connect

> Brightcove integration plugin, manage your Brightcove video cloud from within WordPress, using the latest APIs.
![WordPress tested up to version](https://img.shields.io/badge/WordPress-v5.2%20tested-success.svg)

## Description

Are you looking to handle your Brightcove Video and Playlist library natively from within WordPress?

With this plugin, developed by [10up](http://10up.com), you have the power to handle multiple accounts and video libraries, upload videos and add them to playlists, render shortcodes with your videos and all from within the WordPress admin interface.

## Support Notice

Brightcove Video Connect for WordPress is an open source plugin. Because customer implementations of WordPress and their environments vary, Brightcove can not fully support the plugin. However we love our customers and will do our best to help you resolve your issues.

Brightcove customers experiencing issues with Video Connect for WordPress may submit a ticket to the Support team.

## Installation

[Brightcove Video Connect](https://wordpress.org/plugins/brightcove-video-connect/) is available from the WordPress.org plugin repository and can be installed directly from your WordPress dashboard. Alternatively, if you need or prefer to manually install the plugin, you can do so following these steps:

#### Manual Installation

1. Upload the entire `/brightcove-video-connect` directory to the `/wp-content/plugins/` directory.
2. Activate Brightcove Video Connect through the 'Plugins' menu in WordPress.

## Frequently Asked Questions

##### Can I run the plugin on a WordPress install that isn't publicly accessible?
Yes, it will work whether it is public or not however features requiring a call-back to your site such as status updates, etc will be unavailable.

##### Are there any filters for plugin/theme developers?
* `brightcove_account_actions = [edit, delete];` - What actions are available when manipulating a Brightcove source.
* `brightcove_video_html` - Filter the HTML output.
* `brightcove_account_actions` - Filter the available actions for each source on the Brightcove admin settings page.
* `brightcove_api_callbacks` - Filter the callback URLs passed for Dynamic Ingest requests.
* `brightcove_proxy_cache_time_in_seconds` - Filter the length of time to cache proxied remote calls to the Brightcove API.

##### Will this work on multisite?
Yes it will.

##### Can I use more than one Brightcove account?
Yes, you can add sources from many Brightcove accounts if you want.

##### How does sync work?
The plugin simply pulls information directly from the Brightcove API for display and does not sync videos locally.

##### How can I increase Maximum upload file size?
Maximum file size is determined by your webserver and PHP configuration. You need to set the value of upload_max_filesize and post_max_size in your php.ini. php_ini_loaded_file() can help you find where your PHP.ini is located.

##### How do I add custom fields?
Custom fields must be created within your Brightcove Video Cloud account. Once created, the fields will be available within WordPress.

##### How do I enable change tracking so I can see who updated a video and when they did it?
Create a custom field of type 'text' with an internal name of '_change_history'. Whenever a video is updated, the username and current time will be added to a list of changes recorded in this field.

For nginx:
http://nginx.org/en/docs/http/ngx_http_core_module.html#client_max_body_size (client_max_body_size)
For apache:
http://httpd.apache.org/docs/current/mod/core.html#limitrequestbody (LimitRequestBody)

## Changelog

##### 1.7.0

* Enhancement: Folder API support.
* Fix: Removed extra slashes that appeared on titles and descriptions.
* Fix: Behavior of status update messages.


##### 1.6.1

* Fix: Fixed a bug related to the Gutenberg block.
* Fix: Updated support link on account settings page.


##### 1.6.0

* Enhancement: Gutenberg support.
* Fix: Fixed a bug causing video previews not to show.
* Fix: Fixed a bug causing playlist videos to not be listed.
* Fix: Update to the 'brightcove_media_query_results' filter to allow $processed_results to be used.
* Fix: Removal of deprecated options for Plupload.
* Fix: Adding last two parameters to add_action to remove PHP 7.2 warnings.
* Fix: Removal of hardcoded video page height.

##### 1.5.0

* Enhancement: Video Experience player.
* Enhancement: Video Experience playlists.
* Enhancement: Added filter to allow developer to specify an ingest profile.
* Fix: Resolves issue with WP dashboard being taken down when API is unresponsive.
* Fix: Adjustments to search behavior.
 


##### 1.4.1

* Fix: Fixed a minor issue with the API request.


##### 1.4.0

* Enhancement: Added player support for videos and playlists
* Enhancement: Updated playlist api to v2
* Fix: Fixed a bug that was causing issues when a playlist was edited

##### 1.3.2
* Enhancement: Add a target attribute to change shortcode placement
* Enhancement: Improved performance of search
* Fix: Fixed a bug that was causing an empty thumbnail to be displayed in search results

##### 1.3.1
* Enhancement: Show Brightcove button only in the main content editor
* Enhancement: Increased the timeout used to call Brightcove API
* Enhancement: Removed the 'Processing...' text from image thumbnails

##### 1.3.0
* Various bug fixes

##### 1.2.5

* Enhancement: Removed call to Brightcove status API

##### 1.2.4

* Fix: Fixed issues with "Insert into Post" button
* Enhancement: Make all text translatable.
* Enhancement: Enhancements to Add source screen
* Enhancement: Fixed all PHP warnings

##### 1.2.3
* Fix: Issue where API calls could fail silently

##### 1.2.2
* Fix: Add strict parameter to use of `in_array`
* Fix: Add .avi to list of accepted MIMEtypes
* Fix: Issue where caption file types were deemed invalid if they had a query string attached
* Fix: Add fallback to default player for playlist rendering
* Fix: Issue where search would leave an empty screen

##### 1.2.1
* Enhancement: Add `brightcove_video_html` filter
* Enhancement: Add support for the Heartbeat API
* Fix: Issue where playlist shortcode was mistakenly inserted
* Fix: Issue where spinners would not show on first load

##### 1.2.0
* Enhancement: Enable the presentation and control of custom fields on uploaded videos
* Enhancement: Add support for custom video player selection during publication
* Enhancement: Support ingestion of preroll (poster) images and video thumbnails
* Enhancement: Support ingestion of closed captions
* Enhancement: Track the name and date of any changes to a video

##### 1.1.3
* Fix: Tags should automatically populate drop down on videos page
* Fix: Clear search results if user empties field or clicks search field `x`
* Fix: Improve search handling
* Fix: Improve logic for exit edit mode when closing modal
* Fix: Ensure this bound properly in returned delete callback
* Fix: Prevent body/background from scrolling when modal open
* Fix: Fix a scroll overflow issue in the edit video modal
* Fix: When re-opening modal, always switch back to video grid view
* Fix: Activate the spinner only when opening modal, not in template
* Fix: Ensure close button handler doesn't interfere with other close requests
* Fix: Fix back button disabled detection
* Fix: Make the notices dismissable instead of fading
* Fix: Disable closing modal during sync
* Fix: Disable all buttons and hide delete link while syncing
* Fix: Disable all buttons on the edit video screen while syncing
* Fix: Correct scrollbar on Sync button click, adds some css padding
* Fix: Start with the spinner active, until the initial ajax request completes
* Fix: Set default account id for media manager
* Fix: Correct setting of account on selection
* Fix: Select default account for initial sync (as default)
* Fix: Only localize playlist data, get the rest via ajax
* Fix: Add selected to current account dropdown, remove All option
* Fix: Various miscellaneous corrections for updated WordPress VIP submission

##### 1.1.2
* Fix: Remove extra files. This is a holdout from 1.1.1 to remove all the extra files from the repository.

##### 1.1.1
* Fixed: playlists and other data saving to Database
* Fixed: incorrect close icon on media modal
* Fixed: minimum version check
* Enhancement: Removed callback subscriptions
* Fixed videos not consistently deleting
* Fixed caching issues
* Fixed editing videos from upload screen
* Fixed inconsistent player implementation
* Added responsive playlists and videos
* Removed GUI option for video width and height (more consistent to add manually)
* Added ability to bypass cache entirely
* Removed unnecessary code and other files throughout plugin and consolidated file structure
* Fixed broken shortcode insertion after changing video size via GUI
* Fixed inconsistent “Add new” button behavior on Video screen
* Fixed minimum size for playlist display (no longer only shows video selector when width >= 800px)
* Added notice upon playlist insertion if no compatible player is available - because you can never let people know enough
* Numerous other small typo corrections, fixes and enhancements

##### 1.1.0
* Enhancement: Brightcove Video Connect will now warn users when a part of the Brightcove API system is down that might affect plugin or video behavior.
* Enhancement: The playlist page will now display an error if no playlist compatible player exists
* Fix: Date filters have been removed from playlists and videos as they were unusable
* Fix: The Brightcove URL has been fixed to better support HTTP and HTTPS operations
* Fix: Fix row-action issues in playlists introduced since WordPress 4.4
* Miscellaneous minor fixes and corrections to copy throughout the plugin.

##### 1.0.9
* Fix: Fixed fatal error on uninstall
* Fix: Fixed smart playlist display
* Fix: Remove an error that could happen when adding an acount with empty playlists

##### 1.0.8
* Fix: Default sort for videos is now "newest first" for all screens listing videos.
* Fix: Playlists now display properly

##### 1.0.7
* Fix: Fixed the edit button for playlists

##### 1.0.6
* Fix: JavaScript has been greatly cleaned up and should no longer conflict on the post editor or other screens.

##### 1.0.5
* Refactored and cleaned up to meet WordPress VIP guidelines.

##### 1.0.4
* Fix: Fixed a PHP Fatal error that could occur when the connection to the Brightcove API failed.

##### 1.0.3
* Enhancement: Added ability to specify display size on video and playlist shortcodes.

##### 1.0.2
* Increasing HTTP timeout to fix sporadic issues when adding sources

##### 1.0.1
* Cleanup of references from /brightcove_video_cloud to /brightcove_video_connect.
* Fix: Plugin deactivation wasn't working.

##### 1.0.0
* First release

## Upgrade Notice

##### 1.2.4
Fixed issues with "Insert into Post" button

##### 1.1.3
* 1.1.3 solves many JavaScript errors that users have been experiencing and is recommended for all users

## License
Brightcove Video Connect is free software; you can redistribute it and/or modify it under the terms of the [GNU General Public License](http://www.gnu.org/licenses/gpl-2.0.html) as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
