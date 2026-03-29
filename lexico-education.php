<?php
/**
 * Plugin Name: Lexico Hören Education
 * Plugin URI:  https://lexico.ch
 * Description: Preisrechner und Bestellformular für Lexico Hören Education – Institutionspreise
 * Version:     1.1.0
 * Author:      Lexico / Pappy
 * Text Domain: lexico-education
 */

defined( 'ABSPATH' ) || exit;

define( 'LEXICO_EDU_TABLE', 'lexico_edu_orders' );

// ─── Defaults ─────────────────────────────────────────────────────────────────
function lexico_edu_defaults() {
    return [
        // General
        'notify_email'    => 'mail@pappy.ch',
        'app_store_price' => 349,
        // Texts – front end
        'form_title'      => 'Anfrage senden',
        'intro_text'      => '',
        'submit_label'    => 'Anfrage senden',
        'success_msg'     => 'Anfrage erfolgreich gesendet. Wir melden uns in Kürze!',
        // E-mail – admin notification
        'admin_mail_subject' => 'Neue Lexico Education Anfrage: {org_name} ({n} Lizenzen)',
        // E-mail – customer confirmation
        'confirm_subject' => 'Ihre Anfrage für Lexico Hören Education',
        'confirm_body'    => "Guten Tag {contact_name},\n\nVielen Dank für Ihre Anfrage. Wir haben folgende Angaben erhalten:\n\nOrganisations-Name: {org_name}\nOrganisations-ID:   {org_id}\nLizenzen:           {n}\nPreis / Lizenz:     CHF {price_per}\nGesamtpreis:        CHF {price_total}\n\nWir werden uns in Kürze bei Ihnen melden.\n\nFreundliche Grüsse\nLexico",
    ];
}

function lexico_edu_opt( $key ) {
    $opts = get_option( 'lexico_edu_options', [] );
    $defs = lexico_edu_defaults();
    return isset( $opts[ $key ] ) && $opts[ $key ] !== '' ? $opts[ $key ] : ( $defs[ $key ] ?? '' );
}

// ─── Activation ───────────────────────────────────────────────────────────────
register_activation_hook( __FILE__, function () {
    global $wpdb;
    $t  = $wpdb->prefix . LEXICO_EDU_TABLE;
    $cs = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( "CREATE TABLE $t (
        id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        org_name      VARCHAR(255)    NOT NULL,
        org_id        VARCHAR(255)    NOT NULL,
        license_count SMALLINT        NOT NULL,
        price_per_lic DECIMAL(10,2)   NOT NULL,
        total_price   DECIMAL(10,2)   NOT NULL,
        contact_name  VARCHAR(255)    NOT NULL,
        contact_email VARCHAR(255)    NOT NULL,
        PRIMARY KEY (id)
    ) $cs;" );
    // Save defaults on first activation
    if ( ! get_option( 'lexico_edu_options' ) ) {
        add_option( 'lexico_edu_options', lexico_edu_defaults() );
    }
} );

// ─── Admin menu ───────────────────────────────────────────────────────────────
add_action( 'admin_menu', function () {
    add_menu_page(
        'Lexico Education',
        'Lexico Education',
        'manage_options',
        'lexico-education',
        'lexico_edu_admin_page',
        'dashicons-tablet',
        30
    );
    add_submenu_page(
        'lexico-education',
        'Bestellungen',
        'Bestellungen',
        'manage_options',
        'lexico-education',
        'lexico_edu_admin_page'
    );
    add_submenu_page(
        'lexico-education',
        'Einstellungen',
        'Einstellungen',
        'manage_options',
        'lexico-education-settings',
        'lexico_edu_settings_page'
    );
} );

