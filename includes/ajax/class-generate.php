<?php
/* * * * * * * * * * * * * * * * * * * * *
 *
 *  ██████╗ ███╗   ███╗ ██████╗ ███████╗
 * ██╔═══██╗████╗ ████║██╔════╝ ██╔════╝
 * ██║   ██║██╔████╔██║██║  ███╗█████╗
 * ██║   ██║██║╚██╔╝██║██║   ██║██╔══╝
 * ╚██████╔╝██║ ╚═╝ ██║╚██████╔╝██║
 *  ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚═╝
 *
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2020 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_AJAX_Generate extends OMGF_AJAX
{
    /** @var array $fonts */
    private $fonts = [];

    /**
     * OMGF_AJAX_Generate_Styles constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->init();
    }

    /**
     * Generate the Stylesheet
     */
    private function init()
    {
        header("Content-type: text/css");

        $this->insert_promo();

        $selectedFonts = $this->db->get_total_fonts();

        if (empty($selectedFonts)) {
            OMGF_Admin_Notice::set_notice(__('Hmmm... Seems like there\'s nothing to do here. Have you tried using <strong>search</strong> or <strong>auto detect</strong>?', 'host-webfonts-local'), true, 'error');
        }

        $this->process_fonts($selectedFonts);

        $fonts = implode("\n", $this->fonts);
        $file  = OMGF_FONTS_DIR . '/' . OMGF_FILENAME;

        /**
         * If the file can be created and written. Let's try to write it.
         */
        try {
            $stylesheet = fopen($file, 'w') or OMGF_Admin_Notice::set_notice(__("Cannot create file {$file}", 'host-webfonts-local', true, 'error', 400));
            fwrite($stylesheet, $fonts);
            fclose($stylesheet);

            OMGF_Admin_Notice::set_notice(__('Congratulations! Your stylesheet was generated successfully and added to your theme\'s header.', 'host-webfonts-local'), false);

            $count  = count($selectedFonts);
            $review = 'https://wordpress.org/support/plugin/host-webfonts-local/reviews/?rate=5#new-post';
            $tweet  = "https://twitter.com/intent/tweet?text=I+just+optimized+$count+Google+Fonts+with+OMGF+for+@WordPress!+Try+it+for+yourself:&via=Dan0sz&hashtags=GoogleFonts,WordPress,Pagespeed,Insights&url=https://wordpress.org/plugins/host-webfonts-local/";

            OMGF_Admin_Notice::set_notice(sprintf(__('OMGF has optimized %s fonts. Enjoy your performance boost! Would you be willing to <a href="%s" target="_blank">leave a review</a> or <a href="%s" target="_blank">tweet about it</a>?', 'host-webfonts-local'), $count, $review, $tweet), true, 'info');
        } catch (Exception $e) {
            OMGF_Admin_Notice::set_notice(__("Stylesheet could not be generated:", 'host-webfonts-local') . " $e", true, 'error', $e->getCode());
        }
    }

    /**
     * Insert promo material :)
     *
     * The alignment is crooked, so it'll look nice in the stylesheet.
     */
    private function insert_promo()
    {
        $this->fonts[] = "/** 
 * This file is automagically generated by OMGF
 *
 * @author: Daan van den Bergh
 * @copyright: (c) 2020 Daan van den Bergh
 * @url: " . OMGF_SITE_URL . "
 */";
    }

    /**
     * Prepare fonts for generation.
     */
    private function process_fonts($fonts)
    {
        $fontDisplay = OMGF_DISPLAY_OPTION;

        $i = 1;

        foreach ($fonts as $font) {
            $fontUrlEot  = isset($font['url_eot_local']) ? array(0 => esc_url_raw($font['url_eot_local'])) : array();
            $fontSources = isset($font['url_woff2_local']) ? array('woff2' => esc_url_raw($font['url_woff2_local'])) : array();
            $fontSources = $fontSources + (isset($font['url_woff_local']) ? array('woff' => esc_url_raw($font['url_woff_local'])) : array());
            $fontSources = $fontSources + (isset($font['url_ttf_local']) ? array('truetype' => esc_url_raw($font['url_ttf_local'])) : array());
            $locals      = explode(',', sanitize_text_field($font['local']));

            $this->fonts[$i] = "@font-face { \n";
            $this->fonts[$i] .= $this->build_property('font-family', $font['font_family']);
            $this->fonts[$i] .= $this->build_property('font-display', $fontDisplay);
            $this->fonts[$i] .= $this->build_property('font-style', $font['font_style']);
            $this->fonts[$i] .= $this->build_property('font-weight', $font['font_weight']);
            $this->fonts[$i] .= isset($fontUrlEot) ? "  src: " . $this->build_source_string($fontUrlEot) : '';
            $this->fonts[$i] .= "  src: " . $this->build_source_string($locals, 'local', false);
            // There'll always be at least one font available, so no need to check here if $fontSources is set.
            $this->fonts[$i] .= $this->build_source_string($fontSources);
            $this->fonts[$i] .= "}";

            $i++;
        }
    }

    /**
     * @param $property
     * @param $value
     *
     * @return string
     */
    private function build_property($property, $value)
    {
        $value = sanitize_text_field($value);

        return "  $property: $value;\n";
    }

    /**
     * @param        $sources
     * @param string $type
     * @param bool   $endSemiColon
     *
     * @return string
     */
    private function build_source_string($sources, $type = 'url', $endSemiColon = true)
    {
        $lastSrc = end($sources);
        $source  = '';

        foreach ($sources as $format => $url) {
            $source .= "  $type('$url')" . (!is_numeric($format) ? " format('$format')" : '');

            if ($url === $lastSrc && $endSemiColon) {
                $source .= ";\n";
            } else {
                $source .= ",\n";
            }
        }

        return $source;
    }
}
