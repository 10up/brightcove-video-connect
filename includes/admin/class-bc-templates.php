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
			<div class="brightcove media-frame-content">
				<span id="js-media-loading" class="spinner"></span>
			</div>
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
							<img src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/video-processing-large.png' ); ?>" width="162" height="94">
							<div class="processing"><span><?php esc_html_e( 'Processing...', 'brightcove' ); ?></span></div>
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
						<img src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/video-processing-large.png' ); ?>" width="162" height="94">
						<span><?php esc_html_e( 'Processing...', 'brightcove' ); ?></span>
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
					<a href="#" class="button button-primary button-large media-button brightcove playlist-back"><?php esc_html_e( 'Back', 'brightcove' ); ?></a>
					<span class="spinner is-active"></span>
				</label>
			</div>

			<div class="playlist-videos-list">
				<h2><?php esc_html_e( 'Playlist videos', 'brightcove' );?></h2>
				<ul class="existing-videos"></ul>
			</div>

			<div class="playlist-add-videos-list">
				<h2><?php esc_html_e( 'Video search', 'brightcove' ); ?></h2>
				<ul class="library-videos"></ul>
			</div>
		</script>

		<?php /* Used by views/video-edit.js */?>
		<script type="text/html" id="tmpl-brightcove-video-edit">
			<div class="settings">
				<label class="setting video-name">
					<span class="name"><?php esc_html_e( 'Name', 'brightcove' )?></span>
					<input type="text" class="brightcove-name" maxlength="255" value="{{data.name}}" />
					<p class="description"><?php esc_html_e( 'The name is limited to 255 characters.', 'brightcove' )?></p>
				</label>
				<label class="setting short-description">
					<span class="name"><?php esc_html_e( 'Description', 'brightcove' )?></span>
					<textarea class="brightcove-description" maxlength="250">{{data.description}}</textarea>
					<p class="description"><?php esc_html_e( 'The description is limited to 250 characters.', 'brightcove' )?></p>
				</label>
				<label class="setting long-description">
					<span class="name"><?php esc_html_e( 'Long Description', 'brightcove' )?></span>
					<textarea class="brightcove-long-description" maxlength="5000">{{data.long_description}}</textarea>
					<p class="description"><?php esc_html_e( 'The long description is limited to 5,000 characters.', 'brightcove' )?></p>
				</label>
				<label class="setting tags">
					<span class="name"><?php esc_html_e( 'Tags', 'brightcove' )?></span>
					<input type="text" class="brightcove-tags" value="{{data.tags}}" />
				</label>
				<div class="setting poster">
					<span class="name"><?php esc_html_e( 'Poster (Sugg. 480x360px)', 'brightcove' )?></span>
					<div class="setting-content">
						<div class="attachment <# if ( data.images.poster.src ) { #>active<# } #>">
							<div class="-image">
								<# if ( data.images.poster.src ) { #>
									<img src="{{data.images.poster.src}}" class="thumbnail">
								<# } #>
							</div>

							<button type="button" class="button-link check" tabindex="-1">
								<span class="media-modal-icon"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Remove', 'brightcove' ); ?></span>
							</button>

							<input type="hidden" class="brightcove-poster" value="{{data.poster}}">

							<button class="button button-secondary -poster">
								<?php esc_html_e( 'Select File', 'brightcove' ); ?>
							</button>
						</div>
					</div>
				</div>
				<div class="setting thumbnail">
					<span class="name"><?php esc_html_e( 'Thumbnail (Sugg. 120x90px)', 'brightcove' )?></span>
					<div class="setting-content">
						<div class="attachment <# if ( data.images.thumbnail.src ) { #>active<# } #>">
							<div class="-image">
								<# if ( data.images.thumbnail.src ) { #>
									<img src="{{data.images.thumbnail.src}}" class="thumbnail">
								<# } #>
							</div>

							<button type="button" class="button-link check" tabindex="-1">
								<span class="media-modal-icon"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Remove', 'brightcove' ); ?></span>
							</button>

							<input type="hidden" class="brightcove-thumbnail" value="{{data.thumbnail}}">

							<button class="button button-secondary -thumbnail">
								<?php esc_html_e( 'Select File', 'brightcove' ); ?>
							</button>
						</div>
					</div>
				</div>

				<div id="brightcove-custom-fields"></div>

				<div class="setting captions">
					<span class="name"><?php esc_html_e( 'Closed Captions', 'brightcove' )?></span>
					<div class="setting-content">
						<button class="button button-secondary -captions">
							<# if ( 0 !== data.text_tracks.length ) { #>
								<?php esc_html_e( 'Add Another Caption', 'brightcove' ); ?>
							<# } else { #>
								<?php esc_html_e( 'Select File', 'brightcove' ); ?>
							<# } #>
						</button>
						<a href="#" class="add-remote-caption">
							<# if ( 0 !== data.text_tracks.length ) { #>
								<?php esc_html_e( 'Add another remote caption file', 'brightcove' ); ?>
							<# } else { #>
								<?php esc_html_e( 'Use a remote caption file instead', 'brightcove' ); ?>
							<# } #>
						</a>

						<div id="js-captions">
							<# _.each( data.text_tracks, function( caption ) { #>
								<div id="js-caption-fields" class="caption-repeater repeater-row">
									<input class="brightcove-captions" value="{{caption.src}}">

									<div class="caption-secondary-fields">
										<label class="-language">
											<?php esc_html_e( 'Language', 'brightcove' ); ?>
											<select class="brightcove-captions-language">
												<# _.each( wpbc.languages, function( language, key ) { #>
													<option value="{{language}}" <# if ( language === caption.srclang ) { #>selected<# } #>>
														{{key}}
													</option>
												<# }); #>
											</select>
										</label>

										<label class="-label">
											<?php esc_html_e( 'Label', 'brightcove' )?>
											<input type="text" class="brightcove-captions-label" value="{{caption.label}}">
										</label>

										<div class="action-row">
											<a href="#" class="delete"><?php esc_html_e( 'Remove Caption', 'brightcove' ); ?></a>
										</div>
									</div>
								</div>
							<# }); #>
							<div id="js-caption-empty-row" class="caption-repeater repeater-row empty-row">
								<label class="-src">
									<?php esc_html_e( 'File Source', 'brightcove' ); ?>
									<input class="brightcove-captions" type="text">
								</label>

								<div class="caption-secondary-fields">
									<label class="-language">
										<?php esc_html_e( 'Language', 'brightcove' )?>
										<select class="brightcove-captions-language">
											<# _.each( wpbc.languages, function( language, key) { #>
												<option value="{{language}}">{{key}}</option>
											<# }); #>
										</select>
									</label>

									<label class="-label">
										<?php esc_html_e( 'Label', 'brightcove' )?>
										<input class="brightcove-captions-label" type="text">
									</label>

									<div class="action-row">
										<a href="#" class="delete"><?php esc_html_e( 'Delete Caption', 'brightcove' ); ?></a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div id="brightcove-change-history">
					<label class="setting history">
						<span class="name"><?php esc_html_e( 'Change History', 'brightcove' ); ?></span>
						<textarea class="brightcove-change-history" data-id="history" disabled="disabled"><?php esc_html_e( 'Nothing yet ...', 'brightcove' ); ?></textarea>
					</label>
				</div>
			</div>
			<div class="brightcove brightcove-buttons">
				<span class="delete-action">
					<a href="#" class="brightcove delete"><?php esc_html_e( 'Delete', 'brightcove' ); ?></a>
				</span>

				<span class="more-actions">
					<span class="spinner hidden"></span>
					<?php
					$screen      = get_current_screen();
					$parent_base = $screen->parent_base;

					if ( 'brightcove' === $parent_base ) { ?>
						<a href="#" class="button button-secondary button-large media-button brightcove back"><?php esc_html_e( 'Back', 'brightcove' ); ?></a>
						<a href="#" class="button button-primary button-large media-button brightcove save-sync"><?php esc_html_e( 'Save and Sync Changes', 'brightcove' ); ?></a>
					<?php } ?>
				</span>
			</div>
		</script>
		<script type="text/html" id="tmpl-brightcove-video-edit-custom-string">
			<label class="setting custom">
				<span class="name">{{data.display_name}}</span>
				<input type="text" class="brightcove-custom-string" data-id="{{data.id}}" value="{{data.value}}" />
			</label>
		</script>
		<script type="text/html" id="tmpl-brightcove-video-edit-custom-enum">
			<label class="setting custom">
				<span class="name">{{data.display_name}}</span>
				<select class="brightcove-custom-enum" data-id="{{data.id}}">
					<# _.each(data.enum_values, function (value, index) {
						if (value === data.value) {
						var selected = ' selected';
						} #>

						<option value="{{value}}"{{selected}}>{{value}}</option>
						<# }); #>
				</select>
			</label>
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
	            <button type="button" class="button-link media-modal-close"><span class="brightcove media-modal-icon"><span class="screen-reader-text"><?php esc_html_e( 'Close media panel', 'brightcove' ); ?></span></span></button>
              <div class="media-modal-content">
                <div class="media-frame mode-select wp-core-ui" id="__wp-uploader-id-0">
                  <div class="media-frame-title">
                    <h1>
	                    <img class="bc-page-icon" src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/menu-icon.svg' ); ?>"> <?php esc_html_e( 'Brightcove', 'brightcove' ); ?>
                    </h1>
                  </div>
                  <div class="media-frame-router">
                    <div class="media-router">
	                    <a href="#" class="brightcove upload media-menu-item"><?php esc_html_e( 'Upload Files', 'brightcove' )?></a>
	                    <a href="#" class="brightcove videos media-menu-item active"><?php esc_html_e( 'Videos', 'brightcove' )?></a>
	                    <a href="#" class="brightcove playlists media-menu-item"><?php esc_html_e( 'Playlists', 'brightcove' )?></a>
                    </div>
                  </div>
                  <div class="media-frame-content">
                  </div>
                  <div class="media-frame-toolbar">
                    <div class="media-toolbar">
                      <div class="media-toolbar-secondary">
	                      <a href="#" class="button button-secondary button-large media-button brightcove back" style="display:none;"><?php esc_html_e( 'Back', 'brightcove' ); ?></a>
                        <div class="media-selection empty">
                          <div class="selection-info">
                            <span class="count">0 <?php esc_html_e( 'selected', 'brightcove' ); ?></span> <a class="edit-selection" href="#"><?php esc_html_e( 'Edit Selection', 'brightcove' ); ?></a> <a class="clear-selection" href="#"><?php esc_html_e( 'Clear', 'brightcove' ); ?></a>
                          </div>
                          <div class="selection-view">
                            <ul tabindex="-1" class="attachments" id="__attachments-view-71"></ul>
                          </div>
                        </div>
                      </div>
                      <div class="media-toolbar-primary search-form">
	                      <a href="#" class="button button-primary button-large media-button brightcove save-sync" style="display:none;"><?php esc_html_e( 'Save and Sync Changes', 'brightcove' ); ?></a>
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
			<div class="brightcove-messages"></div>
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
					<button class="brightcove-start-upload button action button-primary button-large"><?php esc_html_e( 'Start Upload', 'brightcove' ); ?></button>
				</div>
			</div>
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
						<p class="upload-instructions drop-instructions"><?php _ex( 'or', 'Uploader: Drop files here - or - Select Files', 'brightcove' ); ?></p>
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
						esc_html_e( 'Please reference the readme.txt file of this plugin for further information on upload file size limits.', 'brightcove' );
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
							<img src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/video-playlist-large.png' ); ?>" class="icon" draggable="false" width="162" height="94"  />
						<# } else { #>
							<img src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/video-processing-large.png' ); ?>" class="icon" draggable="false" width="162" height="94"  />
							<div class="processing"><span></span></div>
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
						<img src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/video-processing-large.png' ); ?>" class="detail-icon" draggable="false" width="300" height="172"  />
						<div class="processing"><span><?php esc_html_e( 'Processing...', 'brightcove' ); ?></span></div>
						<# } #>
					<# if ( data.duration ) { #>
						<div class="detail-duration">
							<span>{{ data.duration }}</span>
						</div>
					<# } #>
				</div>

				<div class="media-actions">
					<# if ('preview' === data.detailsMode) { #>
					<a href="#" class="button media-button brightcove back"><?php esc_html_e( 'Back', 'brightcove' ); ?></a>
					<# } else { #>
					<# if ( data.images && data.images.thumbnail && data.images.thumbnail.src ) { #>
					<a href="#" class="button media-button brightcove edit"><?php esc_html_e( 'Edit', 'brightcove' ); ?></a>
					<a href="#" class="button media-button brightcove preview"><?php esc_html_e( 'Preview', 'brightcove' ); ?></a>
					<# } else { #>
					<a href="#" class="button media-button brightcove edit" disabled><?php esc_html_e( 'Edit', 'brightcove' ); ?></a>
					<a href="#" class="button media-button brightcove preview" disabled><?php esc_html_e( 'Preview', 'brightcove' ); ?></a>
					<# } #>
					<# } #>
				</div>

				<div class="video-info">
					<span class="video-name">{{ data.name }}</span>
                </div>

                <div class="video-details">
                    <span class="left-col">
                        <?php esc_html_e( 'Source: ', 'brightcove' ); ?>
                    </span>
                    <span class="right-col">{{ data.account_name }}</span>

                    <span class="left-col">
                        <?php esc_html_e( 'Video ID: ', 'brightcove' ); ?>
                    </span>
                    <span class="right-col">{{ data.id }}</span>

                    <?php
                    $screen      = get_current_screen();
                    $parent_base = $screen->parent_base;

                    if ( 'edit' === $parent_base ) : ?>

                        <label for="video-player">
                            <?php esc_html_e( 'Video Player: ', 'brightcove' ); ?>
                        </label>
                        <select name="video-player" id="video-player" class="right-col">
                            <# _.each( wpbc.players[data.account_id], function ( player ) { #>
	                            <# if ( ! player.is_playlist ) { #>
									<option value="{{ player.id }}">{{ player.name }}</option>
								<# } #>
                            <# }); #>
                        </select>

                        <label for="autoplay">
                            <?php esc_html_e( 'Autoplay: ', 'brightcove' ); ?>
                        </label>
                        <div class="right-col">
                            <input type="checkbox" id="autoplay" name="autoplay">
                        </div>

                        <label for="embed-style-in-page">
                            <?php esc_html_e( 'Embed Style: ', 'brightcove' ); ?>
                        </label>
                        <div class="right-col">
                            <input type="radio" value="in-page" id="embed-style-in-page" checked name="embed-style"><?php esc_html_e( 'JavaScript', 'brightcove' ); ?>
                            <input type="radio" value="iframe" id="embed-style-iframe" name="embed-style"><?php esc_html_e( 'iFrame', 'brightcove' ); ?>
                        </div>

                        <label for="sizing-responsive">
                            <?php esc_html_e( 'Sizing: ', 'brightcove' ); ?>
                        </label>
                        <div class="right-col">
                            <input type="radio" value="responsive" id="sizing-responsive" checked name="sizing"><?php esc_html_e( 'Responsive', 'brightcove' ); ?>
                            <input type="radio" value="fixed" id="sizing-fixed" name="sizing"><?php esc_html_e( 'Fixed', 'brightcove' ); ?>
                        </div>

                        <label for="aspect-ratio">
                            <?php esc_html_e( 'Aspect Ratio: ', 'brightcove' ); ?>
                        </label>
                        <select class="right-col" name="aspect-ratio" id="aspect-ratio">
                            <option value="16:9">16:9</option>
                            <option value="4:3">4:3</option>
                            <option value="custom"><?php esc_html_e( 'Custom', 'brightcove' ); ?></option>
                        </select>

						<label for="width">
							<?php esc_html_e( 'Width: ', 'brightcove' ); ?>
						</label>
						<input type="number" name="width" id="width" size="5" value="640" class="right-col">

						<label for="height">
							<?php esc_html_e( 'Height: ', 'brightcove' ); ?>
						</label>
						<input type="number" name="height" id="height" class="right-col" value="360" size="5" readonly>

                        <label for="generate-shortcode">
                            <?php esc_html_e( 'Shortcode', 'brightcove' ); ?>
                        </label>
                        <select name="generate-shortcode" id="generate-shortcode" class="right-col">
                            <option value="autogenerate"><?php esc_html_e( 'Auto generate', 'brightcove' ); ?></option>
                            <option value="manual"><?php esc_html_e( 'Manual', 'brightcove' ); ?></option>
                        </select>

                        <textarea class="clear" name="shortcode" id="shortcode" cols="40" rows="8" readonly></textarea>

                    <?php endif; ?>
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
                        <img src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/video-playlist-large.png' ); ?>" class="detail-icon" draggable="false" width="300" height="172"  />
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

				<# if ('EXPLICIT' === data.type) { #>
				<div class="media-actions clear">
					<a href="#" class="button media-button brightcove edit"><?php esc_html_e( 'Edit', 'brightcove' ); ?></a>
				</div>
				<# } #>

				<div class="playlist-info">
					<span class="playlist-name">{{ data.name }}</span>
				</div>

				<div class="playlist-details">
					<span class="left-col">
						<?php esc_html_e( 'Playlist ID: ', 'brightcove' ); ?>
					</span>
					<span class="data">{{ data.id }}</span>

					<span class="left-col"><?php esc_html_e( 'Account Name: ', 'brightcove' ); ?></span><span class="right-col">{{ data.account_name }}</span>
					<span class="left-col"><?php esc_html_e( 'Created At: ', 'brightcove' ); ?></span><span class="right-col">{{ data.created_at_readable }}</span>
					<span class="left-col"><?php esc_html_e( 'Updated At: ', 'brightcove' ); ?></span><span class="right-col">{{ data.updated_at_readable }}</span>
					<span class="left-col"><?php esc_html_e( 'Playlist Type: ', 'brightcove' ); ?></span><span class="right-col">{{ data.type }}</span>

					<?php
					$screen      = get_current_screen();
					$parent_base = $screen->parent_base;

					if ( 'edit' === $parent_base ) : ?>

						<label for="video-player">
							<?php esc_html_e( 'Video Player: ', 'brightcove' ); ?>
						</label>
						<select name="video-player" id="video-player" class="right-col">
							<# _.each( wpbc.players[data.account_id], function ( player ) { #>
								<# if ( player.is_playlist ) { #>
									<option value="{{ player.id }}">{{ player.name }}</option>
								<# } #>
							<# }); #>
						</select>

						<label for="autoplay">
							<?php esc_html_e( 'Autoplay: ', 'brightcove' ); ?>
						</label>
						<div class="right-col">
							<input type="checkbox" id="autoplay" name="autoplay">
						</div>

						<label>
							<?php esc_html_e( 'Embed Style: ', 'brightcove' ); ?>
						</label>
						<div class="right-col">
							<input type="radio" value="iframe" name="embed-style"><?php esc_html_e( 'iFrame', 'brightcove' ); ?>
						</div>

						<label>
							&nbsp;
						</label>
						<div class="right-col">
							<input type="radio" value="in-page-horizontal" name="embed-style"><?php esc_html_e( 'JavaScript Horizontal', 'brightcove' ); ?>
						</div>

						<label>
							&nbsp;
						</label>
						<div class="right-col">
							<input type="radio" value="in-page-vertical" checked name="embed-style"><?php esc_html_e( 'JavaScript Vertical', 'brightcove' ); ?>
						</div>

						<label for="sizing-responsive">
							<?php esc_html_e( 'Sizing: ', 'brightcove' ); ?>
						</label>
						<div class="right-col">
							<input type="radio" value="responsive" id="sizing-responsive" disabled="true" name="sizing"><?php esc_html_e( 'Responsive', 'brightcove' ); ?>
							<input type="radio" value="fixed" id="sizing-fixed" checked disabled="true" name="sizing"><?php esc_html_e( 'Fixed', 'brightcove' ); ?>
						</div>

						<label for="aspect-ratio">
							<?php esc_html_e( 'Aspect Ratio: ', 'brightcove' ); ?>
						</label>
						<select class="right-col" name="aspect-ratio" id="aspect-ratio">
							<option value="16:9">16:9</option>
							<option value="4:3">4:3</option>
							<option value="custom"><?php esc_html_e( 'Custom', 'brightcove' ); ?></option>
						</select>

						<label for="width">
							<?php esc_html_e( 'Width: ', 'brightcove' ); ?>
						</label>
						<input type="number" name="width" id="width" size="5" value="640" class="right-col">

						<label for="height">
							<?php esc_html_e( 'Height: ', 'brightcove' ); ?>
						</label>
						<input type="number" name="height" id="height" class="right-col" value="360" size="5" readonly>

						<label for="generate-shortcode">
							<?php esc_html_e( 'Shortcode', 'brightcove' ); ?>
						</label>
						<select name="generate-shortcode" id="generate-shortcode" class="right-col">
							<option value="autogenerate"><?php esc_html_e( 'Auto generate', 'brightcove' ); ?></option>
							<option value="manual"><?php esc_html_e( 'Manual', 'brightcove' ); ?></option>
						</select>

						<textarea class="clear" name="shortcode" id="shortcode" cols="40" rows="8" readonly></textarea>

					<?php endif; ?>
				</div>
			</div>
		</script>

		<?php /* Used by views/toolbar.js */?>
		<script type="text/html" id="tmpl-brightcove-media-toolbar">
				<div class="media-toolbar-secondary">
					<label for="brightcove-media-source" class="screen-reader-text"><?php esc_html_e( 'Filter by source', 'brightcove' );?></label>
					<select id="brightcove-media-source" class="brightcove-media-source attachment-filters">
						<# _.each(data.accounts, function (account) { #>
							<option value="{{ account.account_id }}"<# if ( data.account === account.account_id ) { #> selected="selected"<# } #>>{{ account.account_name }}</option>
						<# }); #>
					</select>

					<# if (data.mediaType === 'videos') { #>
						<!-- <label for="media-attachment-date-filters" class="screen-reader-text">Filter by date</label>
						<select id="brightcove-media-dates" class="brightcove-media-dates attachment-filters">
							<option value="all">All dates</option>
							<# _.each(data.dates, function (date) { #>
								<option value="{{ date.code }}">{{ date.value }}</option>
								<# }); #>
						</select> -->

						<label for="media-attachment-tags-filters" class="screen-reader-text"><?php esc_html_e( 'Filter by tag', 'brightcove' );?></label>
						<select id="media-attachment-tags-filters" class="brightcove-media-tags attachment-filters">
							<option value="all"><?php esc_html_e( 'All tags', 'brightcove' ); ?></option>
							<# _.each(data.tags, function (tagName, tagId) { #>
								<option value="{{ tagId }}">{{ tagName }}</option>
							<# }); #>
						</select>
					<# }#>

					<# if( data.mediaType === 'playlists' ) { #>
						<div class="notice notice-warning">
							<p>
								<?php esc_html_e( 'Please note that you can create new playlists only from Brightcove.', 'brightcove' ); ?>
							</p>
						</div>
						<p>
							<input type="checkbox" name="brightcove-empty-playlists" id="brightcove-empty-playlists" class="brightcove-empty-playlists attachment-filters">
							<label for="brightcove-empty-playlists"><?php esc_html_e( 'Hide Empty Playlists', 'brightcove' ); ?></label>
						</p>
					<# } #>

					<a href="#" class="button media-button button-primary button-large  delete-selected-button hidden" disabled="disabled"><?php esc_html_e( 'Delete Selected', 'brightcove' ); ?></a>
				</div>
				<# if (data.mediaType === 'videos') { #>
					<div class="media-toolbar-primary search-form">
						<span class="spinner"></span>
						<label for="media-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Media', 'brightcove' ); ?></label>
						<input type="search" placeholder="<?php esc_attr_e( 'Search', 'brightcove' ); ?>" id="media-search-input" class="search">
						<button class="button-secondary" id="media-search"><?php esc_html_e( 'Search', 'brightcove' ); ?></button>
						<a class="brightcove-toolbar" href="#"><?php esc_html_e( 'help', 'brightcove' ); ?></a>
					</div>
				<# }#>
		</script>

		<?php /* Admin notice */ ?>
		<script type="text/html" id="tmpl-brightcove-badformat-notice">
			<div class="notice error badformat is-dismissible">
				<p>{{ wpbc.str_badformat }} <a href="{{ wpbc.badformat_link }}"><?php esc_html_e( 'the Brightcove Documentation page.', 'brightcove' ); ?></a></p>
				<button type="button" class="badformat notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'brightcove' ); ?></span></button>
			</div>
		</script>

		<?php /* Incorrect mediaType notice */ ?>
		<script type="text/html" id="tmpl-brightcove-mediatype-notice">
			<div id="js-mediatype-notice" class="notice error is-dismissible">
				<p><?php esc_html_e( 'This video was not able to be inserted into the page. Please try again later. This may be because the video is still processing. For more information, please visit ', 'brightcove' ); ?> <a href="http://status.brightcove.com/"><?php esc_html_e( 'the Brightcove Status page.', 'brightcove' ); ?></a></p>
				<button type="button" id="js-mediatype-dismiss" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'brightcove' ); ?></span></button>
			</div>
		</script>

		<?php /* ToolTip help on Search */ ?>
		<script type="text/html" id="tmpl-brightcove-tooltip-notice">
			<div id="js-tooltip-notice" class="notice notice-info is-dismissible">
				<p><?php esc_html_e( 'Search exact word or phrases by wrapping search in quotes.', 'brightcove' ); ?><br /><small><?php esc_html_e( 'Example:"My Favorite Video"', 'brightcove' ); ?></small></p>
				<button type="button" id="js-tooltip-dismiss" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'brightcove' ); ?></span></button>
			</div>
</script>
	<?php
	}
}