// ─── Register settings ────────────────────────────────────────────────────────
add_action( 'admin_init', function () {
    register_setting( 'lexico_edu_settings_group', 'lexico_edu_options', [
        'sanitize_callback' => 'lexico_edu_sanitize_options',
    ] );

    // Section: General
    add_settings_section( 'lexico_general', 'Allgemein', '__return_false', 'lexico-education-settings' );
    add_settings_field( 'notify_email',    'Benachrichtigungs-E-Mail',  'lexico_field_notify_email',    'lexico-education-settings', 'lexico_general' );
    add_settings_field( 'app_store_price', 'App Store Einzelpreis (CHF)', 'lexico_field_app_store_price', 'lexico-education-settings', 'lexico_general' );

    // Section: Front-end texts
    add_settings_section( 'lexico_texts', 'Texte (Frontend)', '__return_false', 'lexico-education-settings' );
    add_settings_field( 'intro_text',   'Einleitungstext',         'lexico_field_intro_text',   'lexico-education-settings', 'lexico_texts' );
    add_settings_field( 'form_title',   'Formular-Titel',          'lexico_field_form_title',   'lexico-education-settings', 'lexico_texts' );
    add_settings_field( 'submit_label', 'Beschriftung Senden-Button', 'lexico_field_submit_label', 'lexico-education-settings', 'lexico_texts' );
    add_settings_field( 'success_msg',  'Erfolgsmeldung',          'lexico_field_success_msg',  'lexico-education-settings', 'lexico_texts' );

    // Section: E-mails
    add_settings_section( 'lexico_emails', 'E-Mails', function() {
        echo '<p style="color:#718096;font-size:0.9rem">Verfügbare Platzhalter: <code>{org_name}</code>, <code>{org_id}</code>, <code>{n}</code>, <code>{price_per}</code>, <code>{price_total}</code>, <code>{contact_name}</code>, <code>{contact_email}</code></p>';
    }, 'lexico-education-settings' );
    add_settings_field( 'admin_mail_subject', 'Betreff Benachrichtigung (intern)', 'lexico_field_admin_subject',   'lexico-education-settings', 'lexico_emails' );
    add_settings_field( 'confirm_subject',    'Betreff Bestätigung (Kunde)',       'lexico_field_confirm_subject', 'lexico-education-settings', 'lexico_emails' );
    add_settings_field( 'confirm_body',       'Text Bestätigung (Kunde)',          'lexico_field_confirm_body',    'lexico-education-settings', 'lexico_emails' );
} );

function lexico_edu_sanitize_options( $input ) {
    $clean = [];
    $text_fields = [ 'notify_email', 'form_title', 'submit_label', 'success_msg', 'admin_mail_subject', 'confirm_subject' ];
    foreach ( $text_fields as $f ) {
        $clean[ $f ] = sanitize_text_field( $input[ $f ] ?? '' );
    }
    $clean['app_store_price'] = max( 1, (int) ( $input['app_store_price'] ?? 349 ) );
    $clean['intro_text']    = sanitize_textarea_field( $input['intro_text']    ?? '' );
    $clean['confirm_body']  = sanitize_textarea_field( $input['confirm_body']  ?? '' );
    return $clean;
}

// ─── Settings field renderers ─────────────────────────────────────────────────
function lexico_field_notify_email() {
    $v = lexico_edu_opt('notify_email');
    echo '<input type="email" name="lexico_edu_options[notify_email]" value="' . esc_attr($v) . '" class="regular-text">';
    echo '<p class="description">Hierhin wird jede neue Bestellung gemeldet.</p>';
}
function lexico_field_app_store_price() {
    $v = lexico_edu_opt('app_store_price');
    echo '<input type="number" name="lexico_edu_options[app_store_price]" value="' . esc_attr($v) . '" class="small-text" min="1"> CHF';
    echo '<p class="description">Regulärer App Store Einzelpreis – Basis für die Rabattberechnung.</p>';
}
function lexico_field_intro_text() {
    $v = lexico_edu_opt('intro_text');
    echo '<textarea name="lexico_edu_options[intro_text]" rows="8" class="large-text">' . esc_textarea($v) . '</textarea>';
    echo '<p class="description">Wird oberhalb des Preisrechners angezeigt. Leerzeilen erzeugen neue Absätze.</p>';
}
function lexico_field_form_title() {
    $v = lexico_edu_opt('form_title');
    echo '<input type="text" name="lexico_edu_options[form_title]" value="' . esc_attr($v) . '" class="regular-text">';
}
function lexico_field_submit_label() {
    $v = lexico_edu_opt('submit_label');
    echo '<input type="text" name="lexico_edu_options[submit_label]" value="' . esc_attr($v) . '" class="regular-text">';
}
function lexico_field_success_msg() {
    $v = lexico_edu_opt('success_msg');
    echo '<input type="text" name="lexico_edu_options[success_msg]" value="' . esc_attr($v) . '" class="large-text">';
    echo '<p class="description">Wird nach erfolgreichem Absenden angezeigt.</p>';
}
function lexico_field_admin_subject() {
    $v = lexico_edu_opt('admin_mail_subject');
    echo '<input type="text" name="lexico_edu_options[admin_mail_subject]" value="' . esc_attr($v) . '" class="large-text">';
}
function lexico_field_confirm_subject() {
    $v = lexico_edu_opt('confirm_subject');
    echo '<input type="text" name="lexico_edu_options[confirm_subject]" value="' . esc_attr($v) . '" class="large-text">';
}
function lexico_field_confirm_body() {
    $v = lexico_edu_opt('confirm_body');
    echo '<textarea name="lexico_edu_options[confirm_body]" rows="12" class="large-text">' . esc_textarea($v) . '</textarea>';
}

