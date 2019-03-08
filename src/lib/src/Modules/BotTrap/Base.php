<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\BotTrap;

use FernleafSystems\Wordpress\Plugin\Shield;
use FernleafSystems\Wordpress\Services\Services;

abstract class Base {

	use Shield\AuditTrail\Auditor,
		Shield\Modules\ModConsumer;

	public function run() {
		add_action( 'init', [ $this, 'onWpInit' ] );
	}

	public function onWpInit() {
		if ( !Services::WpUsers()->isUserLoggedIn() ) {
			$this->process();
		}
	}

	protected function process() {
	}

	protected function doTransgression() {
		/** @var \ICWP_WPSF_FeatureHandler_Bottrap $oFO */
		$oFO = $this->getMod();
		$this->isTransgression() ? $oFO->setIpTransgressed() : $oFO->setIpBlocked();
		$this->writeAudit();
	}

	/**
	 * @return bool
	 */
	abstract protected function isTransgression();

	/**
	 * @return $this
	 */
	abstract protected function writeAudit();
}