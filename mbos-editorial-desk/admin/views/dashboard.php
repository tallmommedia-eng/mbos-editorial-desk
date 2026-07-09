<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="mbos-dashboard-widget">
    <div class="mbos-dashboard-header">
        <p class="mbos-dashboard-note">
MBOS Editorial Desk v<?php echo esc_html( MBOS_DASHBOARD_VERSION ); ?>: Site Kit GA4 test build.            <?php if ( ! empty( $status['updated_at'] ) ) : ?>
                Last refreshed: <?php echo esc_html( $status['updated_at'] ); ?>
            <?php endif; ?>
        </p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="mbos_dashboard_refresh">
            <?php wp_nonce_field( 'mbos_dashboard_refresh' ); ?>
            <?php submit_button( 'Refresh', 'secondary small', 'submit', false ); ?>
        </form>
    </div>

    <?php if ( ! empty( $status['message'] ) ) : ?>
        <div class="mbos-status mbos-status-<?php echo esc_attr( ! empty( $status['type'] ) ? $status['type'] : 'info' ); ?>">
            <?php echo esc_html( $status['message'] ); ?>
            <?php if ( ! empty( $status['debug'] ) ) : ?>
                <br><small><?php echo esc_html( $status['debug'] ); ?></small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <table class="widefat striped mbos-editorial-table">
        <thead>
            <tr>
                <th>Title</th>
                <th class="mbos-number">Lifetime Views</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $rows ) ) : ?>
                <?php foreach ( $rows as $row ) : ?>
                    <tr>
                        <td>
                            <?php if ( ! empty( $row['edit_link'] ) ) : ?>
                                <a href="<?php echo esc_url( $row['edit_link'] ); ?>">
                                    <?php echo esc_html( $row['title'] ); ?>
                                </a>
                            <?php else : ?>
                                <?php echo esc_html( $row['title'] ); ?>
                            <?php endif; ?>
                        </td>
                        <td class="mbos-number">
                            <?php echo esc_html( number_format_i18n( absint( $row['lifetime_views'] ) ) ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="2">
                        <strong>No matched post rows yet.</strong><br>
                        Confirm Site Kit is active and Analytics is connected, then hit Refresh. If this message persists, send me the status/debug line above.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
