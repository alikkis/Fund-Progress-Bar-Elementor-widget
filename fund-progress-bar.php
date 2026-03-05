<?php
/**
 * Plugin Name:       Fund Progress Bar
 * Plugin URI:        https://github.com/alikkis/Fund-Progress-Bar-Elementor-widget
 * Description:       Elementor widget that displays an animated progress bar by fetching live percentage data from an API endpoint.
 * Version:           1.1.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Oleksii Kiskukhin
 * License:           GPL v2 or later
 * Text Domain:       fund-progress-bar
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FPB_VERSION',    '1.1.0' );
define( 'FPB_FILE',       __FILE__ );
define( 'FPB_PATH',       plugin_dir_path( __FILE__ ) );
define( 'FPB_URL',        plugin_dir_url( __FILE__ ) );
define( 'FPB_ASSETS_URL', FPB_URL . 'assets/' );

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
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_missing_elementor' ] );
            return;
        }

        add_action( 'elementor/widgets/register',              [ $this, 'register_widgets' ] );
        add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'enqueue_styles' ] );
        add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_scripts' ] );
        add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // Server-side proxy — solves CORS + HTTP/HTTPS mixed-content completely
        add_action( 'rest_api_init', [ $this, 'register_rest_proxy' ] );
    }

    /* ------------------------------------------------------------------
     * REST proxy:  GET /wp-json/fpb/v1/proxy?url=<encoded>&timeout=<s>
     * The browser talks only to its own WP domain; PHP fetches the API.
     * ------------------------------------------------------------------ */
    public function register_rest_proxy() {
        register_rest_route( 'fpb/v1', '/proxy', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'rest_proxy_handler' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'url'     => [ 'required' => true,  'type' => 'string',  'sanitize_callback' => 'esc_url_raw' ],
                'timeout' => [ 'required' => false, 'type' => 'integer', 'default' => 15, 'sanitize_callback' => 'absint' ],
            ],
        ] );
    }

    public function rest_proxy_handler( \WP_REST_Request $request ) {
        $remote_url   = $request->get_param( 'url' );
        $timeout      = min( 60, max( 3, (int) $request->get_param( 'timeout' ) ) );

        // Security: only proxy requests to the known API host (not an open proxy)
        $allowed_host = 'ktds-bots.koyeb.app';
        $parsed       = wp_parse_url( $remote_url );

        if ( empty( $parsed['host'] ) || $parsed['host'] !== $allowed_host ) {
            return new \WP_Error(
                'fpb_forbidden',
                __( 'Proxy only allows the configured API host.', 'fund-progress-bar' ),
                [ 'status' => 403 ]
            );
        }

        $response = wp_remote_get( $remote_url, [
            'timeout'    => $timeout,
            'user-agent' => 'WordPress/FundProgressBar-' . FPB_VERSION,
            'sslverify'  => false,   // allows HTTP endpoints without SSL issues
        ] );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error(
                'fpb_remote_error',
                $response->get_error_message(),
                [ 'status' => 502 ]
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_Error( 'fpb_json_error', 'Invalid JSON from remote API.', [ 'status' => 502 ] );
        }

        return new \WP_REST_Response( $data, $code );
    }

    public function register_widgets( $widgets_manager ) {
        require_once FPB_PATH . 'widget/class-fund-progress-bar-widget.php';
        $widgets_manager->register( new \Fund_Progress_Bar_Widget() );
    }

    public function enqueue_styles() {
        wp_enqueue_style( 'fund-progress-bar', FPB_ASSETS_URL . 'fund-progress-bar.css', [], FPB_VERSION );
    }

    public function register_scripts() {
        wp_register_script( 'fund-progress-bar', FPB_ASSETS_URL . 'fund-progress-bar.js', [ 'jquery' ], FPB_VERSION, true );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'fund-progress-bar' );
        // Pass proxy URL to JS — widgets use this instead of calling the API directly
        wp_localize_script( 'fund-progress-bar', 'fpbConfig', [
            'proxyBase' => rest_url( 'fpb/v1/proxy' ),
            'version'   => FPB_VERSION,
        ] );
    }

    public function admin_notice_missing_elementor() {
        $message = sprintf(
            esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'fund-progress-bar' ),
            '<strong>' . esc_html__( 'Fund Progress Bar', 'fund-progress-bar' ) . '</strong>',
            '<strong>' . esc_html__( 'Elementor', 'fund-progress-bar' ) . '</strong>'
        );
        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }
}

Fund_Progress_Bar_Plugin::instance();
