<?php
/**
 * StudyChurch functions and definitions
 *
 * When using a child theme (see http://codex.wordpress.org/Theme_Development and
 * http://codex.wordpress.org/Child_Themes), you can override certain functions
 * (those wrapped in a function_exists() call) by defining them first in your child theme's
 * functions.php file. The child theme's functions.php file is included before the parent
 * theme's file, so the child theme functions would be used.
 *
 * @package StudyChurch
 * @since   0.1.0
 */

// Useful global constants
define( 'SC_VERSION', '0.2.0' );
define( 'BP_DEFAULT_COMPONENT', 'profile' );

StudyChurch_Theme::get_instance();

class StudyChurch_Theme {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the StudyChurch_Theme
	 *
	 * @return StudyChurch_Theme
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof StudyChurch_Theme ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		$this->add_includes();
		$this->sc_includes();
		$this->add_filters();
		$this->add_actions();
	}

	protected function add_includes() {

		/**
		 * Customizer additions.
		 */
		require get_template_directory() . '/inc/customizer.php';

		/**
		 * Include custom Foundation functionality
		 */
		require get_template_directory() . '/inc/classes.php';
	}

	protected function sc_includes() {
		if ( is_child_theme() ) {
			return;
		}

		require get_template_directory() . '/inc/sc-only/groups.php';
		require get_template_directory() . '/inc/sc-only/hooks.php';
	}

	/**
	 * Wire up filters
	 */
	protected function add_filters() {
		add_filter( 'wp_title', array( $this, 'wp_title_for_home' ) );
		add_filter( 'bp_get_nav_menu_items', array( $this, 'bp_nav_menu_items' ) );
		add_filter( 'bp_template_include', array( $this, 'bp_default_template' ) );

		add_filter( 'gform_userregistration_feed_settings_fields', [ $this, 'gform_registration_email_field' ] );
	}

	/**
	 * Custom page header for home page
	 *
	 * @param $title
	 *
	 * @return string
	 */
	public function wp_title_for_home( $title ) {
		if ( empty( $title ) && ( is_home() || is_front_page() ) ) {
			return get_bloginfo( 'name' ) . ' | ' . get_bloginfo( 'description' );
		}

		return $title;
	}

	/**
	 * Wire up actions
	 */
	protected function add_actions() {
		add_action( 'after_setup_theme', array( $this, 'setup' ) );
		add_action( 'widgets_init', array( $this, 'add_sidebars' ) );
		add_action( 'widgets_init', array( $this, 'unregister_widgets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'branding_styles' ) );
		add_action( 'template_redirect', array( $this, 'redirect_logged_in_user' ) );
		add_action( 'wp_footer', array( $this, 'modals' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'localize' ), 20 );
		add_action( 'sc_ajax_form_sc_group_create', array( $this, 'handle_group_create' ) );
		add_action( 'sc_ajax_form_sc_study_create', array( $this, 'handle_study_create' ) );
	}

	/**
	 * Theme setup
	 */
	public function setup() {
		add_editor_style();

		$this->add_image_sizes();

		$this->add_menus();

		/**
		 * Make theme available for translation
		 * Translations can be filed in the /languages/ directory
		 * If you're building a theme based on sc, use a find and replace
		 * to change 'sc' to the name of your theme in all the template files
		 */
		load_theme_textdomain( 'sc', get_template_directory() . '/languages' );

		/**
		 * Add default posts and comments RSS feed links to head
		 */
		add_theme_support( 'automatic-feed-links' );

		/**
		 * Enable support for Post Thumbnails on posts and pages
		 *
		 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
		 */
		add_theme_support( 'post-thumbnails' );

		add_theme_support( 'title-tag' );

		/**
		 * StudyChurch_Theme the WordPress core custom background feature.
		 */
		add_theme_support( 'custom-background', apply_filters( 'sc_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

	}

	/**
	 * Register theme sidebars
	 */
	public function add_sidebars() {

		$defaults = array(
			'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
			'after_widget'  => '</li>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		);

		$sidebars = array(
			array(
				'id'          => 'blog-sidebar',
				'name'        => 'Blog Sidebar',
				'description' => 'Blog sidebar display',
			),
			array(
				'id'          => 'landing-social',
				'name'        => 'Landing Page Social',
				'description' => 'Social widget for landing page',
			),
			array(
				'id'   => 'post-content-after',
				'name' => 'After Post Content',
			),
		);

		foreach ( $sidebars as $sidebar ) {
			register_sidebar( array_merge( $sidebar, $defaults ) );
		}

	}

	/**
	 * Unregister widgets
	 */
	public function unregister_widgets() {
	}

	/**
	 * Enqueue styles and scripts
	 */
	public function enqueue() {
		$this->enqueue_scripts();
		$this->enqueue_styles();
	}

	/**
	 * Enqueue Styles
	 */
	protected function enqueue_styles() {
		$postfix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( 'sc', get_template_directory_uri() . "/dist/main.css", array(), SC_VERSION );
//		wp_enqueue_style( 'open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:300italic,600italic,300,600' );
//		wp_enqueue_style( 'railway', 'https://fonts.googleapis.com/css?family=Raleway' );
	}

	/**
	 * Enqueue scripts
	 */
	protected function enqueue_scripts() {
		$postfix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';

		/**
		 * Libraries and performance scripts
		 */
		wp_enqueue_script( 'datepicker', get_template_directory_uri() . '/assets/js/lib/foundation-datepicker.min.js', array(), false, true );
		wp_enqueue_script( 'navigation', get_template_directory_uri() . '/assets/js/lib/navigation.js', array(), '20120206', true );
		wp_enqueue_script( 'skip-link-focus-fix', get_template_directory_uri() . '/assets/js/lib/skip-link-focus-fix.js', array(), '20130115', true );
		wp_enqueue_script( 'foundation', get_template_directory_uri() . '/assets/js/lib/foundation' . $postfix . '.js', array( 'jquery' ), '01', true );
		wp_enqueue_script( 'simplePageNav', get_template_directory_uri() . '/assets/js/lib/jquery.singlePageNav.min.js', array( 'jquery' ), '01', true );
		wp_enqueue_script( 'scrollReveal', get_template_directory_uri() . '/assets/js/lib/scrollReveal.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'scrolltofixed', get_template_directory_uri() . '/assets/js/lib/scrolltofixed.js', array( 'jquery' ) );

		wp_enqueue_style( 'froala-content', get_template_directory_uri() . '/assets/css/froala/froala_content.css' );
		wp_enqueue_style( 'froala-editor', get_template_directory_uri() . '/assets/css/froala/froala_editor.css' );
		wp_enqueue_style( 'froala-style', get_template_directory_uri() . '/assets/css/froala/froala_style.css' );

		wp_enqueue_script( 'froala-editor', get_template_directory_uri() . '/assets/js/lib/froala/froala_editor.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'froala-video', get_template_directory_uri() . '/assets/js/lib/froala/plugins/video.js', array( 'jquery' ) );
		wp_enqueue_script( 'froala-fullscreen', get_template_directory_uri() . '/assets/js/lib/froala/plugins/fullscreen.min.js', array(
			'jquery',
			'froala-editor'
		) );

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}

		if ( is_singular() && wp_attachment_is_image() ) {
			wp_enqueue_script( 'sc-keyboard-image-navigation', get_template_directory_uri() . '/js/keyboard-image-navigation.js', array( 'jquery' ), '20120202' );
		}

		wp_enqueue_script( 'sc', get_template_directory_uri() . "/assets/js/studychurch{$postfix}.js", array(
			'jquery',
			'foundation',
			'wp-util',
			'wp-backbone',
			'wp-api',
			'jquery-ui-sortable',
			'froala-editor',
			'datepicker',
			'scrollReveal',
			'scrolltofixed'
		), SC_VERSION, true );
	}

	/**
	 * Is this a development environment?
	 *
	 * @return bool
	 */
	public function is_dev() {
		return ( 'studychurch.dev' == $_SERVER['SERVER_NAME'] );
	}

	/**
	 * Add custom image sizes
	 */
	protected function add_image_sizes() {
		add_image_size( 'post-header', 1500, 500, true );
	}

	/**
	 * Register theme menues
	 */
	protected function add_menus() {
		register_nav_menus( array(
			'members' => 'Main Members Menu',
			'public'  => 'Main Public Menu',
			'footer'  => 'Main Footer Menu',
		) );
	}

	public function bp_nav_menu_items( $items ) {
		// Get the top-level menu parts (Friends, Groups, etc) and sort by their position property
		$top_level_menus = (array) buddypress()->bp_nav;
		usort( $top_level_menus, '_bp_nav_menu_sort' );

		// Iterate through the top-level menus
		foreach ( $top_level_menus as $nav ) {

			// Skip items marked as user-specific if you're not on your own profile
			if ( empty( $nav['show_for_displayed_user'] ) && ! bp_core_can_edit_settings() ) {
				continue;
			}

			if ( 'activity' == $nav['slug'] ) {
				continue;
			}

			// Get the correct menu link. See http://buddypress.trac.wordpress.org/ticket/4624
			$link = trailingslashit( bp_displayed_user_domain() . $nav['link'] );

			// Add this menu
			$menu         = new stdClass;
			$menu->class  = array( 'menu-parent' );
			$menu->css_id = $nav['css_id'];
			$menu->link   = $link;
			$menu->name   = $nav['name'];
			$menu->parent = 0;

			$menus[] = $menu;
		}

		return $menus;
	}

	public function bp_default_template( $template ) {
		if ( get_template_directory() . '/page.php' != $template ) {
			return $template;
		}

		if ( $new_temp = locate_template( 'templates/full-width.php' ) ) {
			$template = $new_temp;
		}

		return $template;
	}

	public function gform_registration_email_field( $fields ) {

		unset( $fields['user_settings']['fields'][5]['args']['input_types'] );
		$fields['user_settings']['fields'][5]['args']['callback'] = [
			gf_user_registration(),
			'is_applicable_field_for_field_select'
		];

		return $fields;
	}

	/**
	 * Redirect logged in users to their profile
	 */
	public function redirect_logged_in_user() {
		if ( ! is_front_page() ) {
			return;
		}

		if ( current_user_can( 'edit_pages' ) || ! is_user_logged_in() ) {
			return;
		}

		if ( ! apply_filters( 'sc_redirect_to_user_domain', true ) ) {
			return;
		}

		wp_safe_redirect( bp_loggedin_user_domain() );
		die();
	}

	public function branding_styles() {
		$primary_color = get_theme_mod( 'primary_color' );
		$link_color    = get_theme_mod( 'link_color' );
		$success_color = get_theme_mod( 'success_color' );
		$warning_color = get_theme_mod( 'warning_color' );
		$error_color   = get_theme_mod( 'error_color' );

		if ( ! $primary_color ) {
			return;
		}

		add_filter( 'body_class', function ( $classes ) {
			$classes[] = 'branded';

			return $classes;
		} );

		?>

		<style>
			body.branded .site-header,
			body.branded .contain-to-grid,
			body.branded .contain-to-grid .top-bar,
			body.branded .top-bar-section > ul > li:not(.has-form) > a:not(.button),
			body.branded .top-bar-section .dropdown li:not(.has-form):hover > a:not(.button),
			body.branded .bg-primary,
			body:not(.logged-in) .site-header .contain-to-grid,
			body:not(.logged-in) .site-header .contain-to-grid .top-bar,
			body:not(.logged-in) .site-header .top-bar-section li:not(.has-form) a:not(.button) {
				background: <?php echo $primary_color; ?>;
				color: white;
			}

			body.branded a,
			body.branded .side-nav li a:not(.button) {
				color: <?php echo $link_color; ?>
			}

			body.branded a:hover {
				text-decoration: underline;
			}

			body.branded button,
			body.branded .button {
				color: white;
				background-color: <?php echo $link_color; ?>;
			}

			body.branded #buddypress div.item-list-tabs#subnav {
				background-color: <?php echo $primary_color; ?>;
			}

			body.branded .site-footer {
				border-color: <?php echo $primary_color; ?>
			}

			<?php if ( $success_color ) : ?>
			/* Success Colors */
			body.branded .avatar-container.online:before {
				background-color: <?php echo $success_color; ?>;
			}

			body.branded button.success,
			body.branded .button.success {
				background-color: <?php echo $success_color; ?>;
			}

			#buddypress div#message.success p,
			#buddypress div#message.updated p {
				border-color: <?php echo $success_color; ?>;
			}

			<?php endif; ?>

			<?php if ( $warning_color ) : ?>
			/* Warning Colors */

			#buddypress div#message p,
			#buddypress #sitewide-notice p {
				border-color: <?php echo $warning_color; ?>;
			}

			body.branded .button.secondary {
				background-color: <?php echo $warning_color; ?>;
			}

			<?php endif; ?>

			<?php if ( $error_color ) : ?>
			/* Alert Colors */
			#buddypress div#message.error p {
				border-color: <?php echo $error_color; ?>;
			}

			body.branded .alert-box.alert {
				background-color: <?php echo $error_color; ?>;
				border-color: <?php echo $error_color; ?>;
			}

			body.branded .button.alert {
				background-color: <?php echo $error_color; ?>;
			}

			<?php endif; ?>

		</style>
		<?php
	}


	/**
	 * Handle study create modal form
	 */
	public function handle_study_create( $data ) {

		if ( empty( $data['security'] ) || ! wp_verify_nonce( $data['security'], 'study-create' ) ) {
			wp_send_json_error();
		}

		$study = apply_filters( 'sc_study_insert_args', array(
			'post_type'    => 'sc_study',
			'post_title'   => sanitize_text_field( $data['study-name'] ),
			'post_excerpt' => wp_filter_kses( $data['study-desc'] ),
			'post_status'  => 'private',
		) );

		$study_id = wp_insert_post( $study );

		if ( ! $study_id ) {
			wp_send_json_error();
		}

		wp_send_json_success( array(
			'message' => __( 'Success! Redirecting you to your new study.', 'sc' ),
			'url'     => sprintf( '/study-edit/?action=edit&study=%d', $study_id )
		) );

	}

	public function handle_group_create( $data ) {

		if ( empty( $data['security'] ) || ! wp_verify_nonce( $data['security'], 'group-create' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Something went wrong. Please refresh and try again.', 'sc' )
			) );
		}

		if ( empty( $data['group-name'] ) ) {
			wp_send_json_error( array(
				'message' => __( 'Please enter a group name', 'sc' )
			) );
		}

		$id = groups_create_group( array(
			'name'        => sanitize_text_field( $data['group-name'] ),
			'description' => esc_textarea( $data['group-desc'] ),
			'status'      => 'hidden',
		) );

		if ( ! $id ) {
			wp_send_json_error( array(
				'message' => __( 'Something went wrong. Please refresh and try again.', 'sc' )
			) );
		}

		if ( ! empty( $_POST['study-name'] ) ) {
			groups_add_groupmeta( $id, 'study_name', sanitize_text_field( $_POST['study-name'] ) );
		}

		$group = groups_get_group( array( 'group_id' => $id ) );

		$url = esc_url( trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug . '/' ) );

		wp_send_json_success( array(
			'message' => __( 'Success! Taking you to your new group.', 'sc' ),
			'url'     => $url
		) );

	}

	public function modals() {
		if ( ! bp_is_user_profile() ) {
			return;
		}

		if ( current_user_can( 'manage_groups' ) ) {
			get_template_part( 'partials/modal', 'group-create' );
		}

		if ( current_user_can( 'edit_posts' ) ) {
			get_template_part( 'partials/modal', 'study-create' );
		}

	}

	public function localize() {
		wp_localize_script( 'sc', 'scGroupCreateData', array(
			'security' => wp_create_nonce( 'group-create' ),
			'success'  => esc_html__( 'Success! Taking you to your group...', 'sc' ),
			'error'    => esc_html__( 'Something went wrong, please try again', 'sc' ),
		) );
	}

	/**
	 * ID for this theme. Used in translation functions.
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_id() {
		return 'studychurch';
	}

	/**
	 * Get the name for this theme
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_name() {
		return 'StudyChurch';
	}

	/**
	 * Alias for get_name
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_plugin_name() {
		return $this->get_name();
	}

	/**
	 * Get the version for this theme
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_version() {
		return SC_VERSION;
	}

	/**
	 * Get the url for this theme directory
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function theme_url() {
		return get_template_directory_uri();
	}

	public function plugin_url() {
		return $this->theme_url();
	}

	/**
	 * Get the API namespace to use
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_api_namespace() {
		return $this->get_id() . '/v1';
	}
}