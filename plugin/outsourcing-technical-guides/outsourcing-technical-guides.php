<?php
/**
 * Plugin Name:  Outsourcing Technical Guides
 * Plugin URI:   https://magellan-solutions.com
 * Description:  Full-page Executive Guides flow with reCAPTCHA v3 and Flamingo.
 *               Works standalone OR as a Magellan Hub project (auto-detected).
 *               Completely overrides the active theme — zero theme CSS interference.
 * Version:      1.1.2
 * Author:       Magellan Solutions
 * License:      GPL-2.0+
 * Text Domain:  outsourcing-technical-guides
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OTG_VERSION',    '1.1.1' );
define( 'OTG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OTG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OTG_DIST_URL',   OTG_PLUGIN_URL . 'dist/' );
define( 'OTG_PDF_URL',    OTG_PLUGIN_URL . 'pdf/' );

require_once OTG_PLUGIN_DIR . 'php/email-templates.php';

/* ═══════════════════════════════════════════════════════════════
   DUAL-MODE DETECTION
   When Magellan Hub is active and has a project matching this
   plugin's form slug, the hub handles all page rendering.
   This plugin defers template_redirect but keeps REST routes,
   session bootstrap, and email functions active.
═══════════════════════════════════════════════════════════════ */

/**
 * Returns true when Magellan Hub is active AND has an active project
 * whose page_slug matches this plugin's configured form page slug.
 */
function otg_running_under_hub(): bool {
    if ( ! function_exists( 'mhub_get_project_by_slug' ) ) return false;
    $slug    = get_option( 'otg_form_page_slug', 'outsourcing-technical-guides' );
    $project = mhub_get_project_by_slug( $slug );
    return ( $project && $project->status === 'active' );
}

/**
 * Retrieve a setting, falling back to Magellan Hub global values when
 * running under the hub and the plugin-level option is blank.
 *
 * Mapping:
 *   otg_recaptcha_site_key   → mhub_recaptcha_site
 *   otg_recaptcha_secret_key → mhub_recaptcha_secret
 *   otg_notify_emails        → mhub_notify_emails
 */
function otg_get_setting( string $option, string $default = '' ): string {
    $value = get_option( $option, '' );
    if ( $value !== '' ) return $value;

    if ( otg_running_under_hub() ) {
        switch ( $option ) {
            case 'otg_recaptcha_site_key':
                return get_option( 'mhub_recaptcha_site', $default );
            case 'otg_recaptcha_secret_key':
                return get_option( 'mhub_recaptcha_secret', $default );
            case 'otg_notify_emails':
                return get_option( 'mhub_notify_emails', get_option( 'admin_email', $default ) );
        }
    }

    return $default;
}

/* ═══════════════════════════════════════════════════════════════
   SESSION BOOTSTRAP
═══════════════════════════════════════════════════════════════ */
add_action( 'init', 'otg_start_session', 1 );

function otg_start_session(): void {
    if ( session_status() === PHP_SESSION_NONE ) {
        session_start();
    }
}

/* ═══════════════════════════════════════════════════════════════
   1. FULL DOCUMENT OVERRIDE  (standalone mode only)
   When running under Magellan Hub the hub handles rendering —
   skip this block entirely.
═══════════════════════════════════════════════════════════════ */
add_action( 'template_redirect', 'otg_maybe_render_page', 1 );

function otg_maybe_render_page(): void {
    if ( otg_running_under_hub() ) return;

    $form_slug     = get_option( 'otg_form_page_slug',     'outsourcing-technical-guides' );
    $download_slug = get_option( 'otg_download_page_slug', 'outsourcing-download-guides' );

    if ( is_page( $form_slug ) ) {
        while ( ob_get_level() ) ob_end_clean();
        include OTG_PLUGIN_DIR . 'templates/page-technical-guides.php';
        exit;
    }

    if ( is_page( $download_slug ) ) {
        if ( empty( $_SESSION['otg_access_token'] ) ) {
            wp_safe_redirect( home_url( '/' . $form_slug ) );
            exit;
        }
        unset( $_SESSION['otg_access_token'] );

        while ( ob_get_level() ) ob_end_clean();
        include OTG_PLUGIN_DIR . 'templates/page-download-guides.php';
        exit;
    }
}

