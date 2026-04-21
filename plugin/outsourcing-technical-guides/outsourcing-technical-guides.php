<?php
/**
 * Plugin Name:  Outsourcing Technical Guides
 * Plugin URI:   https://magellan-solutions.com
 * Description:  Full-page Executive Guides flow with reCAPTCHA v3 and Flamingo.
 *               Works standalone OR as a Magellan Hub project (auto-detected).
 *               Completely overrides the active theme — zero theme CSS interference.
 * Version:      1.1.3
 * Author:       Magellan Solutions
 * License:      GPL-2.0+
 * Text Domain:  outsourcing-technical-guides
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OTG_VERSION',    '1.1.3' );
define( 'OTG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OTG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OTG_DIST_URL',   OTG_PLUGIN_URL . 'dist/' );
define( 'OTG_PDF_URL',    OTG_PLUGIN_URL . 'pdf/' );

require_once OTG_PLUGIN_DIR . 'php/email-templates.php';

/* ═══════════════════════════════════════════════════════════════
   DUAL-MODE DETECTION
═══════════════════════════════════════════════════════════════ */

function otg_running_under_hub(): bool {
    if ( ! function_exists( 'mhub_get_project_by_slug' ) ) return false;
    $slug    = get_option( 'otg_form_page_slug', 'outsourcing-technical-guides' );
    $project = mhub_get_project_by_slug( $slug );
    return ( $project && $project->status === 'active' );
}

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
   ─────────────────────────────────────────────────────────────
   FIX: Sessions must be started as early as possible (init priority 1)
   so that:
     a) The REST endpoint handler (otg_handle_submission) can write to
        $_SESSION after form submit.
     b) The template_redirect handler (otg_maybe_render_page) can read
        $_SESSION to validate access to the download page.

   WordPress REST API requests do NOT start a session automatically.
   Without an explicit session_start() before the REST callback runs,
   any writes to $_SESSION are silently discarded, the access token is
   never stored, and the redirect page blocks every visitor.
═══════════════════════════════════════════════════════════════ */
add_action( 'init', 'otg_start_session', 1 );

function otg_start_session(): void {
    // Only start a new session if one isn't already active.
    // Avoids "headers already sent" warnings when other plugins also
    // call session_start().
    if ( session_status() === PHP_SESSION_NONE ) {

        // ── Cookie lifetime settings ──────────────────────────────────────
        // Default PHP session cookies expire when the browser closes.
        // Set a 30-minute cookie lifetime so the token survives a short
        // browser pause between form submit and clicking the download links.
        // This matches WordPress's own nonce lifetime.
        $lifetime = 30 * MINUTE_IN_SECONDS; // 1800 seconds

        session_set_cookie_params( [
            'lifetime' => $lifetime,
            'path'     => COOKIEPATH  ?: '/',
            'domain'   => COOKIE_DOMAIN ?: '',
            // Secure + SameSite=Lax: required for cross-page navigation on
            // HTTPS sites; prevents the cookie being stripped on redirects.
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ] );

        session_start();
    }
}

/* ═══════════════════════════════════════════════════════════════
   1. FULL DOCUMENT OVERRIDE  (standalone mode only)
═══════════════════════════════════════════════════════════════ */
add_action( 'template_redirect', 'otg_maybe_render_page', 1 );

function otg_maybe_render_page(): void {
    if ( otg_running_under_hub() ) return;

    $form_slug     = get_option( 'otg_form_page_slug',     'outsourcing-technical-guides' );
    $download_slug = get_option( 'otg_download_page_slug', 'outsourcing-download-guides' );

    // ── Form page ─────────────────────────────────────────────────────────
    if ( is_page( $form_slug ) ) {
        while ( ob_get_level() ) ob_end_clean();
        include OTG_PLUGIN_DIR . 'templates/page-technical-guides.php';
        exit;
    }

    // ── Download page ─────────────────────────────────────────────────────
    // FIX: Session guard moved here (from inside the template) so that the
    // redirect to the form page is handled cleanly by PHP before any output
    // is sent. The original template-level guard was unreliable because:
    //   1. When ob_get_level() > 0, WordPress may have already buffered some
    //      output, making wp_safe_redirect() fire "headers already sent".
    //   2. The template's guard also consumed the token unconditionally —
    //      meaning a page refresh after a valid submit would block access
    //      (token already consumed on first load).
    //
    // New flow:
    //   a) User submits form → PHP writes otg_access_token to session.
    //   b) JS redirects browser to /outsourcing-download-guides.
    //   c) This handler checks the token. If missing → redirect to form.
    //   d) Token is consumed HERE (single use) so refreshing the page
    //      after the initial load redirects back to the form, preventing
    //      bookmarkable/shareable access to the download page.
    if ( is_page( $download_slug ) ) {
        otg_start_session(); // ensure session is available

        if ( empty( $_SESSION['otg_access_token'] ) ) {
            // No valid token — redirect to the form page.
            wp_safe_redirect( home_url( '/' . $form_slug ) );
            exit;
        }

        // Consume the token (single-use).
        unset( $_SESSION['otg_access_token'] );

        // Persist contact data for personalised greeting on the download page.
        // (otg_contact is set by the submission handler; it is NOT consumed here
        // so it remains available for the consultation endpoint.)

        while ( ob_get_level() ) ob_end_clean();
        include OTG_PLUGIN_DIR . 'templates/page-download-guides.php';
        exit;
    }
}


