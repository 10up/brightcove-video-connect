# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

<!--
### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security
-->

## [2.8.7] - 2024-10-09

### Fixed
- Add missing script close tag. Props [@burhandodhy](https://github.com/burhandodhy) via [#397](https://github.com/10up/brightcove-video-connect/pull/397).
- Selecting multiple videos while embedding a Video Experience results in error. Props [@burhandodhy](https://github.com/burhandodhy) via [#398](https://github.com/10up/brightcove-video-connect/pull/398).
- Single Video Player Embed appears very different in the block editor. Props [@burhandodhy](https://github.com/burhandodhy) via [#399](https://github.com/10up/brightcove-video-connect/pull/399).
- E2E tests. Props [@burhandodhy](https://github.com/burhandodhy) via [#400](https://github.com/10up/brightcove-video-connect/pull/400).
- Calculate the padding based on the Aspect ratio. [@burhandodhy](https://github.com/burhandodhy) via [#402](https://github.com/10up/brightcove-video-connect/pull/402).
- Video Save History. Props [@burhandodhy](https://github.com/burhandodhy) via [#404](https://github.com/10up/brightcove-video-connect/pull/404).

### Changed
- Allow only png and jpg images for thumbnail and poster. Props [@burhandodhy](https://github.com/burhandodhy) via [#401](https://github.com/10up/brightcove-video-connect/pull/401).
- Hide search field if video folder is selected. Props [@burhandodhy](https://github.com/burhandodhy) via [#403](https://github.com/10up/brightcove-video-connect/pull/403).

## [2.8.6] - 2024-05-08

### Fixed

- Cannot use Brightcove block on custom post types. Props [@spacedmonkey](https://github.com/spacedmonkey) and [@jonnynews](https://github.com/jonnynews) via [#389](https://github.com/10up/brightcove-video-connect/pull/389).
- Typo when saving the language_detection attribute. Props [@claimableperc](https://github.com/claimableperc) via [#390](https://github.com/10up/brightcove-video-connect/pull/390).
- Combined queries with q search parameter. Props [@oscarssanchezz](https://github.com/oscarssanchezz) via [#392](https://github.com/10up/brightcove-video-connect/pull/392).

## [2.8.5] - 2024-04-22

### Changed
- Add Select field for labels. Props [@burhandodhy](https://github.com/burhandodhy) via [#378](https://github.com/10up/brightcove-video-connect/pull/378).
- Cache the API response and display a message from where user can retry the API again. Props [@burhandodhy](https://github.com/burhandodhy) and [@jonnynews](https://github.com/jonnynews) via [#380](https://github.com/10up/brightcove-video-connect/pull/380).
- Update readme and assets. Props [@jeffpaul](https://github.com/jeffpaul) via [#375](https://github.com/10up/brightcove-video-connect/pull/375).

### Fixed
- First parameter type of add_submenu_page() when no parent slug is sent. Props [@jonnynews](https://github.com/jonnynews) via [#365](https://github.com/10up/brightcove-video-connect/pull/365).
- Display error message when the previous request is aborted. Props [@burhandodhy](https://github.com/burhandodhy) via [#368](https://github.com/10up/brightcove-video-connect/pull/368).
- PHP warning while editing a label. Props [@MARQAS](https://github.com/MARQAS) and [@felipeelia](https://github.com/felipeelia) via [#369](https://github.com/10up/brightcove-video-connect/pull/369).
- PHP warning while saving a video. Props [@MARQAS](https://github.com/MARQAS) and [@felipeelia](https://github.com/felipeelia) via [#370](https://github.com/10up/brightcove-video-connect/pull/370).
- Move playlist notice above controls bar. Props [@MARQAS](https://github.com/MARQAS) and [@felipeelia](https://github.com/felipeelia) via [#371](https://github.com/10up/brightcove-video-connect/pull/371).
- Clicking on the "Edit" and "Preview" buttons for the inactive videos breakes the layout. Props [@burhandodhy](https://github.com/burhandodhy) and [@felipeelia](https://github.com/felipeelia) via [#372](https://github.com/10up/brightcove-video-connect/pull/372).
- Add support for special characters. Props [@burhandodhy](https://github.com/burhandodhy) and [@cr0ybot](https://github.com/cr0ybot) via [#373](https://github.com/10up/brightcove-video-connect/pull/373).
- Search box alignment in Media modal. Props [@MARQAS](https://github.com/MARQAS) and [@felipeelia](https://github.com/felipeelia) via [#376](https://github.com/10up/brightcove-video-connect/pull/376).
- Limit API calls on unwanted admin pages. Props [@burhandodhy](https://github.com/burhandodhy) via [#379](https://github.com/10up/brightcove-video-connect/pull/379).
- Video update API fails when labels are empty. Props [@burhandodhy](https://github.com/burhandodhy) via [#381](https://github.com/10up/brightcove-video-connect/pull/381).

## [2.8.4] - 2023-11-30

### Added
- Brightcove version to user agent string. Props [@felipeelia](https://github.com/felipeelia) via [#341](https://github.com/10up/brightcove-video-connect/pull/341).

### Changed
- Use `wp.blockEditor.BlockControls` if available. Props [@felipeelia](https://github.com/felipeelia), [@JakePT](https://github.com/JakePT), and [@oscarssanchez](https://github.com/oscarssanchez) via [#342](https://github.com/10up/brightcove-video-connect/pull/342).

### Fixed
- Empty "Created At:" and "Updated At:" in playlists. Props [@burhandodhy](https://github.com/burhandodhy) and [@MARQAS](https://github.com/MARQAS) via [#349](https://github.com/10up/brightcove-video-connect/pull/349).
- Help notices being displayed more than once. Props [@burhandodhy](https://github.com/burhandodhy) and [@MARQAS](https://github.com/MARQAS) via [#350](https://github.com/10up/brightcove-video-connect/pull/350).
- Caption upload. Props [@burhandodhy](https://github.com/burhandodhy) and [@MARQAS](https://github.com/MARQAS) via [#351](https://github.com/10up/brightcove-video-connect/pull/351).

### Security
- Bumped `@babel/traverse` from 7.22.8 to 7.23.2. Props [@dependabot](https://github.com/dependabot) via [#343](https://github.com/10up/brightcove-video-connect/pull/343).

## [2.8.3] - 2023-08-03

### Fixed
- Playlist experience not rendering correctly in frontend
- Prevent API calls when Brightcove has not been configured yet
- Customization options hidden when switching embed type in in-page experiences

## [2.8.2] - 2022-03-16

### Fixed
- Fixed plays in line not working with iFrame elements. Props [@oscarssanchez](https://github.com/oscarssanchez) and [@felipeelia](https://github.com/felipeelia), via [#313](https://github.com/10up/brightcove-video-connect/pull/313).
- Fixed autplay not working with Brightcove Player 7. Props [@oscarssanchez](https://github.com/oscarssanchez) and [@felipeelia](https://github.com/felipeelia), via [#314](https://github.com/10up/brightcove-video-connect/pull/314).
- Fixed link to authentication API docs. Props [@oscarssanchez](https://github.com/oscarssanchez) and [@felipeelia](https://github.com/felipeelia), via [#315](https://github.com/10up/brightcove-video-connect/pull/315).

## [2.8.1] - 2022-12-13

### Fixed
- Fixes fatal error when Brightcove submenu item is null. Props [@phpbits](https://github.com/phpbits) and [@oscarssanchez](https://github.com/oscarssanchez) via [#300](https://github.com/10up/brightcove-video-connect/pull/300)

## [2.8.0] - 2022-10-17

### Deprecated
- BC_Oauth_API::Method _request_access_token() in favor of BC_Oauth_API::request_access_token()
- Action brightcove/admin/settings_page in favor of brightcove_admin_settings_page
- Action brightcove/admin/videos_page in favor of brightcove_admin_videos_page
- Action brightcove/admin/playlists_page in favor of brightcove_admin_playlists_page
- Action brightcove/admin/edit_source_page in favor of brightcove_admin_edit_source_page
- Action brightcove/admin/labels_page in favor of brightcove_admin_labels_page
- Action brightcove/admin/edit_label_page in favor of brightcove_admin_edit_label_page

### Added
- Ability to add Application ID parameter to video player. Props [@oscarssanchez](https://github.com/oscarssanchez), and [@felipeelia](https://github.com/felipeelia), via [#295](https://github.com/10up/brightcove-video-connect/pull/295).
- Initial E2E tests. Props [@oscarssanchez](https://github.com/oscarssanchez), and [@felipeelia](https://github.com/felipeelia), via [#288](https://github.com/10up/brightcove-video-connect/pull/288).

### Fixed
- PHPCS Fixes. Props [@oscarssanchez](https://github.com/oscarssanchez), and [@felipeelia](https://github.com/felipeelia), via [#286](https://github.com/10up/brightcove-video-connect/pull/286).

## [2.7.0] - 2022-06-29

### Fixed
- Picture in Picture not working. Props [@oscarssanchez](https://github.com/oscarssanchez), and [@felipeelia](https://github.com/felipeelia), via [#272](https://github.com/10up/brightcove-video-connect/pull/272).

### Added

- Enable audio track language detection based on browser language if video has multiple audio tracks. Props [@oscarssanchez](https://github.com/oscarssanchez), and [@felipeelia](https://github.com/felipeelia), via [#279](https://github.com/10up/brightcove-video-connect/pull/279).

## [2.6.1] - 2022-06-07

### Fixed
- Brightcove API changes break video edit view. Props [@oscarssanchez](https://github.com/oscarssanchez), and [@felipeelia](https://github.com/felipeelia), via [#276](https://github.com/10up/brightcove-video-connect/pull/276).

## [2.6.0] - 2022-04-19

### Added
- Support custom fields with multilingual metadata. Props [@oscarssanchez](https://github.com/oscarssanchez), and [@felipeelia](https://github.com/felipeelia), via [#266](https://github.com/10up/brightcove-video-connect/pull/266).

## [2.5.2] - 2022-03-17

### Added
- Update multilingual metadata. Props [@oscarssanchez](https://github.com/oscarssanchez) via [#256](https://github.com/10up/brightcove-video-connect/pull/256).

## [2.5.1] - 2022-01-24

### Added
- Ability to display video URL in videos page. Props [@Rahmon](https://github.com/Rahmon), and [@oscarssanchez](https://github.com/oscarssanchez) via [#247](https://github.com/10up/brightcove-video-connect/pull/247).

### Fixed
- Video and Playlist experiences displays wrong block settings. Props [@Rahmon](https://github.com/Rahmon), and [@oscarssanchez](https://github.com/oscarssanchez) via [#250](https://github.com/10up/brightcove-video-connect/pull/250).
- Update attribute type from int to string. Props [@Rahmon](https://github.com/Rahmon), and [@oscarssanchez](https://github.com/oscarssanchez) via [#246](https://github.com/10up/brightcove-video-connect/pull/246).
- Pass sizing attribute to determine if responsiveness should be enabled. Props [@Rahmon](https://github.com/Rahmon), and [@oscarssanchez](https://github.com/oscarssanchez) via [#249](https://github.com/10up/brightcove-video-connect/pull/249).
- Fix broken Brightcove experiences embedding. Props [@Rahmon](https://github.com/Rahmon), and [@oscarssanchez](https://github.com/oscarssanchez) via [#248](https://github.com/10up/brightcove-video-connect/pull/248).

## [2.5.0] - 2021-12-21

### Added
- State, Scheduled Start Date, and Scheduled End Date fields to the video edit screen. Props [@Rahmon](https://github.com/Rahmon), and [@oscarssanchez](https://github.com/oscarssanchez) via [#241](https://github.com/10up/brightcove-video-connect/pull/241).

## [2.4.0] - 2021-11-29

### Added
- In-Page Experiences. Props [@Rahmon](https://github.com/Rahmon), and [@oscarssanchez](https://github.com/oscarssanchez) via [#239](https://github.com/10up/brightcove-video-connect/pull/239).

## [2.3.1] - 2021-11-04

### Fixed
- Label field on video editing. Props [@Rahmon](https://github.com/Rahmon), and [@oscarssanchez](https://github.com/oscarssanchez) via [#232](https://github.com/10up/brightcove-video-connect/pull/232).

## [2.3.0] - 2021-10-21

### Added
- Settings sidebar to the Brightcove block. Props [@Rahmon](https://github.com/Rahmon), and [@oscarssanchez](https://github.com/oscarssanchez) via [#229](https://github.com/10up/brightcove-video-connect/pull/229).

### Security
- Bump `path-parse` from 1.0.6 to 1.0.7. Props [@dependabot](https://github.com/dependabot) via [#222](https://github.com/10up/brightcove-video-connect/pull/222).
- Bump `tar` from 6.1.5 to 6.1.11. Props [@dependabot](https://github.com/dependabot) via [#223](https://github.com/10up/brightcove-video-connect/pull/223).

## [2.2.1] - 2021-09-08

### Changed
- Updated the screenshots. Props [@Rahmon](https://github.com/Rahmon), and [@jeffpaul](https://github.com/jeffpaul) via [#224](https://github.com/10up/brightcove-video-connect/pull/224).


## [2.2.0] - 2021-08-09

### Breaking Changes
- BC_Utility API changed:
  - See `set_cache_item` and `delete_cache_item` in `includes/class-bc-utility.php`.
  - Removed `remove_deleted_players` function.
### Fixed
- Undefined index warnings. Props [@sanketio](https://github.com/sanketio) via [#197](https://github.com/10up/brightcove-video-connect/pull/197).
- Typo for the `$allowedtags` global used in conjunction with wp_kses. Props [@theskinnyghost](https://github.com/theskinnyghost) via [#203](https://github.com/10up/brightcove-video-connect/pull/203).
- Performance issue related with bc_transient_keys option. Props [@Rahmon](https://github.com/Rahmon) via [#215](https://github.com/10up/brightcove-video-connect/pull/215).
- Playlist preview in the editor. Props [@Rahmon](https://github.com/Rahmon) via [#216](https://github.com/10up/brightcove-video-connect/pull/216).

### Security
- Bump `hosted-git-info` from 2.8.8 to 2.8.9 (props [@dependabot](https://github.com/dependabot) via [#212](https://github.com/10up/brightcove-video-connect/pull/212))
- Bump `normalize-url` from 4.5.0 to 4.5.1 (props [@dependabot](https://github.com/dependabot) via [#213](https://github.com/10up/brightcove-video-connect/pull/213))

## [2.1.4] - 2021-06-23
### Fixed
- Fix: Default Source field when is submitted unchecked.
- Fix: Adjust the position of media details in the editing modal.
- Fix: Clear filtered results when the input search is empty.
- Fix: Add missing mute attribute in the block.

## [2.1.3] - 2021-06-03
### Fixed
- Fix: Playlist player is not available.

## [2.1.2] - 2021-02-17
### Fixed
- Fix: Default state filter: display filtered default on dropdown.
- Fix: jQuery context deprecation.

## [2.1.1] - 2021-01-11
### Fixed
- Fix: Fresh installation bugfix with labels.
- Fix: PHPCS issues.

## [2.1] - 2020-12-21
### Added
- Feature: Labels.
- Feature: VIP error logging to NewRelic.
- Changed to most recent logos.
- Feature: Status filter.

## [2.0] - 2020-09-15
### Added
- Feature: Multi language caption processing.
- Feature: Active/Inactive videos filtering.

## [1.9.2] - 2020-06-29
### Added
- Add a notice when credentials are revoked, prompting user to update them.

### Fixed
- Fix: Fatal error when credentials are revoked.

## [1.9.1] - 2020-03-18
### Fixed
- Bug in preview when switching from classic editor to Gutenberg.
- Bug with editor capabilities.

## [1.9.0] - 2020-02-19
### Added
- Picture in picture support.
- Support to use reference id in videos.

## [1.8.2] - 2020-01-24
### Fixed
- Upload new videos bug.

## [1.8.1] - 2020-01-20
### Fixed
- Adding a new brightcove account bug

## [1.8.0] - 2020-01-02
### Added
- Enable search on playlists
- playsinline option for embeds
- Settings field to have a default player size width

### Fixed
- URL encoding uploads for files with foreign characters
- Player ordering to better resemble the order in Brightcove Studio.

## [1.7.2] - 2019-09-10
### Fixed
- Iframe Padding issue
- Stop showing inactive players from Brightcove studio in the plugin
- Source select account bug

## [1.7.1] - 2019-07-12
### Fixed
- Settings page not loading when plugin is network activated.
- Adding multiple Gutenberg blocks to a post causes videos to sync video content.
- PHP notice when information from Brightcove account not available.

## [1.7.0] - 2019-03-26
### Added
- Folder API support.

### Fixed
- Removed extra slashes that appeared on titles and descriptions.
- Behavior of status update messages.

## [1.6.1] - 2018-12-14
### Fixed
- Bug related to the Gutenberg block.
- Updated support link on account settings page.

## [1.6.0] - 2018-09-19
### Added
- Gutenberg support.

### Fixed
- Bug causing video previews not to show.
- Bug causing playlist videos to not be listed.
- Update to the 'brightcove_media_query_results' filter to allow $processed_results to be used.
- Removal of deprecated options for Plupload.
- Adding last two parameters to add_action to remove PHP 7.2 warnings.
- Removal of hardcoded video page height.

## [1.5.0] - 2018-08-02
### Added
- Video Experience player.
- Video Experience playlists.
- Added filter to allow developer to specify an ingest profile.

### Fixed
- Resolves issue with WP dashboard being taken down when API is unresponsive.
- Adjustments to search behavior.

## [1.4.1] - 2018-04-03
### Fixed
-  Minor issue with the API request.

## [1.4.0] - 2018-04-03
### Added
- Player support for videos and playlists
- Updated playlist api to v2

### Fixed
-  Bug that was causing issues when a playlist was edited

## [1.3.2] - 2017-09-14
### Added
- Target attribute to change shortcode placement
- Improved performance of search

### Fixed
- Bug that was causing an empty thumbnail to be displayed in search results

## [1.3.1] - 2017-03-20
### Added
- Show Brightcove button only in the main content editor
- Increased the timeout used to call Brightcove API
- Removed the 'Processing...' text from image thumbnails

## [1.3.0] - 2016-10-07
### Fixed
- Various bug fixes

## [1.2.5] - 2016-09-14
### Added
- Removed call to Brightcove status API

## [1.2.4] - 2016-06-22
### Added
- Make all text translatable.
- Enhancements to Add source screen
- Fixed all PHP warnings

### Fixed
- Issues with "Insert into Post" button

## [1.2.3] - 2016-06-09
### Fixed
- Issue where API calls could fail silently

## [1.2.2] - 2016-05-20
### Fixed
- Add strict parameter to use of `in_array`
- Add .avi to list of accepted MIMEtypes
- Issue where caption file types were deemed invalid if they had a query string attached
- Add fallback to default player for playlist rendering
- Issue where search would leave an empty screen

## [1.2.1] - 2016-04-29
### Added
- `brightcove_video_html` filter
- Support for the Heartbeat API

### Fixed
- Issue where playlist shortcode was mistakenly inserted
- Issue where spinners would not show on first load

## [1.2.0] - 2016-03-29
### Added
- Enable the presentation and control of custom fields on uploaded videos
- Support for custom video player selection during publication
- Support ingestion of preroll (poster) images and video thumbnails
- Support ingestion of closed captions
- Track the name and date of any changes to a video

## [1.1.3] - 2016-01-15
### Fixed
- Tags should automatically populate drop down on videos page
- Clear search results if user empties field or clicks search field `x`
- Improve search handling
- Improve logic for exit edit mode when closing modal
- Ensure this bound properly in returned delete callback
- Prevent body/background from scrolling when modal open
- Scroll overflow issue in the edit video modal
- When re-opening modal, always switch back to video grid view
- Activate the spinner only when opening modal, not in template
- Ensure close button handler doesn't interfere with other close requests
- Back button disabled detection
- Make the notices dismissable instead of fading
- Disable closing modal during sync
- Disable all buttons and hide delete link while syncing
- Disable all buttons on the edit video screen while syncing
- Correct scrollbar on Sync button click, adds some css padding
- Start with the spinner active, until the initial ajax request completes
- Set default account id for media manager
- Correct setting of account on selection
- Select default account for initial sync (as default)
- Only localize playlist data, get the rest via ajax
- Add selected to current account dropdown, remove All option
- Various miscellaneous corrections for updated WordPress VIP submission

## [1.1.2] - 2015-12-28
### Fixed
- Remove extra files. This is a holdout from 1.1.1 to remove all the extra files from the repository.

## [1.1.1] - 2015-12-28
### Added
- Removed callback subscriptions
- Responsive playlists and videos
- Ability to bypass cache entirely
- Notice upon playlist insertion if no compatible player is available - because you can never let people know enough

### Removed
- GUI option for video width and height (more consistent to add manually)
- Unnecessary code and other files throughout plugin and consolidated file structure

### Fixed
- Playlists and other data saving to Database
- Incorrect close icon on media modal
- Minimum version check
- Videos not consistently deleting
- Caching issues
- Editing videos from upload screen
- Inconsistent player implementation
- Broken shortcode insertion after changing video size via GUI
- Inconsistent “Add new” button behavior on Video screen
- Minimum size for playlist display (no longer only shows video selector when width >= 800px)
- Numerous other small typo corrections, fixes and enhancements

## [1.1.0] - 2015-12-17
### Added
- Brightcove Video Connect will now warn users when a part of the Brightcove API system is down that might affect plugin or video behavior.
- The playlist page will now display an error if no playlist compatible player exists

### Fixed
- Date filters have been removed from playlists and videos as they were unusable
- The Brightcove URL has been fixed to better support HTTP and HTTPS operations
- row-action issues in playlists introduced since WordPress 4.4
- Miscellaneous minor fixes and corrections to copy throughout the plugin.

## [1.0.9] - 2015-11-23
### Fixed
- Fatal error on uninstall
- Smart playlist display
- Remove an error that could happen when adding an acount with empty playlists

## [1.0.8] - 2015-10-29
### Fixed
- Default sort for videos is now "newest first" for all screens listing videos.
- Playlists now display properly

## [1.0.7] - 2015-10-21
### Fixed
- Edit button for playlists

## [1.0.6] - 2015-10-21
### Fixed
- JavaScript has been greatly cleaned up and should no longer conflict on the post editor or other screens.

## [1.0.5] - 2015-09-02
### Fixed
- Refactored and cleaned up to meet WordPress VIP guidelines.

## [1.0.4] - 2015-07-29
### Fixed
- PHP Fatal error that could occur when the connection to the Brightcove API failed.

## [1.0.3] - 2015-07-17
### Added
- Ability to specify display size on video and playlist shortcodes.

## [1.0.2] - 2015-06-29
### Fixed
- Increasing HTTP timeout to fix sporadic issues when adding sources

## [1.0.1] - 2015-06-15
### Fixed
- Cleanup of references from /brightcove_video_cloud to /brightcove_video_connect.
- Plugin deactivation wasn't working.

## [1.0.0] - 2015-06-15
- First release

[Unreleased]: https://github.com/10up/brightcove-video-connect/compare/master...develop
[2.8.7]: https://github.com/10up/brightcove-video-connect/compare/2.8.6...2.8.7
[2.8.6]: https://github.com/10up/brightcove-video-connect/compare/2.8.5...2.8.6
[2.8.5]: https://github.com/10up/brightcove-video-connect/compare/2.8.4...2.8.5
[2.8.4]: https://github.com/10up/brightcove-video-connect/compare/2.8.3...2.8.4
[2.8.3]: https://github.com/10up/brightcove-video-connect/compare/2.8.2...2.8.3
[2.8.2]: https://github.com/10up/brightcove-video-connect/compare/2.8.1...2.8.2
[2.8.1]: https://github.com/10up/brightcove-video-connect/compare/2.8.0...2.8.1
[2.8.0]: https://github.com/10up/brightcove-video-connect/compare/2.7.0...2.8.0
[2.7.0]: https://github.com/10up/brightcove-video-connect/compare/2.6.1...2.7.0
[2.6.1]: https://github.com/10up/brightcove-video-connect/compare/2.6.0...2.6.1
[2.6.0]: https://github.com/10up/brightcove-video-connect/compare/2.5.2...2.6.0
[2.5.2]: https://github.com/10up/brightcove-video-connect/compare/2.5.1...2.5.2
[2.5.1]: https://github.com/10up/brightcove-video-connect/compare/2.5.0...2.5.1
[2.5.0]: https://github.com/10up/brightcove-video-connect/compare/2.4.0...2.5.0
[2.4.0]: https://github.com/10up/brightcove-video-connect/compare/2.3.1...2.4.0
[2.3.1]: https://github.com/10up/brightcove-video-connect/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/10up/brightcove-video-connect/compare/2.2.1...2.3.0
[2.2.1]: https://github.com/10up/brightcove-video-connect/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/10up/brightcove-video-connect/compare/2.1.4...2.2.0
[2.1.4]: https://github.com/10up/brightcove-video-connect/compare/2.1.3...2.1.4
[2.1.3]: https://github.com/10up/brightcove-video-connect/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/10up/brightcove-video-connect/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/10up/brightcove-video-connect/compare/2.1...2.1.1
[2.1.0]: https://github.com/10up/brightcove-video-connect/compare/2.0...2.1
[2.0.0]: https://github.com/10up/brightcove-video-connect/compare/1.9.2...2.0
[1.9.2]: https://github.com/10up/brightcove-video-connect/compare/1.9.1...1.9.2
[1.9.1]: https://github.com/10up/brightcove-video-connect/compare/1.9.0...1.9.1
[1.9.0]: https://github.com/10up/brightcove-video-connect/compare/1.8.2...1.9.0
[1.8.2]: https://github.com/10up/brightcove-video-connect/compare/1.8.1...1.8.2
[1.8.1]: https://github.com/10up/brightcove-video-connect/compare/1.8.0...1.8.1
[1.8.0]: https://github.com/10up/brightcove-video-connect/compare/1.7.2...1.8.0
[1.7.2]: https://github.com/10up/brightcove-video-connect/compare/c7f3fd7...1.7.2
[1.7.1]: https://github.com/10up/brightcove-video-connect/compare/1.7.0...c7f3fd7
[1.7.0]: https://github.com/10up/brightcove-video-connect/compare/1.6.1...1.7.0
[1.6.1]: https://github.com/10up/brightcove-video-connect/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/10up/brightcove-video-connect/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/10up/brightcove-video-connect/compare/1.4.1...1.5.0
[1.4.1]: https://github.com/10up/brightcove-video-connect/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/10up/brightcove-video-connect/compare/1.3.2...1.4.0
[1.3.2]: https://github.com/10up/brightcove-video-connect/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/10up/brightcove-video-connect/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/10up/brightcove-video-connect/compare/1.2.5...1.3.0
[1.2.5]: https://github.com/10up/brightcove-video-connect/compare/1.2.4...1.2.5
[1.2.4]: https://github.com/10up/brightcove-video-connect/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/10up/brightcove-video-connect/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/10up/brightcove-video-connect/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/10up/brightcove-video-connect/compare/66f7e1...1.2.1
[1.2.0]: https://github.com/10up/brightcove-video-connect/compare/1.1.2...66f7e1
[1.1.3]: https://github.com/10up/brightcove-video-connect/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/10up/brightcove-video-connect/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/10up/brightcove-video-connect/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/10up/brightcove-video-connect/compare/1.0.9...1.1.0
[1.0.9]: https://github.com/10up/brightcove-video-connect/releases/tag/1.0.9
[1.0.8]: https://plugins.trac.wordpress.org/changeset/1275489/brightcove-video-connect
[1.0.7]: https://plugins.trac.wordpress.org/changeset/1270703/brightcove-video-connect
[1.0.6]: https://plugins.trac.wordpress.org/changeset/1270415/brightcove-video-connect
[1.0.5]: https://plugins.trac.wordpress.org/changeset/1236593/brightcove-video-connect
[1.0.4]: https://plugins.trac.wordpress.org/changeset/1209004/brightcove-video-connect
[1.0.3]: https://plugins.trac.wordpress.org/changeset/1201210/brightcove-video-connect
[1.0.2]: https://plugins.trac.wordpress.org/changeset/1189511/brightcove-video-connect
[1.0.1]: https://plugins.trac.wordpress.org/changeset/1181268/brightcove-video-connect
[1.0.0]: https://plugins.trac.wordpress.org/changeset/1181185/brightcove-video-connect
