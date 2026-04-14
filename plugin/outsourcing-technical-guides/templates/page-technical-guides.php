<?php
/**
 * templates/page-technical-guides.php
 * Standalone HTML — no wp_head(), zero Betheme interference.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$dist      = OTG_PLUGIN_URL . 'dist/';
$assets    = OTG_PLUGIN_URL . 'assets/';
$site_key  = get_option( 'otg_recaptcha_site_key', '' );
$dl_url    = home_url( '/' . get_option( 'otg_download_page_slug', 'outsourcing-download-guides' ) );
$nonce     = wp_create_nonce( 'wp_rest' );
$rest_url  = rest_url( 'otg/v1/submit' );
$bg_url    = $assets . 'background.webp';
$logo_url  = $assets . 'logo.webp';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Executive Guides – <?php bloginfo('name'); ?></title>
        <meta name="robots" content="noindex">

        <!-- Poppins — loaded via <link> so it always works even if @import is blocked -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">

        <!-- Bootstrap 5 -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <!-- intl-tel-input CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.4/build/css/intlTelInput.css">
        <!-- Our CSS -->
        <link rel="stylesheet" href="<?php echo esc_url( $dist . 'css/base.css' ); ?>?v=<?php echo OTG_VERSION; ?>">
        <link rel="stylesheet" href="<?php echo esc_url( $dist . 'css/technical-guides.css' ); ?>?v=<?php echo OTG_VERSION; ?>">

        <!-- WordPress config for JS -->
        <script>
            window.MagellanConfig = {
            ajaxUrl:          <?php echo wp_json_encode( $rest_url ); ?>,
            nonce:            <?php echo wp_json_encode( $nonce ); ?>,
            recaptchaSiteKey: <?php echo wp_json_encode( $site_key ); ?>,
            downloadPage:     <?php echo wp_json_encode( $dl_url ); ?>,
            itiUtilsUrl:      <?php echo wp_json_encode( 'https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.4/build/js/utils.js' ); ?>
            };
        </script>

        <?php if ( $site_key ) : ?>
        <script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr( $site_key ); ?>" async defer></script>
        <?php endif; ?>
    </head>
    <body>

        <div id="landing" class="d-flex flex-column min-vh-100"
            style="background-image: url('<?php echo esc_url( $bg_url ); ?>');">

            <!-- ── NAV ──────────────────────────────────────────────── -->
            <nav class="landing-nav d-flex align-items-center justify-content-between px-4 px-lg-5 py-4 position-relative" style="z-index:2">
            <span class="nav-logo">
                <img src="<?php echo esc_url( $logo_url ); ?>" alt="Magellan Solutions Logo" width="220">
            </span>
            <ul class="nav-social d-flex gap-4 list-unstyled mb-0">
                <li><a href="https://www.facebook.com/magellanbpo"             target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a></li>
                <li><a href="https://www.linkedin.com/company/455507/"          target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a></li>
                <li><a href="https://www.tiktok.com/@magellanbpo?lang=en"       target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a></li>
                <li><a href="https://www.youtube.com/@magellanbpo"              target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a></li>
            </ul>
            </nav>

            <!-- ── MAIN SPLIT ─────────────────────────────────────────── -->
            <main class="mg-page-wrap d-flex flex-column flex-lg-row flex-grow-1">

            <!-- LEFT: HERO -->
            <section class="mg-hero-col d-flex flex-column justify-content-center p-5 col-12 col-lg-6">

                <div class="mg-badge mb-4 mg-fade-1">Confidential Executive Resource</div>

                <h1 class="mg-quote mb-3 mg-fade-2">
                "Outsourcing Isn't Just About Cost Savings —
                It's About Building <em>Resilient, Scalable Operations.</em>"
                </h1>

                <p class="mg-hero-desc mb-4 mg-fade-3">
                Magellan Solutions' technical capabilities guides help decision-makers
                evaluate outsourcing partners with clarity and confidence.
                </p>

                <div class="mg-stats-row d-flex flex-wrap mb-4 mg-fade-4">
                <div class="mg-stat">
                    <div class="mg-stat-num" data-target="116">0</div>
                    <div class="mg-stat-label">SMEs Served</div>
                </div>

                <div class="mg-stat">
                    <div class="mg-stat-num" id="years-counter">0</div>
                    <div class="mg-stat-label">Years in BPO</div>
                </div>

                <div class="mg-stat">
                    <div class="mg-stat-num" data-target="80">0</div>
                    <div class="mg-stat-label">Industries Catered to</div>
                </div>
                </div>
                <div class="d-flex flex-column gap-2 mg-fade-5">
                <div class="mg-feature d-flex align-items-start gap-3 p-3">
                    <div class="mg-feature-icon"><i class="bi bi-headset"></i></div>
                    <div><div class="mg-feature-title">Omnichannel Contact Center Operations</div>
                        <div class="mg-feature-sub">Voice, chat, email, social, SMS &amp; QA processes</div></div>
                </div>
                <div class="mg-feature d-flex align-items-start gap-3 p-3">
                    <div class="mg-feature-icon"><i class="bi bi-gear-wide-connected"></i></div>
                    <div><div class="mg-feature-title">Back-Office &amp; Process Support</div>
                        <div class="mg-feature-sub">Data processing, finance &amp; compliance frameworks</div></div>
                </div>
                <div class="mg-feature d-flex align-items-start gap-3 p-3">
                    <div class="mg-feature-icon"><i class="bi bi-shield-check"></i></div>
                    <div><div class="mg-feature-title">Technical Support &amp; Helpdesk</div>
                        <div class="mg-feature-sub">Escalation workflows, certifications &amp; SLA examples</div></div>
                </div>
                </div>

            </section>

            <!-- RIGHT: FORM -->
            <section class="mg-form-col d-flex align-items-center justify-content-center p-4 p-lg-5 col-12 col-lg-6">
                <div class="mg-form-card p-4 p-sm-5 mg-fade-2">

                <h2 class="mg-form-title mb-1">Access Your <span>Executive Guides</span></h2>
                <p class="mg-form-subtitle mb-4">
                    Complete the form to receive Magellan Solutions' technical capabilities guides.
                </p>

                <!-- method="post" prevents browser falling back to GET if JS fails -->
                <form id="mg-guide-form" method="post" action="#" novalidate autocomplete="off">

                    <div class="row g-3 mb-3">
                    <div class="col-6 first-name-wrap">
                        <label class="mg-label" for="first_name">First Name <span class="mg-req">*</span></label>
                        <input type="text" id="first_name" name="first_name" class="mg-input" placeholder="Jane" required>
                    </div>
                    <div class="col-6 last-name-wrap">
                        <label class="mg-label" for="last_name">Last Name <span class="mg-req">*</span></label>
                        <input type="text" id="last_name" name="last_name" class="mg-input" placeholder="Doe" required>
                    </div>
                    </div>

                    <div class="mb-3 company-wrap">
                    <label class="mg-label" for="company_name">Company Name <span class="mg-req">*</span></label>
                    <input type="text" id="company_name" name="company_name" class="mg-input" placeholder="Acme Corporation" required>
                    </div>

                    <div class="mb-3 email-wrap">
                    <label class="mg-label" for="work_email">Work Email <span class="mg-req">*</span></label>
                    <input type="email" id="work_email" name="work_email" class="mg-input" placeholder="jane@company.com" required>
                    </div>

                    <div class="mb-3">
                    <label class="mg-label" for="phone_number">Phone Number <span class="mg-req">*</span></label>
                    <input type="tel" id="phone_number" name="phone_number" class="mg-input" placeholder="912 345 6789" required>
                    </div>

                    <p class="mg-recaptcha-note mb-3">
                    Protected by reCAPTCHA.
                    <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacy</a> &amp;
                    <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Terms</a> apply.
                    </p>

                    <button type="submit" id="mg-submit-btn"
                            class="mg-btn-submit btn w-100 py-3 d-flex align-items-center justify-content-center gap-2">
                    <span class="mg-btn-label"><i class="bi bi-download me-1"></i>Download the Guides</span>
                    <span class="mg-spinner"></span>
                    </button>

                </form>

                <hr class="mg-divider-line my-3">

                <div class="mg-trust d-flex align-items-start gap-2 p-3">
                    <i class="bi bi-lock-fill mt-1"></i>
                    <p class="mb-0">Your information is secure. Magellan Solutions is trusted by SMEs worldwide.</p>
                </div>

                </div>
            </section>

            </main>

            <footer class="mg-footer text-center py-3" style="position:relative;z-index:2">
            © <span class="mg-year"></span> Magellan Solutions | Confidential Executive Resource
            </footer>

        </div><!-- /#landing -->

        <!-- Scripts at bottom -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- intl-tel-input MUST load before our JS -->
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.4/build/js/intlTelInput.min.js"></script>
        <!-- Our compiled JS -->
        <script src="<?php echo esc_url( $dist . 'js/technical-guides.js' ); ?>?v=<?php echo OTG_VERSION; ?>"></script>

    </body>
</html>
