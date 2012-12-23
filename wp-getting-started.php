<?php
/*
  Plugin Name: WP Getting Started
  Plugin URI: http://trenvo.com
  Description: Replace WordPress' Welcome Panel with a simple but effective walkthrough
  Version: 0.1
  Author: Mike Martel
  Author URI: http://trenvo.com
 */

// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * Version number
 *
 * @since 0.1
 */
define('WPGS_VERSION', '0.1');

/**
 * PATHs and URLs
 *
 * @since 0.1
 */
define('WPGS_DIR', plugin_dir_path(__FILE__));
define('WPGS_URL', plugin_dir_url(__FILE__));
define('WPGS_INC_URL', WPGS_URL . '_inc/');
define('WPGS_IMAGES_URL', WPGS_INC_URL . 'images/');

/**
 * Requires and includes
 */
require_once ( WPGS_DIR . 'lib/class.wp-help-pointers.php' );

if ( ! class_exists('WPGettingStarted') ) :

    class WPGettingStarted    {

        protected $complete = 0;
        protected $completed_all = false;
        protected $walkthrough = false;
        protected $progress = array();

        /**
         * Creates an instance of the WPGettingStarted class
         *
         * @return WPGettingStarted object
         * @since 0.1
         * @static
        */
        public static function &init() {
            static $instance = false;

            if (!$instance) {
                load_plugin_textdomain('wp-getting-started', false, WPGS_DIR . '/languages/');
                $instance = new WPGettingStarted;
            }

            return $instance;
        }

        /**
         * Constructor
         *
         * @since 0.1
         */
        public function __construct() {
            $this->set_current_state();

            $this->set_welcome_panel();

            if ( $this->walkthrough )
                $this->walkthrough_workflow();

            add_action ( 'admin_enqueue_scripts', array ( &$this, 'set_pointers' ) );
        }

            /**
             * PHP4
             *
             * @since 0.1
                 */
            public function wpgettingstarted() {
                $this->__construct();
            }

        /**
         * Makes sure all roads lead back to Rome
         *
         * @since 0.1
         */
        protected function walkthrough_workflow() {
            // Change redirect after save and activate in customizer
            add_action ('customize_controls_print_footer_scripts', array ( &$this, 'change_customizer_activate_redirect' ) );

            // Change redirect after close in customizer
            add_action('customize_controls_init', array ( &$this, 'change_customizer_close_redirect' ) );

            // Return to dashboard after post
            add_action ( 'submitpage_box', array ( &$this, 'add_wpgs_hidden_field' ) );
            add_action ( 'submitpost_box', array ( &$this, 'add_wpgs_hidden_field' ) );
            add_filter('redirect_post_location', array ( &$this, 'redirect_post_dashboard' ), 10, 2 );

            // Change redirect after close in Live Theme Preview (if active)
            add_action('wp_ltp_init', array ( &$this, 'change_ltp_close_redirect' ) );

            // Keep the flow if Live Theme Preview is activated
            add_filter ( 'wp_ltp_editurl', array ( &$this, 'add_wpgs_param' ) );
            add_filter ( 'wp_ltp_activateurl', array ( &$this, 'add_wpgs_param' ) );

            // But also ensure a nice flow without LTP...
            add_filter ( 'theme_action_links', array ( &$this, 'modify_theme_action_links' ), 10, 2 );
            add_filter ( 'clean_url', array ( &$this, 'modify_theme_action_urls' ) );
            add_action ( 'wp_redirect', array ( &$this, 'modify_switch_theme_redirect' ) );

        }

        /**
         * Makes Theme Customizer urls in themes.php carry over the wpgs param
         *
         * @param str $url
         * @return str
         * @since 0.1
         */
        public function modify_theme_action_urls( $url ) {
            if ( $GLOBALS['current_screen']->id != 'themes' ) return $url;
            if ( strpos ( $url, 'customize.php' ) ) {
                return $this->add_wpgs_param( $url );
            } else return $url;
        }

        /**
         * Makes activation links carry the wpgs param
         *
         * @param array $actions
         * @return array
         * @since 0.1
         */
        public function modify_theme_action_links( $actions ) {
            $actions['activate'] = str_replace( 'themes.php?action=activate', 'themes.php?action=activate&wpgs=1', $actions['activate'] );
            return $actions;
        }

        /**
         * When a theme is activated, take our user to the customizer
         *
         * @param str $location
         * @return str $location
         */
        public function modify_switch_theme_redirect( $location ) {
            if ( strpos ( $location, 'themes.php?activated=true' ) )
                return admin_url ( 'customize.php?wpgs=1' );
            else return $location;
        }

        /**
         * Add wpgs=1 to any URL
         *
         * @param str $url
         * @return str
         * @since 0.1
         */
        public function add_wpgs_param ( $url ) {
            return add_query_arg( 'wpgs', '1', $url );
        }

        /**
         * Print hidden wpgs field
         *
         * @since 0.1
         */
        public function add_wpgs_hidden_field () {
            echo "<input type='hidden' name='wpgs' value=1>";
        }

        /**
         * A bit hackishly change the redirect when an unactivated theme is being saved and activated in theme customizer
         *
         * @since 0.1
         */
        public function change_customizer_activate_redirect() {
            echo
                "
                <script>
                    setTimeout('_wpCustomizeSettings.url.activated = \"" . admin_url('index.php?wpgs_action=theme_activated') ."\";', 50);
                </script>
                ";
        }

        /**
         * Change redirect on 'Close' in Theme Customizer
         *
         * @global str $return
         * @global WP_Customize $wp_customize
         * @since 0.1
         */
        public function change_customizer_close_redirect() {
            global $return, $wp_customize;
            $return = ( $wp_customize->is_theme_active() ) ? admin_url('index.php') : admin_url('themes.php?live=1&wpgs=1');
        }

        /**
         * Change redirect on 'Close' in Live Theme Preview
         *
         * @global str $return
         * @since 0.1
         */
        public function change_ltp_close_redirect() {
            global $return;
            $return = admin_url('index.php');
        }

        /**
         * Redirect back to dashboard after saving new post or page
         *
         * @param str $location
         * @param int $postid
         * @since 0.1
         */
        public function redirect_post_dashboard( $location, $postid ) {
            if (isset($_POST['save']) || isset($_POST['publish'])) {
                if (preg_match("/post=([0-9]*)/", $location, $match)) {
                    $pl = get_permalink($match[1]);
                    if ($pl) {
                        wp_redirect( admin_url ("index.php?wpgs_action=saved&id=$postid&post_type={$GLOBALS['typenow']}" ) );
                    }
                }
            }

        }

        /**
         * Hook into the WP Welcome Panel
         *
         * @since 0.1
         */
        protected function set_welcome_panel() {
            remove_action( 'welcome_panel', 'wp_welcome_panel' );
            add_action   ( 'welcome_panel', array ( &$this, 'the_welcome_panel' ) );
            add_action   ( 'wp_after_welcome_panel', array ( &$this, 'the_instruction_panel' ) );

            add_action ( 'admin_print_styles', array ( &$this, 'enqueue_welcome_style' ) );

            if ( $_GET['wpgs_action'] && ! empty ( $_GET['wpgs_action'] ) )
                add_action ( 'admin_notices', array ( &$this, 'admin_notice' ) );
        }

        /**
         * Styles for the welcome panel
         *
         * @since 0.1
         */
        public function enqueue_welcome_style() {
            if ( $GLOBALS['current_screen']->id == 'dashboard' )
                wp_enqueue_style('wp-getting-started', WPGS_INC_URL . 'css/wp-getting-started.css', null, 0.1 );
        }

        /**
         * Show admin notice at the Dashboard
         *
         * @since 0.1
         */
        public function admin_notice() {
            $action = $_GET['wpgs_action'];

            switch ( $action ) {
                case "saved" :
                    if ( ! isset ( $_GET['id'] ) ) break;
                    $post_ID = $_GET['id'];
                    if ( isset ( $_GET['post_type'] ) && $_GET['post_type'] == "page" )
                        $message = sprintf( __('Page published. <a href="%s">View page</a>'), esc_url( get_permalink($post_ID) ) . '" target="_blank'  );
                    else
                        $message = sprintf( __('Post published. <a href="%s">View post</a>'), esc_url( get_permalink($post_ID) ) . '" target="_blank' );
                    break;
                case "theme_activated" :
                    $message = sprintf( __( 'Settings saved and theme activated. <a href="%s">Visit site</a>' ), home_url( '/' ) . '" target="_blank' );
            }
            printf( '<div class="updated"> <p> %s </p> </div>', __ ( $message, 'wp-gettings-started' ) );
        }

        /**
         * Loader for pointers
         *
         * @since 0.1
         */
        public function set_pointers() {
            $this->dashboard_pointers();
            if ( $this->walkthrough ) $this->walkthrough_pointers();
        }

        /**
         * Pointer(s) shown on the dashboard
         *
         * Only shown to users that have not modified their install yet. It's the only pointer that shows not following a link of WPGS (this is the starting point after all).
         *
         * @since 0.1
         */
        public function dashboard_pointers() {
            if ( $this->complete < 1 ) {
                $pointers = array(
                    array(
                        'id' => 'wpgs_dash',
                        'screen' => 'dashboard',
                        'target' => '#menu-dashboard',
                        'title' => __ ( 'Dashboard' ),
                        'content' => __( 'Welcome to your WordPress Dashboard! This is the screen you will see when you log in to your site, and gives you access to all the site management features of WordPress. You can get help for any screen by clicking the Help tab in the upper corner.' ) . "</p><p>" . __ ( "You can always return to this screen by clicking the Dashboard icon above.", 'wp-getting-started' ),
                        'position' => array(
                                'edge' => 'top',
                                'align' => 'top'
                            )
                        )
                    );
            /*} elseif ( $this->completed_all ) {
                 $pointers = array(
                    array(
                        'id' => 'wpgs_help',
                        'screen' => 'dashboard',
                        'target' => '#welcome-panel h3',
                        'title' => __ ( 'Complete!' ),
                        'content' => __ ( "You have now set up your website and learned how to manage it. Remember that you can always look up information using the 'Help' tab here.", 'wp-getting-started' ),
                        'position' => array(
                                'edge' => 'top',
                                'align' => 'left'
                            )
                        )
                    );*/
            } else return;

            $pointers = apply_filters( 'wpgs_dashboard_pointers', $pointers );
            new WP_Help_Pointer($pointers);
        }

        /**
         * Pointers for the walkthrough
         *
         * @since 0.1
         */
        public function walkthrough_pointers() {
            $pointers = array(
                            array(
                                'id' => 'wpgs_post',
                                'screen' => 'post',
                                'target' => '#menu-posts',
                                'title' => __ ( 'Posts' ),
                                'content' => __ ( "Posts are what make your blog a blog - they're servings of content, similar to journal entries, listed in reverse chronological order. Posts can be as short or as long as you like; some are as brief as Twitter updates, while others are the length of essays.", 'wp-getting-started' ),
                                'position' => array(
                                        'edge' => 'top',
                                        'align' => 'top'
                                    )
                                ),
                            array(
                                'id' => 'wpgs_page',
                                'screen' => 'page',
                                'target' => '#menu-pages',
                                'title' => __ ( 'Pages' ),
                                'content' => __ ( "Pages are for more timeless content that you want your visitors to be able to easily access from your main menu, like your About Me or Contact sections. You can edit them any time.", 'wp-getting-started' ),
                                'position' => array(
                                        'edge' => 'top',
                                        'align' => 'top'
                                    )
                                ),
                            array(
                                'id' => 'wpgs_theme',
                                'screen' => 'themes',
                                'target' => '#menu-appearance',
                                'title' => __ ( 'Themes' ),
                                'content' => __ ( "Aside from the default theme included with your WordPress installation, themes are designed and developed by third parties. Use the 'Appearance' menu to change your theme. You can change and customize your themes at any time.", 'wp-getting-started' ),
                                'position' => array(
                                        'edge' => 'top',
                                        'align' => 'top'
                                    )
                                ),
                             );
            $pointers = apply_filters( 'wpgs_walkthrough_pointers', $pointers );
            new WP_Help_Pointer( $pointers );
        }

        /**
         * Boolean check if the theme is chosen
         *
         * @global str $wp_version
         * @return boolean
         * @since 0.1
         */
        protected function is_theme_chosen() {
            if ( has_filter ( 'wpgs_is_theme_chosen' ) )
                return apply_filters ( 'wpgs_is_theme_chosen', false );

            global $wp_version;
            if ( floatval ( $wp_version ) >= 3.5 )
                return ( get_stylesheet() != "twentytwelve" );
            else return ( get_stylesheet() != "twentyeleven" );
        }

        /**
         * Boolean check if theme is customized
         *
         * @return boolean
         * @since 0.1
         */
        protected function is_theme_customized() {
            if ( has_filter ( 'wpgs_is_theme_customized' ) )
                return apply_filters ( 'wpgs_is_theme_customized', false );

            $options = get_option( "theme_mods_" . get_stylesheet() );

            if ( $options ) {
                $theme = wp_get_theme();
                $default_options = (array) get_option( 'mods_' . $theme->get("Name") );
                return ( $default_options != $options );
            }
            return false;
        }

        /**
         * Boolean check if site has pages
         *
         * @return boolean
         * @since 0.1
         */
        protected function site_has_pages() {
            if ( has_filter ( 'wpgs_site_has_pages' ) )
                return apply_filters ( 'wpgs_site_has_pages', false );

            $pages = get_posts( array( 'numberposts' => 1, 'exclude' => array ( 2 ), 'post_type' => 'page' ) );
            return ( ! empty ( $pages ) );
        }

        /**
         * Boolean check if site has posts
         *
         * @return boolean
         * @since 0.1
         */
        protected function site_has_posts() {
            if ( has_filter ( 'wpgs_site_has_posts' ) )
                return apply_filters ( 'wpgs_site_has_posts', false );

            $posts = get_posts( array( 'numberposts' => 1, 'exclude' => array ( 1 ) ) );
            return ( ! empty ( $posts ) );
        }


        /**
         * Renders the instruction panel, after the welcome panel
         *
         * @uses do_action 'wpgs_before_instuction_panel'
         * @uses do_action 'wpgs_after_instuction_panel'
         * @since 0.1
         */
        public function the_instruction_panel() {
            do_action ('wpgs_before_instuction_panel');
            ?>
            <div class="welcome-panel-column-container">

            <?php if ( $this->complete < 2 ) : ?>

                <div class="welcome-panel-column">
                    <h4><?php _e( 'Congratulations!', 'wp-getting-started' ); ?></h4>
                    <p><?php _e( 'Your new website is now up and running.', 'wp-getting-started' ); ?></p>
                </div>

                <div class="welcome-panel-column">
                    <h4>Next step</h4>
                    <p><?php _e( 'To get started, follow the easy steps above.', 'wp-getting-started' ); ?></p>
                </div>

                <div class="welcome-panel-column welcome-panel-last">
                    <h4>Help</h4>
                    <p><?php _e( 'If things are ever unclear, use the \'Help\' button in top-right corner.', 'wp-getting-started' ); ?></p>
                </div>

            <?php elseif ( ! $this->completed_all ) : ?>

                <div class="welcome-panel-column">
                    <h4><?php _e( 'Posts and pages', 'wp-getting-started' ); ?></h4>
                    <p><?php _e( 'Now that your website has a unique look, you need some content.', 'wp-getting-started' ); ?></p>
                    <p><?php _e( "There's an important difference between pages and posts. Please read the descriptions here, or read further under the help tab.", 'wp-getting-started' ); ?></p>
                </div>

                <div class="welcome-panel-column">
                    <h4><?php _e( 'Pages', 'wp-getting-started' ); ?></h4>
                    <p><?php _e( 'Pages are for more timeless content that you want your visitors to be able to easily access from your main menu, like your About Me or Contact sections.', 'wp-getting-started' ); ?></p>
                </div>

                <div class="welcome-panel-column">
                    <h4><?php _e( 'Posts', 'wp-getting-started' ); ?></h4>
                    <p><?php _e( 'Posts are entries listed in reverse chronological order on the blog home page or on the posts page.', 'wp-getting-started' ); ?></p>
                </div>

            <?php else : ?>

                <div class="welcome-panel-column">
                    <h4><?php _e( 'Compeleted!', 'wp-getting-started' ); ?></h4>
                    <p><?php _e( 'You have now set up your website and learned how to manage it.', 'wp-getting-started' ); ?></p>
                    <p><?php printf ( __( "Click '%s' to close this panel and start using your website. You can always reopen it by selecting 'Screen Options' and then 'Welcome'.", 'wp-getting-started' ),
                            "<a class='welcome-panel-close' href='" . esc_url( admin_url( '?welcome=0' ) ) . "'>" .  __( 'Dismiss' ) . "</a>" ); ?>
                    </p>
                </div>

                <div class="welcome-panel-column">
                    <h4><?php _e( 'Help', 'wp-getting-started' ); ?></h4>
                    <p><?php _e( "Remember that you can always consult the 'Help' tab at the top of the screen, to find out more about the screen you are currently viewing.", 'wp-getting-started' ); ?></p>
                </div>

                <div class="welcome-panel-column">
                    <h4><?php _e( 'Useful links', 'wp-getting-started' ); ?></h4>
                    <ul>
                        <li><?php printf( '<a href="%s">' . __( 'View your site' ) . '</a>', home_url( '/' ) ); ?></li><li><?php printf( '<a id="wp350_add_images" href="%s">' . __( 'Add image/media' ) . '</a>', admin_url( 'media-new.php' ) ); ?></li>
                        <li><?php printf( '<a id="wp350_widgets" href="%s">' . __( 'Add/remove widgets' ) . '</a>', admin_url( 'widgets.php' ) ); ?></li>
                        <li><?php printf( '<a id="wp350_edit_menu" href="%s">' . __( 'Edit your navigation menu' ) . '</a>', admin_url( 'nav-menus.php' ) ); ?></li>
                    </ul>
                </div>

            <?php endif;

            do_action ('wpgs_after_instuction_panel');
        }

        /**
         * Sets class vars
         *
         * @uses apply_filters 'wpgs_progress'
         * @uses apply_filters 'wpgs_walkthrough'
         * @since 0.1
         */
        protected function set_current_state() {
            $this->progress = apply_filters ( 'wpgs_progress', array(
                "theme_chosen"      => ( $this->is_theme_chosen() ) ? true : false,
                "theme_customized"  => ( $this->is_theme_customized() ) ? true : false,
                "theme_edited"      => ( $this->is_theme_customized() || $this->is_theme_chosen() ) ? true : false,
                "has_pages"         => ( $this->site_has_pages() ) ? true : false,
                "has_posts"         => ( $this->site_has_posts() ) ? true : false,
            ) );

            $i = 0;
            foreach ( $this->progress as $prog ) {
                if ( $prog )
                    $this->complete = $i;
                $i++;
            }

            $this->completed_all = ( $this->progress['theme_edited'] && $this->progress['has_pages'] && $this->progress['has_posts'] );

            $this->walkthrough = apply_filters ( 'wpgs_walkthrough', ( isset ( $_REQUEST['wpgs'] ) && $_REQUEST['wpgs'] == true ) );
        }

        /**
         * The Welcome Panel
         *
         * @uses do_action 'wp_before_welcome_panel'
         * @uses do_action 'wp_after_welcome_panel'
         * @since 0.1
         */
        public function the_welcome_panel() {
            do_action( 'wp_before_welcome_panel');
            ?>
            <div class="welcome-panel-content<?php if ( $this->completed_all ) echo " completed"; ?>">
            <h3><?php _e( 'Welcome to WordPress!' ); ?></h3>
            <p class="about-description"><?php _e( 'We&#8217;ve assembled some links to get you started:' ); ?></p>
            <div class="welcome-panel-column-container">

                <div class="welcome-progression-block">
                    <?php // For some setups you just don't want your users to folow the first, so this is pluggable
                    if ( has_action ( 'wpgs_disable_first_link' ) ) :
                        $link = 'javascript:void(0)';
                    else :
                        $link = get_bloginfo('url');
                    endif;
                    ?>

                    <a href="<?php echo $link ?>">
                        <h2>1. <?php _e( 'Website', 'wp-getting-started' ); ?></h2>
                    </a>

                    <a href="<?php echo $link ?>">
                        <img src="<?php echo WPGS_IMAGES_URL . "setup"; ?>.png">

                        <p class="completed"><?php _e( 'Setup your website', 'wp-getting-started' ); ?></p>
                    </a>
                </div>

                <?php $this->print_arrow (); ?>

                <div class="welcome-progression-block">

                    <a href="<?php echo admin_url( 'themes.php?live=1&wpgs=1' ); ?>">
                        <h2>2. <?php _e( 'Theme', 'wp-getting-started' ); ?></h2>
                    </a>

                    <div class="welcome-progression-block">

                        <a href="<?php echo admin_url( 'themes.php?live=1&wpgs=1' ); ?>">
                            <img src="<?php echo WPGS_IMAGES_URL . "change"; if ( ! $this->progress['theme_edited'] ) echo "_incomplete";  ?>.png">

                            <p<?php if ( $this->progress['theme_edited'] ) echo " class='completed'"; ?>><?php _e ( 'Change', 'wp-getting-started' ); ?></p>
                        </a>

                    </div>

                    <div class="welcome-progression-block separate">
                        <h2><?php _e( 'or', 'wp-getting-started' ); ?></h2>
                    </div>

                    <div class="welcome-progression-block">
                        <a href="<?php echo wp_customize_url() . '?wpgs=1'; ?>">
                            <img src="<?php echo WPGS_IMAGES_URL . "customize"; if ( ! $this->progress['theme_customized'] ) echo "_incomplete";  ?>.png">

                            <p<?php if ( $this->progress['theme_customized'] ) echo " class='completed'"; ?>><?php _e( 'Customize' ); ?></p>
                        </a>
                    </div>
                </div>

                <?php $this->print_arrow ( 2 ); ?>

                <div class="welcome-progression-block">
                    <a href="<?php echo admin_url( 'post-new.php?post_type=page&wpgs=1' ); ?>">
                        <h2>3. <?php _e( 'Pages', 'wp-getting-started' ); ?></h2>
                    </a>

                    <a href="<?php echo admin_url( 'post-new.php?post_type=page&wpgs=1' ); ?>">
                        <img src="<?php echo WPGS_IMAGES_URL . "pages"; if ( ! $this->progress['has_pages'] ) echo "_incomplete";  ?>.png">

                        <p<?php if ( $this->progress['has_pages'] ) echo " class='completed'"; ?>><?php _e( 'Add some pages to your website', 'wp-getting-started' ); ?></p>
                    </a>
                </div>

                <?php $this->print_arrow ( 3 ); ?>

                <div class="welcome-progression-block">
                    <a href="<?php echo admin_url( 'post-new.php?wpgs=1' ); ?>">
                        <h2>4. <?php _e ( 'Posts', 'wp-getting-started' ); ?></h2>
                    </a>

                    <a href="<?php echo admin_url( 'post-new.php?wpgs=1' ); ?>">
                        <img src="<?php echo WPGS_IMAGES_URL . "posts"; if ( ! $this->progress['has_posts'] ) echo "_incomplete";  ?>.png">
                        <p<?php if ( $this->progress['has_posts'] ) echo " class='completed'"; ?>><?php _e( 'Create your first blog entry','wp-getting-started' ); ?></p>
                    </a>
                </div>

            </div>

            <?php do_action( 'wp_after_welcome_panel'); ?>

            </div>
            </div>

            <?php
        }

        /**
         * Print the arrow, dark for next step, light for 'not yet'
         *
         * @param int $which
         */
        private function print_arrow ( $which = 0 ) {
            ?>
            <div class="welcome-progression-block">
                <img src="<?php echo WPGS_IMAGES_URL; ?>arrow_right<?php if ( $this->complete < $which ) echo '2'; ?>.png">
            </div>
            <?php
        }

    }

    add_action('admin_init', array('WPGettingStarted', 'init'));
endif;