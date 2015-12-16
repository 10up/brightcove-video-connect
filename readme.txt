=== Brightcove Video Connect ===

Contributors:      10up, ivankk, technosailor, ChrisWiegman, tott, eduardmaghakyan, mattonomics, phoenixfireball, karinedo, foobuilder, helen, tlovett1, jonathantneal, brightcove, adamsilverstein
Donate link:       https://supporters.eff.org/donate
Tags:              brightcove, 10up, videos, video
Requires at least: 4.2
Tested up to:      4.4
Stable tag:        1.0.9
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Brightcove integration plugin, manage your Brightcove video cloud from within
WordPress, using latest APIs

== Description ==

Are you looking to handle your Brightcove Video and Playlist library natively
from within WordPress?

With this plugin, developed by 10up.com, you have the power to handle
multiple accounts and video libraries, upload videos and add them to
playlists, render shortcodes with your videos and all from within the
WordPress admin interface.

=== Support Notice ===

Video Connect for Wordpress is an open source plugin. Because customer implementations of Wordpress and their environments vary, Brightcove can not fully support the plugin. However we love our customers and will do our best to help you resolve your issues.
Brightcove customers experiencing issues with Video Connect for Wordpress may submit a ticket to the Support team.

== Installation ==

= Manual Installation =

1. Upload the entire `/brightcove-video-connect` directory to the `/wp-content/plugins/` directory.
2. Activate Brightcove Video Connect through the 'Plugins' menu in WordPress.

== Usage ==

Refer to screenshots for walkthrough of the usage of the plugin.

= I want to put a new video into a post =
As a writer for my WordPress-powered publication, I  would like to upload a video to Brightcove and render it in an article I am writing.

The process is pretty straightforward.

During a typical editorial process, an article is created in the WordPress post (or page, or any other custom post type) edit screen. As core functionality, above the editor, but below the title, there is an "Add Media" Button. With Brightcove Video Connect active, there is also a "Brightcove Media" button available.

**Screenshot 10**

By clicking on this button, a media modal, very similar to WordPress core, appears.

In this modal, there are three tabs &mdash; Upload Files, Videos, and Playlists. By clicking on the "Upload Files" tab, you have access to two methods of uploading media. You can choose the "Select Files" button and browse to it using the Finder or Windows Explorer or, more simply, you can drag and drop to the browser window which doubles as a "drop zone", not unlike core media uploading.

**Screenshot 6**

**Note:**
You may select multiple files to upload at a single time.

In WordPress core media upload support, media immediately uploads without prompt, however due to the nature of the plugins' ability to support multiple Brightcove Accounts, the next step does not include an automatic upload.

Once the files have been queued for upload, you may edit the details of the video before it is sent to Brightcove. The ingestion.upload does not begin until you hit the "Start Upload" button. Note that, at any time, video data can be edited by selecting the queued upload and editing details in the right sidebar.

**Screenshot 8**

Once the "Start Upload" button has been clicked, the queued media will be sent to Brightcove for ingestion. There is a progress bar that indicates the progress of the upload and an error or success message will appear below the queue upon
queue to indicate the result of the request.

Assuming a successful upload, at this point the video is _not_ on Brightcove and is unable to be used until Brightcove transcodes the video into the formats they support. When that transcoding process completes, Brightcove will notify WordPress of the success of the transcoding and your video is ready for use in WordPress.

In WordPress itself, the video is available for insertion, however, due to transcoding incompletion, it cannot be rendered, even if it can be inserted into the post.

Back in the modal, switching to the "Videos" tab displays a list of filterable videos available to WordPress. Find the video in the list, and select it.

**Screenshot 4**

By selecting the video, the video details show up in the right sidebar. At this point, if you'd like to see how the video looks in a Brightcove player, you can choose the "Preview" button to see an interactive view of the video prior to insertion.

If you feel the need to modify the details of the video &mdash; title, description, tags &mdash; click on the "Edit" button for a view to do that.

