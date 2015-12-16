<?php
class BC_Admin_Templates {

	public function __construct() {
		add_action( 'admin_footer', array( $this, 'add_templates' ) );
	}

	/**
	 * Adds all templates for Backbone application
	 */
	public function add_templates() {
		?>

		<?php /* Used by views/media-manager.js */?>
		<script type="text/html" id="tmpl-brightcove-media">
			<div class="brightcove media-frame-router"></div>
			<div class="brightcove-message message hidden"></div>
			<div class="brightcove media-frame-content"></div>
			<div class="brightcove media-frame-menu hidden"></div>
			<div class="brightcove media-frame-details"></div>
			<div class="brightcove media-frame-toolbar"></div>
			<div class="brightcove media-frame-uploader"></div>
			<div class="brightcove-uploader"></div>
		</script>

		<?php /* Used by views/playlist-edit.js */?>
		<script type="text/html" id="tmpl-brightcove-playlist-edit-video-in-playlist">
			<li class="attachment brightcove">
				<div class="js--select-attachment type- subtype- ">
					<div class="thumbnail">
						<# if ( data.images && data.images.thumbnail && data.images.thumbnail.src ) { #>
							<img src="{{data.images.thumbnail.src}}" width="162" height="94">
						<# } else { #>
							<img src="<?php echo BRIGHTCOVE_URL . 'images/admin/video-processing-large.png'; ?>" width="162" height="94">
						<# } #>
						<div class="duration">
							<span>{{data.duration}}</span>
						</div>
					</div>
					<div class="bc-info">
						<span class="bc-name">{{data.name}}</span>
						<span class="bc-updated">{{data.updated_at_readable}}</span>
							<span class="row-actions">
								<span class="video-move-up"><a href="#">&uarr; <?php esc_html_e( 'Move up', 'brightcove' )?></a></span> |
								<span class="video-move-down"><a href="#">&darr; <?php esc_html_e( 'Move Down', 'brightcove' )?></a></span> |
								<span class="trash"><a href="#"><?php esc_html_e( 'Remove', 'brightcove' )?></a></span>
							</span>
					</div>
				</div>
			</li>
		</script>

		<?php /* Used by views/playlist-edit.js */?>
		<script type="text/html" id="tmpl-brightcove-playlist-edit-video-in-library">
		<li class="attachment brightcove">
			<div class=" js--select-attachment type- subtype- ">
				<div class="thumbnail">
					<# if ( data.images && data.images.thumbnail && data.images.thumbnail.src ) { #>
						<img src="{{data.images.thumbnail.src}}" width="162" height="94">
					<# } else { #>
						<img src="<?php echo BRIGHTCOVE_URL . 'images/admin/video-processing-large.png'; ?>" width="162" height="94">
					<# } #>
					<div class="duration">
						<span>{{data.duration}}</span>
					</div>
				</div>
				<div class="bc-info">
					<span class="bc-name">{{data.name}}</span>
					<span class="bc-updated">{{data.updated_at_readable}}</span>
					<span class="row-actions"><span class="add-to-playlist"><a href="#" class="button action"><?php esc_html_e( 'Add to playlist', 'brightcove' )?></a></span></span>
				</div>
			</div>
		</li>
		</script>

		<?php /* Used by views/playlist-edit.js */?>
		<script type="text/html" id="tmpl-brightcove-playlist-edit">
			<div class="settings">
				<label class="playlist-name">
					<span class="name"><?php esc_html_e( 'Playlist Name', 'brightcove' )?></span>
					<input type="text" class="brightcove-name" value="{{data.name}}" />
					<a href="#" class="button button-primary button-large media-button brightcove back"><?php esc_html_e( 'Back', 'brightcove' ); ?></a>
					<span class="spinner is-active"></span>
				</label>
			</div>

			<div class="playlist-videos-list">
				<h2>Playlist videos</h2>
				<ul class="existing-videos"></ul>
			</div>

			<div class="playlist-add-videos-list">
				<h2>Video search</h2>
				<ul class="library-videos"></ul>
			</div>
		</script>

		<?php /* Used by views/video-edit.js */?>
		<script type="text/html" id="tmpl-brightcove-video-edit">
			<div class="settings">
				<label class="setting video-name">
					<span class="name"><?php esc_html_e( 'Name', 'brightcove' )?></span>
					<input type="text" class="brightcove-name" value="{{data.name}}" />
				</label>
				<label class="setting short-description">
					<span class="name"><?php esc_html_e( 'Description', 'brightcove' )?></span>
					<textarea class="brightcove-description">{{data.description}}</textarea>
				</label>
				<label class="setting long-description">
					<span class="name"><?php esc_html_e( 'Long Description', 'brightcove' )?></span>
					<textarea class="brightcove-long-description">{{data.long_description}}</textarea>

				</label>
				<label class="setting tags">
					<span class="name"><?php esc_html_e( 'Tags', 'brightcove' )?></span>
					<input type="text" class="brightcove-tags" value="{{data.tags}}" />
				</label>
				<label class="setting width">
					<span class="name"><?php esc_html_e( 'Display Width', 'brightcove' )?></span>
					<input type="text" class="brightcove-width" value="{{data.width}}" />
				</label>
				<label class="setting height">
					<span class="name"><?php esc_html_e( 'Display Height', 'brightcove' )?></span>
					<input type="text" class="brightcove-height" value="{{data.height}}" />
				</label>
			</div>
			<div class="brightcove brightcove-buttons">
				<span class="delete-action">
					<a href="#" class="brightcove delete"><?php esc_html_e( 'Delete', 'brightcove' ); ?></a>
				</span>

				<span class="more-actions">
					<span class="spinner hidden"></span>
					<a href="#" class="button button-secondary button-large media-button brightcove back"><?php esc_html_e( 'Back', 'brightcove' ); ?></a>
					<a href="#" class="button button-primary button-large media-button brightcove save-sync"><?php esc_html_e( 'Save and Sync Changes', 'brightcove' ); ?></a>
				</span>
			</div>
		</script>

		<?php /* Used by views/video-preview.js */?>
		<script type="text/html" id="tmpl-brightcove-video-preview">
			<!-- Start of Brightcove Player -->
			 <iframe src="//players.brightcove.net/{{data.account_id}}/default_default/index.html?videoId={{data.id}}" allowfullscreen webkitallowfullscreen mozallowfullscreen></iframe>
			<!-- End of Brightcove Player -->
		</script>

		<?php /* Used by views/modal.js */?>
		<script type="text/html" id="tmpl-brightcove-media-modal">
            <div class="media-modal wp-core-ui">
              <a class="media-modal-close brightcove" href="#"><span class="media-modal-icon"><span class="screen-reader-text">Close media panel</span></span></a>
              <div class="media-modal-content">
                <div class="media-frame mode-select wp-core-ui" id="__wp-uploader-id-0">
                  <div class="media-frame-title">
                    <h1>
	                    <img class="bc-page-icon" src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/admin/menu-icon.svg' ); ?>"> <?php esc_html_e( 'Brightcove', 'brightcove' ); ?>
                    </h1>
                  </div>
                  <div class="media-frame-router">
                    <div class="media-router">
                      <a href="#" class="brightcove upload media-menu-item"><?php esc_html_e( 'Upload Files', 'brightcove' )?></a><a href="#" class="brightcove videos media-menu-item active"><?php esc_html_e( 'Videos', 'brightcove' )?></a><a href="#" class="brightcove playlists media-menu-item"><?php esc_html_e( 'Playlists', 'brightcove' )?></a>
                    </div>
                  </div>
                  <div class="media-frame-content">
                  </div>
                  <div class="media-frame-toolbar">
                    <div class="media-toolbar">
                      <div class="media-toolbar-secondary">
                        <div class="media-selection empty">
                          <div class="selection-info">
                            <span class="count">0 selected</span> <a class="edit-selection" href="#"><?php esc_html_e( 'Edit Selection', 'brightcove' ); ?></a> <a class="clear-selection" href="#"><?php esc_html_e( 'Clear', 'brightcove' ); ?></a>
                          </div>
                          <div class="selection-view">
                            <ul tabindex="-1" class="attachments" id="__attachments-view-71"></ul>
                          </div>
                        </div>
                      </div>
                      <div class="media-toolbar-primary search-form">
                        <a href="#" class="button media-button button-primary button-large media-button-insert brightcove" disabled="disabled"><?php esc_html_e( 'Insert Into Post', 'brightcove' ); ?></a>
                      </div>
                    </div>
                  </div>
                  <div class="media-frame-uploader">
                    <div class="uploader-window">
                      <div class="uploader-window-content">
                        <h3>
	                        <?php esc_html_e( 'Drop files to upload', 'brightcove' ); ?>
                        </h3>
                      </div>
                    </div>
                  </div>
                  <div id="html5_19jecn1m2eteuqiud64df15595_container" class="moxie-shim moxie-shim-html5" style="position: absolute; top: 0px; left: 0px; width: 0px; height: 0px; overflow: hidden; z-index: 0;">
                    <input id="html5_19jecn1m2eteuqiud64df15595" type="file" style="font-size: 999px; opacity: 0; position: absolute; top: 0px; left: 0px; width: 100%; height: 100%;" multiple accept="">
                  </div>
                </div>
              </div>
            </div>
            <div class="media-modal-backdrop"></div>
		</script>

		<?php /* Used by views/media-manager.js */?>
		<script type="text/html" id="tmpl-brightcove-uploader-container">
			<div class="brightcove-uploader media-frame mode-grid"></div>
		</script>

		<?php /* Used by views/upload-video-manager.js */?>
		<script type="text/html" id="tmpl-brightcove-uploader-queued-files">
			<div class="brightcove-upload-queued-files">
				<div class="pending-uploads">
					<table class="wp-list-table widefat">
						<thead>
							<tr>
							<th><?php esc_html_e( 'File Name', 'brightcove' )?></th>
							<th><?php esc_html_e( 'Size', 'brightcove' )?></th>
							<th><?php esc_html_e( 'Source', 'brightcove' )?></th>
							<th><?php esc_html_e( 'Progress', 'brightcove' )?></th>
							</tr>
						</thead>
						<tbody class="brightcove-pending-uploads">
						</tbody>
					</table>
					<br>
					<button class="brightcove-start-upload button action"><?php esc_html_e( 'Start Upload', 'brightcove' ); ?></button>
				</div>
			</div>
			<div class="brightcove-messages"></div>
		</script>

		<?php /* Used by views/upload.js */?>
		<script type="text/html" id="tmpl-brightcove-pending-upload">
			<td>{{data.fileName}}</td>
			<td>{{data.size}}</td>
			<td>{{data.accountName}}</td>
			<td>
				<# if (data.percent > 0 || data.activeUpload) #>
					<progress value="{{data.percent}}" max="100">{{data.percent}} %</progress>
			</td>
		</script>

		<?php /* Used by views/upload-details.js */?>
		<script type="text/html" id="tmpl-brightcove-pending-upload-details">
			<# if (data.uploaded) {
				var disabled = ' disabled';
				var readOnly = ' readonly';
				} #>
			<div class="settings">
			<label class="file-name setting">
				<span class="name"><?php esc_html_e( 'File Name', 'brightcove' )?></span>
				<span>{{data.fileName}}</span>
			</label>
			<label class="video-name setting">
				<span class="name"><?php esc_html_e( 'Name', 'brightcove' )?></span>
				<input type="text" class="brightcove-name" value="{{data.fileName}}"{{readOnly}} />
			</label>
			<label class="tags setting">
				<span class="name"><?php esc_html_e( 'Tags', 'brightcove' )?></span>
				<input type="text" class="brightcove-tags" value="{{data.tags}}"{{readOnly}} />
			</label>
			<label class="account setting">
				<span class="name"><?php esc_html_e( 'Source', 'brightcove' )?></span>
				<select id="brightcove-media-source" class="brightcove-media-source"{{disabled}}>
					<# _.each(data.accounts, function (account, hash) {
						if (account.client_id === data.accounts[data.account].client_id) {
							var selected = ' selected';
						} #>

