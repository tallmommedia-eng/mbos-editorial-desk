<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBOS_Settings {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_settings_page() {
        add_options_page(
            'MBOS Editorial Desk',
            'MBOS Editorial Desk',
            'manage_options',
            'mbos-editorial-desk',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'mbos_settings', 'mbos_ga4_property_id' );
        register_setting( 'mbos_settings', 'mbos_ga4_service_account_json' );
        register_setting( 'mbos_settings', 'mbos_debug_mode' );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>MBOS Editorial Desk Settings</h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'mbos_settings' ); ?>

                <h2>Google Analytics</h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="mbos_ga4_property_id">GA4 Property ID</label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="mbos_ga4_property_id"
                                name="mbos_ga4_property_id"
                                value="<?php echo esc_attr( get_option( 'mbos_ga4_property_id', '' ) ); ?>"
                                class="regular-text"
                                placeholder="Example: 123456789"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mbos_ga4_service_account_json">Service Account JSON</label>
                        </th>
                        <td>
                            <textarea
                                id="mbos_ga4_service_account_json"
                                name="mbos_ga4_service_account_json"
                                class="large-text code"
                                rows="12"
                                placeholder="Paste service account JSON here for now"
                            ><?php echo esc_textarea( get_option( 'mbos_ga4_service_account_json', '' ) ); ?></textarea>

                            <p class="description">
                                Temporary field for GA4 credentials. Next we will replace this with a secure upload/storage flow.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Debug Mode</th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="mbos_debug_mode"
                                    value="1"
                                    <?php checked( get_option( 'mbos_debug_mode' ), '1' ); ?>
                                >
                                Enable MBOS debug messages
                            </label>
                        </td>
                    </tr>
                </table>

                <hr>

                <h2>Sync</h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Last Sync</th>
                        <td>
                            <?php echo esc_html( get_option( 'mbos_last_sync', 'Never' ) ); ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}