/* ═══════════════════════════════════════════════════════════════
   2. SETTINGS PAGE
   Shown in both modes. In hub mode, reCAPTCHA and notification
   emails inherit from Magellan Hub Settings when left blank.
═══════════════════════════════════════════════════════════════ */
add_action( 'admin_menu', function (): void {
    add_options_page(
        'Outsourcing Guides Settings',
        'Outsourcing Guides',
        'manage_options',
        'outsourcing-technical-guides',
        'otg_settings_page'
    );
} );

add_action( 'admin_init', function (): void {
    register_setting( 'otg_settings', 'otg_recaptcha_site_key' );
    register_setting( 'otg_settings', 'otg_recaptcha_secret_key' );
    register_setting( 'otg_settings', 'otg_notify_emails' );
    register_setting( 'otg_settings', 'otg_form_page_slug' );
    register_setting( 'otg_settings', 'otg_download_page_slug' );
} );

function otg_settings_page(): void {
    $under_hub = otg_running_under_hub();
    $saved     = isset( $_GET['settings-updated'] );
    ?>
    <div class="wrap">
        <h1>Outsourcing Technical Guides – Settings</h1>

        <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible"><p>&#10003; Settings saved.</p></div>
        <?php endif; ?>

        <?php if ( $under_hub ) : ?>
        <div class="notice notice-info">
            <p>
                <strong>Running under Magellan Hub.</strong>
                reCAPTCHA keys and notification emails are inherited from
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=magellan-hub-settings' ) ); ?>">Magellan Hub &rarr; Settings</a>
                when left blank below. Page rendering is handled by Magellan Hub.
            </p>
        </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'otg_settings' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">reCAPTCHA v3 Site Key</th>
                    <td><input type="text" name="otg_recaptcha_site_key"
                        value="<?php echo esc_attr( get_option('otg_recaptcha_site_key', '') ); ?>"
                        class="regular-text"
                        <?php if ( $under_hub ) echo 'placeholder="Inherited from Magellan Hub if blank"'; ?>></td>
                </tr>
                <tr>
                    <th scope="row">reCAPTCHA v3 Secret Key</th>
                    <td><input type="password" name="otg_recaptcha_secret_key"
                        value="<?php echo esc_attr( get_option('otg_recaptcha_secret_key', '') ); ?>"
                        class="regular-text"
                        <?php if ( $under_hub ) echo 'placeholder="Inherited from Magellan Hub if blank"'; ?>></td>
                </tr>
                <tr>
                    <th scope="row">Lead Notification Email(s)</th>
                    <td>
                        <input type="text" name="otg_notify_emails"
                            value="<?php echo esc_attr( get_option('otg_notify_emails', '') ); ?>"
                            class="large-text"
                            <?php if ( $under_hub ) echo 'placeholder="Inherited from Magellan Hub if blank"'; else echo 'placeholder="sales@company.com, manager@company.com"'; ?>>
                        <p class="description">
                            One email or multiple separated by commas.
                            <?php if ( $under_hub ) : ?>
                            <br><em>Leave blank to use Magellan Hub's Lead Notification Emails.</em>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Form Page Slug</th>
                    <td>
                        <input type="text" name="otg_form_page_slug"
                            value="<?php echo esc_attr( get_option('otg_form_page_slug', 'outsourcing-technical-guides') ); ?>"
                            class="regular-text">
                        <p class="description">Slug of the WordPress page showing the lead capture form.
                        <?php if ( $under_hub ) echo '<br><em>Must match the project\'s page slug in Magellan Hub.</em>'; ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Download Page Slug</th>
                    <td>
                        <input type="text" name="otg_download_page_slug"
                            value="<?php echo esc_attr( get_option('otg_download_page_slug', 'outsourcing-download-guides') ); ?>"
                            class="regular-text">
                        <p class="description">Slug of the WordPress page showing the guide downloads.
                        <?php if ( $under_hub ) echo '<br><em>Must match the project\'s redirect page slug in Magellan Hub.</em>'; ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php }

