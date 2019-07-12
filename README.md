# Brightcove Video Connect

> Brightcove integration plugin, manage your Brightcove video cloud from within WordPress, using the latest APIs.

[![Support Level](https://img.shields.io/badge/support-active-green.svg)](#support-level) [![Release Version](https://img.shields.io/github/release/10up/brightcove-video-connect.svg)](https://github.com/10up/brightcove-video-connect/releases/latest) ![WordPress tested up to version](https://img.shields.io/badge/WordPress-v5.2%20tested-success.svg) [![GPLv2 License](https://img.shields.io/github/license/10up/brightcove-video-connect.svg)](https://github.com/10up/brightcove-video-connect/blob/develop/LICENSE.md)

## Description

Are you looking to handle your [Brightcove](https://www.brightcove.com/en/online-video-platform) Video and Playlist library natively from within WordPress?

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

## Support Level

**Active:** 10up is actively working on this, and we expect to continue work for the foreseeable future including keeping tested up to the most recent version of WordPress.  Bug reports, feature requests, questions, and pull requests are welcome.

## Changelog

A complete listing of all notable changes to Brightcove Video Connect are documented in [CHANGELOG.md](https://github.com/10up/brightcove-video-connect/blob/develop/CHANGELOG.md).
