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
 * @copyright: (c) 2021 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Optimize
{
    /** @var string */
    private $plugin_text_domain = 'host-webfonts-local';

    /** @var string */
    private $settings_page = '';

    /** @var string */
    private $settings_tab = '';

    /** @var string */
    private $settings_updated = '';

    /**
     * OMGF_Optimize constructor.
     */
    public function __construct()
    {
        $this->settings_page    = $_GET['page'] ?? '';
        $this->settings_tab     = $_GET['tab'] ?? OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_OPTIMIZE;
        $this->settings_updated = $_GET['settings-updated'] ?? '';

        $this->init();
    }

    /**
     * Run either manual or auto mode after settings are updated.
     * 
     * @return void 
     */
    private function init()
    {
        if (OMGF_Admin_Settings::OMGF_ADMIN_PAGE != $this->settings_page) {
            return;
        }

        if (OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_OPTIMIZE != $this->settings_tab) {
            return;
        }

        if (!$this->settings_updated) {
            return;
        }

        add_filter('http_request_args', [$this, 'verify_ssl']);

        $optimization_mode = apply_filters('omgf_optimization_mode', OMGF_OPTIMIZATION_MODE);

        if ('manual' == $optimization_mode) {
            $this->run_manual();
        }
    }

    /**
     * If this site is non-SSL it makes no sense to verify its SSL certificates.
     *
     * Settings sslverify to false will set CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST
     * to 0 further down the road.
     *
     * @param mixed $url
     * @return array
     */
    public function verify_ssl($args)
    {
        $args['sslverify'] = strpos(home_url(), 'https:') !== false;

        return $args;
    }

    /**
     * @return void
     */
    private function run_manual()
    {
        $url        = esc_url_raw(OMGF_MANUAL_OPTIMIZE_URL);
        $front_html = $this->remote_get($url);

        if (is_wp_error($front_html) || wp_remote_retrieve_response_code($front_html) != 200) {
            $this->frontend_fetch_failed($front_html);
        }

        $urls     = [];
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        @$document->loadHtml(wp_remote_retrieve_body($front_html));

        foreach ($document->getElementsByTagName('link') as $link) {
            /** @var $link DOMElement */
            if ($link->hasAttribute('href') && strpos($link->getAttribute('href'), '/omgf/v1/download/')) {
                $urls[] = $link->getAttribute('href');
            }
        }

        if (empty($urls)) {
            $this->no_urls_found();
        }

        foreach ($urls as $url) {
            $download = $this->remote_get($url);

            if (is_wp_error($download)) {
                $this->download_failed($download);
            }
        }

        $this->optimization_succeeded();
    }

    /**
     * @return void
     */
    private function optimization_succeeded()
    {
        add_settings_error('general', 'omgf_optimization_success', __('Optimization completed successfully.'), 'success');

        OMGF_Admin_Notice::set_notice(
            __('If you\'re using any 3rd party optimization plugins (e.g. WP Rocket, Autoptimize, W3 Total Cache, etc.) make sure to flush their caches for OMGF\'s optimizations to take effect.', $this->plugin_text_domain),
            'omgf-cache-notice',
            false,
            'warning'
        );
    }

    /**
     * @param $response WP_Error|array
     */
    private function download_failed($response)
    {
        add_settings_error('general', 'omgf_download_failed', __('OMGF encountered an error while downloading Google Fonts', $this->plugin_text_domain) . ': ' . $this->get_error_code($response) . ' - ' . $this->get_error_message($response), 'error');
    }

    /**
     * @param $response WP_Error|array
     */
    private function frontend_fetch_failed($response)
    {
        add_settings_error('general', 'omgf_frontend_fetch_failed', __('OMGF encountered an error while fetching this site\'s frontend HTML', $this->plugin_text_domain) . ': ' . $this->get_error_code($response) . ' - ' . $this->get_error_message($response), 'error');
    }

    /**
     * @return void
     */
    private function no_urls_found()
    {
        add_settings_error('general', 'omgf_no_urls_found', sprintf(__('No (additional) Google Fonts found to optimize. If you believe this is an error, please refer to the %stroubleshooting%s section of the documentation for possible solutions.', $this->plugin_text_domain), '<a href="https://ffw.press/docs/omgf-pro/troubleshooting">', '</a>'), 'info');
    }

    /**
     * Wrapper for wp_remote_get() with preset params.
     *
     * @param mixed $url
     * @return array|WP_Error
     */
    private function remote_get($url)
    {
        return wp_remote_get(
            $this->no_cache_optimize_url($url),
            [
                'timeout' => 30
            ]
        );
    }

    /**
     * @param $url
     *
     * @return string
     */
    private function no_cache_optimize_url($url)
    {
        return add_query_arg(['omgf_optimize' => 1, 'nocache' => substr(md5(microtime()), rand(0, 26), 5)], $url);
    }

    /**
     * @param array|WP_Error $response 
     * @return int|string 
     */
    private function get_error_code($response)
    {
        if (is_wp_error($response)) {
            return $response->get_error_code();
        }

        return wp_remote_retrieve_response_code($response);
    }

    /**
     * @param array|WP_Error $response 
     * @return int|string 
     */
    private function get_error_message($response)
    {
        if (is_wp_error($response)) {
            return $response->get_error_message();
        }

        return wp_remote_retrieve_response_message($response);
    }
}
