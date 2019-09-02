<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Scans\Wpv;

use FernleafSystems\Wordpress\Plugin\Shield;

class Scan extends Shield\Scans\Base\BaseScan {

	protected function scanSlice() {
		/** @var ScanActionVO $oAction */
		$oAction = $this->getScanActionVO();
		$oTempRs = $oAction->getNewResultsSet();

		$oCopier = new Shield\Scans\Helpers\CopyResultsSets();
		foreach ( $oAction->items as $sFile => $sContext ) {
			$oNewRes = $this->getItemScanner( $sContext )->scan( $sFile );
			if ( $oNewRes instanceof Shield\Scans\Base\BaseResultsSet ) {
				$oCopier->copyTo( $oNewRes, $oTempRs );
			}
		}

		if ( $oTempRs->hasItems() ) {
			$aNewItems = [];
			foreach ( $oTempRs->getAllItems() as $oNewRes ) {
				$aNewItems[] = $oNewRes->getRawDataAsArray();
			}
			if ( empty( $oAction->results ) ) {
				$oAction->results = [];
			}
			$oAction->results = array_merge( $oAction->results, $aNewItems );
		}
	}

	/**
	 * @param string $sContext
	 * @return PluginScanner|ThemeScanner
	 */
	protected function getItemScanner( $sContext ) {
		if ( $sContext == 'plugins' ) {
			return ( new PluginScanner() )->setScanActionVO( $this->getScanActionVO() );
		}
		else {
			return ( new ThemeScanner() )->setScanActionVO( $this->getScanActionVO() );
		}
	}
}