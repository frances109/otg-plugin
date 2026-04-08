<?php
/**
 * email-templates.php
 * Builds styled HTML email bodies for all outgoing plugin emails.
 * Required by outsourcing-technical-guides.php via:
 *   require_once OTG_PLUGIN_DIR . 'php/email-templates.php';
 *
 * Public functions:
 *   otg_email_confirmation( array $d )       → string  (to submitter)
 *   otg_email_admin_notification( array $d, array $recipients ) → string
 *   otg_email_consultation( array $d )       → string  (to lead emails)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ─────────────────────────────────────────────────────────────
   SHARED WRAPPER
   Wraps any email body in a consistent branded shell.
   $header_label  — small pill text above the logo area
   $content       — the inner HTML unique to each email type
───────────────────────────────────────────────────────────── */
function otg_email_wrap( string $header_label, string $content ): string {
    $logo_url = OTG_PLUGIN_URL . '../assets/logo-email.png';

    return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
</head>
<body style="margin:0;padding:0;background:#f0f2f8;font-family:Arial,Helvetica,sans-serif;">

  <!-- OUTER WRAPPER -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0"
         style="background:#f0f2f8;padding:40px 16px;">
    <tr>
      <td align="center">

        <!-- CARD -->
        <table width="100%" cellpadding="0" cellspacing="0" border="0"
               style="max-width:560px;background:#ffffff;border-radius:12px;
                      overflow:hidden;box-shadow:0 4px 24px rgba(4,13,43,.12);">

          <!-- HEADER BAND -->
          <tr>
            <td style="background:#040d2b;padding:32px 40px 28px;text-align:center;">
              <!-- Logo -->
              <img src="' . esc_url( $logo_url ) . '"
                   alt="Magellan Solutions" width="240" height="55"
                   style="display:block;margin:0 auto 18px;width:240px;height:auto;border:0;">
              <!-- Label pill -->
              <span style="display:inline-block;padding:4px 16px;
                           background:rgba(56,217,245,.15);
                           border:1px solid rgba(56,217,245,.40);
                           border-radius:100px;
                           font-size:10px;font-weight:700;
                           letter-spacing:.14em;text-transform:uppercase;
                           color:#38d9f5;">
                ' . esc_html( $header_label ) . '
              </span>
            </td>
          </tr>

          <!-- BODY -->
          <tr>
            <td style="padding:36px 40px 32px;">
              ' . $content . '
            </td>
          </tr>

          <!-- FOOTER BAND -->
          <tr>
            <td style="background:#f7f8fc;border-top:1px solid #e8eaef;
                       padding:20px 40px;text-align:center;">
              <p style="margin:0 0 6px;font-size:11px;color:#9a9fb5;line-height:1.6;">
                © ' . gmdate('Y') . ' Magellan Solutions &nbsp;|&nbsp;
                Confidential Executive Resource
              </p>
              <p style="margin:0;font-size:11px;color:#b8bdd4;">
                <a href="https://www.magellan-solutions.com"
                   style="color:#38d9f5;text-decoration:none;">
                  www.magellan-solutions.com
                </a>
              </p>
            </td>
          </tr>

        </table>
        <!-- /CARD -->

      </td>
    </tr>
  </table>
  <!-- /OUTER WRAPPER -->

</body>
</html>';
}

/* ─────────────────────────────────────────────────────────────
   SHARED: contact detail rows used in admin + consultation emails
───────────────────────────────────────────────────────────── */
function otg_email_contact_rows( array $d, string $extra_row = '' ): string {
    $rows = [
        [ 'Name',    esc_html( trim( $d['first_name'] . ' ' . $d['last_name'] ) ) ],
        [ 'Company', esc_html( $d['company_name'] ) ],
        [ 'Email',   esc_html( $d['work_email'] ) ],
        [ 'Phone',   esc_html( $d['phone_number'] ) ],
    ];

    $html = '<table width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="border-collapse:collapse;font-size:14px;color:#1a1a1a;">';

    foreach ( $rows as $i => $row ) {
        $bg = ( $i % 2 === 0 ) ? '#f7f8fc' : '#ffffff';
        $html .= '
        <tr style="background:' . $bg . ';">
          <td style="padding:11px 14px;border:1px solid #e8eaef;
                     font-weight:700;width:130px;color:#040d2b;font-size:13px;">
            ' . esc_html( $row[0] ) . '
          </td>
          <td style="padding:11px 14px;border:1px solid #e8eaef;color:#333;">
            ' . $row[1] . '
          </td>
        </tr>';
    }

    if ( $extra_row ) {
        $html .= $extra_row;
    }

    $html .= '</table>';
    return $html;
}

