<?php
/**
 * Plugin Name:  Outsourcing Technical Guides
 * Plugin URI:   https://magellan-solutions.com
 * Description:  Full-page Executive Guides flow with reCAPTCHA v3 and Flamingo.
 *               Works standalone OR as a Magellan Hub project (auto-detected).
 *               Completely overrides the active theme — zero theme CSS interference.
 * Version:      1.1.4
 * Author:       Magellan Solutions
 * License:      GPL-2.0+
 * Text Domain:  outsourcing-technical-guides
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OTG_VERSION',    '1.1.4' );
define( 'OTG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OTG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OTG_DIST_URL',   OTG_PLUGIN_URL . 'dist/' );
define( 'OTG_PDF_URL',    OTG_PLUGIN_URL . 'pdf/' );

/*
 * Load shared config first — defines otg_get_setting(), otg_running_under_hub(),
 * and otg_start_session(). Must come before all other files.
 */
require_once OTG_PLUGIN_DIR . 'php/shared-config.php';

/*
 * Load email template builders — defines otg_email_admin_notification(), etc.
 * Must come before rest-routes.php which calls these functions.
 */
require_once OTG_PLUGIN_DIR . 'php/email-templates.php';

/*
 * Load REST routes — defines all register_rest_route() calls and handler
 * functions. Guarded with function_exists() so hub-loaded version wins.
 */
require_once OTG_PLUGIN_DIR . 'php/rest-routes.php';

/*
 * Start session early so the REST submission handler can write to $_SESSION.
 * otg_start_session() is defined in shared-config.php.
 */
add_action( 'init', 'otg_start_session', 1 );


/* ═══════════════════════════════════════════════════════════════
   FULL DOCUMENT OVERRIDE  (standalone mode only)
═══════════════════════════════════════════════════════════════ */
add_action( 'template_redirect', 'otg_maybe_render_page', 1 );

function otg_maybe_render_page(): void {
    if ( otg_running_under_hub() ) return;

    $form_slug     = get_option( 'otg_form_page_slug',     'outsourcing-technical-guides' );
    $download_slug = get_option( 'otg_download_page_slug', 'outsourcing-download-guides' );

    if ( is_page( $form_slug ) ) {
        while ( ob_get_level() ) ob_end_clean();
        include OTG_PLUGIN_DIR . 'templates/outsourcing-technical-guides.php';
        exit;
    }

    if ( is_page( $download_slug ) ) {
        otg_start_session();

        if ( empty( $_SESSION['otg_access_token'] ) ) {
            wp_safe_redirect( home_url( '/' . $form_slug ) );
            exit;
        }

        unset( $_SESSION['otg_access_token'] );

        while ( ob_get_level() ) ob_end_clean();
        include OTG_PLUGIN_DIR . 'templates/outsourcing-download-guides.php';
        exit;
    }
}


/* ═══════════════════════════════════════════════════════════════
   SETTINGS PAGE
═══════════════════════════════════════════════════════════════ */
add_action( 'admin_menu', function (): void {
    add_options_page( 'Outsourcing Guides Settings', 'Outsourcing Guides', 'manage_options', 'outsourcing-technical-guides', 'otg_settings_page' );
} );

add_action( 'admin_init', function (): void {
    foreach ( [ 'otg_recaptcha_site_key', 'otg_recaptcha_secret_key', 'otg_notify_emails', 'otg_form_page_slug', 'otg_download_page_slug' ] as $opt ) {
        register_setting( 'otg_settings', $opt );
    }
} );

function otg_settings_page(): void {
    $under_hub = otg_running_under_hub();
    $saved     = isset( $_GET['settings-updated'] );
    ?>
    <div class="wrap">
        <h1>Outsourcing Technical Guides – Settings</h1>
        <?php if ( $saved ) : ?><div class="notice notice-success is-dismissible"><p>&#10003; Settings saved.</p></div><?php endif; ?>
        <?php if ( $under_hub ) : ?>
        <div class="notice notice-info"><p><strong>Running under Magellan Hub.</strong> reCAPTCHA keys and notification emails are inherited from <a href="<?php echo esc_url( admin_url( 'admin.php?page=magellan-hub-settings' ) ); ?>">Magellan Hub → Settings</a> when left blank.</p></div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'otg_settings' ); ?>
            <table class="form-table">
                <tr><th>reCAPTCHA v3 Site Key</th><td><input type="text" name="otg_recaptcha_site_key" value="<?php echo esc_attr( get_option('otg_recaptcha_site_key', '') ); ?>" class="regular-text" <?php if ( $under_hub ) echo 'placeholder="Inherited from hub if blank"'; ?>><p class="description">Must be registered for: <strong><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></strong></p></td></tr>
                <tr><th>reCAPTCHA v3 Secret Key</th><td><input type="password" name="otg_recaptcha_secret_key" value="<?php echo esc_attr( get_option('otg_recaptcha_secret_key', '') ); ?>" class="regular-text" <?php if ( $under_hub ) echo 'placeholder="Inherited from hub if blank"'; ?>></td></tr>
                <tr><th>Lead Notification Email(s)</th><td><input type="text" name="otg_notify_emails" value="<?php echo esc_attr( get_option('otg_notify_emails', '') ); ?>" class="large-text" <?php if ( $under_hub ) echo 'placeholder="Inherited from hub if blank"'; else echo 'placeholder="sales@company.com"'; ?>></td></tr>
                <tr><th>Form Page Slug</th><td><input type="text" name="otg_form_page_slug" value="<?php echo esc_attr( get_option('otg_form_page_slug', 'outsourcing-technical-guides') ); ?>" class="regular-text"></td></tr>
                <tr><th>Download Page Slug</th><td><input type="text" name="otg_download_page_slug" value="<?php echo esc_attr( get_option('otg_download_page_slug', 'outsourcing-download-guides') ); ?>" class="regular-text"></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php }