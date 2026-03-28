<?php
/**
 * White-Label Configuration
 *
 * Centralizes all branding (logo, favicon, title, footer) in one file.
 * To white-label openSIS, edit the values below or place custom logo
 * files in assets/branding/.
 *
 * Logo files:
 *   assets/branding/logo.png       - Main app logo (navbar, ~200x50px)
 *   assets/branding/logo-login.png - Login page logo (~300x80px)
 *   assets/branding/favicon.ico    - Browser tab icon (32x32 or 16x16)
 *   assets/branding/favicon.png    - PNG favicon alternative (32x32)
 *
 * If custom files don't exist, defaults to openSIS branding.
 */

if (!defined('WHITELABEL_INC')) {
    define('WHITELABEL_INC', 1);

    // ── Brand Name ───────────────────────────────────────────────────
    // Shown in page titles, footers, and headers
    $GLOBALS['WhiteLabel'] = [

        'app_name'    => 'openSIS',
        'app_title'   => 'openSIS Student Information System',
        'footer_html' => 'openSIS is a product of Open Solutions for Education, Inc. (<a href="http://www.os4ed.com">OS4ED</a>) and is licensed under the <a href="http://www.gnu.org/licenses/gpl.html" target="_blank">GPL license</a>.',

        // ── Logo Paths ───────────────────────────────────────────────
        // Relative to document root. Checked in order: custom → default
        'logo'          => null,  // auto-detect
        'logo_login'    => null,  // auto-detect
        'favicon'       => null,  // auto-detect
    ];

    // ── Auto-detect custom branding files ────────────────────────────
    $brandDir = dirname(__FILE__) . '/assets/branding';

    if (file_exists($brandDir . '/logo.png')) {
        $GLOBALS['WhiteLabel']['logo'] = 'assets/branding/logo.png';
    } else {
        $GLOBALS['WhiteLabel']['logo'] = 'assets/opensis_logo.png';
    }

    if (file_exists($brandDir . '/logo-login.png')) {
        $GLOBALS['WhiteLabel']['logo_login'] = 'assets/branding/logo-login.png';
    } else {
        $GLOBALS['WhiteLabel']['logo_login'] = 'assets/images/opensis_logo.png';
    }

    if (file_exists($brandDir . '/favicon.ico')) {
        $GLOBALS['WhiteLabel']['favicon'] = 'assets/branding/favicon.ico';
    } elseif (file_exists($brandDir . '/favicon.png')) {
        $GLOBALS['WhiteLabel']['favicon'] = 'assets/branding/favicon.png';
    } else {
        $GLOBALS['WhiteLabel']['favicon'] = 'favicon.ico';
    }
}

/**
 * Get a white-label value.
 */
function WhiteLabel($key) {
    return $GLOBALS['WhiteLabel'][$key] ?? '';
}