						<option value="{{ hash }}"{{selected}}>{{ account.account_name }}</option>
					<# }); #>
				</select>
			</label>
			</div>
		</script>

		<?php /* Used by views/upload-window.js */?>
		<script type="text/html" id="tmpl-brightcove-uploader-window">
			<div id="drop-target" class="uploader-window-content">
				<h3><?php esc_html_e( 'Drop files to upload', 'brightcove' ); ?></h3>
			</div>
		</script>


		<?php /* Used by views/upload-video-manager.js */?>
		<script type="text/html" id="tmpl-brightcove-uploader-inline">
			<div class="uploader-inline-content">
				<?php if ( is_multisite() && ! is_upload_space_available() ) : ?>
					<h3 class="upload-instructions"><?php esc_html_e( 'Upload Limit Exceeded', 'brightcove' ); ?></h3>
				<?php else : ?>
					<div class="upload-ui">
						<h3 class="upload-instructions drop-instructions"><?php esc_html_e( 'Drop files anywhere to upload', 'brightcove' ); ?></h3>
						<p class="upload-instructions drop-instructions"><?php _ex( 'or', 'Uploader: Drop files here - or - Select Files' ); ?></p>
						<a href="#" id="brightcove-select-files-button" class="browser button button-hero"><?php esc_html_e( 'Select Files', 'brightcove' ); ?></a>
					</div>