/* ═══════════════════════════════════════════════════════════════
   3. REST API ENDPOINTS
   Registered in both modes.
═══════════════════════════════════════════════════════════════ */
add_action( 'rest_api_init', function (): void {
    register_rest_route( 'otg/v1', '/submit', [
        'methods'             => 'POST',
        'callback'            => 'otg_handle_submission',
        'permission_callback' => '__return_true',
        'args' => [
            'first_name'      => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'last_name'       => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'company_name'    => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'work_email'      => [ 'required' => true,  'sanitize_callback' => 'sanitize_email' ],
            'phone_number'    => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'recaptcha_token' => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );

    register_rest_route( 'otg/v1', '/consultation', [
        'methods'             => 'POST',
        'callback'            => 'otg_handle_consultation',
        'permission_callback' => '__return_true',
        'args' => [
            'first_name'   => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'last_name'    => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'company_name' => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'work_email'   => [ 'required' => true,  'sanitize_callback' => 'sanitize_email' ],
            'phone_number' => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'guide_name'   => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
        ],
    ] );

    register_rest_route( 'otg/v1', '/geo', [
        'methods'             => 'GET',
        'callback'            => 'otg_geo_lookup',
        'permission_callback' => '__return_true',
    ] );
} );

function otg_handle_submission( WP_REST_Request $request ): WP_REST_Response {
    otg_start_session();

    $data  = $request->get_params();
    $recap = otg_verify_recaptcha( $data['recaptcha_token'] );
    if ( is_wp_error( $recap ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => $recap->get_error_message() ], 400 );
    }
    if ( ! is_email( $data['work_email'] ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid email address.' ], 400 );
    }

    otg_store_flamingo( $data );
    otg_send_admin_notification( $data );

    $_SESSION['otg_access_token'] = bin2hex( random_bytes( 16 ) );
    $_SESSION['otg_contact'] = [
        'first_name'   => $data['first_name'],
        'last_name'    => $data['last_name'],
        'company_name' => $data['company_name'],
        'work_email'   => $data['work_email'],
        'phone_number' => $data['phone_number'],
    ];

    $slug = get_option( 'otg_download_page_slug', 'outsourcing-download-guides' );
    return new WP_REST_Response( [
        'success'      => true,
        'message'      => 'Submission received.',
        'redirect_url' => home_url( '/' . $slug ),
    ], 200 );
}

function otg_handle_consultation( WP_REST_Request $request ): WP_REST_Response {
    $data = $request->get_params();

    if ( ! is_email( $data['work_email'] ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid email address.' ], 400 );
    }

    $fullname = trim( $data['first_name'] . ' ' . $data['last_name'] );
    $guide    = ! empty( $data['guide_name'] ) ? ' – ' . $data['guide_name'] : '';
    otg_save_to_flamingo(
        $data,
        "Consultation Request – {$fullname} ({$data['company_name']}){$guide}",
        'outsourcing-technical-guides'
    );

    otg_send_consultation( $data );
    otg_send_consultation_confirmation( $data );

    return new WP_REST_Response( [ 'success' => true, 'message' => 'Consultation request sent.' ], 200 );
}

/* ═══════════════════════════════════════════════════════════════
   4. RECAPTCHA v3
   Uses otg_get_setting() — inherits hub keys when blank.
═══════════════════════════════════════════════════════════════ */
function otg_verify_recaptcha( string $token ) {
    $secret = otg_get_setting( 'otg_recaptcha_secret_key' );
    if ( empty( $secret ) ) return true;

    // Reject sentinel tokens that indicate a client-side load failure
    if ( empty( $token ) || in_array( $token, [ 'not-loaded', 'dev-bypass' ], true ) ) {
        return new WP_Error( 'recaptcha_low_score', 'reCAPTCHA score too low. Please try again.' );
    }

    $res = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
        'body' => [ 'secret' => $secret, 'response' => $token ],
    ] );
    if ( is_wp_error( $res ) ) {
        return new WP_Error( 'recaptcha_failed', 'reCAPTCHA check failed.' );
    }
    $body = json_decode( wp_remote_retrieve_body( $res ), true );
    if ( empty( $body['success'] ) || ( isset( $body['score'] ) && $body['score'] < 0.5 ) ) {
        return new WP_Error( 'recaptcha_low_score', 'reCAPTCHA score too low. Please try again.' );
    }
    return true;
}

/**
 * Server-side geo lookup proxy — avoids ad-blocker blocks on direct ipapi.co calls.
 * Returns { country_code: "PH" } on any failure so intl-tel-input always resolves.
 */
function otg_geo_lookup(): WP_REST_Response {
    $res = wp_remote_get( 'https://ipapi.co/json/', [
        'timeout' => 5,
        'headers' => [ 'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) ],
    ] );
    if ( is_wp_error( $res ) ) {
        return new WP_REST_Response( [ 'country_code' => 'PH' ], 200 );
    }
    $body = json_decode( wp_remote_retrieve_body( $res ), true );
    return new WP_REST_Response( [
        'country_code' => strtoupper( $body['country_code'] ?? 'PH' ),
    ], 200 );
}

/* ═══════════════════════════════════════════════════════════════
   5. FLAMINGO
═══════════════════════════════════════════════════════════════ */
function otg_save_to_flamingo( array $d, string $subject, string $channel ): void {
    $email    = strtolower( trim( $d['work_email'] ?? '' ) );
    $fullname = trim( ( $d['first_name'] ?? '' ) . ' ' . ( $d['last_name'] ?? '' ) );

    if ( empty( $email ) ) return;

    if ( class_exists( 'Flamingo_Inbound_Message' ) ) {
        Flamingo_Inbound_Message::add( [
            'channel'    => $channel,
            'subject'    => $subject,
            'from'       => $fullname . ' <' . $email . '>',
            'from_name'  => $fullname,
            'from_email' => $email,
            'fields'     => $d,
            'meta'       => [
                'remote_ip'  => sanitize_text_field( $_SERVER['REMOTE_ADDR']     ?? '' ),
                'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
            ],
        ] );
    }

    if ( class_exists( 'Flamingo_Contact' ) ) {
        $existing = Flamingo_Contact::search_by_email( $email );
        $props    = $existing ? (array) $existing->props : [];

        $props['company'] = $d['company_name'] ?? ( $props['company'] ?? '' );
        $props['phone']   = $d['phone_number'] ?? ( $props['phone']   ?? '' );
        $props['channel'] = $channel;

        Flamingo_Contact::add( [
            'email'          => $email,
            'name'           => $fullname,
            'props'          => $props,
            'last_contacted' => current_time( 'mysql' ),
            'channel'        => $channel,
        ] );
    }
}

function otg_store_flamingo( array $d ): void {
    $fullname = trim( $d['first_name'] . ' ' . $d['last_name'] );
    otg_save_to_flamingo(
        $d,
        "New Guide Request – {$fullname} ({$d['company_name']})",
        'outsourcing-technical-guides'
    );
}

/* ═══════════════════════════════════════════════════════════════
   6. EMAIL NOTIFICATIONS
   Uses otg_get_setting() for notify emails — inherits hub's
   Lead Notification Emails when the plugin-level option is blank.
═══════════════════════════════════════════════════════════════ */
function otg_get_notify_emails(): array {
    $raw    = otg_get_setting( 'otg_notify_emails', get_option( 'admin_email' ) );
    $emails = array_map( 'trim', explode( ',', $raw ) );
    return array_values( array_filter( $emails, 'is_email' ) );
}

function otg_send_admin_notification( array $d ): void {
    $recipients = otg_get_notify_emails();
    if ( empty( $recipients ) ) return;

    $subject = sprintf( '[Magellan Guides] New Lead: %s %s – %s',
        $d['first_name'], $d['last_name'], $d['company_name'] );

    wp_mail(
        $recipients,
        $subject,
        otg_email_admin_notification( $d, $recipients ),
        [ 'Content-Type: text/html; charset=UTF-8' ]
    );
}

function otg_send_confirmation( array $d ): void {
    $subject = 'Thank You – Magellan Solutions Executive Guides';
    wp_mail( $d['work_email'], $subject, otg_email_confirmation( $d ), [ 'Content-Type: text/html; charset=UTF-8' ] );
}

function otg_send_consultation( array $d ): void {
    $recipients = otg_get_notify_emails();
    if ( empty( $recipients ) ) return;

    $subject = sprintf( '[Magellan Guides] Book a Consultation – %s %s (%s)',
        $d['first_name'], $d['last_name'], $d['company_name'] );

    wp_mail( $recipients, $subject, otg_email_consultation( $d ), [ 'Content-Type: text/html; charset=UTF-8' ] );
}

function otg_send_consultation_confirmation( array $d ): void {
    $subject = 'Your Consultation Request – Magellan Solutions';
    wp_mail(
        $d['work_email'],
        $subject,
        otg_email_consultation_confirmation( $d ),
        [ 'Content-Type: text/html; charset=UTF-8' ]
    );
}