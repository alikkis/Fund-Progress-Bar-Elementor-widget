<?php
/**
 * Fund Progress Bar – Elementor Widget
 *
 * Renders an animated circular + linear progress bar whose fill percentage
 * is fetched live from api Endpoint
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;

class Fund_Progress_Bar_Widget extends Widget_Base {

    /* ------------------------------------------------------------------ */
    /* Widget metadata                                                      */
    /* ------------------------------------------------------------------ */

    public function get_name()  { return 'fund_progress_bar'; }
    public function get_title() { return esc_html__( 'Fund Progress Bar', 'fund-progress-bar' ); }
    public function get_icon()  { return 'eicon-skill-bar'; }
    public function get_categories() { return [ 'general' ]; }
    public function get_keywords() { return [ 'progress', 'bar', 'fund', 'percentage', 'animated' ]; }

    /* ------------------------------------------------------------------ */
    /* Controls                                                             */
    /* ------------------------------------------------------------------ */

    protected function register_controls() {

        /* ── Content Tab ── */
        $this->start_controls_section( 'section_content', [
            'label' => esc_html__( 'Content', 'fund-progress-bar' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'title', [
            'label'       => esc_html__( 'Title', 'fund-progress-bar' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => esc_html__( 'Fundraising Goal', 'fund-progress-bar' ),
            'placeholder' => esc_html__( 'Enter title…', 'fund-progress-bar' ),
        ] );

        $this->add_control( 'subtitle', [
            'label'   => esc_html__( 'Subtitle', 'fund-progress-bar' ),
            'type'    => Controls_Manager::TEXT,
            'default' => esc_html__( 'Help us reach our target!', 'fund-progress-bar' ),
        ] );

        $this->add_control( 'api_url', [
            'label'   => esc_html__( 'API Endpoint', 'fund-progress-bar' ),
            'type'    => Controls_Manager::TEXT,
            'default' => 'https://',
            'description' => esc_html__( 'Must return JSON: { "percentage": 13.4 }', 'fund-progress-bar' ),
        ] );

        $this->add_control( 'default_percentage', [
            'label'       => esc_html__( 'Default / Fallback Percentage', 'fund-progress-bar' ),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 0,
            'max'         => 100,
            'step'        => 0.1,
            'default'     => 0,
            'description' => esc_html__( 'Shown immediately on page load (lazy-load placeholder) and used permanently if the API fails or times out.', 'fund-progress-bar' ),
        ] );

        $this->add_control( 'api_timeout', [
            'label'       => esc_html__( 'API Timeout (seconds)', 'fund-progress-bar' ),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 3,
            'max'         => 60,
            'step'        => 1,
            'default'     => 15,
            'description' => esc_html__( 'How long to wait for the API before giving up. The bar is visible immediately with the default value — this does NOT block the page.', 'fund-progress-bar' ),
        ] );

        $this->add_control( 'refresh_interval', [
            'label'   => esc_html__( 'Auto-refresh (seconds, 0 = off)', 'fund-progress-bar' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 0,
            'max'     => 3600,
            'step'    => 5,
            'default' => 60,
        ] );

        $this->add_control( 'bar_style', [
            'label'   => esc_html__( 'Bar Style', 'fund-progress-bar' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'linear'   => esc_html__( 'Linear (horizontal)', 'fund-progress-bar' ),
                'circular' => esc_html__( 'Circular', 'fund-progress-bar' ),
                'both'     => esc_html__( 'Both', 'fund-progress-bar' ),
            ],
            'default' => 'both',
        ] );

        $this->add_control( 'animation_duration', [
            'label'   => esc_html__( 'Animation Duration (ms)', 'fund-progress-bar' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 200,
            'max'     => 5000,
            'step'    => 100,
            'default' => 1800,
        ] );

        $this->end_controls_section();

        /* ── Style Tab ── */
        $this->start_controls_section( 'section_style_bar', [
            'label' => esc_html__( 'Progress Bar', 'fund-progress-bar' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'color_fill', [
            'label'   => esc_html__( 'Fill Colour', 'fund-progress-bar' ),
            'type'    => Controls_Manager::COLOR,
            'default' => '#27ae60',
            'selectors' => [
                '{{WRAPPER}} .fpb-linear-fill'       => 'background: {{VALUE}};',
                '{{WRAPPER}} .fpb-circle-progress'   => 'stroke: {{VALUE}};',
                '{{WRAPPER}} .fpb-percent-value'     => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'color_track', [
            'label'   => esc_html__( 'Track Colour', 'fund-progress-bar' ),
            'type'    => Controls_Manager::COLOR,
            'default' => '#e0e0e0',
            'selectors' => [
                '{{WRAPPER}} .fpb-linear-track'   => 'background: {{VALUE}};',
                '{{WRAPPER}} .fpb-circle-track'   => 'stroke: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'color_bg', [
            'label'   => esc_html__( 'Card Background', 'fund-progress-bar' ),
            'type'    => Controls_Manager::COLOR,
            'default' => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .fpb-card' => 'background: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'linear_height', [
            'label'      => esc_html__( 'Linear Bar Height (px)', 'fund-progress-bar' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 4, 'max' => 40 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 18 ],
            'selectors'  => [
                '{{WRAPPER}} .fpb-linear-track' => 'height: {{SIZE}}{{UNIT}}; border-radius: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .fpb-linear-fill'  => 'height: {{SIZE}}{{UNIT}}; border-radius: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'circle_size', [
            'label'      => esc_html__( 'Circle Diameter (px)', 'fund-progress-bar' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 80, 'max' => 320 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 180 ],
            'selectors'  => [
                '{{WRAPPER}} .fpb-circle-wrap' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'stroke_width', [
            'label'   => esc_html__( 'Stroke Width', 'fund-progress-bar' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 2,
            'max'     => 30,
            'default' => 12,
        ] );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .fpb-card',
        ] );

        $this->end_controls_section();

        /* ── Typography ── */
        $this->start_controls_section( 'section_style_text', [
            'label' => esc_html__( 'Typography', 'fund-progress-bar' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'color_title', [
            'label'   => esc_html__( 'Title Colour', 'fund-progress-bar' ),
            'type'    => Controls_Manager::COLOR,
            'default' => '#1a1a2e',
            'selectors' => [ '{{WRAPPER}} .fpb-title' => 'color: {{VALUE}};' ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'selector' => '{{WRAPPER}} .fpb-title',
        ] );

        $this->add_control( 'color_subtitle', [
            'label'   => esc_html__( 'Subtitle Colour', 'fund-progress-bar' ),
            'type'    => Controls_Manager::COLOR,
            'default' => '#666666',
            'selectors' => [ '{{WRAPPER}} .fpb-subtitle' => 'color: {{VALUE}};' ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'subtitle_typography',
            'selector' => '{{WRAPPER}} .fpb-subtitle',
        ] );

        $this->end_controls_section();
    }

    /* ------------------------------------------------------------------ */
    /* Render                                                               */
    /* ------------------------------------------------------------------ */

    protected function render() {
        $s          = $this->get_settings_for_display();
        $widget_id  = $this->get_id();
        $bar_style  = $s['bar_style'];
        $anim_ms    = absint( $s['animation_duration'] );
        $refresh    = absint( $s['refresh_interval'] );
        $stroke     = absint( $s['stroke_width'] ?? 12 );
        $api_url    = esc_url( $s['api_url'] );

        // Default / fallback percentage (shown immediately; used if API fails)
        $default_pct = isset( $s['default_percentage'] )
            ? floatval( $s['default_percentage'] )
            : 0;
        $default_pct = max( 0, min( 100, $default_pct ) );

        // API timeout in seconds (does NOT block rendering)
        $api_timeout = isset( $s['api_timeout'] ) ? absint( $s['api_timeout'] ) : 15;
        $api_timeout = max( 3, $api_timeout );

        $show_linear   = in_array( $bar_style, [ 'linear', 'both' ], true );
        $show_circular = in_array( $bar_style, [ 'circular', 'both' ], true );

        // Radius & circumference for SVG circle
        $radius        = 50;   // viewBox units
        $circumference = round( 2 * M_PI * $radius, 4 );

        // Pre-compute default offset so the ring renders instantly at the right fill
        $default_offset = round( $circumference - ( $default_pct / 100 ) * $circumference, 4 );

        $data_attrs = sprintf(
            'data-api="%s" data-anim="%d" data-refresh="%d" data-stroke="%d" data-circumference="%s" data-default="%s" data-timeout="%d"',
            esc_attr( $api_url ),
            $anim_ms,
            $refresh,
            $stroke,
            $circumference,
            $default_pct,
            $api_timeout
        );
        ?>
        <div class="fpb-card fpb-widget fpb-loaded"
             id="fpb-<?php echo esc_attr( $widget_id ); ?>"
             <?php echo $data_attrs; ?>>

            <?php if ( $s['title'] ) : ?>
                <h3 class="fpb-title"><?php echo esc_html( $s['title'] ); ?></h3>
            <?php endif; ?>

            <?php if ( $s['subtitle'] ) : ?>
                <p class="fpb-subtitle"><?php echo esc_html( $s['subtitle'] ); ?></p>
            <?php endif; ?>

            <?php if ( $show_circular ) : ?>
            <div class="fpb-circle-wrap">
                <svg class="fpb-circle-svg" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                    <circle class="fpb-circle-track"
                            cx="60" cy="60"
                            r="<?php echo $radius; ?>"
                            fill="none"
                            stroke-width="<?php echo $stroke; ?>"
                            stroke-linecap="round"/>
                    <!-- stroke-dashoffset pre-set to default so ring is visible immediately -->
                    <circle class="fpb-circle-progress"
                            cx="60" cy="60"
                            r="<?php echo $radius; ?>"
                            fill="none"
                            stroke-width="<?php echo $stroke; ?>"
                            stroke-linecap="round"
                            stroke-dasharray="<?php echo $circumference; ?>"
                            stroke-dashoffset="<?php echo $default_offset; ?>"
                            transform="rotate(-90 60 60)"/>
                </svg>
                <div class="fpb-circle-label">
                    <span class="fpb-percent-value"><?php echo number_format( $default_pct, 1 ); ?></span>
                    <span class="fpb-percent-sign">%</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( $show_linear ) : ?>
            <div class="fpb-linear-wrap">
                <div class="fpb-linear-track">
                    <!-- width pre-set to default so bar is visible immediately -->
                    <div class="fpb-linear-fill" style="width:<?php echo esc_attr( $default_pct ); ?>%"></div>
                </div>
                <div class="fpb-linear-label">
                    <span class="fpb-percent-text"><?php echo number_format( $default_pct, 1 ); ?>%</span>
                    <span class="fpb-status-text"><?php esc_html_e( 'Funded', 'fund-progress-bar' ); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Subtle "fetching live data" indicator (small dot, not a full spinner) -->
            <div class="fpb-live-indicator" title="<?php esc_attr_e( 'Fetching live data…', 'fund-progress-bar' ); ?>">
                <span class="fpb-live-dot"></span>
                <span class="fpb-live-label"><?php esc_html_e( 'live', 'fund-progress-bar' ); ?></span>
            </div>

            <div class="fpb-error" style="display:none;">
                ⚠ <?php esc_html_e( 'Could not load live data — showing default value.', 'fund-progress-bar' ); ?>
            </div>
        </div>
        <?php
    }

    /* Editor preview (identical to front-end) */
    protected function content_template() {}
}