Once you have found your video, and made any necessary changes to it, click on the "Insert into Post" button. WordPress will send a <a href="https://codex.wordpress.org/Shortcode">shortcode</a> to the editor containing the necessary parameters needed for WordPress to render the video.

While the editor is in text mode, the shortcode will display in plain text.

**Screenshot 11**

While in Visual mode, the video is rendered in the editor.

Publish!

= I want to put a playlist into a post =
Open Edit Post Page > Brightcove Media button > Playlists Tab.
Select the playlist which you want to insert into post.
Click "Insert Into Post" button on the right bottom.
In order to create a new playlist you need to login to http://videocloud.brightcove.com/.

== I want to edit a video ==


From your [WordPress dashboard](https://en.support.wordpress.com/dashboard/), click the Brightcove widget from the dashboard menu. You will see the Brightcove dashboard with a list of recent videos.
Click on a video thumbnail from the videos list. You will see more information and actions for this video.
Click the **Edit** button. You will be taken to a page where you may edit the **name**, **description**, **long description**, and any **tags** for the video.
In the actions section, you may also click the **Delete** link if you wish to delete the video. This will immediately delete the video and there will not be a confirmation beforehand.
Once finished, click the **Save and Sync Changes** button. The video will be updated immediately.
To leave the edit screen, click the **Back** button. You will be returned to the Brightcove dashboard with a list of recent videos.

= I want to edit a playlist =
N.B. If you want to create or delete a playlist, that can only be performed via the studio: http://videocloud.brightcove.com/

Open Brightcove Menu > Playlists or
Edit Post Page > Brightcove Media Button > Playlists Tab
Select the Playlist you wish to edit from the grid view. You can use the filters to search for it.
With the playlist details visible, press the Edit Button to open the Edit View. - screenshot-16
Videos in the playlist are on the left, videos available to be added to the playlist are on the right.
Add a video to the playlist with Add to Playlist Button
Change the order of the playlist by pressing the Move Up / Move Down buttons next to each video.
Remove videos by pressing the remove video button.
Playlist Name can be changed via the text field at the top.
No need to save anything as all changes are synchronized to Brightcove automatically.

= I want to add an account =
Create your API credentials via http://docs.brightcove.com/en/video-cloud/studio/managing-accounts/managing-api-credentials.html
Visit the settings page, Brightcove Menu > Settings.
Input a memorable name to differentiate your source from all other sources.
Input your API credentials and press the Check Credentials button.
We don't recommend adding more than one source for a particular ID.


== Frequently Asked Questions ==

= Can I run the plugin on a WordPress install that isn't publicly accessible? =
Yes you can, however video ingestion will NOT work as Brightcove can not reach
your uploaded video assets to ingest them. Make sure you add this line to your
wp-config.php
define( 'BRIGHTCOVE_FORCE_SYNC', true );

= Are there any filters for plugin/theme developers? =
brightcove_videos_per_page =  100; // Number of videos to
fetch at a time when we're performing a sync.
brightcove_account_actions = [edit, delete]; // What actions are available when manipulating a Brightcove source.

= Will this work on multisite? =
Yes it will.

= Can I use more than one Brightcove account? =
Yes, you can add sources from many Brightcove accounts if you want.

= How does sync work? =
Brightcove has a notifications API that lets your WordPress site know when a video has been ingested, and also if any video metadata has changed. We use that as a chance to ensure our library matches Brightcove's, so they're both showing the same content.

= How can I increase Maximum upload file size? =
Maximum file size is determined by your webserver and PHP configuration. You need to set the value of upload_max_filesize and post_max_size in your php.ini. php_ini_loaded_file() can help you find where your PHP.ini is located.

For nginx:
http://nginx.org/en/docs/http/ngx_http_core_module.html#client_max_body_size (client_max_body_size)
For apache:
http://httpd.apache.org/docs/current/mod/core.html#limitrequestbody (LimitRequestBody)

== Screenshots ==
1. 1. List of all sources configured for Brightcove 2.Account ID for the source, 3 source's Client ID, 4. Add a new source
button.
2. 1-4 Brightcove account settings from https://videocloud.brightcove.com/admin/api We only support accounts with full read/write permissions. 5. Whether this should be the default account that we upload videos to.
3. Confirmation of a valid account.
4. 1. Videos in grid view. 2. Select video to open details sidebar. 3. Divider. 4. Thumbnail of video. 5. Toggle edit video mode. 6.  Toggle video preview mode. 7. Show videos of specific source. 8. Show videos uploaded in certain month. 9. Show videos of a specific tag. 10. Search. 11.  Add a new video.
5. Modal Window, 1. Upload new files, 2. Current Tab, 3. Playlist Grid View.  4. Insert selected video as a shortcode into the post.
6. Uploader supporting select or drag and drop for multiple video uploads.
7. 1. Edit Source page, 1. Source name, 2. Account ID, 3. Make the source default
8. 1. Metadata changes are reflected in row. 2. Hide empty play lists. 3. Search playlist. 4. Thumbnail of playlist. 5. Selected playlist to edit.
9.  Playlist in grid view. 1. Show playlist from all sources 2. Select video to open details sidebar. 3. Divider. 4. Thumbnail of video. 5. Toggle edit video mode. 6.  Toggle video preview mode. 7. Show videos of specific source. 8. Show videos uploaded in certain month. 9. Show videos of a specific tag. 10. Search. 11.  Add a new video.
10. Post edit view displaying 1. Brightcove Media modal button. 2. Video shortcode. 3. Playlist shortcode. 4. Toggle to visual mode where players are rendered.
11. Post edit view in visual mode. 1. Processed shortcode rendered by Brightcove. 2. Video Edit to launch modal.
12. Video preview from video grid modal.
13. Rendered post with videos inserted. What the user sees.
14. Playlist tab in modal and playlists page containing 1. Sources filter, 2. Whether we hide empty playlists. 3. Search bar. 4. Select a playlist to open the details tab. 5. Playlist video count. 6. Edit Playlist button.

== attribution ==
All videos used in our demo are freely downloadable at https://vimeo.com
These include:
The Things about dogs
https://vimeo.com/71336599
The Village
https://vimeo.com/25353089
Monkey Moon
https://vimeo.com/33711317
TimeScapes: Rapture
https://vimeo.com/album/2079687/video/16369165
Oxygen
https://vimeo.com/album/2079687/video/4433312
Splice Holiday Video 2011
https://vimeo.com/33971928
Sample Video
http://sample-videos.com/

== Changelog ==

= 1.0.9 =
* Fix: Fixed fatal error on uninstall
* Fix: Fixed smart playlist display
* Fix: Remove an error that could happen when adding an acount with empty playlists

= 1.0.8 =
* Fix: Default sort for videos is now "newest first" for all screens listing videos.
* Fix: Playlists now display properly

= 1.0.7 =
* Fix: Fixed the edit button for playlists

= 1.0.6 =
* Fix: JavaScript has been greatly cleaned up and should no longer conflict on the post editor or other screens.

= 1.0.5 =
* Refactored and cleaned up to meet WordPress VIP guidelines.

= 1.0.4 =
* Fix: Fixed a PHP Fatal error that could occur when the connection to the Brightcove API failed.

= 1.0.3 =
* Enhancement: Added ability to specify display size on video and playlist shortcodes.

= 1.0.2 =
* Increasing HTTP timeout to fix sporadic issues when adding sources

= 1.0.1 =
* Cleanup of references from /brightcove_video_cloud to /brightcove_video_connect.
* Fix: Plugin deactivation wasn't working.

= 1.0.0 =
* First release

== Upgrade Notice ==

= 1.0.9 =
1.0.9 is an important bugfix and is recommended for all users

= 1.0.8 =
1.0.8 is an important bugfix and is recommended for all users

= 1.0.6 =
1.0.6 is a major bugfix and is recommended for all users

= 1.0.0 =
First Release


