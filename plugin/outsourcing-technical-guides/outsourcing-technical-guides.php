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

/* ═══════════════════════════════════════════════════════════════
   SESSION BOOTSTRAP
   Start session early enough for all hooks to use it.
   Only starts once — safe to call multiple times.
═══════════════════════════════════════════════════════════════ */
add_action( 'init', 'otg_start_session', 1 );

function otg_start_session() {
    if ( session_status() === PHP_SESSION_NONE ) {
        session_start();
    }
}

/* ═══════════════════════════════════════════════════════════════
   1. FULL DOCUMENT OVERRIDE  (fixes Betheme / any theme conflict)
   ─────────────────────────────────────────────────────────────
   WHY wp_head() BREAKS THINGS:
   Even when you bypass the theme template with template_include,
   calling wp_head() still fires every action hooked to it —
   including Betheme's wp_enqueue_scripts which loads monstrous
   CSS files (be_style.css, mfn-opts.css, etc) that override
   everything we write.

   THE FIX:
   On our two pages we intercept ALL output via output buffering,
   discard it, and print our own complete HTML document with only
   the assets we need. No wp_head(), no Betheme, no conflicts.
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
        // ── ACCESS GUARD ──────────────────────────────────────
        // Only allow access if a valid token was written to the
        // session by the REST submission handler. Anyone who hits
        // this URL directly (no prior form submission) gets
        // redirected back to the form page.
        if ( empty( $_SESSION['otg_access_token'] ) ) {
            wp_safe_redirect( home_url( '/' . $form_slug ) );
            exit;
        }

        // Consume the token — one-time use, prevents reload-sharing.
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
                            placeholder="sales@company.com, manager@company.com, ceo@company.com">
                        <p class="description">
                            Enter one email <strong>or multiple emails separated by commas</strong>.<br>
                            Example: <code>sales@company.com, manager@company.com</code><br>
                            All addresses will receive the lead notification simultaneously.
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
} );

function otg_handle_submission( WP_REST_Request $request ) {
    // Session must be active for the REST request too.
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
    otg_send_confirmation( $data );

    // ── WRITE ACCESS TOKEN ─────────────────────────────────────
    // A random token in the session tells the download page that
    // this visitor just completed a valid form submission.
    // The token is consumed (deleted) on first use — see the
    // access guard in otg_maybe_render_page().
    $_SESSION['otg_access_token'] = bin2hex( random_bytes( 16 ) );

    $slug = get_option( 'otg_download_page_slug', 'outsourcing-download-guides' );
    return new WP_REST_Response( [
        'success'      => true,
        'message'      => 'Submission received.',
        'redirect_url' => home_url( '/' . $slug ),
    ], 200 );
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
   5. FLAMINGO STORAGE
═══════════════════════════════════════════════════════════════ */
function otg_store_flamingo( array $d ) {
    if ( ! class_exists( 'Flamingo_Inbound_Message' ) ) return;

    Flamingo_Inbound_Message::add( [
        'channel'    => 'outsourcing-technical-guides',
        'subject'    => sprintf( 'New Guide Request - %s %s (%s)', $d['first_name'], $d['last_name'], $d['company_name'] ),
        'from'       => sprintf( '%s %s <%s>', $d['first_name'], $d['last_name'], $d['work_email'] ),
        'from_name'  => trim( $d['first_name'] . ' ' . $d['last_name'] ),
        'from_email' => $d['work_email'],
        'fields'     => $d,
        'meta'       => [ 'remote_ip' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ) ],
        'timestamp'  => time(),
    ] );
}

/* ═══════════════════════════════════════════════════════════════
   6. ADMIN NOTIFICATION  (supports multiple comma-separated emails)
═══════════════════════════════════════════════════════════════ */
function otg_get_notify_emails(): array {
    $raw     = get_option( 'otg_notify_emails', get_option('admin_email') );
    $emails  = array_map( 'trim', explode( ',', $raw ) );
    return array_values( array_filter( $emails, 'is_email' ) );
}

function otg_send_admin_notification( array $d ) {
    $recipients = otg_get_notify_emails();
    if ( empty( $recipients ) ) return;

    $subject = sprintf( '[Magellan Guides] New Lead: %s %s – %s',
        $d['first_name'], $d['last_name'], $d['company_name'] );

    $body = '
    <div style="font-family:sans-serif;max-width:520px;color:#1a1a1a">
      <h2 style="color:#0a1a5c;margin-bottom:16px">New Executive Guide Request</h2>
      <table style="border-collapse:collapse;width:100%;font-size:14px">
        <tr style="background:#f9f9f9">
          <td style="padding:10px 12px;border:1px solid #e5e5e5;font-weight:600;width:120px">Name</td>
          <td style="padding:10px 12px;border:1px solid #e5e5e5">'    . esc_html( $d['first_name'] . ' ' . $d['last_name'] ) . '</td>
        </tr>
        <tr>
          <td style="padding:10px 12px;border:1px solid #e5e5e5;font-weight:600">Company</td>
          <td style="padding:10px 12px;border:1px solid #e5e5e5">'    . esc_html( $d['company_name'] ) . '</td>
        </tr>
        <tr style="background:#f9f9f9">
          <td style="padding:10px 12px;border:1px solid #e5e5e5;font-weight:600">Email</td>
          <td style="padding:10px 12px;border:1px solid #e5e5e5">'    . esc_html( $d['work_email'] )   . '</td>
        </tr>
        <tr>
          <td style="padding:10px 12px;border:1px solid #e5e5e5;font-weight:600">Phone</td>
          <td style="padding:10px 12px;border:1px solid #e5e5e5">'    . esc_html( $d['phone_number'] ) . '</td>
        </tr>
      </table>
      <p style="margin-top:20px;font-size:12px;color:#888">
        Submitted: ' . current_time('mysql') . '<br>
        Notified: ' . implode( ', ', array_map( 'esc_html', $recipients ) ) . '
      </p>
    </div>';

    // Send to ALL recipients in one call — wp_mail() accepts an array
    wp_mail( $recipients, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
}

/* ═══════════════════════════════════════════════════════════════
   7. CONFIRMATION EMAIL TO SUBMITTER
═══════════════════════════════════════════════════════════════ */
function otg_send_confirmation( array $d ) {
    $download_url = home_url( '/' . get_option( 'otg_download_page_slug', 'outsourcing-download-guides' ) );
    $subject      = 'Your Magellan Solutions Executive Guides Are Ready';
    $body = '
    <div style="font-family:sans-serif;max-width:520px;color:#1a1a1a">
      <h2 style="color:#0a1a5c">Hi ' . esc_html( $d['first_name'] ) . ',</h2>
      <p>Thank you for requesting Magellan Solutions\' Executive Guides. Your downloads are ready:</p>
      <p style="margin:24px 0">
        <a href="' . esc_url( $download_url ) . '"
           style="background:#38d9f5;color:#040d2b;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px">
          Access Your Guides →
        </a>
      </p>
      <p style="margin-top:32px">Best regards,<br><strong>Magellan Solutions Team</strong></p>
      <hr style="border:none;border-top:1px solid #eee;margin-top:32px">
      <p style="font-size:11px;color:#aaa">
        This email was sent to ' . esc_html( $d['work_email'] ) . '
      </p>
    </div>';

    wp_mail( $d['work_email'], $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
}