// ─── Settings page ────────────────────────────────────────────────────────────
function lexico_edu_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1>Lexico Education – Einstellungen</h1>
        <?php settings_errors( 'lexico_edu_options' ); ?>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'lexico_edu_settings_group' );
                do_settings_sections( 'lexico-education-settings' );
                submit_button( 'Einstellungen speichern' );
            ?>
        </form>
        <hr style="margin:32px 0 24px">
        <p style="color:#718096;font-size:0.85rem">
            Shortcode zur Einbindung: <code>[lexico_education]</code>
        </p>
    </div>
    <?php
}

// ─── Admin orders page ────────────────────────────────────────────────────────
function lexico_edu_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    global $wpdb;
    $table = $wpdb->prefix . LEXICO_EDU_TABLE;

    if ( isset( $_GET['export'] ) && $_GET['export'] === 'csv' ) {
        $rows = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="lexico-bestellungen-' . date( 'Y-m-d' ) . '.csv"' );
        $out = fopen( 'php://output', 'w' );
        fputs( $out, "\xEF\xBB\xBF" );
        fputcsv( $out, [ 'Datum', 'Org.-Name', 'Org.-ID', 'Lizenzen', 'CHF/Lizenz', 'Gesamt CHF', 'Kontaktname', 'E-Mail' ], ';' );
        foreach ( $rows as $r ) {
            fputcsv( $out, [
                date( 'd.m.Y H:i', strtotime( $r['created_at'] ) ),
                $r['org_name'], $r['org_id'], $r['license_count'],
                number_format( $r['price_per_lic'], 2, '.', '' ),
                number_format( $r['total_price'],   2, '.', '' ),
                $r['contact_name'], $r['contact_email'],
            ], ';' );
        }
        fclose( $out );
        exit;
    }

    $orders = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
    $count  = count( $orders );
    ?>
    <div class="wrap">
        <h1>Lexico Education – Bestellungen
            <?php if ( $count ) echo "<span class='awaiting-mod'>$count</span>"; ?>
        </h1>
        <p style="margin:16px 0 20px">
            <a href="<?php echo esc_url( add_query_arg( 'export', 'csv' ) ); ?>" class="button button-primary">↓ &nbsp;CSV exportieren</a>
        </p>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr>
                <th style="width:140px">Datum</th>
                <th>Organisations-Name</th>
                <th style="width:140px">Organisations-ID</th>
                <th style="width:80px;text-align:right">Lizenzen</th>
                <th style="width:110px;text-align:right">CHF / Lizenz</th>
                <th style="width:120px;text-align:right">Gesamt CHF</th>
                <th style="width:160px">Kontaktname</th>
                <th>E-Mail</th>
            </tr></thead>
            <tbody>
            <?php if ( $orders ) : foreach ( $orders as $o ) : ?>
                <tr>
                    <td><?php echo esc_html( date( 'd.m.Y H:i', strtotime( $o->created_at ) ) ); ?></td>
                    <td><strong><?php echo esc_html( $o->org_name ); ?></strong></td>
                    <td><code><?php echo esc_html( $o->org_id ); ?></code></td>
                    <td style="text-align:right"><?php echo (int) $o->license_count; ?></td>
                    <td style="text-align:right"><?php echo number_format( $o->price_per_lic, 2, '.', "'" ); ?></td>
                    <td style="text-align:right"><strong><?php echo number_format( $o->total_price, 2, '.', "'" ); ?></strong></td>
                    <td><?php echo esc_html( $o->contact_name ); ?></td>
                    <td><a href="mailto:<?php echo esc_attr( $o->contact_email ); ?>"><?php echo esc_html( $o->contact_email ); ?></a></td>
                </tr>
            <?php endforeach; else : ?>
                <tr><td colspan="8" style="text-align:center;padding:32px;color:#888">Noch keine Bestellungen vorhanden.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function lexico_edu_calc( $n ) {
    $asp = (float) lexico_edu_opt( 'app_store_price' );
    $s1  = ( $asp * 20 * 0.5 - $asp ) / 19;   // 1 → 20 : full price → 50 % off
    $s2  = ( 100 * 100 - $asp * 20 * 0.5 ) / 80; // 20 → 100 : 50 % off → 100.–
    if      ( $n <= 1  ) $total = $asp;
    elseif  ( $n <= 20 ) $total = $asp + $s1 * ( $n - 1 );
    elseif  ( $n <= 100) $total = $asp * 10 + $s2 * ( $n - 20 );
    else                 $total = 100 * $n;
    return [ 'total' => $total, 'per_lic' => $total / $n ];
}

