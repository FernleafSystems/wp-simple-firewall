<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Scans\Ufc;

use FernleafSystems\Wordpress\Plugin\Shield;

class BuildScanAction extends Shield\Scans\Base\BaseBuildScanAction {

	protected function setCustomFields() {
		/** @var ScanActionVO $oAction */
		$oAction = $this->getScanActionVO();
		/** @var Shield\Modules\HackGuard\Options $oOpts */
		$oOpts = $this->getMod()->getOptions();

		$oAction->item_processing_limit = $oAction->is_async ? $oOpts->getFileScanLimit() : 0;
		$oAction->exclusions = $oOpts->getUfcFileExclusions();
		$oAction->scan_dirs = $oOpts->getUfcScanDirectories();
		$oAction->scan_items = ( new Shield\Scans\Ufc\BuildFileMap() )
			->setScanActionVO( $oAction )
			->build();
		$oAction->total_scan_items = count( $oAction->scan_items );
	}
}