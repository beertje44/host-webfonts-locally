<?php
/**
 * @package: OMGF
 * @author: Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url: https://daan.dev
 */

// Exit if accessed directly
if (!defined( 'ABSPATH')) exit;

/**
 * set the content type header
 */
header("Content-type: text/css");

/**
 * Check if user has the needed permissions.
 */
if (!current_user_can('manage_options'))
{
	wp_die(__("You're not cool enough to access this page."));
}

/**
 * Insert promotional material :)
 */
$fonts[] = "
/** This file is automagically generated by OMGF
  *
  * @author: Daan van den Bergh
  * @copyright: (c) 2019 Daan van den Bergh
  * @url: " . CAOS_WEBFONTS_SITE_URL . "
  */";
$fontDisplay = CAOS_WEBFONTS_DISPLAY_OPTION;

/**
 * Reload the fonts.
 */
$selectedFonts = hwlGetTotalFonts();

/**
 * Let's generate the stylesheet.
 */
foreach ($selectedFonts as $font) {
	$fontFamily     = sanitize_text_field($font->font_family);
	$fontStyle      = sanitize_text_field($font->font_style);
	$fontWeight     = sanitize_text_field($font->font_weight);
	$fontUrlEot     = esc_url_raw($font->url_eot);
	$fontUrlWoffTwo = esc_url_raw($font->url_woff2);
	$fontUrlWoff    = esc_url_raw($font->url_woff);
	$fontUrlTtf     = esc_url_raw($font->url_ttf);
	$locals         = explode(',', sanitize_text_field($font->local));
	$fontLocal      = isset($locals[0]) ? $locals[0] : $fontFamily . " " . ucfirst($fontStyle);
	$fontLocalDash  = isset($locals[1]) ? $locals[1] : $fontFamily . "-" . ucfirst($fontStyle);

	$fonts[] =
		"@font-face {
            font-family: '$fontFamily';
            font-display: $fontDisplay;
            font-style: $fontStyle;
            font-weight: $fontWeight;
            src: url('$fontUrlEot'); /* IE9 Compatible */
            src: local('$fontLocal'), local('$fontLocalDash'),
                 url('$fontUrlWoffTwo') format('woff2'), /* Super Modern Browsers */
                 url('$fontUrlWoff') format('woff'), /* Modern Browsers */
                 url('$fontUrlTtf') format('truetype'); /* Safari, Android, iOS */
        }";
}

$fonts = implode("\n", $fonts);
$file  = CAOS_WEBFONTS_UPLOAD_DIR . '/' . CAOS_WEBFONTS_FILENAME;

/**
 * If the file can be created and uploaded. Let's try to write it.
 */
try {
	$stylesheet = fopen($file, 'w') or die ("Cannot create file {$file}");
	fwrite ($stylesheet, $fonts);
	fclose ($stylesheet);
	wp_die(__('Stylesheet was successfully generated and added to your theme\'s header.'));
} catch (Exception $e) {
	wp_die(__("Stylesheet could not be generated: $e"));
}