					<div class="upload-inline-status"></div>

					<div class="post-upload-ui">
						<?php
						$max_upload_size = wp_max_upload_size();
						if ( ! $max_upload_size ) {
							$max_upload_size = 0;
						}
						?>

						<p class="max-upload-size">
						<?php
						printf( esc_html__( 'Maximum upload file size: ', 'brightcove' ) . esc_html( size_format( $max_upload_size ) ) . '.' ); ?><br>
						<?php
						_e( 'Please reference the readme.txt file of this plugin for further information on upload file size limits.', 'brightcove' );
						?>
						</p>

					</div>
				<?php endif; ?>
			</div>
		</script>

		<?php /* Used by views/media.js */?>
		<script type="text/html" id="tmpl-brightcove-media-item-grid">
			<div class="attachment-preview js--select-attachment type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }}">
				<div class="thumbnail">
					<# if ( data.images && data.images.thumbnail && data.images.thumbnail.src ) { #>
						<img src="{{ data.images.thumbnail.src }}" class="icon" draggable="false" width="162" height="94" />
					<# } else { #>
						<# if ( data.video_ids ) { #>
							<img src="<?php echo BRIGHTCOVE_URL . 'images/admin/video-playlist-large.png'; ?>" class="icon" draggable="false" width="162" height="94"  />
						<# } else { #>
							<img src="<?php echo BRIGHTCOVE_URL . 'images/admin/video-processing-large.png'; ?>" class="icon" draggable="false" width="162" height="94"  />
						<# } #>
					<# } #>
					<# if ( data.duration ) { #>
						<div class="duration">
							<span>{{ data.duration }}</span>
						</div>
					<# } #>
					<# if ( data.video_ids ) { #>
						<div class="video-count">
							<# if ( 'EXPLICIT' === data.type && data.video_ids ) { #>
								<# if ( 1 === data.video_ids.length ) { #>
									<span>1 <?php esc_html_e( 'Video', 'brightcove' ); ?></span>
								<# } else { #>
									<span>{{ data.video_ids.length }} <?php esc_html_e( 'Videos', 'brightcove' ); ?></span>
								<# } #>
							<# } else { #>
								<span class="brightcove-smart-playlist"><?php esc_html_e( 'Smart', 'brightcove' ); ?></span>
							<# } #>
						</div>
					<# } #>
				</div>
				<div class="bc-info">
					<span class="bc-name">{{ data.name }}</span>
					<# if ( data.updated_at_readable ) { #>
						<span class="bc-updated">{{ data.updated_at_readable }}</span>
					<# } #>
				</div>
				<div class="media-actions">
					<a href="#" class="button media-button brightcove edit"><?php esc_html_e( 'Edit', 'brightcove' ); ?></a>
					<a href="#" class="button media-button brightcove preview"><?php esc_html_e( 'Preview', 'brightcove' ); ?></a>
				</div>
			</div>
		</script>

