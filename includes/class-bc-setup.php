<?php

class BC_Setup {

	/**
	 * Generic bootstrap function that is hooked into the default `init` method
	 */
	public static function action_init() {
		global $bc_accounts;

		require_once( BRIGHTCOVE_PATH . 'includes/class-bc-errors.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/class-bc-logging.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/class-bc-playlist-shortcode.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/class-bc-video-shortcode.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/class-bc-video-upload.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/sync/class-bc-playlists.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/sync/class-bc-videos.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/class-bc-accounts.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/api/class-bc-text-track.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/api/class-bc-api.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/api/class-bc-cms-api.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/api/class-bc-oauth.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/api/class-bc-player-management-api.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/api/class-bc-player-management-api2.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/class-bc-tags.php' );

		$locale = apply_filters( 'plugin_locale', get_locale(), 'brightcove' );

		load_textdomain( 'brightcove', WP_LANG_DIR . '/brightcove/brightcove-' . $locale . '.mo' );
		load_plugin_textdomain( 'brightcove', false, 'languages' );

		// Preload Errors Class First.
		new BC_Errors();

		$bc_accounts = new BC_Accounts();
		$players     = get_option( '_bc_player_playlist_ids_' . $bc_accounts->get_account_id() );

		if ( false === $players || ! is_array( $players ) ) {
			define( 'BRIGHTCOVE_FORCE_SYNC', true );
		}

		// Load Administrative Resources.
		if ( BC_Utility::current_user_can_brightcove() ) {

			require_once( BRIGHTCOVE_PATH . 'includes/admin/api/class-bc-admin-media-api.php' );
			require_once( BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-settings-page.php' );
			require_once( BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-playlists-page.php' );
			require_once( BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-videos-page.php' );
			require_once( BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-sources.php' );
			require_once( BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-user-profile.php' );
			require_once( BRIGHTCOVE_PATH . 'includes/admin/class-bc-templates.php' );

			// Load Brightcove API resources.
			new BC_Admin_Media_API();
			new BC_Admin_Settings_Page();
			new BC_Admin_Playlists_Page();
			new BC_Admin_Videos_Page();
			new BC_Admin_Sources();
			new BC_Admin_Templates();
			new BC_Admin_User_Profile();

		}

		new BC_Playlists();
		new BC_Videos();

		add_action( 'admin_enqueue_scripts', array( 'BC_Setup', 'admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( 'BC_Setup', 'frontend_enqueue_scripts' ) );
		add_filter( 'upload_mimes', array( 'BC_Setup', 'mime_types' ) );
		add_action( 'media_buttons', array( 'BC_Setup', 'add_brightcove_media_button' ) );
		add_action( 'admin_footer', array( 'BC_Setup', 'add_brightcove_media_modal_container' ) );

		// Show admin notice only if there are not sources.
		add_action( 'admin_notices', array( 'BC_Setup', 'bc_activation_admin_notices' ) );
	}

	/**
	 * Load admin init actions for all pages
	 *
	 * Loads various admin init actions required for all admin pages such as the admin menu.
	 *
	 * @since 1.0.5
	 *
	 * @return void
	 */
	public static function action_init_all() {

		require_once( BRIGHTCOVE_PATH . 'includes/class-bc-utility.php' );
		require_once( BRIGHTCOVE_PATH . 'includes/class-bc-permissions.php' );

		// Load WordPress resources.
		new BC_Permissions();

		if ( BC_Utility::current_user_can_brightcove() ) {

			require_once( BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-menu.php' );

			new BC_Admin_Menu();

		}

		// Set up rewrites for the Brightcove callback endpoint
		add_rewrite_tag( '%bc-api%', '([^&]+)' );
		add_rewrite_rule( 'bc-api$', 'index.php?bc-api=1', 'top' );

		add_action( 'pre_get_posts', array( 'BC_Setup', 'redirect' ), 1 );
		add_action( 'init',  array( 'BC_Setup', 'register_post_types' ) );
	}

	public static function add_brightcove_media_button( $editor_id ) {

		if ( BC_Utility::current_user_can_brightcove() && 'content' === $editor_id ) {
			echo '<a href="#" data-target="content" id="brightcove-add-media" class="button brightcove-add-media"><img class="bc-button-icon" src="' . esc_url( BRIGHTCOVE_URL . 'images/menu-icon.svg' ) . '"> ' . esc_html__( 'Brightcove Media', 'brightcove' ) . '</a>';
		}
	}

	public static function add_brightcove_media_modal_container() {

		global $pagenow;

		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
			echo '<div tabindex="0" class="brightcove-modal supports-drag-drop"></div>';
		}
	}

	public static function preload_params() {

		global $bc_accounts;

		$tags = new BC_Tags();

		$params = array();

		// Fetch all preload vids/playlists as appropriate.
		$uri  = $_SERVER['REQUEST_URI'];
		$type = 'videos';

		if ( BC_Utility::current_user_can_brightcove() ) {

			$cms_api         = new BC_CMS_API();
			$admin_media_api = new BC_Admin_Media_API();

			if ( false !== strpos( $uri, BC_Admin_Menu::get_playlists_page_uri_component() ) ) {
				$type                = 'playlists';
				$params['playlists'] = $cms_api->playlist_list();
			}
		} else {
			return false;
		}

		$params['dates'] = array( $type => BC_Utility::get_video_playlist_dates_for_display( $type ) );
		$params['nonce'] = wp_create_nonce( '_bc_ajax_search_nonce' );
		$params['tags']  = $tags->get_tags();

		$params['plupload'] = array(
			'runtimes'            => 'html5,silverlight,flash,html4',
			'browse_button'       => 'brightcove-select-files-button',
			'container'           => 'drop-target',
			'drop_element'        => 'drop-target',
			'multiple_queues'     => true,
			'max_file_size'       => wp_max_upload_size() . 'b',
			'url'                 => admin_url( 'admin-ajax.php?action=bc_media_upload' ),
			'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
			'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
			'filters'             => array( array( 'title' => esc_html__( 'Allowed Files' ), 'extensions' => '*' ) ),
			'multipart'           => true,
			'urlstream_upload'    => true,
			'multi_selection'     => true,
			'multipart_params'    => array(
				'action' => 'bc_media_upload',
			),
		);

		$params['messages'] = array(
			'confirmDelete'  => esc_html__( 'Deleting this video will prevent it from showing in any existing posts. Are you sure you want to delete?', 'brightcove' ),
			'ongoingSync'    => esc_html__( 'We are currently performing a sync of your new Brightcove source, you may not see all videos and playlists until that is complete.', 'brightcove' ),
			'successUpload'  => esc_html__( 'Successfully uploaded file with name %%s%%.', 'brightcove' ),
			'unableToUpload' => esc_html__( 'We were unable to upload the file with name %%s%%. Please try reuploading it again.', 'brightcove' ),
		);

		// Fetch all account hash/name combos.
		$params['accounts'] = $bc_accounts->get_sanitized_all_accounts();

		// Fetch all supported mime types.
		$params['mimeTypes'] = BC_Utility::get_all_brightcove_mimetypes();

		$defaultAccount             = $bc_accounts->get_account_details_for_user();
		$params['defaultAccount']   = $defaultAccount['hash'];
		$params['defaultAccountId'] = $defaultAccount['account_id'];

		return $params;

	}

	public static function admin_enqueue_scripts() {

		global $wp_version;
		global $bc_accounts;

		// Use minified libraries if SCRIPT_DEBUG is turned off.
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		$player_api = new BC_Player_Management_API2();
		$players = $player_api->get_all_players();

		$js_variable = array(
			'path'           => esc_url( BRIGHTCOVE_URL . 'assets/js/src/' ),
			'preload'        => BC_Setup::preload_params(),
			'wp_version'     => $wp_version,
			'languages'  => BC_Utility::languages(),
			'players'    => $players,
			'str_badformat'  => esc_html__( 'This file is not the proper format. Please use .vtt files, for more information visit', 'brightcove' ),
			'badformat_link' => esc_url( 'https://support.brightcove.com/en/video-cloud/docs/adding-captions-videos#captionsfile' ),
			'str_addcaption' => esc_html__( 'Add Another Caption', 'brightcove' ),
			'str_addremote'  => esc_html__( 'Add another remote file', 'brightcove' ),
			'str_selectfile' => esc_html__( 'Select File', 'brightcove' ),
			'str_useremote'  => esc_html__( 'Use a remote file instead', 'brightcove' ),
			'str_apifailure'  => esc_html__( "Sorry! We weren't able to reach the Brightcove API even after trying a few times. Please try refreshing the page.", 'brightcove' ),
			'posts_per_page' => absint( apply_filters( 'brightcove_posts_per_page', 100 ) ),
		);

		wp_register_script( 'brightcove', '//sadmin.brightcove.com/js/BrightcoveExperiences.js' );

		$playlist_enabled_players_for_accounts = array();
		$accounts                              = $bc_accounts->get_sanitized_all_accounts();

		foreach ( $accounts as $account ) {
			$playlist_enabled_players_for_accounts[ $account['account_id'] ] = get_option( '_bc_player_playlist_ids_' . $account['account_id'] );
		}

		wp_enqueue_script( 'tinymce_preview', esc_url( BRIGHTCOVE_URL . 'assets/js/src/tinymce.js' ), array( 'mce-view' ) );
		wp_localize_script( 'tinymce_preview', 'bctiny', array( 'wp_version' => $wp_version, 'playlistEnabledPlayers' => $playlist_enabled_players_for_accounts ) );

		$dependencies = array(
			'jquery',
			'backbone',
			'wp-backbone',
			'media',
			'media-editor',
			'media-grid',
			'media-models',
			'media-upload',
			'media-views',
			'plupload-all',
			'brightcove',
			'wp-mediaelement',
			'tinymce_preview',
		);

		wp_register_script( 'brightcove-admin', esc_url( BRIGHTCOVE_URL . 'assets/js/brightcove-admin' . $suffix . '.js' ), $dependencies );
		wp_localize_script( 'brightcove-admin', 'wpbc', $js_variable );
		wp_enqueue_script( 'brightcove-admin' );

		if ( isset( $GLOBALS['post_ID'] ) ) {
			wp_enqueue_media( array( 'post' => $GLOBALS['post_ID'] ) );
		} else {
			wp_enqueue_media();
		}

		wp_register_style( 'brightcove-video-connect', esc_url( BRIGHTCOVE_URL . 'assets/css/brightcove_video_connect' . $suffix . '.css' ), array() );
		wp_enqueue_style( 'brightcove-video-connect' );

	}

	public static function frontend_enqueue_scripts() {

		// Use minified libraries if SCRIPT_DEBUG is turned off.
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style( 'brightcove-playlist', BRIGHTCOVE_URL . 'assets/css/brightcove_playlist' . $suffix . '.css', array() );
		wp_enqueue_style( 'brightcove-playlist' );

	}

	public static function mime_types( $mime_types ) {

		$bc_mime_types = BC_Utility::get_all_brightcove_mimetypes();

		foreach ( $bc_mime_types as $ext => $mime_type ) {

			// If, for instance, video/mp4 pre-exists exists, we still need to check extensions as many mime types have multiple extensions.
			if ( in_array( $mime_type, $mime_types ) ) {

				// The mime type does exist, but does it exist with the given extension? If not, add it to the list.
				if ( ! array_key_exists( $ext, $mime_types ) ) {
					$mime_types[ $ext ] = $mime_type;
				}
			} else {

				// The mime type does not exist, so we can safely add it to the list.
				$mime_types[ $ext ] = $mime_type;

			}
		}

		return $mime_types;

	}

	public static function bc_activation_admin_notices() {

		global $bc_accounts;

		if ( count( $bc_accounts->get_sanitized_all_accounts() ) > 0 ) {

			delete_option( '_brightcove_plugin_activated' );

			return;

		}

		if ( get_option( '_brightcove_plugin_activated' ) !== false
		     && current_user_can( 'manage_options' )
		     && get_current_screen()->base !== 'brightcove_page_brightcove-sources'
		     && get_current_screen()->base !== 'brightcove_page_brightcove-edit-source'
			 && get_current_screen()->base !== 'admin_page_page-brightcove-edit-source'
		) {

			$notices[] = array(
				'message' => sprintf(
					'%s <a href="%s"><strong>%s</strong></a>',
					esc_html__( 'Please configure Brightcove settings from', 'brightcove' ),
					esc_url( admin_url( 'admin.php?page=brightcove-sources' ) ),
					esc_html__( 'here', 'brightcove' )
				),
				'type'    => 'updated',
			);

			BC_Utility::admin_notice_messages( $notices );

		}
	}

	/**
	 * Register `in-process` hidden video post type.
	 */
	public static function register_post_types() {
		$labels = array(
			'name' => __( 'BC In Process Videos', 'brightcove' )
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
		);

		register_post_type( 'bc-in-process-video', $args );
	}

	public static function bc_check_minimum_wp_version() {

		if ( version_compare( get_bloginfo( 'version' ), '4.2', '<' ) ) {

			if ( current_user_can( 'manage_options' ) ) {

				add_action( 'admin_init', 'bc_plugin_deactivate' );
				add_action( 'admin_notices', 'bc_plugin_incompatible_admin_notice' );

				function bc_plugin_deactivate() {

					deactivate_plugins( BRIGHTCOVE_BASENAME );

				}

				function bc_plugin_incompatible_admin_notice() {

					echo wp_kses_post( sprintf( __( '<div class="error"><p><strong>Brightcove Video Cloud Enhanced</strong> has been <strong>deactivated</strong> because it\'s incompatibale with WordPress version %s! The minimum compatible WordPress version is <strong>4.2</strong></p></div>', 'brightcove' ), esc_html( get_bloginfo( 'version' ) ) ) );

					if ( isset( $_GET['activate'] ) ) {
						unset( $_GET['activate'] );
					}
				}
			}
		}
	}

	/**
	 * Hijack requests for potential callback processing.
	 *
	 * @param \WP_Query $query Main query instance.
	 */
	public static function redirect( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		$type = get_query_var( 'bc-api' );
		if ( empty( $type ) ) {
			return;
		}

		/**
		 * Fire an action when a request comes in to the /bc-api endpoint.
		 */
		do_action( 'brightcove_api_request' );

		// Kill the response immediately
		die;
	}
}
