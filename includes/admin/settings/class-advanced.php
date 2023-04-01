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
 * @copyright: © 2022 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined( 'ABSPATH' ) || exit;

class OMGF_Admin_Settings_Advanced extends OMGF_Admin_Settings_Builder {

	/**
	 * OMGF_Admin_Settings_Advanced constructor.
	 */
	public function __construct() {
		 parent::__construct();

		$this->title = __( 'Advanced Settings', 'host-webfonts-local' );

		// Open
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_title' ], 10 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_description' ], 15 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_before' ], 20 );

		// Settings
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_cache_dir' ], 50 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_promo_fonts_source_url' ], 60 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_compatibility' ], 70 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_used_subsets' ], 80 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_debug_mode' ], 90 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_download_log' ], 100 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_uninstall' ], 110 );

		// Close
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_after' ], 200 );
	}

	/**
	 * Description
	 */
	public function do_description() {      ?>
		<p>
			<?php echo __( 'Use these settings to make OMGF work with your specific configuration.', 'host-webfonts-local' ); ?>
		</p>
		<?php
	}

	/**
	 *
	 */
	public function do_cache_dir() {        
		?>
		<tr>
			<th scope="row"><?php echo __( 'Fonts Cache Directory', 'host-webfonts-local' ); ?></th>
			<td>
				<p class="description">
					<?php echo sprintf( __( 'Downloaded stylesheets and font files %1$s are stored in: <code>%2$s</code>.', 'host-webfonts-local' ), is_multisite() ? __( '(for this site)', 'host-webfonts-local' ) : '', str_replace( ABSPATH, '', OMGF_UPLOAD_DIR ) ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 *
	 */
	public function do_promo_fonts_source_url() {
		$this->do_text(
			__( 'Modify Source URL (Pro)', 'host-webfonts-local' ),
			'omgf_pro_source_url',
			__( 'e.g. https://cdn.mydomain.com/alternate/relative-path', 'host-webfonts-local' ),
			OMGF::get( 'omgf_pro_source_url' ),
			sprintf(
				__( "Modify the <code>src</code> attribute for font files and stylesheets generated by OMGF Pro. This can be anything; from an absolute URL pointing to your CDN (e.g. <code>%s</code>) to an alternate relative URL (e.g. <code>/renamed-wp-content-dir/alternate/path/to/font-files</code>) to work with <em>security thru obscurity</em> plugins. Enter the full path to OMGF's files. Default: (empty)", 'host-webfonts-local' ),
				'https://your-cdn.com/wp-content/uploads/omgf'
			) . ' ' . $this->promo,
			! defined( 'OMGF_PRO_SOURCE_URL' )
		);
	}

	/**
	 * 
	 */
	public function do_compatibility() {
		$this->do_checkbox(
			__( 'Divi/Elementor Compatibility', 'host-webfonts-local' ),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_COMPATIBILITY,
			! empty( OMGF::get( OMGF_Admin_Settings::OMGF_ADV_SETTING_COMPATIBILITY ) ),
			__( 'Divi and Elementor use the same handle for Google Fonts stylesheets with different configurations. OMGF includes compatibility fixes to make sure these different stylesheets are processed correctly. Enable this if you see some fonts not appearing correctly. Default: off', 'host-webfonts-local' )
		);
	}

	/**
	 * Preload Subsets
	 * 
	 * @return void 
	 */
	public function do_used_subsets() {
		$this->do_select(
			__( 'Used Subset(s)', 'host-webfonts-local' ),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_SUBSETS,
			OMGF_Admin_Settings::OMGF_SUBSETS,
			OMGF::get( OMGF_Admin_Settings::OMGF_ADV_SETTING_SUBSETS ),
			( ! empty( OMGF::get( OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS ) ) ? '<span class="used-subsets-notice info">' . sprintf( __( 'Any changes made to this setting will be overwritten, because <strong>Auto-configure Subsets</strong> is enabled. <a href="%s">Disable it</a> if you wish to manage <strong>Used Subset(s)</strong> yourself. <u>Novice users shouldn\'t change this setting</u>!', 'host-webfonts-local' ), admin_url( OMGF_Admin_Settings::OMGF_OPTIONS_GENERAL_PAGE_OPTIMIZE_WEBFONTS ) ) . '</span>' : '' ) . __( 'A subset is a (limited) set of characters belonging to an alphabet. Default: <code>latin</code>, <code>latin-ext</code>. Limit the selection to subsets your site actually uses. Selecting <u>too many</u> subsets can negatively impact performance! <em>Latin Extended and Vietnamese are an add-ons for Latin and can\'t be used by itself. Use CTRL + click to select multiple values.</em>', 'host-webfonts-local' ),
			true
		);
	}

	public function do_debug_mode() {
		$this->do_checkbox(
			__( 'Debug Mode', 'host-webfonts-local' ),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_DEBUG_MODE,
			! empty( OMGF::get( OMGF_Admin_Settings::OMGF_ADV_SETTING_DEBUG_MODE ) ),
			__( 'Don\'t enable this option, unless when asked by me (Daan) or, if you know what you\'re doing.', 'host-webfonts-local' )
		);
	}

	/**
	 * Show Download Log button if debug mode is on and debug file exists.
	 */
	public function do_download_log() {
		if ( ! empty( OMGF::get( OMGF_Admin_Settings::OMGF_ADV_SETTING_DEBUG_MODE ) ) ) : 
			?>
			<tr>
				<th></th>
				<td>
					<?php if ( file_exists( OMGF::$log_file ) ) : ?>
						<?php
						clearstatcache();
						$nonce = wp_create_nonce( OMGF_Admin_Settings::OMGF_ADMIN_PAGE );
						?>
						<a class="button button-secondary" href="<?php echo admin_url( "admin-ajax.php?action=omgf_download_log&nonce=$nonce" ); ?>"><?php _e( 'Download Log', 'host-webfonts-local' ); ?></a>
						<a id="omgf-delete-log" class="button button-cancel" data-nonce="<?php echo $nonce; ?>"><?php _e( 'Delete log', 'host-webfonts-local' ); ?></a>
						<?php if ( filesize( OMGF::$log_file ) > MB_IN_BYTES ) : ?>
							<p class="omgf-warning"><?php _e( 'Your log file is currently larger than 1MB. To protect your filesystem, debug logging has stopped. Delete the log file to enable debug logging again.', 'host-webfonts-local' ); ?></p>
						<?php endif; ?>
					<?php else : ?>
						<p class="description"><?php _e( 'No log file available for download.', 'host-webfonts-local' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<?php 
endif;
	}

	/**
	 * Remove Settings/Files at Uninstall.
	 */
	public function do_uninstall() {
		$this->do_checkbox(
			__( 'Remove Settings/Files At Uninstall', 'host-webfonts-local' ),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_UNINSTALL,
			! empty( OMGF::get( OMGF_Admin_Settings::OMGF_ADV_SETTING_UNINSTALL ) ),
			__( 'Warning! This will remove all settings and cached fonts upon plugin deletion.', 'host-webfonts-local' )
		);
	}
}