		<?php /* Used by views/media.js */?>
		<script type="text/html" id="tmpl-brightcove-media-item-list">
			<li class="attachment-preview js--select-attachment type-list subtype-{{ data.subtype }} {{ data.orientation }}">
					{{ data.name }}
					<# if ( data.duration ) { #>
						{{ data.duration }}
					<# } #>
					<# if ( data.updated_at ) { #>
							<em>{{ data.updated_at }}</em>
					<# } #>
			</li>
		</script>


		<?php /* Used by views/media-details.js */?>
		<script type="text/html" id="tmpl-brightcove-media-item-details-videos">
			<div class="attachment-detail js--select-attachment type-list subtype-{{ data.subtype }} {{ data.orientation }}">
				<div class="thumbnail">
					<# if ( data.images && data.images.poster && data.images.poster.src ) { #>
						<img src="{{ data.images.poster.src }}" class="detail-icon" draggable="false" width="300" height="172"  />
					<# } else { #>
						<img src="<?php echo BRIGHTCOVE_URL . 'images/admin/video-processing-large.png'; ?>" class="detail-icon" draggable="false" width="300" height="172"  />
					<# } #>
					<# if ( data.duration ) { #>
						<div class="detail-duration">
							<span>{{ data.duration }}</span>
						</div>
					<# } #>
				</div>
				<div class="video-info">
					<span class="video-name">{{ data.name }}</span>
					<div class="video-source"><span class="title"><?php esc_html_e( 'Source: ', 'brightcove' ); ?></span><span class="data">{{ data.account_name }}</span></div>
					<div class="video-id"><span class="title"><?php esc_html_e( 'Video ID: ', 'brightcove' ); ?></span><span class="data">{{ data.id }}</span></div>
				</div>
				<div class="media-actions">
					<# if ('preview' === data.detailsMode) { #>
						<a href="#" class="button media-button brightcove back"><?php esc_html_e( 'Back', 'brightcove' ); ?></a>
					<# } else { #>
						<a href="#" class="button media-button brightcove edit"><?php esc_html_e( 'Edit', 'brightcove' ); ?></a>
						<a href="#" class="button media-button brightcove preview"><?php esc_html_e( 'Preview', 'brightcove' ); ?></a>
					<# } #>
				</div>
			</div>
		</script>