/* ─────────────────────────────────────────────────────────────
   1. CONFIRMATION EMAIL  →  sent to the form submitter
      Content: thank-you only, no download link
───────────────────────────────────────────────────────────── */
function otg_email_confirmation( array $d ): string {

    $content = '
      <!-- Greeting -->
      <h1 style="margin:0 0 8px;font-size:22px;font-weight:700;
                 color:#040d2b;line-height:1.25;">
        Hi ' . esc_html( $d['first_name'] ) . ',
      </h1>
      <p style="margin:0 0 24px;font-size:15px;color:#444;line-height:1.7;">
        Thank you for your interest in Magellan Solutions&rsquo; Executive Guides.
        Our team will be in touch with you shortly.
      </p>

      <!-- Divider -->
      <hr style="border:none;border-top:1px solid #e8eaef;margin:0 0 24px;">

      <!-- What to expect -->
      <p style="margin:0 0 14px;font-size:13px;font-weight:700;
                color:#040d2b;letter-spacing:.04em;text-transform:uppercase;">
        What happens next
      </p>

      <!-- Step 1 -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="margin-bottom:12px;">
        <tr>
          <td width="36" valign="top">
            <div style="width:28px;height:28px;border-radius:50%;
                        background:#0a1a5c;text-align:center;
                        line-height:28px;font-size:13px;
                        font-weight:700;color:#38d9f5;">
              1
            </div>
          </td>
          <td style="padding-left:12px;vertical-align:top;">
            <p style="margin:4px 0 2px;font-size:14px;font-weight:700;color:#1a1a1a;">
              Discovery Call
            </p>
            <p style="margin:0;font-size:13px;color:#666;line-height:1.55;">
              Our team will follow up within 1 business day to schedule a discovery call.
            </p>
          </td>
        </tr>
      </table>

      <!-- Step 2 -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="margin-bottom:12px;">
        <tr>
          <td width="36" valign="top">
            <div style="width:28px;height:28px;border-radius:50%;
                        background:#0a1a5c;text-align:center;
                        line-height:28px;font-size:13px;
                        font-weight:700;color:#38d9f5;">
              2
            </div>
          </td>
          <td style="padding-left:12px;vertical-align:top;">
            <p style="margin:4px 0 2px;font-size:14px;font-weight:700;color:#1a1a1a;">
              Custom Proposal
            </p>
            <p style="margin:0;font-size:13px;color:#666;line-height:1.55;">
              We&rsquo;ll put together a tailored outsourcing plan based on your needs.
            </p>
          </td>
        </tr>
      </table>

      <!-- Step 3 -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="margin-bottom:28px;">
        <tr>
          <td width="36" valign="top">
            <div style="width:28px;height:28px;border-radius:50%;
                        background:#0a1a5c;text-align:center;
                        line-height:28px;font-size:13px;
                        font-weight:700;color:#38d9f5;">
              3
            </div>
          </td>
          <td style="padding-left:12px;vertical-align:top;">
            <p style="margin:4px 0 2px;font-size:14px;font-weight:700;color:#1a1a1a;">
              Launch Your Engagement
            </p>
            <p style="margin:0;font-size:13px;color:#666;line-height:1.55;">
              Onboard within days with a dedicated client success manager.
            </p>
          </td>
        </tr>
      </table>

      <!-- Sign-off -->
      <p style="margin:0;font-size:14px;color:#444;line-height:1.7;">
        Best regards,<br>
        <strong style="color:#040d2b;">The Magellan Solutions Team</strong>
      </p>

      <!-- Sent-to note -->
      <p style="margin:24px 0 0;font-size:11px;color:#aaa;">
        This email was sent to ' . esc_html( $d['work_email'] ) . '
      </p>';

    return otg_email_wrap( 'Executive Guides', $content );
}

/* ─────────────────────────────────────────────────────────────
   2. ADMIN NOTIFICATION EMAIL  →  sent to Lead Notification Email(s)
      Content: new lead alert with full contact table
───────────────────────────────────────────────────────────── */
function otg_email_admin_notification( array $d, array $recipients ): string {

    $content = '
      <h1 style="margin:0 0 6px;font-size:20px;font-weight:700;color:#040d2b;">
        New Executive Guide Request
      </h1>
      <p style="margin:0 0 24px;font-size:14px;color:#555;line-height:1.6;">
        A new lead submitted the Executive Guides form on
        <strong>' . current_time('mysql') . '</strong>.
      </p>

      ' . otg_email_contact_rows( $d ) . '

      <p style="margin:20px 0 0;font-size:11px;color:#aaa;line-height:1.6;">
        Notified: ' . implode( ', ', array_map( 'esc_html', $recipients ) ) . '
      </p>';

    return otg_email_wrap( 'New Lead', $content );
}

