<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\Shield\Databases\Traffic;

use FernleafSystems\Wordpress\Plugin\Shield\Databases\Base;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\Traffic\Options;

class Handler extends Base\Handler {

	public function autoCleanDb() {
		/** @var Options $opts */
		$opts = $this->getOptions();
		$this->tableCleanExpired( $opts->getAutoCleanDays() );
		$this->tableTrimExcess( $opts->getMaxEntries() );
	}

	protected function getDefaultTableName() :string {
		return $this->getOptions()->getDef( 'traffic_table_name' );
	}

	protected function getCustomColumns() :array {
		return $this->getOptions()->getDef( 'traffic_table_columns' );
	}
}