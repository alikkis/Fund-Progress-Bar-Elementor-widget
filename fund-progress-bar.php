<?php
/**
 * Plugin Name:       Fund Progress Bar
 * Plugin URI:        https://github.com/alikkis/Fund-Progress-Bar-Elementor-widget
 * Description:       Elementor widget that displays an animated progress bar by fetching live percentage data from an API endpoint.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Oleksii Kiskukhin
 * License:           GPL v2 or later
 * Text Domain:       fund-progress-bar
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'FPB_VERSION',     '1.0.0' );
define( 'FPB_FILE',        __FILE__ );
define( 'FPB_PATH',        plugin_dir_path( __FILE__ ) );
define( 'FPB_URL',         plugin_dir_url( __FILE__ ) );
define( 'FPB_ASSETS_URL',  FPB_URL . 'assets/' );

/**
 * Main plugin class.
 */
final class Fund_Progress_Bar_Plugin {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init() {
        // Check Elementor is active
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_missing_elementor' ] );
            return;
        }

        // Register widget
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );

        // Enqueue frontend assets
        add_action( 'elementor/frontend/after_enqueue_styles',  [ $this, 'enqueue_styles' ] );
        add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_scripts' ] );
        add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    public function register_widgets( $widgets_manager ) {
        require_once FPB_PATH . 'widget/class-fund-progress-bar-widget.php';
        $widgets_manager->register( new \Fund_Progress_Bar_Widget() );
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'fund-progress-bar',
            FPB_ASSETS_URL . 'fund-progress-bar.css',
            [],
            FPB_VERSION
        );
    }

    public function register_scripts() {
        wp_register_script(
            'fund-progress-bar',
            FPB_ASSETS_URL . 'fund-progress-bar.js',
            [ 'jquery' ],
            FPB_VERSION,
            true
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'fund-progress-bar' );
    }

    public function admin_notice_missing_elementor() {
        $message = sprintf(
            /* translators: 1: Plugin name, 2: Elementor */
            esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'fund-progress-bar' ),
            '<strong>' . esc_html__( 'Fund Progress Bar', 'fund-progress-bar' ) . '</strong>',
            '<strong>' . esc_html__( 'Elementor', 'fund-progress-bar' ) . '</strong>'
        );
        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }
}

Fund_Progress_Bar_Plugin::instance();