/* ─────────────────────────────────────────────────────────────
   3. CONSULTATION EMAIL  →  sent to Lead Notification Email(s)
      Content: consultation request with contact table + guide name
───────────────────────────────────────────────────────────── */
function otg_email_consultation( array $d ): string {

    // Optional extra row for the guide that was downloaded
    $extra_row = '';
    if ( ! empty( $d['guide_name'] ) ) {
        $extra_row = '
        <tr style="background:#f0f7ff;">
          <td style="padding:11px 14px;border:1px solid #e8eaef;
                     font-weight:700;width:130px;color:#040d2b;font-size:13px;">
            Guide Downloaded
          </td>
          <td style="padding:11px 14px;border:1px solid #e8eaef;
                     color:#0a1a5c;font-weight:600;">
            ' . esc_html( $d['guide_name'] ) . '
          </td>
        </tr>';
    }

    $content = '
      <h1 style="margin:0 0 6px;font-size:20px;font-weight:700;color:#040d2b;">
        Book a Consultation
      </h1>
      <p style="margin:0 0 24px;font-size:14px;color:#555;line-height:1.6;">
        A visitor has requested a consultation after viewing the Executive Guides
        on <strong>' . current_time('mysql') . '</strong>.
      </p>

      ' . otg_email_contact_rows( $d, $extra_row ) . '

      <p style="margin:20px 0 0;font-size:11px;color:#aaa;">
        Please follow up within 1 business day.
      </p>';

    return otg_email_wrap( 'Consultation Request', $content );
}

/* ─────────────────────────────────────────────────────────────
   4. CONSULTATION CONFIRMATION  →  sent to the submitter
      Confirms their consultation request was received.
───────────────────────────────────────────────────────────── */
function otg_email_consultation_confirmation( array $d ): string {

    $guide_line = ! empty( $d['guide_name'] )
        ? '<p style="margin:0 0 24px;font-size:14px;color:#555;line-height:1.6;">
             You requested a consultation regarding the guide:
             <strong style="color:#040d2b;">' . esc_html( $d['guide_name'] ) . '</strong>.
           </p>'
        : '';

    $content = '
      <h1 style="margin:0 0 8px;font-size:22px;font-weight:700;
                 color:#040d2b;line-height:1.25;">
        Hi ' . esc_html( $d['first_name'] ) . ',
      </h1>
      <p style="margin:0 0 16px;font-size:15px;color:#444;line-height:1.7;">
        Thank you for requesting a consultation with Magellan Solutions.
        We&rsquo;ve received your request and will be in touch within
        <strong>1 business day</strong>.
      </p>

      ' . $guide_line . '

      <hr style="border:none;border-top:1px solid #e8eaef;margin:0 0 24px;">

      <!-- What to expect -->
      <p style="margin:0 0 14px;font-size:13px;font-weight:700;
                color:#040d2b;letter-spacing:.04em;text-transform:uppercase;">
        Your contact details on file
      </p>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border-collapse:collapse;font-size:14px;margin-bottom:28px;">
        <tr style="background:#f7f8fc;">
          <td style="padding:10px 14px;border:1px solid #e8eaef;font-weight:700;
                     width:110px;color:#040d2b;font-size:13px;">Name</td>
          <td style="padding:10px 14px;border:1px solid #e8eaef;color:#333;">
            ' . esc_html( trim( $d['first_name'] . ' ' . $d['last_name'] ) ) . '
          </td>
        </tr>
        <tr style="background:#ffffff;">
          <td style="padding:10px 14px;border:1px solid #e8eaef;font-weight:700;
                     color:#040d2b;font-size:13px;">Company</td>
          <td style="padding:10px 14px;border:1px solid #e8eaef;color:#333;">
            ' . esc_html( $d['company_name'] ) . '
          </td>
        </tr>
        <tr style="background:#f7f8fc;">
          <td style="padding:10px 14px;border:1px solid #e8eaef;font-weight:700;
                     color:#040d2b;font-size:13px;">Phone</td>
          <td style="padding:10px 14px;border:1px solid #e8eaef;color:#333;">
            ' . esc_html( $d['phone_number'] ) . '
          </td>
        </tr>
      </table>

      <p style="margin:0;font-size:14px;color:#444;line-height:1.7;">
        Best regards,<br>
        <strong style="color:#040d2b;">The Magellan Solutions Team</strong>
      </p>

      <p style="margin:24px 0 0;font-size:11px;color:#aaa;">
        This email was sent to ' . esc_html( $d['work_email'] ) . '
      </p>';

    return otg_email_wrap( 'Consultation Request', $content );
}