		<?php /* Used by views/media-details.js */?>
		<script type="text/html" id="tmpl-brightcove-media-item-details-playlists">
			<div class="attachment-detail js--select-attachment type-list subtype-{{ data.subtype }} {{ data.orientation }}">
				<div class="thumbnail">
					<# if ( data.images && data.images.poster && data.images.poster.src ) { #>
						<img src="{{ data.images.poster.src }}" class="detail-icon" draggable="false" width="300" height="172" />
                    <# } else { #>
                        <img src="<?php echo BRIGHTCOVE_URL . 'images/admin/video-playlist-large.png'; ?>" class="detail-icon" draggable="false" width="300" height="172"  />
                    <# } #>
                    <# if ( data.video_ids ) { #>
                        <div class="detail-video-count">
							<# if ( 'EXPLICIT' === data.type && data.video_ids ) { #>
								<# if ( 1 === data.video_ids.length ) { #>
									<span>1 <?php esc_html_e( 'Video', 'brightcove' ); ?></span>
								<# } else { #>
									<span>{{ data.video_ids.length }} <?php esc_html_e( 'Videos', 'brightcove' ); ?></span>
								<# } #>
							<# } else { #>
								<span class="brightcove-smart-playlist"><?php esc_html_e( 'Smart', 'brightcove' ); ?></span>
							<# } #>
                        </div>
                    <# } #>
				</div>
				<div class="playlist-info">
					<span class="playlist-name">{{ data.name }}</span>
					<div class="playlist-id"><span class="title"><?php esc_html_e( 'Playlist ID: ', 'brightcove' ); ?></span><span class="data">{{ data.id }}</span></div>
					<div class="account-name"><span class="title"><?php esc_html_e( 'Account Name: ', 'brightcove' ); ?></span><span class="data">{{ data.account_name }}</span></div>
					<div class="created-date"><span class="title"><?php esc_html_e( 'Created At: ', 'brightcove' ); ?></span><span class="data">{{ data.created_at_readable }}</span></div>
					<div class="updated-date"><span class="title"><?php esc_html_e( 'Updated At: ', 'brightcove' ); ?></span><span class="data">{{ data.updated_at_readable }}</span></div>
					<div class="playlist-type"><span class="title"><?php esc_html_e( 'Playlist Type: ', 'brightcove' ); ?></span><span class="data">{{ data.type }}</span></div>
				</div>
				<# if ('EXPLICIT' === data.type) { #>
					<div class="media-actions">
						<a href="#" class="button media-button brightcove edit"><?php esc_html_e( 'Edit', 'brightcove' ); ?></a>
					</div>
				<# } #>
			</div>
		</script>