/* ═══════════════════════════════════════════════════════════════
   2. SETTINGS PAGE
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
                when left blank below.
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
                        <?php if ( $under_hub ) echo 'placeholder="Inherited from Magellan Hub if blank"'; ?>>
                        <p class="description">Must be registered for: <strong><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></strong></p>
                    </td>
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
   3. REST API ENDPOINTS
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


/* ═══════════════════════════════════════════════════════════════
   4. SUBMISSION HANDLER
   ─────────────────────────────────────────────────────────────
   FIX: The original handler called otg_start_session() inline but by
   that point WordPress REST API bootstrapping may have already sent
   response headers, making it impossible to set a session cookie.

   The session is now started at init priority 1 (see otg_start_session
   hooked above) so the PHPSESSID cookie is set in the very first
   response headers, long before the REST API runs.

   The fetch in api.js uses `credentials: 'same-origin'` which ensures
   the browser sends the PHPSESSID cookie with the POST request, so PHP
   can write otg_access_token to the correct session.

   FIX: redirect_url was constructed from the option but the JS was
   receiving it correctly. The real failure was that $_SESSION writes
   were being lost because session_start() was called too late (after
   headers sent). Fixing the session bootstrap above resolves this.
═══════════════════════════════════════════════════════════════ */
function otg_handle_submission( WP_REST_Request $request ): WP_REST_Response {
    // Session must already be started by the init hook.
    // This is a safety call in case something skipped the hook.
    otg_start_session();

    $data  = $request->get_params();
    $recap = otg_verify_recaptcha( $data['recaptcha_token'] );
    if ( is_wp_error( $recap ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => $recap->get_error_message() ], 400 );
    }
    if ( ! is_email( $data['work_email'] ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid email address.' ], 400 );
    }

    // ── Flamingo & admin notification ─────────────────────────────────────
    otg_store_flamingo( $data );
    otg_send_admin_notification( $data );
    // NOTE: User confirmation email is intentionally not sent here.
    // The user receives the PDF/guides by downloading them directly.
    // A confirmation email can be added by calling otg_send_confirmation($data).

    // ── Write session token ───────────────────────────────────────────────
    // This token is checked by otg_maybe_render_page() when the browser
    // hits the download page after the JS redirect.
    $_SESSION['otg_access_token'] = bin2hex( random_bytes( 16 ) );

    // Store contact data for personalised greeting and consultation endpoint.
    $_SESSION['otg_contact'] = [
        'first_name'   => $data['first_name'],
        'last_name'    => $data['last_name'],
        'company_name' => $data['company_name'],
        'work_email'   => $data['work_email'],
        'phone_number' => $data['phone_number'],
    ];

    // ── Build redirect URL ────────────────────────────────────────────────
    $slug         = get_option( 'otg_download_page_slug', 'outsourcing-download-guides' );
    $redirect_url = home_url( '/' . $slug );

    return new WP_REST_Response( [
        'success'      => true,
        'message'      => 'Submission received.',
        'redirect_url' => $redirect_url,
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
   5. RECAPTCHA v3
═══════════════════════════════════════════════════════════════ */
function otg_verify_recaptcha( string $token ) {
    $secret = otg_get_setting( 'otg_recaptcha_secret_key' );
    if ( empty( $secret ) ) return true;

    // Dev bypass — skip verification.
    if ( $token === 'dev-bypass' ) return true;

    // Script failed to load on the client.
    if ( empty( $token ) || $token === 'not-loaded' ) {
        return new WP_Error(
            'recaptcha_not_loaded',
            'Security check could not complete. Please disable any ad blockers or browser extensions and try again.'
        );
    }

    $res = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
        'body'    => [ 'secret' => $secret, 'response' => $token ],
        'timeout' => 10,
    ] );

    if ( is_wp_error( $res ) ) {
        // Network failure — allow through rather than blocking legit users.
        error_log( '[OTG] reCAPTCHA remote request failed: ' . $res->get_error_message() );
        return true;
    }

    $body = json_decode( wp_remote_retrieve_body( $res ), true );

    if ( empty( $body['success'] ) ) {
        $error_codes = implode( ', ', (array) ( $body['error-codes'] ?? [] ) );
        error_log( '[OTG] reCAPTCHA token invalid. Error codes: ' . $error_codes );
        return new WP_Error( 'recaptcha_invalid', 'Security verification failed. Please refresh and try again.' );
    }

    // Threshold lowered to 0.3 — see OSC plugin comment for rationale.
    $threshold = apply_filters( 'otg_recaptcha_score_threshold', 0.3 );

    if ( isset( $body['score'] ) && (float) $body['score'] < $threshold ) {
        error_log( sprintf(
            '[OTG] reCAPTCHA score too low: %.2f (threshold: %.2f)',
            $body['score'], $threshold
        ) );
        return new WP_Error( 'recaptcha_low_score', 'reCAPTCHA score too low. Please try again.' );
    }

    return true;
}

/**
 * Server-side geo lookup proxy.
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
   6. FLAMINGO
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
   7. EMAIL NOTIFICATIONS
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