function lexico_edu_replace( $tpl, $vars ) {
    foreach ( $vars as $k => $v ) {
        $tpl = str_replace( '{' . $k . '}', $v, $tpl );
    }
    return $tpl;
}

// ─── AJAX ─────────────────────────────────────────────────────────────────────
add_action( 'wp_ajax_lexico_edu_submit',        'lexico_edu_submit' );
add_action( 'wp_ajax_nopriv_lexico_edu_submit', 'lexico_edu_submit' );

function lexico_edu_submit() {
    check_ajax_referer( 'lexico_edu_nonce', 'nonce' );

    $org_name  = sanitize_text_field( $_POST['org_name']      ?? '' );
    $org_id    = sanitize_text_field( $_POST['org_id']        ?? '' );
    $n         = max( 1, (int) ( $_POST['license_count']      ?? 1 ) );
    $con_name  = sanitize_text_field( $_POST['contact_name']  ?? '' );
    $con_email = sanitize_email(      $_POST['contact_email'] ?? '' );

    if ( ! $org_name || ! $org_id || ! $con_name || ! is_email( $con_email ) ) {
        wp_send_json_error( [ 'message' => 'Bitte füllen Sie alle Felder korrekt aus.' ] );
    }

    $calc    = lexico_edu_calc( $n );
    $total   = $calc['total'];
    $per_lic = $calc['per_lic'];

    global $wpdb;
    $wpdb->insert( $wpdb->prefix . LEXICO_EDU_TABLE, [
        'org_name'      => $org_name,
        'org_id'        => $org_id,
        'license_count' => $n,
        'price_per_lic' => round( $per_lic, 2 ),
        'total_price'   => round( $total,   2 ),
        'contact_name'  => $con_name,
        'contact_email' => $con_email,
    ] );

    $vars = [
        'org_name'     => $org_name,
        'org_id'       => $org_id,
        'n'            => $n,
        'price_per'    => number_format( round( $per_lic ), 0, '.', "'" ) . '.–',
        'price_total'  => number_format( round( $total   ), 0, '.', "'" ) . '.–',
        'contact_name' => $con_name,
        'contact_email'=> $con_email,
    ];

    // Admin notification
    wp_mail(
        lexico_edu_opt( 'notify_email' ),
        lexico_edu_replace( lexico_edu_opt( 'admin_mail_subject' ), $vars ),
        lexico_edu_replace(
            "Neue Anfrage eingegangen:\n\nOrganisations-Name: {org_name}\nOrganisations-ID:   {org_id}\nLizenzen:           {n}\nPreis / Lizenz:     CHF {price_per}\nGesamtpreis:        CHF {price_total}\n\nKontaktname: {contact_name}\nE-Mail:      {contact_email}",
            $vars
        )
    );

    // Customer confirmation
    wp_mail(
        $con_email,
        lexico_edu_replace( lexico_edu_opt( 'confirm_subject' ), $vars ),
        lexico_edu_replace( lexico_edu_opt( 'confirm_body'    ), $vars )
    );

    wp_send_json_success( [ 'message' => lexico_edu_opt( 'success_msg' ) ] );
}