		<?php /* Used by views/toolbar.js */?>
		<script type="text/html" id="tmpl-brightcove-media-toolbar">
				<div class="media-toolbar-secondary">
					<label for="brightcove-media-source" class="screen-reader-text">Filter by source</label>
					<select id="brightcove-media-source" class="brightcove-media-source attachment-filters">
						<# var allValue = 1 === _.size(data.accounts) ? data.accounts[_.keys(data.accounts)[0]]['account_id'] : 'all'; #>
						<option value="{{ allValue }}">All sources</option>
						<# _.each(data.accounts, function (account) { #>
							<option value="{{ account.account_id }}">{{ account.account_name }}</option>
						<# }); #>
					</select>

					<# if (data.mediaType === 'videos') { #>
						<label for="media-attachment-date-filters" class="screen-reader-text">Filter by date</label>
						<select id="brightcove-media-dates" class="brightcove-media-dates attachment-filters">
							<option value="all">All dates</option>
							<# _.each(data.dates, function (date) { #>
								<option value="{{ date.code }}">{{ date.value }}</option>
								<# }); #>
						</select>

						<label for="media-attachment-tags-filters" class="screen-reader-text">Filter by tag</label>
						<select id="media-attachment-tags-filters" class="brightcove-media-tags attachment-filters">
							<option value="all">All tags</option>
							<# _.each(data.tags, function (tagName, tagId) { #>
								<option value="{{ tagId }}">{{ tagName }}</option>
							<# }); #>
						</select>
					<# }#>

					<# if( data.mediaType === 'playlists' ) { #>
						<input type="checkbox" name="brightcove-empty-playlists" id="brightcove-empty-playlists" class="brightcove-empty-playlists attachment-filters">
						<label for="brightcove-empty-playlists">Hide Empty Playlists</label>
					<# } #>

					<a href="#" class="button media-button button-primary button-large  delete-selected-button hidden" disabled="disabled">Delete Selected</a>
				</div>
				<# if (data.mediaType === 'videos') { #>
					<div class="media-toolbar-primary search-form">
						<span class="spinner hidden"></span>
						<label for="media-search-input" class="screen-reader-text">Search Media</label>
						<input type="search" placeholder="Search" id="media-search-input" class="search">
					</div>
				<# }#>
		</script>

	<?php
	}
}
