<?php
/**
 * templates/outsourcing-download-guides.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$hub_mode_early = isset( $mhub_current_project );
$dist     = $hub_mode_early ? ( rtrim( $mhub_dist_url, '/' ) . '/' )   : OTG_PLUGIN_URL . 'dist/';
$assets   = $hub_mode_early ? ( rtrim( $mhub_assets_url, '/' ) . '/' ) : OTG_PLUGIN_URL . 'assets/';
$pdf_base = $hub_mode_early ? ( rtrim( dirname( rtrim( $mhub_dist_url, '/' ) ), '/' ) . '/pdf/' ) : OTG_PLUGIN_URL . 'pdf/';
$form_url = home_url( '/' . get_option( 'otg_form_page_slug', 'outsourcing-technical-guides' ) );
$bg_url   = $assets . 'background.webp';
$logo_url = $assets . 'logo.webp';
$nonce    = wp_create_nonce( 'wp_rest' );
$rest_url = rest_url( 'otg/v1/consultation' );

// Contact data stored in session by otg_handle_submission().
// Safe to read here; the token-based guard has already run in the plugin.
$contact = $_SESSION['otg_contact'] ?? [];

$hub_mode = $hub_mode_early;

/* ── HUB MODE ONLY: enqueue CDN CSS via WordPress ───────── */
if ( $hub_mode ) :
    wp_enqueue_style( 'otg-google-fonts',  'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap', [], null );
    wp_enqueue_style( 'otg-bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',          [], null );
    wp_enqueue_style( 'otg-bi-css',        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css', [], null );
endif;

/* ── STANDALONE ONLY: open full HTML document ──────────── */
if ( ! $hub_mode ) : ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Your Guides – <?php bloginfo('name'); ?></title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo esc_url( $dist . 'css/base.css' ); ?>?v=<?php echo OTG_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo esc_url( $dist . 'css/download-guides.css' ); ?>?v=<?php echo OTG_VERSION; ?>">
<?php endif; ?>

    <!-- JS runtime config — output in both modes -->
    <script>
        window.MagellanConfig = {
            consultationUrl: <?php echo wp_json_encode( $rest_url ); ?>,
            nonce:           <?php echo wp_json_encode( $nonce ); ?>,
            contact: {
                first_name:   <?php echo wp_json_encode( $contact['first_name']   ?? '' ); ?>,
                last_name:    <?php echo wp_json_encode( $contact['last_name']    ?? '' ); ?>,
                company_name: <?php echo wp_json_encode( $contact['company_name'] ?? '' ); ?>,
                work_email:   <?php echo wp_json_encode( $contact['work_email']   ?? '' ); ?>,
                phone_number: <?php echo wp_json_encode( $contact['phone_number'] ?? '' ); ?>
            }
        };
    </script>

<?php if ( ! $hub_mode ) : ?>
</head>
<body>
<?php endif; ?>

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

        <!-- ── DOWNLOAD PAGE ─────────────────────────────────────── -->
        <main class="mg-dl-page d-flex flex-column align-items-center justify-content-center py-5 px-3 flex-grow-1">
            <div id="mg-dots" aria-hidden="true"></div>

            <div class="mg-dl-inner w-100 text-center">

                <div class="mg-check-ring mx-auto mb-4"><i class="bi bi-check-lg"></i></div>
                <div class="mg-tag mb-3"><i class="bi bi-stars me-1"></i>Ready for Download</div>

                <h1 class="mg-dl-heading my-2 mg-fade-2" id="mg-greeting">
                    Your Guides Are <span>Ready.</span>
                </h1>

                <!-- GUIDE CARDS -->
                <div class="row g-4 my-2 mg-fade-3">
                    <div class="col-12 col-md-4">
                        <div class="mg-guide-card h-100 p-4 d-flex flex-column">
                        <div class="mg-card-icon mb-3"><i class="bi bi-headset"></i></div>
                        <h3 class="mg-card-title mb-2">Omnichannel Contact Center Operations</h3>
                        <p class="mg-card-desc flex-grow-1 mb-3">Voice, chat, email, social &amp; SMS workflows and QA frameworks used by 500+ SMEs.</p>
                        <a href="<?php echo esc_url( $pdf_base . 'omnichannel-contact-center.pdf' ); ?>"
                            download="Magellan-Omnichannel-Guide.pdf"
                            class="mg-dl-btn btn w-100 py-2"
                            data-guide-name="Omnichannel Contact Center Operations">
                            <i class="bi bi-file-earmark-pdf"></i> Download PDF
                        </a>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="mg-guide-card h-100 p-4 d-flex flex-column">
                        <div class="mg-card-icon mb-3"><i class="bi bi-gear-wide-connected"></i></div>
                        <h3 class="mg-card-title mb-2">Back-Office &amp; Process Support</h3>
                        <p class="mg-card-desc flex-grow-1 mb-3">Data processing, finance operations &amp; compliance frameworks for growing businesses.</p>
                        <div style="color:#8b0000;font-weight:600;margin-bottom:0.5rem;font-size:.7rem;font-style:italic;">Currently Unavailable</div>
                        <a href="<?php echo esc_url( $pdf_base . 'back-office-process-support.pdf' ); ?>"
                            download="Magellan-BackOffice-Guide.pdf"
                            class="mg-dl-btn btn w-100 py-2"
                            disabled style="pointer-events:none;opacity:0.6;"
                            data-guide-name="Back-Office &amp; Process Support">
                            <i class="bi bi-file-earmark-pdf"></i> Download PDF
                        </a>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="mg-guide-card h-100 p-4 d-flex flex-column">
                        <div class="mg-card-icon mb-3"><i class="bi bi-shield-check"></i></div>
                        <h3 class="mg-card-title mb-2">Technical Support &amp; Helpdesk</h3>
                        <p class="mg-card-desc flex-grow-1 mb-3">Escalation workflows, certifications &amp; real SLA examples from active engagements.</p>
                        <div style="color:#8b0000;font-weight:600;margin-bottom:0.5rem;font-size:.7rem;font-style:italic;">Currently Unavailable</div>
                        <a href="<?php echo esc_url( $pdf_base . 'technical-support-helpdesk.pdf' ); ?>"
                            download="Magellan-Helpdesk-Guide.pdf"
                            class="mg-dl-btn btn w-100 py-2"
                            disabled style="pointer-events:none;opacity:0.6;"
                            data-guide-name="Technical Support &amp; Helpdesk">
                            <i class="bi bi-file-earmark-pdf"></i> Download PDF
                        </a>
                        </div>
                    </div>
                </div>

                <div class="mg-next-panel text-start p-4 my-4 mg-fade-4">
                    <div class="d-flex align-items-start gap-3">
                    <div class="mg-next-icon"><i class="bi bi-calendar2-check"></i></div>
                        <div>
                            <div class="mg-next-title mb-1">Connect With Business Development</div>
                            <div class="mg-next-sub">Schedule a consultation with our team to discuss how outsourcing can solve your specific challenges.</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-start mt-3">
                        <button id="mg-consult-btn" class="mg-dl-btn btn py-2" type="button">
                            <span class="mg-btn-label">Book a Consultation</span>
                            <span class="mg-spinner spinner-border spinner-border-sm ms-2" role="status" style="display:none;">
                                <span class="visually-hidden">Loading...</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="mg-next-panel text-start p-4 mb-4 mg-fade-4">
                    <div class="d-flex align-items-start gap-3">
                    <div class="mg-next-icon"><i class="bi bi-rocket-takeoff"></i></div>
                        <div>
                            <div class="mg-next-title mb-1">Check Your Outsourcing Readiness</div>
                            <div class="mg-next-sub">Take our quick 3-minute scorecard to see if your company is ready to outsource effectively.</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-start mt-3">
                        <a href="https://www.magellan-solutions.com/outsourcing-scorecard" class="mg-dl-btn btn py-2" target="_blank" style="width:auto;">
                            Start Assessment
                        </a>
                    </div>
                </div>

                <div class="mg-dl-cta-bar mg-fade-5">
                <a href="<?php echo esc_url( $form_url ); ?>" class="mg-back-link d-inline-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i> Back to Guides Form
                </a>
                </div>

            </div>
        </main>

        <footer class="mg-footer text-center py-3" style="position:relative;z-index:2">
            © <span class="mg-year"></span> Magellan Solutions | Confidential Executive Resource
        </footer>

    </div><!-- /#landing -->

    <div id="mg-toast" aria-live="polite"></div>

<?php if ( ! $hub_mode ) : ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo esc_url( $dist . 'js/download-guides.js' ); ?>?v=<?php echo OTG_VERSION; ?>"></script>
</body>
</html>
<?php else :
    $pfx = 'otg-dl-vendor';
    wp_enqueue_script( $pfx . '-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [], null, true );
    wp_enqueue_script( 'otg-download', $dist . 'js/download-guides.js', [ $pfx . '-bootstrap' ], defined('OTG_VERSION') ? OTG_VERSION : MHUB_VERSION, true );
endif; ?>