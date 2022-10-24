<?php
defined('ABSPATH') || exit;

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
 * @copyright: © 2022 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

class OMGF_Optimize_Run
{
    const DOCS_TEST_URL = 'https://daan.dev/docs/omgf-pro-troubleshooting/test-omgf-pro/';

    /** @var string */
    private $plugin_text_domain = 'host-webfonts-local';

    /**
     * Build class.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->run();
    }

    /**
     * Does a quick fetch to the site_url to trigger all the action.
     * 
     * @return void 
     */
    private function run()
    {
        update_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_HAS_RUN, true);

        $front_html = $this->get_front_html(get_home_url());
        $error      = false;

        if (is_wp_error($front_html) || wp_remote_retrieve_response_code($front_html) != 200) {
            $this->frontend_fetch_failed($front_html);

            $error = true;
        }

        if (!$error) {
            $this->optimization_succeeded();
        }
    }

    /**
     * Wrapper for wp_remote_get() with preset params.
     *
     * @param mixed $url
     * @return array|WP_Error
     */
    private function get_front_html($url)
    {
        return wp_remote_get(
            $this->no_cache_optimize_url($url),
            [
                'timeout' => 60
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
     * @return void
     */
    private function optimization_succeeded()
    {
        if (count(get_settings_errors())) {
            global $wp_settings_errors;

            $wp_settings_errors = [];
        }

        add_settings_error('general', 'omgf_optimization_success', __('Optimization completed successfully.', $this->plugin_text_domain) . ' ' . sprintf('<a target="_blank" href="%s">', self::DOCS_TEST_URL) . __('How can I verify it\'s working?', $this->plugin_text_domain) . '</a>', 'success');

        OMGF_Admin_Notice::set_notice(
            sprintf(
                __('Make sure you flush any caches of 3rd party plugins you\'re using (e.g. Revolution Slider, WP Rocket, Autoptimize, W3 Total Cache, etc.) to allow %s\'s optimizations to take effect. ', $this->plugin_text_domain),
                'omgf-cache-notice',
                'warning'
            ),
            apply_filters('omgf_settings_page_title', 'OMGF')
        );
    }

    /**
     * @param $response WP_Error|array
     */
    private function frontend_fetch_failed($response)
    {
        if ($response instanceof WP_REST_Response && $response->is_error()) {
            // Convert to WP_Error if WP_REST_Response
            $response = $response->as_error();
        }

        add_settings_error('general', 'omgf_frontend_fetch_failed', sprintf(__('%s encountered an error while fetching this site\'s frontend HTML', $this->plugin_text_domain), apply_filters('omgf_settings_page_title', 'OMGF')) . ': ' . $this->get_error_code($response) . ' - ' . $this->get_error_message($response), 'error');
    }

    /**
     * @param WP_REST_Response|WP_Error|array $response 
     * 
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
     * @param WP_REST_Response|WP_Error|array $response 
     * 
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
