<?php
/**
 * Plugin Name:  Outsourcing Technical Guides
 * Plugin URI:   https://magellan-solutions.com
 * Description:  Full-page Executive Guides flow. Completely overrides Betheme
 *               (and any other theme) by outputting its own HTML document —
 *               zero theme CSS interference. Integrates WP Mail SMTP,
 *               reCAPTCHA v3, and Flamingo.
 * Version:      1.0.1
 * Author:       Magellan Solutions
 * License:      GPL-2.0+
 * Text Domain:  outsourcing-technical-guides
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OTG_VERSION',    '1.0.1' );
define( 'OTG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OTG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OTG_DIST_URL',   OTG_PLUGIN_URL . 'dist/' );
define( 'OTG_PDF_URL',    OTG_PLUGIN_URL . 'pdf/' );

/* Email template builders live in a dedicated file */
require_once OTG_PLUGIN_DIR . 'php/email-templates.php';

/* ═══════════════════════════════════════════════════════════════
   SESSION BOOTSTRAP
═══════════════════════════════════════════════════════════════ */
add_action( 'init', 'otg_start_session', 1 );

function otg_start_session() {
    if ( session_status() === PHP_SESSION_NONE ) {
        session_start();
    }
}

/* ═══════════════════════════════════════════════════════════════
   1. FULL DOCUMENT OVERRIDE
═══════════════════════════════════════════════════════════════ */
add_action( 'template_redirect', 'otg_maybe_render_page', 1 );

function otg_maybe_render_page() {
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
═══════════════════════════════════════════════════════════════ */
add_action( 'admin_menu', function () {
    add_options_page(
        'Outsourcing Guides Settings',
        'Outsourcing Guides',
        'manage_options',
        'outsourcing-technical-guides',
        'otg_settings_page'
    );
} );

add_action( 'admin_init', function () {
    register_setting( 'otg_settings', 'otg_recaptcha_site_key' );
    register_setting( 'otg_settings', 'otg_recaptcha_secret_key' );
    register_setting( 'otg_settings', 'otg_notify_emails' );   // comma-separated
    register_setting( 'otg_settings', 'otg_form_page_slug' );
    register_setting( 'otg_settings', 'otg_download_page_slug' );
} );

function otg_settings_page() { ?>
    <div class="wrap">
        <h1>Outsourcing Technical Guides – Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'otg_settings' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">reCAPTCHA v3 Site Key</th>
                    <td><input type="text" name="otg_recaptcha_site_key"
                        value="<?php echo esc_attr( get_option('otg_recaptcha_site_key') ); ?>"
                        class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">reCAPTCHA v3 Secret Key</th>
                    <td><input type="text" name="otg_recaptcha_secret_key"
                        value="<?php echo esc_attr( get_option('otg_recaptcha_secret_key') ); ?>"
                        class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">Lead Notification Email(s)</th>
                    <td>
                        <input type="text" name="otg_notify_emails"
                            value="<?php echo esc_attr( get_option('otg_notify_emails', get_option('admin_email')) ); ?>"
                            class="large-text"
                            placeholder="sales@company.com, manager@company.com">
                        <p class="description">
                            Enter one email <strong>or multiple emails separated by commas</strong>.<br>
                            All addresses receive lead notifications and consultation requests.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Form Page Slug</th>
                    <td>
                        <input type="text" name="otg_form_page_slug"
                            value="<?php echo esc_attr( get_option('otg_form_page_slug', 'outsourcing-technical-guides') ); ?>"
                            class="regular-text">
                        <p class="description">Slug of the WordPress page showing the lead capture form.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Download Page Slug</th>
                    <td>
                        <input type="text" name="otg_download_page_slug"
                            value="<?php echo esc_attr( get_option('otg_download_page_slug', 'outsourcing-download-guides') ); ?>"
                            class="regular-text">
                        <p class="description">Slug of the WordPress page showing the guide downloads.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php }

/* ═══════════════════════════════════════════════════════════════
   3. REST API ENDPOINT  /wp-json/otg/v1/submit
═══════════════════════════════════════════════════════════════ */
add_action( 'rest_api_init', function () {
    register_rest_route( 'otg/v1', '/submit', [
        'methods'             => 'POST',
        'callback'            => 'otg_handle_submission',
        'permission_callback' => '__return_true',
        'args' => [
            'first_name'      => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'last_name'       => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'company_name'    => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'work_email'      => [ 'required' => true, 'sanitize_callback' => 'sanitize_email' ],
            'phone_number'    => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'recaptcha_token' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );

    /* ── CONSULTATION  /wp-json/otg/v1/consultation ─────────── */
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
            // guide_name is the label of the card button the user clicked
            'guide_name'   => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
        ],
    ] );

} );

