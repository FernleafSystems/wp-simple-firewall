<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\Traffic;

use FernleafSystems\Wordpress\Plugin\Shield;

class AjaxHandler extends Shield\Modules\BaseShield\AjaxHandler {

	protected function processAjaxAction( string $action ) :array {

		switch ( $action ) {
			case 'render_table_traffic':
				$aResponse = $this->ajaxExec_BuildTableTraffic();
				break;

			default:
				$aResponse = parent::processAjaxAction( $action );
		}

		return $aResponse;
	}

	/**
	 * @return array
	 */
	private function ajaxExec_BuildTableTraffic() {
		/** @var \ICWP_WPSF_FeatureHandler_Traffic $oMod */
		$oMod = $this->getMod();
		return [
			'success' => true,
			'html'    => ( new Shield\Tables\Build\Traffic() )
				->setMod( $oMod )
				->setDbHandler( $oMod->getDbHandler_Traffic() )
				->render()
		];
	}
}