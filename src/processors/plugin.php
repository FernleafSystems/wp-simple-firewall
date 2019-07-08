<?php

use FernleafSystems\Wordpress\Services\Services;

class ICWP_WPSF_Processor_Plugin extends ICWP_WPSF_Processor_BasePlugin {

	/**
	 */
	public function run() {
		parent::run();
		/** @var ICWP_WPSF_FeatureHandler_Plugin $oFO */
		$oFO = $this->getMod();
		$this->getSubProCronDaily()
			 ->run();
		$this->getSubProCronHourly()
			 ->run();

		$this->removePluginConflicts();
		$this->getSubProBadge()
			 ->run();

		if ( $oFO->isTrackingEnabled() || !$oFO->isTrackingPermissionSet() ) {
			$this->getSubProTracking()->run();
		}

		if ( $oFO->isImportExportPermitted() ) {
			$this->getSubProImportExport()->run();
		}

		switch ( Services::Request()->query( 'shield_action', '' ) ) {
			case 'dump_tracking_data':
				add_action( 'wp_loaded', [ $this, 'dumpTrackingData' ] );
				break;

			case 'importexport_export':
			case 'importexport_import':
			case 'importexport_handshake':
			case 'importexport_updatenotified':
				if ( $oFO->isImportExportPermitted() ) {
					add_action( 'init', [ $this->getSubProImportExport(), 'runAction' ] );
				}
				break;
			default:
				break;
		}

		add_action( 'admin_footer', [ $this, 'printAdminFooterItems' ], 100, 0 );
	}

	public function onWpLoaded() {
		if ( $this->getCon()->isValidAdminArea() ) {
			$this->maintainPluginLoadPosition();
		}
	}

	/**
	 * @return ICWP_WPSF_Processor_Plugin_Badge
	 */
	protected function getSubProBadge() {
		return $this->getSubPro( 'badge' );
	}

	/**
	 * @return ICWP_WPSF_Processor_Plugin_CronDaily
	 */
	protected function getSubProCronDaily() {
		return $this->getSubPro( 'crondaily' );
	}

	/**
	 * @return ICWP_WPSF_Processor_Plugin_CronHourly
	 */
	protected function getSubProCronHourly() {
		return $this->getSubPro( 'cronhourly' );
	}

	/**
	 * @return ICWP_WPSF_Processor_Plugin_Tracking
	 */
	protected function getSubProTracking() {
		return $this->getSubPro( 'tracking' );
	}

	/**
	 * @return ICWP_WPSF_Processor_Plugin_ImportExport
	 */
	public function getSubProImportExport() {
		return $this->getSubPro( 'importexport' );
	}

	/**
	 * @return array
	 */
	protected function getSubProMap() {
		return [
			'badge'        => 'ICWP_WPSF_Processor_Plugin_Badge',
			'importexport' => 'ICWP_WPSF_Processor_Plugin_ImportExport',
			'tracking'     => 'ICWP_WPSF_Processor_Plugin_Tracking',
			'crondaily'    => 'ICWP_WPSF_Processor_Plugin_CronDaily',
			'cronhourly'   => 'ICWP_WPSF_Processor_Plugin_CronHourly',
		];
	}

	public function printAdminFooterItems() {
		$this->printPluginDeactivateSurvey();
		$this->printToastTemplate();
	}

	/**
	 * Sets this plugin to be the first loaded of all the plugins.
	 */
	private function printToastTemplate() {
		if ( $this->getCon()->isModulePage() ) {
			$aRenderData = [
				'strings'     => [
					'title' => $this->getCon()->getHumanName(),
				],
				'js_snippets' => []
			];
			echo $this->getMod()
					  ->renderTemplate( 'snippets/toaster.twig', $aRenderData, true );
		}
	}

	private function printPluginDeactivateSurvey() {
		if ( Services::WpPost()->isCurrentPage( 'plugins.php' ) ) {

			$aOpts = [
				'reason_confusing'   => "It's too confusing",
				'reason_expected'    => "It's not what I expected",
				'reason_accident'    => "I downloaded it accidentally",
				'reason_alternative' => "I'm already using an alternative",
				'reason_trust'       => "I don't trust the developer :(",
				'reason_not_work'    => "It doesn't work",
				'reason_errors'      => "I'm getting errors",
			];

			$aRenderData = [
				'strings'     => [
					'editing_restricted' => __( 'Editing this option is currently restricted.', 'wp-simple-firewall' ),
				],
				'inputs'      => [
					'checkboxes' => Services::DataManipulation()->shuffleArray( $aOpts )
				],
				'js_snippets' => []
			];
			echo $this->getMod()
					  ->renderTemplate( 'snippets/plugin-deactivate-survey.php', $aRenderData );
		}
	}

	/**
	 */
	public function dumpTrackingData() {
		if ( $this->getCon()->isPluginAdmin() ) {
			echo sprintf( '<pre><code>%s</code></pre>', print_r( $this->getSubProTracking()
																	  ->collectTrackingData(), true ) );
			die();
		}
	}

	public function runDailyCron() {
		/** @var ICWP_WPSF_FeatureHandler_Plugin $oFO */
		$oFO = $this->getMod();
		$this->getCon()->fireEvent( 'test_cron_run' );
	}

	/**
	 * Sets this plugin to be the first loaded of all the plugins.
	 */
	protected function maintainPluginLoadPosition() {
		$oWpPlugins = Services::WpPlugins();
		$sBaseFile = $this->getCon()->getPluginBaseFile();
		$nLoadPosition = $oWpPlugins->getActivePluginLoadPosition( $sBaseFile );
		if ( $nLoadPosition !== 0 && $nLoadPosition > 0 ) {
			$oWpPlugins->setActivePluginLoadFirst( $sBaseFile );
		}
	}

	/**
	 * Lets you remove certain plugin conflicts that might interfere with this plugin
	 */
	protected function removePluginConflicts() {
		if ( class_exists( 'AIO_WP_Security' ) && isset( $GLOBALS[ 'aio_wp_security' ] ) ) {
			remove_action( 'init', [ $GLOBALS[ 'aio_wp_security' ], 'wp_security_plugin_init' ], 0 );
		}
	}

	/**
	 * @param array $aNoticeAttributes
	 * @throws \Exception
	 * @deprecated
	 */
	public function addNotice_override_forceoff() {
		return;
	}

	/**
	 * @param array $aNoticeAttributes
	 * @throws \Exception
	 * @deprecated
	 */
	public function addNotice_plugin_mailing_list_signup() {
		return;
	}
}