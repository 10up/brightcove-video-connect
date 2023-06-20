<?php
/**
 * BC_Setup class file.
 *
 * @package Brightcove_Video_Connect
 */

/**
 * BC_Setup class
 */
class BC_Setup {

	/**
	 * Generic bootstrap function that is hooked into the default `init` method
	 */
	public static function action_init() {
		global $bc_accounts;

		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( 'BC_Setup', 'frontend_enqueue_scripts' ) );
		}

		require_once BRIGHTCOVE_PATH . 'includes/class-bc-errors.php';
		require_once BRIGHTCOVE_PATH . 'includes/class-bc-logging.php';
		require_once BRIGHTCOVE_PATH . 'includes/class-bc-playlist-shortcode.php';
		require_once BRIGHTCOVE_PATH . 'includes/class-bc-video-shortcode.php';
		require_once BRIGHTCOVE_PATH . 'includes/class-bc-experiences-shortcode.php';
		require_once BRIGHTCOVE_PATH . 'includes/class-bc-in-page-experience-shortcode.php';
		require_once BRIGHTCOVE_PATH . 'includes/class-bc-video-upload.php';
		require_once BRIGHTCOVE_PATH . 'includes/sync/class-bc-playlists.php';
		require_once BRIGHTCOVE_PATH . 'includes/sync/class-bc-videos.php';
		require_once BRIGHTCOVE_PATH . 'includes/class-bc-accounts.php';
		require_once BRIGHTCOVE_PATH . 'includes/api/class-bc-text-track.php';
		require_once BRIGHTCOVE_PATH . 'includes/api/class-bc-api.php';
		require_once BRIGHTCOVE_PATH . 'includes/api/class-bc-cms-api.php';
		require_once BRIGHTCOVE_PATH . 'includes/api/class-bc-oauth.php';
		require_once BRIGHTCOVE_PATH . 'includes/api/class-bc-player-management-api.php';
		require_once BRIGHTCOVE_PATH . 'includes/api/class-bc-player-management-api2.php';
		require_once BRIGHTCOVE_PATH . 'includes/api/class-bc-experiences-api.php';
		require_once BRIGHTCOVE_PATH . 'includes/class-bc-tags.php';
		require_once BRIGHTCOVE_PATH . 'includes/class-bc-labels.php';

		$locale = apply_filters( 'plugin_locale', get_locale(), 'brightcove' );

		load_textdomain( 'brightcove', WP_LANG_DIR . '/brightcove/brightcove-' . $locale . '.mo' );
		load_plugin_textdomain( 'brightcove', false, 'languages' );

		// Preload Errors Class First.
		new BC_Errors();

		$bc_accounts = new BC_Accounts();

		// Load Administrative Resources.
		if ( BC_Utility::current_user_can_brightcove() ) {
			require_once BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-settings-page.php';
            require_once BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-sources.php';
			new BC_Admin_Settings_Page();
            new BC_Admin_Sources();

			if ( get_option( '_brightcove_accounts' ) ) {
				require_once BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-labels-page.php';
				require_once BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-playlists-page.php';
				require_once BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-videos-page.php';

				require_once BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-user-profile.php';
				require_once BRIGHTCOVE_PATH . 'includes/admin/class-bc-templates.php';
				require_once BRIGHTCOVE_PATH . 'includes/admin/api/class-bc-admin-media-api.php';
				add_action( 'admin_enqueue_scripts', array( 'BC_Setup', 'brightcove_enqueue_assets' ) );
				// Load Brightcove API resources.
				new BC_Admin_Media_API();
				new BC_Admin_Labels_Page();
				new BC_Admin_Playlists_Page();
				new BC_Admin_Videos_Page();
				new BC_Admin_Templates();
				new BC_Admin_User_Profile();
			}
		}

		new BC_Playlists();
		new BC_Videos();

		add_action( 'admin_enqueue_scripts', array( 'BC_Setup', 'admin_enqueue_scripts' ) );
		add_filter( 'upload_mimes', array( 'BC_Setup', 'mime_types' ) );
		add_action( 'media_buttons', array( 'BC_Setup', 'add_brightcove_media_button' ) );
		add_action( 'admin_footer', array( 'BC_Setup', 'add_brightcove_media_modal_container' ) );

		// Show admin notice only if there are not sources.
		add_action( 'admin_notices', array( 'BC_Setup', 'bc_admin_notices' ) );
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

		require_once BRIGHTCOVE_PATH . 'includes/class-bc-utility.php';
		require_once BRIGHTCOVE_PATH . 'includes/class-bc-permissions.php';

		// Load WordPress resources.
		new BC_Permissions();

		if ( BC_Utility::current_user_can_brightcove() ) {

			require_once BRIGHTCOVE_PATH . 'includes/admin/class-bc-admin-menu.php';

			new BC_Admin_Menu();

		}

		// Set up rewrites for the Brightcove callback endpoint
		add_rewrite_tag( '%bc-api%', '([^&]+)' );
		add_rewrite_rule( 'bc-api$', 'index.php?bc-api=1', 'top' );

		add_action( 'pre_get_posts', array( 'BC_Setup', 'redirect' ), 1 );
		add_action( 'init', array( 'BC_Setup', 'register_post_types' ) );

		if ( function_exists( 'register_block_type' ) ) {
			wp_register_script(
				'brightcove-block',
				BRIGHTCOVE_URL . 'assets/js/src/block.js',
				array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-i18n' ),
				filemtime( BRIGHTCOVE_PATH . 'assets/js/src/block.js' ),
				true
			);

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'brightcove-block', 'brightcove', plugin_basename( BRIGHTCOVE_PATH ) . '/languages/' );
			}

			wp_localize_script( 'brightcove-block', 'bcBlock', array( 'userPermission' => BC_Utility::current_user_can_brightcove() ) );

			register_block_type(
				'bc/brightcove',
				array(
					'editor_script'   => 'brightcove-block',
					'render_callback' => array( 'BC_Setup', 'render_shortcode' ),
					'attributes'      => array(
						'account_id'            => array(
							'type' => 'string',
						),
						'player_id'             => array(
							'type' => 'string',
						),
						'video_id'              => array(
							'type' => 'string',
						),
						'playlist_id'           => array(
							'type' => 'string',
						),
						'experience_id'         => array(
							'type' => 'string',
						),
						'video_ids'             => array(
							'type' => 'string',
						),
						'embed'                 => array(
							'type' => 'string',
						),
						'autoplay'              => array(
							'type' => 'string',
						),
						'mute'                  => array(
							'type' => 'string',
						),
						'playsinline'           => array(
							'type' => 'string',
						),
						'picture_in_picture'    => array(
							'type' => 'string',
						),
						'language_detection'    => array(
							'type' => 'string',
						),
						'application_id'        => array(
							'type' => 'string',
						),
						'height'                => array(
							'type' => 'string',
						),
						'width'                 => array(
							'type' => 'string',
						),
						'min_width'             => array(
							'type' => 'string',
						),
						'max_width'             => array(
							'type' => 'string',
						),
						'padding_top'           => array(
							'type' => 'string',
						),
						'sizing'                => array(
							'type' => 'string',
						),
						'aspect_ratio'          => array(
							'type' => 'string',
						),
						'max_height'            => array(
							'type' => 'string',
						),
						'in_page_experience_id' => array(
							'type' => 'string',
						),
					),
				)
			);
		}
	}

	/**
	 * Render our shortcodes for our custom block.
	 *
	 * Determine if this is an experience video, a
	 * normal video or playlist shortcode and use
	 * the proper rendering method for each.
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$output = '';

		if ( ! empty( $atts['experience_id'] ) ) {
			$output = call_user_func( array( 'BC_Experiences_Shortcode', 'bc_experience' ), $atts );
		} elseif ( ! empty( $atts['video_id'] ) ) {
			$output = call_user_func( array( 'BC_Video_Shortcode', 'bc_video' ), $atts );
		} elseif ( ! empty( $atts['playlist_id'] ) ) {
			$output = call_user_func( array( 'BC_Playlist_Shortcode', 'bc_playlist' ), $atts );
		} elseif ( ! empty( $atts['in_page_experience_id'] ) ) {
			$output = call_user_func( array( 'BC_In_Page_Experience_Shortcode', 'bc_in_page_experience' ), $atts );
		}

		return $output;
	}

	/**
	 * Add Brightcove media button to editor
	 *
	 * @param int $editor_id The ID of the editor.
	 */
	public static function add_brightcove_media_button( $editor_id ) {

		if ( BC_Utility::current_user_can_brightcove() && 'content' === $editor_id ) {
			echo '<a href="#" data-target="content" id="brightcove-add-media" class="button brightcove-add-media"><img class="bc-button-icon" src="' . esc_url( BRIGHTCOVE_URL . 'images/menu-icon.svg' ) . '"> ' . esc_html__( 'Brightcove Media', 'brightcove' ) . '</a>';
		}
	}

	/**
	 * Display brightcove media modal container html
	 */
	public static function add_brightcove_media_modal_container() {

		global $pagenow;

		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			echo '<div tabindex="0" class="brightcove-modal supports-drag-drop"></div>';
		}
	}

	/**
	 * Preload params to add to JS variable
	 *
	 * @return array|false
	 */
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

		$params['dates']   = array( $type => BC_Utility::get_video_playlist_dates_for_display( $type ) );
		$params['nonce']   = wp_create_nonce( '_bc_ajax_search_nonce' );
		$params['tags']    = $tags->get_tags();
		$params['folders'] = array();
		if ( BC_Utility::current_user_can_brightcove() ) {
			$params['folders'] = $cms_api->fetch_folders();
			$params['labels']  = $cms_api->get_account_labels();
		}

		$params['plupload'] = array(
			'browse_button'    => 'brightcove-select-files-button',
			'container'        => 'drop-target',
			'drop_element'     => 'drop-target',
			'multiple_queues'  => true,
			'max_file_size'    => wp_max_upload_size() . 'b',
			'url'              => admin_url( 'admin-ajax.php?action=bc_media_upload' ),
			'filters'          => array(
				array(
					'title'      => esc_html__( 'Allowed Files' ),
					'extensions' => '*',
				),
			),
			'multipart'        => true,
			'urlstream_upload' => true,
			'multi_selection'  => true,
			'multipart_params' => array(
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

		$default_account            = $bc_accounts->get_account_details_for_user();
		$params['defaultAccount']   = ! empty( $default_account['hash'] ) ? $default_account['hash'] : '';
		$params['defaultAccountId'] = ! empty( $default_account['account_id'] ) ? $default_account['account_id'] : '';

		return $params;

	}

    /**
     *
     *
     * @return void
     */
    public static function brightcove_enqueue_assets() {
        global $wp_version;

        $suffix = BC_Utility::get_suffix();

        $player_api = new BC_Player_Management_API2();
        $players    = $player_api->get_all_players();

        $experiences_api = new BC_Experiences_API();
        $experiences     = $experiences_api->get_experiences();

        $js_variable = array(
            'path'           => esc_url( BRIGHTCOVE_URL . 'assets/js/src/' ),
            'preload'        => self::preload_params(),
            'wp_version'     => $wp_version,
            'languages'      => BC_Utility::languages(),
            'players'        => $players,
            'experiences'    => $experiences,
            'str_badformat'  => esc_html__( 'This file is not the proper format. Please use .vtt files, for more information visit', 'brightcove' ),
            'badformat_link' => esc_url( 'https://support.brightcove.com/en/video-cloud/docs/adding-captions-videos#captionsfile' ),
            'str_addcaption' => esc_html__( 'Add Another Caption', 'brightcove' ),
            'str_addremote'  => esc_html__( 'Add another remote file', 'brightcove' ),
            'str_selectfile' => esc_html__( 'Select File', 'brightcove' ),
            'str_useremote'  => esc_html__( 'Use a remote file instead', 'brightcove' ),
            'str_apifailure' => esc_html__( "Sorry! We weren't able to reach the Brightcove API even after trying a few times. Please try refreshing the page.", 'brightcove' ),
            'posts_per_page' => absint( apply_filters( 'brightcove_posts_per_page', 100 ) ), // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
        );

        wp_register_script( 'brightcove', '//sadmin.brightcove.com/js/BrightcoveExperiences.js', array(), BRIGHTCOVE_VERSION, false );

        wp_enqueue_script( 'tinymce_preview', esc_url( BRIGHTCOVE_URL . 'assets/js/src/tinymce.js' ), array( 'mce-view' ), BRIGHTCOVE_VERSION, true );
        wp_localize_script(
            'tinymce_preview',
            'bctiny',
            array(
                'wp_version' => $wp_version,
            )
        );

        $dependencies = array(
            'jquery-ui-autocomplete',
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
            'jquery-ui-datepicker',
        );

        wp_register_script( 'brightcove-admin', esc_url( BRIGHTCOVE_URL . 'assets/js/brightcove-admin' . $suffix . '.js' ), $dependencies, BRIGHTCOVE_VERSION, true );
        wp_localize_script( 'brightcove-admin', 'wpbc', $js_variable );
        wp_enqueue_script( 'brightcove-admin' );

        if ( isset( $GLOBALS['post_ID'] ) ) {
            wp_enqueue_media( array( 'post' => $GLOBALS['post_ID'] ) );
        } else {
            wp_enqueue_media();
        }
    }

	/**
	 * Enqueue the admin scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		// Use minified libraries if SCRIPT_DEBUG is turned off.
		$suffix = BC_Utility::get_suffix();

		wp_register_style( 'brightcove-video-connect', esc_url( BRIGHTCOVE_URL . 'assets/css/brightcove_video_connect' . $suffix . '.css' ), array(), BRIGHTCOVE_VERSION );
		wp_enqueue_style( 'brightcove-video-connect' );
		wp_register_style( 'jquery-ui-datepicker-style', esc_url( BRIGHTCOVE_URL . 'assets/css/jquery-ui-datepicker' . $suffix . '.css' ), array(), BRIGHTCOVE_VERSION );
		wp_enqueue_style( 'jquery-ui-datepicker-style' );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public static function frontend_enqueue_scripts() {
		// Use minified libraries if SCRIPT_DEBUG is turned off.
		$suffix = BC_Utility::get_suffix();

		wp_enqueue_style( 'brightcove-pip-css', 'https://players.brightcove.net/videojs-pip/1/videojs-pip.css', [], BRIGHTCOVE_VERSION );
		wp_register_style( 'brightcove-playlist', BRIGHTCOVE_URL . 'assets/css/brightcove_playlist' . $suffix . '.css', array(), BRIGHTCOVE_VERSION );
		wp_enqueue_style( 'brightcove-playlist' );
	}

	/**
	 * Adds allowed mime types
	 *
	 * @param  array $mime_types Mime types.
	 * @return mixed
	 */
	public static function mime_types( $mime_types ) {

		$bc_mime_types = BC_Utility::get_all_brightcove_mimetypes();

		foreach ( $bc_mime_types as $ext => $mime_type ) {

			// If, for instance, video/mp4 pre-exists exists, we still need to check extensions as many mime types have multiple extensions.
			if ( in_array( $mime_type, $mime_types, true ) ) {

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

	/**
	 * Displays various notices while on plugin setup process.
	 */
	public static function bc_admin_notices() {

		global $bc_accounts;
		$player_api = new BC_Player_Management_API2();
		$players    = $player_api->get_all_players();

		if ( count( $bc_accounts->get_sanitized_all_accounts() ) > 0 && ! empty( $players ) && is_array( $players ) ) {

			if ( false !== get_option( '_brightcove_plugin_activated' ) ) {
				delete_option( '_brightcove_plugin_activated' );
			}

			return;

		}

		if ( count( $bc_accounts->get_sanitized_all_accounts() ) > 0 && empty( $players ) && is_array( $players ) ) {
			$notices[] = array(
				'message' => sprintf(
					'%s <a href="%s"><strong>%s</strong></a>',
					esc_html__( 'It looks like one or more of your accounts API authentication changed recently. Please update your settings ', 'brightcove' ),
					esc_url( admin_url( 'admin.php?page=brightcove-sources' ) ),
					esc_html__( 'here', 'brightcove' )
				),
				'type'    => 'error',
			);
		}

		if ( get_option( '_brightcove_plugin_activated' ) !== false
			&& current_user_can( 'manage_options' )
			&& get_current_screen()->base !== 'brightcove_page_brightcove-sources'
			&& get_current_screen()->base !== 'brightcove_page_brightcove-edit-source'
			&& get_current_screen()->base !== 'admin_page_page-brightcove-edit-source'
		) {

			$notices[] = array(
				'message'    => sprintf(
					'%s <a href="%s"><strong>%s</strong></a>',
					esc_html__( 'Please configure Brightcove settings from', 'brightcove' ),
					esc_url( admin_url( 'admin.php?page=brightcove-sources' ) ),
					esc_html__( 'here', 'brightcove' )
				),
				'type'       => 'updated',
				'identifier' => 'configure-brightcove',
			);

		}

		if ( ! empty( $notices ) ) {
			BC_Utility::admin_notice_messages( $notices );
		}
	}

	/**
	 * Register `in-process` hidden video post type.
	 */
	public static function register_post_types() {
		$labels = array(
			'name' => __( 'BC In Process Videos', 'brightcove' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
		);

		register_post_type( 'bc-in-process-video', $args );
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
