<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\Traffic;

use FernleafSystems\Wordpress\Plugin\Shield\Modules\Base;

class Options extends Base\ShieldOptions {

	/**
	 * @return int
	 */
	public function getAutoCleanDays() {
		return (int)$this->getOpt( 'auto_clean' );
	}

	/**
	 * @return string[]
	 */
	public function getDbColumns_TrafficLog() {
		return $this->getDef( 'traffic_table_columns' );
	}

	/**
	 * @return string
	 */
	public function getDbTable_TrafficLog() {
		return $this->getCon()->prefixOption( $this->getDef( 'traffic_table_name' ) );
	}
}