function otg_handle_submission( WP_REST_Request $request ) {
    otg_start_session();

    $data = $request->get_params();

    $recap = otg_verify_recaptcha( $data['recaptcha_token'] );
    if ( is_wp_error( $recap ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => $recap->get_error_message() ], 400 );
    }
    if ( ! is_email( $data['work_email'] ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid email address.' ], 400 );
    }

    otg_store_flamingo( $data );
    otg_send_admin_notification( $data );
    
    // Optional: send confirmation email to user immediately on form submit
    // otg_send_confirmation( $data );

    // Grant access to the download page (one-time token)
    $_SESSION['otg_access_token'] = bin2hex( random_bytes( 16 ) );

    // Persist contact data so the download page can attach it to
    // a consultation request without re-prompting the user.
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

function otg_handle_consultation( WP_REST_Request $request ) {
    $data = $request->get_params();

    if ( ! is_email( $data['work_email'] ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid email address.' ], 400 );
    }

    // Save consultation request to Flamingo (inbound message + address book)
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
═══════════════════════════════════════════════════════════════ */
function otg_verify_recaptcha( string $token ) {
    $secret = get_option( 'otg_recaptcha_secret_key', '' );
    if ( empty( $secret ) ) return true; // Skip in dev if not configured

    $res = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
        'body' => [ 'secret' => $secret, 'response' => $token ],
    ] );
    if ( is_wp_error( $res ) ) {
        return new WP_Error( 'recaptcha_failed', 'reCAPTCHA check failed.' );
    }
    $body = json_decode( wp_remote_retrieve_body( $res ), true );
    if ( empty( $body['success'] ) || ( isset( $body['score'] ) && $body['score'] < 0.5 ) ) {
        return new WP_Error( 'recaptcha_low_score', 'reCAPTCHA validation failed. Please try again.' );
    }
    return true;
}

/* ═══════════════════════════════════════════════════════════════
   5. FLAMINGO — INBOUND MESSAGE + ADDRESS BOOK
═══════════════════════════════════════════════════════════════ */

/**
 * Save an inbound message + upsert address-book contact.
 * Shared by both the guide-request and consultation flows.
 *
 * Flamingo_Contact API (from source):
 *   - search_by_email( $email )   → object|null  (queries _email meta directly)
 *   - add( ['email','name','props','last_contacted'] )  → upserts and saves
 *   - props[]  stores arbitrary extra fields (company, phone, etc.)
 *   - NO find_or_create(), NO meta/channels properties
 */
function otg_save_to_flamingo( array $d, string $subject, string $channel ): void {

    // Normalize values
    $email    = strtolower( trim( $d['work_email'] ?? '' ) );
    $fullname = trim( ($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '') );

    if ( empty( $email ) ) {
        return; // Cannot proceed without email
    }

    // ─────────────────────────────────────────
    // 1. Save Inbound Message (FIXED - uses Flamingo API)
    // ─────────────────────────────────────────
    if ( class_exists( 'Flamingo_Inbound_Message' ) ) {

        Flamingo_Inbound_Message::add( [
            'channel'    => $channel,
            'subject'    => $subject,
            'from'       => $fullname . ' <' . $email . '>',
            'from_name'  => $fullname,
            'from_email' => $email,
            'fields'     => $d,
            'meta'       => [
                'remote_ip' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
                'user_agent'=> sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
            ],
        ] );
    }

    // ─────────────────────────────────────────
    // 2. Save / Update Address Book
    // ─────────────────────────────────────────
    if ( class_exists( 'Flamingo_Contact' ) ) {

        // Get existing contact (if any)
        $existing = Flamingo_Contact::search_by_email( $email );
        $props    = $existing ? (array) $existing->props : [];

        // Preserve old props, update only needed fields
        $props['company'] = $d['company_name'] ?? ($props['company'] ?? '');
        $props['phone']   = $d['phone_number'] ?? ($props['phone'] ?? '');
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

/** Called on guide-request form submit */
function otg_store_flamingo( array $d ): void {
    $fullname = trim( $d['first_name'] . ' ' . $d['last_name'] );
    otg_save_to_flamingo(
        $d,
        "New Guide Request – {$fullname} ({$d['company_name']})",
        'outsourcing-technical-guides'
    );
}

/* ═══════════════════════════════════════════════════════════════
   6. ADMIN NOTIFICATION EMAIL (on form submit)
═══════════════════════════════════════════════════════════════ */
function otg_get_notify_emails(): array {
    $raw    = get_option( 'otg_notify_emails', get_option('admin_email') );
    $emails = array_map( 'trim', explode( ',', $raw ) );
    return array_values( array_filter( $emails, 'is_email' ) );
}

function otg_send_admin_notification( array $d ) {
    $recipients = otg_get_notify_emails();
    if ( empty( $recipients ) ) return;

    $subject = sprintf( '[Magellan Guides] New Lead: %s %s – %s',
        $d['first_name'], $d['last_name'], $d['company_name'] );

    wp_mail( $recipients, $subject, otg_email_admin_notification( $d, $recipients ), [ 'Content-Type: text/html; charset=UTF-8' ] );
}

/* ═══════════════════════════════════════════════════════════════
   7. CONFIRMATION EMAIL TO SUBMITTER
═══════════════════════════════════════════════════════════════ */
function otg_send_confirmation( array $d ) {
    $subject = 'Thank You – Magellan Solutions Executive Guides';

    wp_mail( $d['work_email'], $subject, otg_email_confirmation( $d ), [ 'Content-Type: text/html; charset=UTF-8' ] );
}

/* ═══════════════════════════════════════════════════════════════
   8. CONSULTATION EMAIL
   Fires when the user clicks "Book a Consultation" on the
   download page. Sends to all Lead Notification Email(s).
   Includes the contact's details and the guide they downloaded.
═══════════════════════════════════════════════════════════════ */
function otg_send_consultation( array $d ) {
    $recipients = otg_get_notify_emails();
    if ( empty( $recipients ) ) return;

    $subject = sprintf( '[Magellan Guides] Book a Consultation – %s %s (%s)',
        $d['first_name'], $d['last_name'], $d['company_name'] );

    wp_mail( $recipients, $subject, otg_email_consultation( $d ), [ 'Content-Type: text/html; charset=UTF-8' ] );
}
/* ═══════════════════════════════════════════════════════════════
   9. CONSULTATION CONFIRMATION EMAIL TO SUBMITTER
   Fires alongside the admin consultation email so the visitor
   knows their request was received.
═══════════════════════════════════════════════════════════════ */
function otg_send_consultation_confirmation( array $d ) {
    $subject = 'Your Consultation Request – Magellan Solutions';

    wp_mail(
        $d['work_email'],
        $subject,
        otg_email_consultation_confirmation( $d ),
        [ 'Content-Type: text/html; charset=UTF-8' ]
    );
}
