<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBOS_Settings {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_post_mbos_upload_ga4_credentials', [ $this, 'handle_credentials_upload' ] );
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
        register_setting( 'mbos_settings', 'mbos_debug_mode' );
    }

    public function handle_credentials_upload() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to upload MBOS credentials.', 'mbos-dashboard' ) );
        }

        check_admin_referer( 'mbos_upload_ga4_credentials' );

        if ( empty( $_FILES['mbos_ga4_credentials_file']['tmp_name'] ) ) {
            wp_safe_redirect( admin_url( 'options-general.php?page=mbos-editorial-desk&mbos_upload=missing' ) );
            exit;
        }

        $file = $_FILES['mbos_ga4_credentials_file'];

        if ( ! empty( $file['error'] ) ) {
            wp_safe_redirect( admin_url( 'options-general.php?page=mbos-editorial-desk&mbos_upload=error' ) );
            exit;
        }

        $contents = file_get_contents( $file['tmp_name'] );
        $decoded  = json_decode( $contents, true );

        if ( empty( $decoded ) || ! is_array( $decoded ) ) {
            wp_safe_redirect( admin_url( 'options-general.php?page=mbos-editorial-desk&mbos_upload=invalid_json' ) );
            exit;
        }

        if ( empty( $decoded['client_email'] ) || empty( $decoded['private_key'] ) ) {
            wp_safe_redirect( admin_url( 'options-general.php?page=mbos-editorial-desk&mbos_upload=invalid_credentials' ) );
            exit;
        }

        $upload_dir = wp_upload_dir();
        $mbos_dir   = trailingslashit( $upload_dir['basedir'] ) . 'mbos';

        if ( ! file_exists( $mbos_dir ) ) {
            wp_mkdir_p( $mbos_dir );
        }

        $credentials_path = trailingslashit( $mbos_dir ) . 'ga4-service-account.json';

        file_put_contents( $credentials_path, $contents );

        update_option( 'mbos_ga4_credentials_path', $credentials_path );
        update_option( 'mbos_ga4_credentials_client_email', sanitize_email( $decoded['client_email'] ) );

        wp_safe_redirect( admin_url( 'options-general.php?page=mbos-editorial-desk&mbos_upload=success' ) );
        exit;
    }

    public function render_notice() {
        if ( empty( $_GET['mbos_upload'] ) ) {
            return;
        }

        $status = sanitize_key( $_GET['mbos_upload'] );

        $messages = [
            'success'             => [ 'updated', 'GA4 credentials uploaded successfully.' ],
            'missing'             => [ 'error', 'No JSON file was selected.' ],
            'error'               => [ 'error', 'The file upload failed.' ],
            'invalid_json'        => [ 'error', 'The uploaded file is not valid JSON.' ],
            'invalid_credentials' => [ 'error', 'The JSON file does not look like a valid Google service account file.' ],
        ];

        if ( empty( $messages[ $status ] ) ) {
            return;
        }

        [ $class, $message ] = $messages[ $status ];
        ?>
        <div class="notice notice-<?php echo esc_attr( $class ); ?> is-dismissible">
            <p><?php echo esc_html( $message ); ?></p>
        </div>
        <?php
    }

    public function render_settings_page() {
        $credentials_path  = get_option( 'mbos_ga4_credentials_path', '' );
        $credentials_email = get_option( 'mbos_ga4_credentials_client_email', '' );
        $has_credentials   = ! empty( $credentials_path ) && file_exists( $credentials_path );
        ?>
        <div class="wrap">
            <h1>MBOS Editorial Desk Settings</h1>

            <?php $this->render_notice(); ?>

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
                        <th scope="row">Service Account JSON</th>
                        <td>
                            <?php if ( $has_credentials ) : ?>
                                <p><strong>Status:</strong> Credential file saved.</p>
                                <p><strong>Client Email:</strong> <code><?php echo esc_html( $credentials_email ); ?></code></p>
                                <p><strong>File:</strong> <code><?php echo esc_html( basename( $credentials_path ) ); ?></code></p>
                            <?php else : ?>
                                <p><strong>Status:</strong> No credential file uploaded yet.</p>
                            <?php endif; ?>
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
                        <td><?php echo esc_html( get_option( 'mbos_last_sync', 'Never' ) ); ?></td>
                    </tr>
                </table>

                <?php submit_button( 'Save Settings' ); ?>
            </form>

            <hr>

            <h2>Upload GA4 Credentials</h2>

            <form
                method="post"
                action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                enctype="multipart/form-data"
            >
                <input type="hidden" name="action" value="mbos_upload_ga4_credentials">
                <?php wp_nonce_field( 'mbos_upload_ga4_credentials' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="mbos_ga4_credentials_file">Service Account JSON File</label>
                        </th>
                        <td>
                            <input
                                type="file"
                                id="mbos_ga4_credentials_file"
                                name="mbos_ga4_credentials_file"
                                accept="application/json,.json"
                            >
                            <p class="description">
                                Upload the Google Cloud service account JSON file. MBOS will validate it and store it in the WordPress uploads directory.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( 'Upload Credentials', 'secondary' ); ?>
            </form>
        </div>
        <?php
    }
}