<?php
/**
 * Plugin Name: MBOS Editorial Desk
 * Description: Adds the MBOS Editorial Desk dashboard widget for editorial analytics. Sprint 0.3 improves Google Site Kit GA4 reporting and diagnostics.
 * Version: 0.3.1
 * Author: Mary & Blake Media
 * Text Domain: mbos-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MBOS_DASHBOARD_VERSION', '0.3.1' );
define( 'MBOS_DASHBOARD_FILE', __FILE__ );
define( 'MBOS_DASHBOARD_PATH', plugin_dir_path( __FILE__ ) );
define( 'MBOS_DASHBOARD_URL', plugin_dir_url( __FILE__ ) );

require_once MBOS_DASHBOARD_PATH . 'analytics/class-cache.php';
require_once MBOS_DASHBOARD_PATH . 'analytics/class-ga-service.php';
require_once MBOS_DASHBOARD_PATH . 'includes/class-post-mapper.php';
require_once MBOS_DASHBOARD_PATH . 'admin/class-dashboard.php';

function mbos_editorial_desk_boot() {
    new MBOS_Dashboard();
}
add_action( 'plugins_loaded', 'mbos_editorial_desk_boot' );
