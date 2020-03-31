<?php

use FernleafSystems\Wordpress\Plugin\Shield;

class ICWP_WPSF_FeatureHandler_Reporting extends ICWP_WPSF_FeatureHandler_BaseWpsf {

	/**
	 * @return Shield\Databases\Reports\Handler
	 */
	public function getDbHandler_Sessions() {
		return $this->getDbH( 'reports' );
	}

	/**
	 * @return string
	 */
	protected function getNamespaceBase() {
		return 'Reporting';
	}
}