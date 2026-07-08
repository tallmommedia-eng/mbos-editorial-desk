<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBOS_Dashboard {

    public function __construct() {
        add_action( 'wp_dashboard_setup', [ $this, 'register_widget' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_post_mbos_dashboard_refresh', [ $this, 'handle_refresh' ] );
    }

    public function register_widget() {
        wp_add_dashboard_widget(
            'mbos_editorial_desk_widget',
            'MBOS Editorial Desk',
            [ $this, 'render_widget' ]
        );
    }

    public function enqueue_assets( $hook ) {
        if ( 'index.php' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'mbos-dashboard-admin',
            MBOS_DASHBOARD_URL . 'assets/admin.css',
            [],
            MBOS_DASHBOARD_VERSION
        );
    }

    public function handle_refresh() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to refresh MBOS analytics.', 'mbos-dashboard' ) );
        }

        check_admin_referer( 'mbos_dashboard_refresh' );

        $service = new MBOS_GA_Service();
        $service->get_lifetime_post_views( true );

        wp_safe_redirect( admin_url( 'index.php#mbos_editorial_desk_widget' ) );
        exit;
    }

    public function render_widget() {
        $service = new MBOS_GA_Service();
        $rows    = $service->get_lifetime_post_views();
        $status  = $service->get_status();

        include MBOS_DASHBOARD_PATH . 'admin/views/dashboard.php';
    }
}
