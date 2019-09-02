<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\HackGuard\ScanQueue;

use FernleafSystems\Wordpress\Plugin\Shield;

/**
 * Class BuildScanAction
 * @package FernleafSystems\Wordpress\Plugin\Shield\Modules\HackGuard\ScanQueue
 */
class BuildScanAction {

	use Shield\Modules\ModConsumer;

	/**
	 * @param string $sSlug
	 * @return Shield\Scans\Base\BaseScanActionVO|mixed
	 * @throws \Exception
	 */
	public function build( $sSlug ) {
		$oAction = ( new ScanActionFromSlug() )->getAction( $sSlug );

		// Build the action definition:

		$sClass = $oAction->getScanNamespace().'BuildScanAction';
		/** @var Shield\Scans\Base\BaseBuildScanAction $oBuilder */
		$oBuilder = new $sClass();
		$oBuilder->setMod( $this->getMod() )
				 ->setScanActionVO( $oAction )
				 ->build();
		return $oAction;
	}
}
