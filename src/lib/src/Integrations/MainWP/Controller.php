<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\Shield\Integrations\MainWP;

use FernleafSystems\Wordpress\Plugin\Shield\Modules\PluginControllerConsumer;

class Controller {

	use PluginControllerConsumer;

	private $childKey;

	public function run() {
		try {
			$this->runServerSide();
		}
		catch ( \Exception $e ) {
			try {
				$this->runClientSide();
			}
			catch ( \Exception $e ) {
				$this->setCon( null );
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	private function runClientSide() {
	}

	/**
	 * @throws \Exception
	 */
	private function runServerSide() {
		if ( !$this->isMainWPServerActive() ) {
			throw new \Exception( 'MainWP not active' );
		}
		$this->childKey = $this->initExt();
		if ( empty( $this->childKey ) ) {
			throw new \Exception( 'No child key provided' );
		}
		( new SyncHandler() )
			->setCon( $this->getCon() )
			->execute();
		( new SitesListTableHandler() )
			->setCon( $this->getCon() )
			->execute();
	}

	private function initExt() :string {
		add_filter( 'mainwp_getextensions', function ( $exts ) {
			$exts[] = [
				'plugin'   => $this->getCon()->getRootFile(),
				'callback' => [ $this, 'mainExtSettingsPage' ],
			];
			return $exts;
		}, 10, 1 );
		$childEnabled = apply_filters( 'mainwp_extension_enabled_check', $this->getCon()->getRootFile() );
		return $childEnabled[ 'key' ] ?? '';
	}

	private function isMainWPServerActive() :bool {
		return (bool)apply_filters( 'mainwp_activated_check', false );
	}

	public function mainExtSettingsPage() {
		$con = $this->getCon();
		do_action( 'mainwp_pageheader_extensions', $this->getCon()->getRootFile() );

		$sites = apply_filters( 'mainwp_getsites', $con->getRootFile(), $this->childKey );
		var_dump( $sites );
		?>
		https://mainwp.com/passing-information-to-your-child-sites/
		<div id="uploader_select_sites_box" class="mainwp_config_box_right">
        <?php
		do_action( 'mainwp_select_sites_box', __( "Select Sites", 'mainwp' ), 'checkbox', true, true, 'mainwp_select_sites_box_right', "", [], [] );
		?></div>
		<?php

		do_action( 'mainwp_pagefooter_extensions', $con->getRootFile() );
	}
}
