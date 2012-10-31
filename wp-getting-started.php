<?php
/*
  Plugin Name: WP Getting Started
  Plugin URI: http://trenvo.com
  Description: Replaces WPs Welcome Panel with a simple step-by-step getting started screen
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
define('WPGS_IMAGES_URL', WPGS_URL . 'images/');

if ( ! class_exists('WPGettingStarted') ) :

    class WPGettingStarted    {

        protected $complete = 0;

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
            remove_action( 'welcome_panel', 'wp_welcome_panel' );
            add_action   ( 'welcome_panel', array ( &$this, 'the_welcome_panel' ) );
            add_action   ( 'wp_after_welcome_panel', array ( &$this, 'the_completed_panel' ) );
        }

            /**
             * PHP4
             *
             * @since 0.1
                 */
            public function wpgettingstarted() {
                $this->__construct();
            }

        protected function is_theme_chosen() {
            global $wp_version;
            if ( floatval ( $wp_version ) >= 3.5 )
                return ( get_stylesheet() != "twentytwelve" );
            else return ( get_stylesheet() != "twentyeleven" );
        }

        protected function is_theme_customized() {
            $options = get_option( "theme_mods_" . get_stylesheet() );

            if ( $options ) {
                $theme = wp_get_theme();
                $default_options = (array) get_option( 'mods_' . $theme->get("Name") );
                return ( $default_options != $options );
            }
            return false;
        }

        protected function site_has_pages() {
            $pages = get_posts( array( 'numberposts' => 1, 'exclude' => array ( 2 ), 'post_type' => 'page' ) );
            return ( ! empty ( $pages ) );
        }

        protected function site_has_posts() {
            $posts = get_posts( array( 'numberposts' => 1, 'exclude' => array ( 1 ) ) );
            return ( ! empty ( $posts ) );
        }


        public function the_completed_panel() {
            ?>
            <div class="welcome-panel-column-container">

            <div class="welcome-panel-column">
                <h4><?php _e( 'Get Started' ); ?></h4>
                <p><?php _e( 'First, tweak the look of your site:' ); ?></p>
                <a class="button-primary welcome-button load-customize hide-if-no-customize" href="<?php echo wp_customize_url(); ?>"><?php _e( 'Customize Your Site' ); ?></a>
                <a class="button-primary welcome-button hide-if-customize" href="<?php echo admin_url( 'themes.php' ); ?>"><?php _e( 'Customize Your Site' ); ?></a>
                <?php if ( current_user_can( 'install_themes' ) || ( current_user_can( 'switch_themes' ) && count( wp_get_themes( array( 'allowed' => true ) ) ) > 1 ) ) : ?>
                    <p class="hide-if-no-customize"><?php printf( __( 'or, <a href="%s">change your theme completely</a>' ), admin_url( 'themes.php' ) ); ?></p>
                <?php endif; ?>
            </div>
            <div class="welcome-panel-column">
                <h4><?php _e( 'Next Steps' ); ?></h4>
                <ul>
                <?php if ( 'page' == get_option( 'show_on_front' ) && ! get_option( 'page_for_posts' ) ) : ?>
                    <li><?php printf( '<a href="%s">' . __( 'Edit your front page' ) . '</a>', get_edit_post_link( get_option( 'page_on_front' ) ) ); ?></li>
                    <li><?php printf( '<a href="%s">' . __( 'Add additional pages' ) . '</a>', admin_url( 'post-new.php?post_type=page' ) ); ?></li>
                <?php elseif ( 'page' == get_option( 'show_on_front' ) ) : ?>
                    <li><?php printf( '<a href="%s">' . __( 'Edit your front page' ) . '</a>', get_edit_post_link( get_option( 'page_on_front' ) ) ); ?></li>
                    <li><?php printf( '<a href="%s">' . __( 'Add additional pages' ) . '</a>', admin_url( 'post-new.php?post_type=page' ) ); ?></li>
                    <li><?php printf( '<a href="%s">' . __( 'Add a blog post' ) . '</a>', admin_url( 'post-new.php' ) ); ?></li>
                <?php else : ?>
                    <li><?php printf( '<a href="%s">' . __( 'Write your first blog post' ) . '</a>', admin_url( 'post-new.php' ) ); ?></li>
                    <li><?php printf( '<a href="%s">' . __( 'Add an About page' ) . '</a>', admin_url( 'post-new.php?post_type=page' ) ); ?></li>
                <?php endif; ?>
                    <li><?php printf( '<a href="%s">' . __( 'View your site' ) . '</a>', home_url( '/' ) ); ?></li>
                </ul>
            </div>

            <div class="welcome-panel-column welcome-panel-last">
                <h4><?php _e( 'Learn How To' ); ?></h4>
                <ul>
                    <li><?php printf( '<a id="wp350_add_images" href="%s">' . __( 'Add image/media' ) . '</a>', admin_url( 'media-new.php' ) ); ?></li>
                    <li><?php printf( '<a id="wp350_widgets" href="%s">' . __( 'Add/remove widgets' ) . '</a>', admin_url( 'widgets.php' ) ); ?></li>
                    <li><?php printf( '<a id="wp350_edit_menu" href="%s">' . __( 'Edit your navigation menu' ) . '</a>', admin_url( 'nav-menus.php' ) ); ?></li>
                </ul>
            </div>
            <?php
        }

        public function the_welcome_panel() {
            $progress = array(
                "theme_chosen"      => ( $this->is_theme_chosen() ) ? true : false,
                "theme_customized"  => ( $this->is_theme_customized() ) ? true : false,
                "theme_edited"      => ( $this->is_theme_customized() || $this->is_theme_chosen() ) ? true : false,
                "has_pages"         => ( $this->site_has_pages() ) ? true : false,
                "has_posts"         => ( $this->site_has_posts() ) ? true : false,
            );

            $i = 0;
            foreach ( $progress as $prog ) {
                if ( $prog ) {
                    $this->complete = $i;
                }
                $i++;
            }

            ?>
            <style>
                div.welcome-progression-block {
                    display: inline-block;
                    vertical-align: middle;
                }
                div.welcome-progression-block * {
                    text-align: center;
                }
                div.welcome-progression-block a, div.welcome-progression-block a p {
                    text-decoration: none;
                    color: #21759B;
                    font-size: 13px;
                    font-weight: bold;
                }
                div.welcome-progression-block a:hover p, div.welcome-progression-block a:active p {
                    color: #D54E21;
                }
                div.welcome-progression-block h2 {
                    margin-bottom: 20px;
                }
                div.welcome-progression-block.separate h2 {
                    padding: 0 10px;
                }
                div.welcome-progression-block img {
                    display: block;
                    margin: 0 auto;
                    padding: 0 10px;
                }
                div.welcome-progression-block p.completed {
                    text-decoration: line-through;
                }

            </style>

            <div class="welcome-panel-content">
            <h3><?php _e( 'Welcome to WordPress!' ); ?></h3>
            <p class="about-description"><?php _e( 'We&#8217;ve assembled some links to get you started:' ); ?></p>
            <div class="welcome-panel-column-container">

                <div class="welcome-progression-block">
                    <h2>1. <?php _e( 'Website', 'wp-gettings-started' ); ?></h2>

                    <a href="<?php bloginfo('url'); ?>">
                        <img src="<?php echo WPGS_IMAGES_URL . "setup"; ?>.png">

                        <p class="completed"><?php _e( 'Setup your website', 'wp-gettings-started' ); ?></p>
                    </a>
                </div>

                <?php $this->print_arrow (); ?>

                <div class="welcome-progression-block">

                    <h2>2. <?php _e( 'Theme', 'wp-gettings-started' ); ?></h2>
                    <div class="welcome-progression-block">

                        <a href="<?php echo admin_url( 'themes.php' ) . '?live=1'; ?>">
                            <img src="<?php echo WPGS_IMAGES_URL . "change"; if ( ! $progress['theme_edited'] ) echo "_incomplete";  ?>.png">

                            <p<?php if ( $progress['theme_edited'] ) echo " class='completed'"; ?>><?php _e ( 'Change', 'wp-gettings-started' ); ?></p>
                        </a>

                    </div>

                    <div class="welcome-progression-block separate">
                        <h2><?php _e( 'or', 'wp-gettings-started' ); ?></h2>
                    </div>

                    <div class="welcome-progression-block">
                        <a href="<?php echo wp_customize_url(); ?>">
                            <img src="<?php echo WPGS_IMAGES_URL . "customize"; if ( ! $progress['theme_customized'] ) echo "_incomplete";  ?>.png">

                            <p<?php if ( $progress['theme_customized'] ) echo " class='completed'"; ?>><?php _e( 'Customize' ); ?></p>
                        </a>
                    </div>
                </div>

                <?php $this->print_arrow ( 2 ); ?>

                <div class="welcome-progression-block">
                    <h2>3. <?php _e( 'Pages', 'wp-gettings-started' ); ?></h2>

                    <a href="<?php echo admin_url( 'post-new.php?post_type=page' ); ?>">
                        <img src="<?php echo WPGS_IMAGES_URL . "pages"; if ( ! $progress['has_pages'] ) echo "_incomplete";  ?>.png">

                        <p<?php if ( $progress['has_pages'] ) echo " class='completed'"; ?>><?php _e( 'Add some pages to your website', 'wp-gettings-started' ); ?></p>
                    </a>
                </div>

                <?php $this->print_arrow ( 3 ); ?>

                <div class="welcome-progression-block">
                    <h2>4. <?php _e ( 'Posts', 'wp-gettings-started' ); ?></h2>

                    <a href="<?php echo admin_url( 'post-new.php' ); ?>">
                        <img src="<?php echo WPGS_IMAGES_URL . "posts"; if ( ! $progress['has_posts'] ) echo "_incomplete";  ?>.png">
                        <p<?php if ( $progress['has_posts'] ) echo " class='completed'"; ?>><?php _e( 'Create your first blog entry','wp-gettings-started' ); ?></p>
                    </a>
                </div>

            </div>

            <?php do_action( 'wp_after_welcome_panel'); ?>

            <?php // Left in the original ( WP3.5 ) welcome pane code if you want to include it ?>
            <?php /*
            <div class="welcome-panel-column-container">

            <div class="welcome-panel-column">
                <h4><?php _e( 'Get Started' ); ?></h4>
                <p><?php _e( 'First, tweak the look of your site:' ); ?></p>
                <a class="button-primary welcome-button load-customize hide-if-no-customize" href="<?php echo wp_customize_url(); ?>"><?php _e( 'Customize Your Site' ); ?></a>
                <a class="button-primary welcome-button hide-if-customize" href="<?php echo admin_url( 'themes.php' ); ?>"><?php _e( 'Customize Your Site' ); ?></a>
                <?php if ( current_user_can( 'install_themes' ) || ( current_user_can( 'switch_themes' ) && count( wp_get_themes( array( 'allowed' => true ) ) ) > 1 ) ) : ?>
                    <p class="hide-if-no-customize"><?php printf( __( 'or, <a href="%s">change your theme completely</a>' ), admin_url( 'themes.php' ) ); ?></p>
                <?php endif; ?>
            </div>
            <div class="welcome-panel-column">
                <h4><?php _e( 'Next Steps' ); ?></h4>
                <ul>
                <?php if ( 'page' == get_option( 'show_on_front' ) && ! get_option( 'page_for_posts' ) ) : ?>
                    <li><?php printf( '<a href="%s">' . __( 'Edit your front page' ) . '</a>', get_edit_post_link( get_option( 'page_on_front' ) ) ); ?></li>
                    <li><?php printf( '<a href="%s">' . __( 'Add additional pages' ) . '</a>', admin_url( 'post-new.php?post_type=page' ) ); ?></li>
                <?php elseif ( 'page' == get_option( 'show_on_front' ) ) : ?>
                    <li><?php printf( '<a href="%s">' . __( 'Edit your front page' ) . '</a>', get_edit_post_link( get_option( 'page_on_front' ) ) ); ?></li>
                    <li><?php printf( '<a href="%s">' . __( 'Add additional pages' ) . '</a>', admin_url( 'post-new.php?post_type=page' ) ); ?></li>
                    <li><?php printf( '<a href="%s">' . __( 'Add a blog post' ) . '</a>', admin_url( 'post-new.php' ) ); ?></li>
                <?php else : ?>
                    <li><?php printf( '<a href="%s">' . __( 'Write your first blog post' ) . '</a>', admin_url( 'post-new.php' ) ); ?></li>
                    <li><?php printf( '<a href="%s">' . __( 'Add an About page' ) . '</a>', admin_url( 'post-new.php?post_type=page' ) ); ?></li>
                <?php endif; ?>
                    <li><?php printf( '<a href="%s">' . __( 'View your site' ) . '</a>', home_url( '/' ) ); ?></li>
                </ul>
            </div>

            <div class="welcome-panel-column welcome-panel-last">
                <h4><?php _e( 'Learn How To' ); ?></h4>
                <ul>
                    <li><?php printf( '<a id="wp350_add_images" href="%s">' . __( 'Add image/media' ) . '</a>', admin_url( 'media-new.php' ) ); ?></li>
                    <li><?php printf( '<a id="wp350_widgets" href="%s">' . __( 'Add/remove widgets' ) . '</a>', admin_url( 'widgets.php' ) ); ?></li>
                    <li><?php printf( '<a id="wp350_edit_menu" href="%s">' . __( 'Edit your navigation menu' ) . '</a>', admin_url( 'nav-menus.php' ) ); ?></li>
                </ul>
            </div>
             */ ?>
            </div>
            </div>

            <?php
        }

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