// ─── Shortcode [lexico_education] ─────────────────────────────────────────────
add_shortcode( 'lexico_education', function () {
    $nonce      = wp_create_nonce( 'lexico_edu_nonce' );
    $ajax_url   = admin_url( 'admin-ajax.php' );
    $asp        = (int) lexico_edu_opt( 'app_store_price' );
    $intro      = lexico_edu_opt( 'intro_text' );
    $form_title = lexico_edu_opt( 'form_title' );
    $btn_label  = lexico_edu_opt( 'submit_label' );
    $success    = lexico_edu_opt( 'success_msg' );

    // Convert newlines to <br> paragraphs for intro text
    $intro_html = '';
    if ( $intro ) {
        $paras = array_filter( array_map( 'trim', explode( "\n\n", $intro ) ) );
        foreach ( $paras as $p ) {
            $intro_html .= '<p>' . nl2br( esc_html( $p ) ) . '</p>';
        }
    }

    ob_start();
    ?>

<style>
.lexico-edu * { box-sizing: border-box; margin: 0; padding: 0; }
.lexico-edu {
    font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif;
    color: #1a202c;
    padding: 8px 0 24px;
}
.lexico-edu .lx-intro {
    font-size: 0.97rem;
    line-height: 1.75;
    color: #4a5568;
    margin-bottom: 28px;
}
.lexico-edu .lx-intro p { margin-bottom: 14px; }
.lexico-edu .lx-intro p:last-child { margin-bottom: 0; }
.lexico-edu .lx-card {
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}
/* ── Slider ── */
.lexico-edu .slider-row { display: flex; align-items: center; gap: 16px; margin-bottom: 28px; }
.lexico-edu .slider-row label { font-size: 0.95rem; font-weight: 500; color: #4a5568; white-space: nowrap; min-width: 130px; }
.lexico-edu input#lx-slider {
    flex: 1; -webkit-appearance: none; appearance: none;
    height: 8px; border-radius: 4px; outline: none; cursor: pointer; border: none; padding: 0;
}
.lexico-edu input#lx-slider::-webkit-slider-thumb {
    -webkit-appearance: none; appearance: none;
    width: 26px; height: 26px; border-radius: 50%;
    background: #4299e1; border: 3px solid #fff;
    box-shadow: 0 1px 6px rgba(0,0,0,0.28); cursor: pointer;
}
.lexico-edu input#lx-slider::-moz-range-thumb {
    width: 22px; height: 22px; border-radius: 50%;
    background: #4299e1; border: 3px solid #fff;
    box-shadow: 0 1px 6px rgba(0,0,0,0.28); cursor: pointer;
}
.lexico-edu input#lx-slider::-moz-range-track { height: 8px; border-radius: 4px; background: #cbd5e0; }
.lexico-edu .slider-val { font-size: 1.4rem; font-weight: 700; color: #2b6cb0; min-width: 48px; text-align: right; }
/* ── Panels ── */
.lexico-edu .panels { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
.lexico-edu .panel { background: #ebf8ff; border-radius: 12px; padding: 22px 16px 18px; text-align: center; }
.lexico-edu .panel.hl { background: #4299e1; }
.lexico-edu .plabel { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #4a9fc7; margin-bottom: 12px; line-height: 1.4; }
.lexico-edu .panel.hl .plabel { color: rgba(255,255,255,0.85); }
.lexico-edu .pval { font-size: 2.2rem; font-weight: 800; color: #2b6cb0; line-height: 1; }
.lexico-edu .panel.hl .pval { color: #fff; }
/* ── Form ── */
.lexico-edu .form-title { font-size: 1.05rem; font-weight: 600; color: #2d3748; margin-bottom: 20px; }
.lexico-edu .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.lexico-edu .form-group { display: flex; flex-direction: column; gap: 5px; }
.lexico-edu .form-group label { font-size: 0.83rem; font-weight: 600; color: #4a5568; }
.lexico-edu .form-group input {
    padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: 0.95rem; color: #2d3748; background: #fff; width: 100%; transition: border-color 0.2s;
}
.lexico-edu .form-group input:focus { outline: none; border-color: #4299e1; }
.lexico-edu .form-group input[readonly] { background: #f7fafc; color: #718096; cursor: default; }
.lexico-edu .form-note { font-size: 0.76rem; color: #a0aec0; line-height: 1.4; }
.lexico-edu .submit-row { margin-top: 24px; }
.lexico-edu .submit-btn {
    width: 100% !important; padding: 14px !important; border: none !important;
    border-radius: 10px !important; background: #4299e1 !important;
    color: #fff !important; font-size: 1rem !important; font-weight: 700 !important;
    cursor: pointer !important; transition: background 0.2s !important;
    box-shadow: none !important; text-transform: none !important;
    letter-spacing: normal !important; opacity: 1 !important;
}
.lexico-edu .submit-btn:hover    { background: #3182ce !important; }
.lexico-edu .submit-btn:disabled { background: #a0aec0 !important; cursor: default !important; }
.lexico-edu .lx-msg { margin-top: 16px; padding: 13px 18px; border-radius: 8px; font-size: 0.9rem; display: none; }
.lexico-edu .lx-msg.ok  { background: #c6f6d5; color: #22543d; }
.lexico-edu .lx-msg.err { background: #fed7d7; color: #742a2a; }
@media (max-width: 580px) {
    .lexico-edu .panels    { grid-template-columns: 1fr; }
    .lexico-edu .form-grid { grid-template-columns: 1fr; }
    .lexico-edu .lx-card   { padding: 20px 16px; }
    .lexico-edu .slider-row {
        flex-wrap: wrap;
        gap: 8px 12px;
    }
    .lexico-edu .slider-row label {
        min-width: unset !important;
        width: 100%;
        white-space: normal;
    }
    .lexico-edu input#lx-slider {
        flex: 1 1 0;
        min-width: 0;
        width: 100%;
    }
    .lexico-edu .slider-val {
        min-width: 36px;
        font-size: 1.2rem;
    }
}
</style>

<div class="lexico-edu">

  <?php if ( $intro_html ) : ?>
  <div class="lx-intro"><?php echo $intro_html; ?></div>
  <?php endif; ?>

  <!-- Pricing calculator -->
  <div class="lx-card">
    <div class="slider-row">
      <label>Anzahl Lizenzen</label>
      <input type="range" id="lx-slider" min="1" max="100" value="20" step="1">
      <span class="slider-val" id="lx-count">20</span>
    </div>
    <div class="panels">
      <div class="panel">
        <div class="plabel">Preis pro Lizenz</div>
        <div class="pval" id="lx-per">–</div>
      </div>
      <div class="panel hl">
        <div class="plabel">Gesamtpreis</div>
        <div class="pval" id="lx-total">–</div>
      </div>
      <div class="panel">
        <div class="plabel">Rabatt auf den Einzelpreis von&nbsp;<?php echo $asp; ?>.–</div>
        <div class="pval" id="lx-disc">–</div>
      </div>
    </div>
  </div>

  <!-- Order form -->
  <div class="lx-card">
    <div class="form-title"><?php echo esc_html( $form_title ); ?></div>
    <form id="lx-form" autocomplete="on" novalidate>
      <div class="form-grid">
        <div class="form-group">
          <label for="lx-org-name">Organisations-Name *</label>
          <input type="text" id="lx-org-name" name="org_name" required placeholder="Schule Mustertal">
          <span class="form-note">Exakt wie im Apple School / Business Manager hinterlegt</span>
        </div>
        <div class="form-group">
          <label for="lx-org-id">Organisations-ID *</label>
          <input type="text" id="lx-org-id" name="org_id" required placeholder="AB12345678">
          <span class="form-note">Zu finden im Apple School / Business Manager unter Einstellungen</span>
        </div>
        <div class="form-group">
          <label for="lx-con-name">Kontaktname *</label>
          <input type="text" id="lx-con-name" name="contact_name" required placeholder="Vorname Nachname">
        </div>
        <div class="form-group">
          <label for="lx-con-email">E-Mail *</label>
          <input type="email" id="lx-con-email" name="contact_email" required placeholder="name@schule.ch">
        </div>
        <div class="form-group">
          <label>Anzahl Lizenzen</label>
          <input type="text" id="lx-lic-display" readonly>
        </div>
        <div class="form-group">
          <label>Gesamtpreis</label>
          <input type="text" id="lx-total-display" readonly>
        </div>
      </div>
      <input type="hidden" id="lx-lic-hidden" name="license_count" value="20">
      <div class="submit-row">
        <button type="submit" class="submit-btn"><?php echo esc_html( $btn_label ); ?></button>
      </div>
      <div class="lx-msg" id="lx-msg"></div>
    </form>
  </div>

</div>

<script>
(function () {
    const AJAX    = '<?php echo esc_js( $ajax_url ); ?>';
    const NONCE   = '<?php echo esc_js( $nonce ); ?>';
    const ASP     = <?php echo (int) $asp; ?>;
    const SUCCESS = <?php echo json_encode( $success ); ?>;
    const BTN_LBL = <?php echo json_encode( $btn_label ); ?>;
    const S1 = (ASP * 10 - ASP) / 19;
    const S2 = (10000 - ASP * 10) / 80;

    function calcTotal(n) {
        if (n <= 1)   return ASP;
        if (n <= 20)  return ASP  + S1 * (n - 1);
        if (n <= 100) return ASP * 10 + S2 * (n - 20);
        return 100 * n;
    }
    function fmt(n) { return Math.round(n).toLocaleString('de-CH') + '.–'; }

    function update() {
        const slider = document.getElementById('lx-slider');
        const n      = parseInt(slider.value);
        const total  = calcTotal(n);
        const perLic = total / n;
        const disc   = Math.round((1 - perLic / ASP) * 100);
        const pct    = (n - 1) / 99 * 100;
        slider.style.setProperty('background',
            'linear-gradient(to right,#4299e1 ' + pct + '%,#cbd5e0 ' + pct + '%)', 'important');
        document.getElementById('lx-count').textContent   = n;
        document.getElementById('lx-per').textContent     = fmt(perLic);
        document.getElementById('lx-total').textContent   = fmt(total);
        document.getElementById('lx-disc').textContent    = disc < 1 ? '0 %' : disc + ' %';
        document.getElementById('lx-lic-hidden').value    = n;
        document.getElementById('lx-lic-display').value   = n + (n === 1 ? ' Lizenz' : ' Lizenzen');
        document.getElementById('lx-total-display').value = 'CHF ' + fmt(total);
    }

    document.getElementById('lx-slider').addEventListener('input', update);
    update();

    document.getElementById('lx-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = this.querySelector('.submit-btn');
        const msg = document.getElementById('lx-msg');
        const ids = ['lx-org-name', 'lx-org-id', 'lx-con-name', 'lx-con-email'];
        let ok = true;
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (!el.value.trim()) { el.style.borderColor = '#fc8181'; ok = false; }
            else el.style.borderColor = '';
        });
        if (!ok) {
            msg.className = 'lx-msg err'; msg.textContent = 'Bitte füllen Sie alle Pflichtfelder aus.';
            msg.style.display = 'block'; return;
        }
        btn.disabled = true; btn.textContent = 'Wird gesendet…'; msg.style.display = 'none';
        const data = new FormData(this);
        data.append('action', 'lexico_edu_submit');
        data.append('nonce',  NONCE);
        fetch(AJAX, { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                msg.style.display = 'block';
                if (res.success) {
                    msg.className = 'lx-msg ok'; msg.textContent = SUCCESS;
                    document.getElementById('lx-form').reset(); update();
                } else {
                    msg.className = 'lx-msg err'; msg.textContent = res.data.message;
                    btn.disabled = false; btn.textContent = BTN_LBL;
                }
            })
            .catch(() => {
                msg.className = 'lx-msg err';
                msg.textContent = 'Verbindungsfehler. Bitte versuchen Sie es erneut.';
                msg.style.display = 'block'; btn.disabled = false; btn.textContent = BTN_LBL;
            });
    });
})();
</script>

    <?php
    return ob_get_clean();
} );
