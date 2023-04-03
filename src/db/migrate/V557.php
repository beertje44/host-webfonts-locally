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
* @copyright: © 2023 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\DB\Migrate;

use OMGF\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class V557 {

	/** @var $version string The version number this migration script was introduced with. */
	private $version = '5.5.7';

	/**
	 * All DB rows that need to be migrated and removed.
	 * 
	 * @var string[]
	 */
	private $rows = [];

	/**
	 * Buid
	 * 
	 * @return void 
	 */
	public function __construct() {
		$this->rows = [
			Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS,
			Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION,
			Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE,
			Settings::OMGF_ADV_SETTING_COMPATIBILITY,
			Settings::OMGF_ADV_SETTING_SUBSETS,
			Settings::OMGF_ADV_SETTING_DEBUG_MODE,
			Settings::OMGF_ADV_SETTING_UNINSTALL,
		];
		
		$this->init();
	}

	/**
	 * Initialize
	 * 
	 * @return void 
	 */
	private function init() {
		$new_settings = get_option( 'omgf_settings', [] );
		
		foreach ( $this->rows as $row ) {
			$new_settings[ $row ] = get_option( "omgf_$row" );
			delete_option( "omgf_$row" );
		}

		update_option( 'omgf_settings', $new_settings );

		/**
		 * Update stored version number.
		 */
		update_option( Settings::OMGF_CURRENT_DB_VERSION, $this->version );
	}
}
