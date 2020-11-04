<?php

use FernleafSystems\Wordpress\Plugin\Shield;

/**
 * Class ICWP_WPSF_FeatureHandler_Events
 * @deprecated 10.1
 */
class ICWP_WPSF_FeatureHandler_Events extends ICWP_WPSF_FeatureHandler_BaseWpsf {

	/**
	 * @return false|Shield\Databases\Events\Handler
	 */
	public function getDbHandler_Events() {
		return $this->getDbH( 'events' );
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	protected function isReadyToExecute() {
		return ( $this->getDbHandler_Events() instanceof Shield\Databases\Events\Handler )
			   && $this->getDbHandler_Events()->isReady()
			   && parent::isReadyToExecute();
	}

	/**
	 * @return string
	 */
	protected function getNamespaceBase() :string {
		return 'Events';
	}
}